<?php
session_start();
include "../config/database.php";

if (!isset($_SESSION['login'])) {
    header("Location: /auth/auth_login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All fields are required";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters";
    } else {
        // Get current password hash from database
        $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_user);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        // Verify current password (assuming MD5 hash from your login system)
        if (md5($current_password) !== $user['password']) {
            $error_message = "Current password is incorrect";
        } else {
            // Update password
            $new_password_hash = md5($new_password); // Sesuai dengan sistem yang ada
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id_user = ?");
            mysqli_stmt_bind_param($update_stmt, "si", $new_password_hash, $id_user);

            if (mysqli_stmt_execute($update_stmt)) {
                $success_message = "Password changed successfully!";

                // Log activity (optional)
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $log_stmt = mysqli_prepare($conn, "
                    INSERT INTO activity_logs (id_user, activity_type, description, ip_address) 
                    VALUES (?, 'password_change', ?, ?)
                ");
                mysqli_stmt_bind_param($log_stmt, "iss", $id_user, 'Password changed', $ip_address);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
            } else {
                $error_message = "Failed to change password. Please try again.";
            }
            mysqli_stmt_close($update_stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Change Password | Midnight Play</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .change-password-container {
            max-width: 600px;
            margin: 30px auto;
            background: #171a21;
            border-radius: 15px;
            padding: 30px;
            border: 1px solid #2a475e;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #2a475e;
        }

        .form-header h1 {
            color: #66c0f4;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #8f98a0;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            color: #c7d5e0;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
        }

        .password-input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 12px 45px 12px 15px;
            background: #2a2f3a;
            border: 1px solid #3d4452;
            border-radius: 8px;
            color: #c7d5e0;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #66c0f4;
            box-shadow: 0 0 0 3px rgba(102, 192, 244, 0.2);
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #8f98a0;
            cursor: pointer;
            padding: 5px;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #66c0f4;
        }

        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #2a2f3a;
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-meter {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 2px;
        }

        .strength-weak {
            width: 33%;
            background: #dc3545;
        }

        .strength-medium {
            width: 66%;
            background: #ffc107;
        }

        .strength-strong {
            width: 100%;
            background: #10b981;
        }

        .password-hints {
            background: rgba(102, 192, 244, 0.1);
            border: 1px solid rgba(102, 192, 244, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            color: #8f98a0;
            font-size: 14px;
        }

        .password-hints ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }

        .password-hints li {
            margin-bottom: 5px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #2a475e;
        }

        .btn-change {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            justify-content: center;
        }

        .btn-change:hover {
            background: linear-gradient(135deg, #34d399, #10b981);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-change:disabled {
            background: #4b5563;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-cancel {
            background: #2a2f3a;
            color: #c7d5e0;
            padding: 12px 30px;
            border: 1px solid #3d4452;
            border-radius: 8px;
            font-size: 16px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            justify-content: center;
        }

        .btn-cancel:hover {
            background: #323844;
            border-color: #ef4444;
            color: #ef4444;
        }

        .security-note {
            text-align: center;
            color: #8f98a0;
            font-size: 14px;
            margin-top: 20px;
            padding: 15px;
            border: 1px dashed #2a475e;
            border-radius: 8px;
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <h2>Midnight Play</h2>
        <div class="nav-right">
            <span class="nav-user">
                <i class="fas fa-user-circle"></i>
                <?= htmlspecialchars($_SESSION['username']); ?>
            </span>
            <a href="/auth/auth_logout.php" class="btn-logout">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </nav>

    <div class="epic-layout">
        <!-- SIDEBAR -->
        <aside class="epic-sidebar fade-in">
            <div class="sidebar-section">
                <a href="/index.php" class="sidebar-item">
                    <i class="fa-solid fa-store"></i>
                    <span>Store</span>
                </a>
                <a href="/library/library_user_games.php" class="sidebar-item">
                    <i class="fa-solid fa-gamepad"></i>
                    <span>Library</span>
                </a>
                <a href="/invoices/invoice_user_list.php" class="sidebar-item">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Purchases</span>
                </a>
                <a href="user_profile.php" class="sidebar-item">
                    <i class="fa-solid fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="/user/user_wallet.php" class="sidebar-item">
                    <i class="fa-solid fa-wallet"></i>
                    <span>Wallet</span>
                </a>
            </div>
        </aside>

        <!-- CONTENT -->
        <main class="epic-content">
            <div class="change-password-container">
                <div class="form-header">
                    <h1><i class="fas fa-key"></i> Change Password</h1>
                    <p>Update your account password</p>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Change Password Form -->
                <form method="POST" action="" id="passwordForm">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock"></i> Current Password
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password" class="form-control" name="current_password" id="currentPassword"
                                required placeholder="Enter your current password">
                            <button type="button" class="toggle-password" data-target="currentPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock"></i> New Password
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password" class="form-control" name="new_password" id="newPassword" required
                                placeholder="Enter new password (min. 6 characters)">
                            <button type="button" class="toggle-password" data-target="newPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-meter" id="strengthMeter"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock"></i> Confirm New Password
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password" class="form-control" name="confirm_password" id="confirmPassword"
                                required placeholder="Confirm new password">
                            <button type="button" class="toggle-password" data-target="confirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="passwordMatch" style="font-size: 12px; margin-top: 5px;"></div>
                    </div>

                    <div class="password-hints">
                        <strong><i class="fas fa-lightbulb"></i> Password Requirements:</strong>
                        <ul>
                            <li>Minimum 6 characters</li>
                            <li>Include uppercase and lowercase letters</li>
                            <li>Include numbers for better security</li>
                            <li>Avoid using personal information</li>
                        </ul>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="change_password" class="btn-change" id="submitBtn">
                            <i class="fas fa-save"></i> Change Password
                        </button>
                        <a href="user_profile.php" class="btn-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>

                <div class="security-note">
                    <i class="fas fa-shield-alt"></i>
                    <strong>Security Tip:</strong> Use a unique password that you don't use elsewhere.
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContent