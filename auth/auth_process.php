<?php
session_start();
include "../config/database.php";

$username = $_POST['username'];
$password = md5($_POST['password']); // Sesuai UAS

// Validasi input
if (empty($username) || empty($_POST['password'])) {
    // Redirect dengan parameter error
    header("Location: auth_login.php?error=empty");
    exit();
}

// Ambil user berdasarkan username & password
$query = mysqli_query($conn, "
    SELECT * FROM users 
    WHERE username='$username' AND password='$password'
");

$user = mysqli_fetch_assoc($query);

if ($user) {
    $_SESSION['login'] = true;
    $_SESSION['id_user'] = $user['id_user'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role']; // Penting!

    // Redirect sesuai role
    if ($user['role'] == 'admin') {
        header("Location: ../admin/admin_dashboard.php");
    } else {
        header("Location: ../index.php");
    }
    exit;
} else {
    // Method 1: Using localStorage flag (more modern)
    echo "<script>
        localStorage.setItem('showLoginError', 'true');
        window.location.href = 'auth_login.php';
    </script>";

    // Method 2: Or using URL parameter (fallback)
    // header("Location: auth_login.php?error=invalid");
    exit();
}
?>