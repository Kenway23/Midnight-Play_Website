<!DOCTYPE html>
<html>

<head>
    <title>Register | Midnight Play</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <div class="auth-wrapper">
        <div class="auth-box">
            <h2>Register</h2>

            <form action="auth_register_process.php" method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>

                <button class="btn" type="submit">Register</button>
            </form>

            <p>
                Sudah punya akun?
                <a href="auth_login.php">Login</a>
            </p>
        </div>
    </div>

</body>


</html>