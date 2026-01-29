<?php
session_start();
include "../../config/database.php";

/* Proteksi admin */
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/auth_login.php");
    exit();
}

// Cek parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['role'])) {
    $_SESSION['error'] = "Parameter tidak valid";
    header("Location: user_list.php");
    exit();
}

$user_id = intval($_GET['id']);
$new_role = $_GET['role'] === 'admin' ? 'admin' : 'user';

// Cek apakah user mencoba mengubah role sendiri
if ($user_id == $_SESSION['id_user']) {
    $_SESSION['error'] = "Anda tidak dapat mengubah role akun Anda sendiri!";
    header("Location: user_list.php");
    exit();
}

// Ambil data user sebelum diubah
$stmt = mysqli_prepare($conn, "SELECT username, role FROM users WHERE id_user = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "User tidak ditemukan";
    header("Location: user_list.php");
    exit();
}

$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Cek role saat ini
$current_role = $user['role'];

// Jika role sama, tidak perlu update
if ($current_role == $new_role) {
    $_SESSION['info'] = "Role user sudah " . ($new_role == 'admin' ? 'Administrator' : 'Customer');
    header("Location: user_list.php");
    exit();
}

// Update role user
$update_stmt = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE id_user = ?");
mysqli_stmt_bind_param($update_stmt, "si", $new_role, $user_id);

if (mysqli_stmt_execute($update_stmt)) {
    // Log perubahan
    $log_message = "User #" . $user_id . " (" . $user['username'] . ") role changed from " .
        $current_role . " to " . $new_role . " by " . $_SESSION['username'];
    error_log("ROLE_CHANGE: " . $log_message);

    // Pesan sukses berbeda untuk upgrade/downgrade
    if ($new_role == 'admin') {
        $_SESSION['success'] = "User <strong>" . htmlspecialchars($user['username']) . "</strong> telah dijadikan Administrator!";
        $_SESSION['warning'] = "PERINGATAN: User ini sekarang memiliki akses penuh ke sistem admin!";
    } else {
        $_SESSION['success'] = "User <strong>" . htmlspecialchars($user['username']) . "</strong> telah diturunkan menjadi Customer";
        $_SESSION['info'] = "User ini tidak lagi memiliki akses ke panel admin";
    }
} else {
    $_SESSION['error'] = "Gagal mengubah role user: " . mysqli_error($conn);
}

mysqli_stmt_close($update_stmt);
header("Location: user_list.php");
exit();
?>