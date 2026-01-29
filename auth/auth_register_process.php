<?php
session_start();
include "../config/database.php";

$username = $_POST['username'];
$password = md5($_POST['password']); // MD5 sesuai UAS

// Cek apakah username sudah ada
$check = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
if (mysqli_num_rows($check) > 0) {
    echo "<script>
        alert('Username sudah digunakan!');
        window.location='auth_register.php';
    </script>";
    exit;
}

$role = ($username == 'admin') ? 'admin' : 'user';

mysqli_query($conn, "
    INSERT INTO users (username, password, role)
    VALUES ('$username', '$password', '$role')
");

echo "<script>
    alert('Registrasi berhasil! Silakan login.');
    window.location='auth_login.php';
</script>";
