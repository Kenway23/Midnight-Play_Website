<?php
session_start();
include "../config/database.php";

if (!isset($_SESSION['login'])) {
    header("Location:/auth/auth_login.php");
    exit();
}

// Ambil data dari session jika ada
$success_data = $_SESSION['purchase_success'] ?? null;

if (!$success_data) {
    header("Location:/index.php");
    exit();
}

if (isset($_GET['success']) && $_GET['success'] == '1') {
    // Tampilkan modal sukses
    echo '<script>document.addEventListener("DOMContentLoaded", function() { setTimeout(showSuccessModal, 500); });</script>';
}

// Hapus session data setelah ditampilkan
unset($_SESSION['purchase_success']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Successful | Midnight Play</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div style="text-align: center; padding: 50px;">
        <div style="font-size: 80px; color: #10b981; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1 style="color: #10b981; margin-bottom: 20px;">Purchase Successful!</h1>
        <p style="font-size: 18px; margin-bottom: 30px; color: #c7d5e0;">
            Thank you for your purchase. Transaction ID: #
            <?= $success_data['transaction_id']; ?>
        </p>
        <div style="margin: 30px 0;">
            <a href="/library/library_user_games.php" style="background: #66c0f4; color: white; padding: 12px 30px; 
                      border-radius: 5px; text-decoration: none; margin-right: 10px;">
                <i class="fas fa-gamepad"></i> Go to Library
            </a>
            <a href="/index.php" style="background: #2a2f3a; color: #c7d5e0; padding: 12px 30px; 
                      border-radius: 5px; text-decoration: none; border: 1px solid #3d4452;">
                <i class="fas fa-home"></i> Back to Store
            </a>
        </div>
    </div>
</body>

</html>