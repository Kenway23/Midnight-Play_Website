<?php

$host = getenv("MYSQLHOST");
$port = getenv("MYSQLPORT");
$dbname = getenv("MYSQLDATABASE");
$username = getenv("MYSQLUSER");
$password = getenv("MYSQLPASSWORD");

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
} catch (PDOException $e) {
    // JANGAN echo di production
    error_log($e->getMessage());
    exit;
}
