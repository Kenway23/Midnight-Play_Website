<?php
session_start();
include "../config/database.php";

/* Proteksi login */
if (!isset($_SESSION['login'])) {
    $_SESSION['error'] = "Please login first!";
    header("Location: /auth/auth_login.php");
    exit();
}

// Debug logging
error_log("Transaction Controller Accessed - Method: " . $_SERVER['REQUEST_METHOD']);

// Hanya terima POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method!";
    header("Location: /index.php");
    exit();
}

$id_user = $_SESSION['id_user'] ?? null;
$id_game = $_POST['id_game'] ?? '';
$payment_method = $_POST['payment_method'] ?? 'midnight_wallet';
$agree_terms = $_POST['agree_terms'] ?? '0';

/* Validasi data */
if (!$id_game || !is_numeric($id_game)) {
    $_SESSION['error'] = "Invalid game ID";
    header("Location: /transactions/transaction_buy_game.php?id=" . $id_game);
    exit();
}

if ($agree_terms !== '1') {
    $_SESSION['error'] = "You must agree to the Terms & Conditions";
    header("Location: /transactions/transaction_buy_game.php?id=" . $id_game);
    exit();
}

/* Ambil data game */
$stmt = mysqli_prepare($conn, "SELECT * FROM games WHERE id_game = ?");
mysqli_stmt_bind_param($stmt, "i", $id_game);
mysqli_stmt_execute($stmt);
$game_result = mysqli_stmt_get_result($stmt);
$game = mysqli_fetch_assoc($game_result);

if (!$game) {
    $_SESSION['error'] = "Game not found";
    header("Location: /transactions/transaction_buy_game.php?id=" . $id_game);
    exit();
}
mysqli_stmt_close($stmt);

/* Cek apakah sudah memiliki game ini */
$stmt = mysqli_prepare($conn, "SELECT * FROM library WHERE id_user = ? AND id_game = ?");
mysqli_stmt_bind_param($stmt, "ii", $id_user, $id_game);
mysqli_stmt_execute($stmt);
$check_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($check_result) > 0) {
    $_SESSION['error'] = "You already own this game!";
    header("Location: /library/library_user_games.php");
    exit();
}
mysqli_stmt_close($stmt);

/* Cek saldo user */
$stmt = mysqli_prepare($conn, "SELECT wallet_balance FROM users WHERE id_user = ?");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$balance_result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($balance_result);
$user_balance = $user_data['wallet_balance'] ?? 0;

if ($user_balance < $game['price']) {
    mysqli_stmt_close($stmt);
    $_SESSION['error'] = "Insufficient wallet balance! Please top up your wallet.";
    header("Location: /transactions/transaction_buy_game.php?id=" . $id_game);
    exit();
}
mysqli_stmt_close($stmt);

// Mulai transaksi database
mysqli_begin_transaction($conn);

try {
    // 1. Kurangi saldo wallet
    $new_balance = $user_balance - $game['price'];
    $stmt = mysqli_prepare($conn, "UPDATE users SET wallet_balance = ? WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, "ii", $new_balance, $id_user);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to update wallet balance");
    }
    mysqli_stmt_close($stmt);

    // 2. Buat transaksi (jika ada table transactions)
    $transaction_id = null;
    if (tableExists($conn, 'transactions')) {
        $transaction_date = date('Y-m-d H:i:s');
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO transactions (id_user, total_price, payment_method, transaction_date, status) 
             VALUES (?, ?, ?, ?, 'completed')"
        );
        mysqli_stmt_bind_param($stmt, "iiss", $id_user, $game['price'], $payment_method, $transaction_date);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to create transaction");
        }

        $transaction_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // 3. Buat transaction detail (jika ada table transaction_details)
        if (tableExists($conn, 'transaction_details')) {
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO transaction_details (id_transaction, id_game, price) 
                 VALUES (?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "iii", $transaction_id, $id_game, $game['price']);

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to create transaction detail");
            }
            mysqli_stmt_close($stmt);
        }
    }

    // 4. Tambahkan game ke library - SESUAIKAN DENGAN FIELD ANDA
    // Field: id_library, id_user, id_game, purchased_at
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO library (id_user, id_game, purchased_at) 
         VALUES (?, ?, NOW())"
    );
    mysqli_stmt_bind_param($stmt, "ii", $id_user, $id_game);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to add game to library");
    }
    $library_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // 5. Commit semua perubahan
    mysqli_commit($conn);

    // SUCCESS - Set session success message
    $_SESSION['success'] = "Purchase successful! " . htmlspecialchars($game['title']) . " has been added to your library.";
    $_SESSION['purchase_data'] = [
        'transaction_id' => $transaction_id ?: ('LIB' . $library_id),
        'game_title' => $game['title'],
        'amount' => $game['price'],
        'balance_remaining' => $new_balance,
        'library_id' => $library_id
    ];

    // Redirect ke success page
    header("Location: /transactions/transaction_success.php");
    exit();

} catch (Exception $e) {
    // Rollback jika ada error
    mysqli_rollback($conn);

    error_log("Transaction Error: " . $e->getMessage());
    $_SESSION['error'] = "Purchase failed: " . $e->getMessage();
    header("Location: /transactions/transaction_buy_game.php?id=" . $id_game);
    exit();
}

// Helper function untuk cek table exists
function tableExists($conn, $table)
{
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return mysqli_num_rows($result) > 0;
}
?>