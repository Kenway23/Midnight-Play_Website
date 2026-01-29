<?php
session_start();
include "../../config/database.php";

/* Proteksi admin */
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/auth_login.php");
    exit();
}

// Cek parameter ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: user_list.php");
    exit();
}

$user_id = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil data user
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id_user = '$user_id'");
if (mysqli_num_rows($user_query) == 0) {
    header("Location: user_list.php?error=User tidak ditemukan");
    exit();
}

$user = mysqli_fetch_assoc($user_query);

// Jangan izinkan reset password sendiri dari sini (gunakan change_password.php)
if ($user_id == $_SESSION['id_user']) {
    header("Location: user_list.php?error=Tidak bisa reset password sendiri dari sini");
    exit();
}

// Proses reset password
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Password default
    $default_password = 'password123';
    $hashed_password = md5($default_password);

    // Update password
    $update_query = mysqli_query($conn, "UPDATE users SET password = '$hashed_password' WHERE id_user = '$user_id'");

    if ($update_query) {
        header("Location: user_list.php?success=Password user " . $user['username'] . " berhasil direset ke default");
        exit();
    } else {
        header("Location: user_list.php?error=Gagal mereset password");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password User | Midnight Play Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Modal Styles - Ukuran lebih kecil TANPA SCROLL */
        .confirm-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .modal-content {
            background: #171a21;
            width: 420px;
            /* Lebih kecil dari 500px */
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid #f59e0b;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .modal-header {
            background: linear-gradient(135deg, #d97706, #f59e0b);
            padding: 20px;
            text-align: center;
        }

        .modal-header-icon {
            font-size: 36px;
            color: #fff;
            margin-bottom: 10px;
        }

        .modal-title {
            color: white;
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }

        .modal-body {
            padding: 20px;
            color: #c7d5e0;
            /* HAPUS max-height dan overflow-y untuk menghilangkan scroll */
        }

        .modal-message {
            text-align: center;
            margin-bottom: 15px;
            line-height: 1.5;
            font-size: 14px;
        }

        .user-details-modal {
            background: #1b1f27;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
            border: 1px solid #2a475e;
        }

        .user-details-modal p {
            margin: 8px 0;
            font-size: 13px;
        }

        .user-details-modal strong {
            color: #66c0f4;
        }

        .password-display-modal {
            font-family: monospace;
            background: #0b1320;
            padding: 12px;
            border-radius: 6px;
            font-size: 16px;
            color: #fbbf24;
            letter-spacing: 1px;
            margin: 12px 0;
            text-align: center;
            border: 2px solid #f59e0b;
            font-weight: bold;
        }

        .info-box {
            background: rgba(96, 165, 250, 0.1);
            border: 1px solid #60a5fa;
            border-radius: 6px;
            padding: 12px;
            margin: 12px 0;
        }

        .info-box p {
            margin: 6px 0;
            font-size: 12px;
            color: #c7d5e0;
        }

        .warning-box {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 12px;
            margin: 12px 0;
        }

        .warning-box strong {
            color: #fbbf24;
            font-size: 14px;
        }

        .warning-box p {
            margin: 5px 0;
            font-size: 12px;
            color: #c7d5e0;
        }

        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            min-width: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .modal-cancel {
            background: #4b5563;
            color: white;
        }

        .modal-cancel:hover {
            background: #374151;
        }

        .modal-confirm {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .modal-confirm:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
        }

        .btn-primary {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
        }

        .btn-secondary {
            background: #4b5563;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: #374151;
            transform: translateY(-2px);
        }

        .highlight-box {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(245, 158, 11, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
            }
        }
    </style>
</head>

<body>
    <!-- Confirmation Modal -->
    <div id="confirmModal" class="confirm-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-icon">
                    <i class="fas fa-key"></i>
                </div>
                <h2 class="modal-title">KONFIRMASI RESET PASSWORD</h2>
            </div>
            <div class="modal-body">
                <div class="modal-message">
                    <p style="font-size: 16px; margin-bottom: 8px; color: #fbbf24;">
                        <strong>PERHATIAN!</strong>
                    </p>
                    <p>Anda akan mereset password untuk user berikut:</p>
                </div>

                <div class="user-details-modal">
                    <p><strong>Username:</strong> <?= htmlspecialchars($user['username']); ?></p>
                    <p><strong>ID User:</strong> #<?= $user['id_user']; ?></p>
                    <p><strong>Role:</strong>
                        <?php if ($user['role'] == 'admin'): ?>
                            <span style="color: #3b82f6; font-weight: bold;">Administrator</span>
                        <?php else: ?>
                            <span style="color: #10b981; font-weight: bold;">Customer</span>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="info-box">
                    <p><strong>Password baru akan menjadi:</strong></p>
                    <div class="password-display-modal">
                        password123
                    </div>
                </div>

                <div class="warning-box">
                    <p><strong>⚠️ PERINGATAN PENTING:</strong></p>
                    <p>1. User harus login dengan password default ini</p>
                    <p>2. User harus mengganti password saat login pertama</p>
                    <p style="color: #fca5a5;">3. Beritahu user tentang password baru dengan aman!</p>
                </div>

                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-cancel" onclick="closeConfirmModal()">
                        <i class="fas fa-times"></i> BATAL
                    </button>
                    <button type="button" class="modal-btn modal-confirm" id="finalResetBtn" onclick="submitForm()">
                        <i class="fas fa-redo"></i> RESET
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Navbar -->
        <div class="navbar">
            <h1><i class="fas fa-key"></i> Reset Password User</h1>
            <div class="nav-right">
                <span class="nav-user"><i class="fas fa-user-shield"></i>
                    <?= $_SESSION['username']; ?>
                </span>
                <a href="../admin_dashboard.php" class="btn-login"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="../../auth/auth_logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Main Layout -->
        <div class="epic-layout">
            <!-- Sidebar -->
            <div class="epic-sidebar">
                <div class="sidebar-section">
                    <a href="../admin_dashboard.php" class="sidebar-item">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="../game_management/admin_game_list.php" class="sidebar-item">
                        <i class="fas fa-gamepad"></i>
                        Kelola Game
                    </a>
                    <a href="user_list.php" class="sidebar-item">
                        <i class="fas fa-users"></i>
                        Kelola User
                    </a>
                    <a href="../reports/admin_transaction_report.php" class="sidebar-item">
                        <i class="fas fa-file-invoice"></i>
                        Laporan Transaksi
                    </a>
                    <a href="../../index.php" class="sidebar-item">
                        <i class="fas fa-store"></i>
                        Kembali ke Toko
                    </a>
                </div>
            </div>

            <!-- Content Area -->
            <div style="flex: 1; margin-left: 20px; padding: 10px;">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <a href="user_list.php" style="color: #66c0f4; text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar User
                    </a>
                </div>

                <!-- Header -->
                <div style="text-align: center; margin-bottom: 30px;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #f59e0b, #fbbf24); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;"
                        class="highlight-box">
                        <i class="fas fa-key" style="color: #fff; font-size: 32px;"></i>
                    </div>

                    <h2><i class="fas fa-user-cog"></i> Reset Password User</h2>
                    <p style="color: #8f98a0; margin-top: 10px;">Konfirmasi reset password untuk user berikut</p>
                </div>

                <!-- User Info Card -->
                <div style="background: #171a21; border-radius: 10px; padding: 25px; margin-bottom: 30px;">
                    <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, 
                            <?= $user['role'] == 'admin' ? '#1e3a8a' : '#065f46'; ?>, 
                            <?= $user['role'] == 'admin' ? '#3b82f6' : '#10b981'; ?>); 
                            border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user" style="color: #fff; font-size: 24px;"></i>
                        </div>

                        <div>
                            <h3 style="color: #fff; margin-bottom: 5px;">
                                <?= htmlspecialchars($user['username']); ?>
                            </h3>
                            <div style="display: flex; gap: 15px;">
                                <span style="color: #8f98a0;">
                                    <i class="fas fa-id-card"></i> ID: #
                                    <?= $user['id_user']; ?>
                                </span>
                                <span style="color: #8f98a0;">
                                    <i class="fas fa-user-tag"></i> Role:
                                    <?php if ($user['role'] == 'admin'): ?>
                                        <span style="color: #3b82f6;">Administrator</span>
                                    <?php else: ?>
                                        <span style="color: #10b981;">Customer</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        style="background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b; padding: 15px; border-radius: 4px;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                            <strong style="color: #f59e0b;">Apa yang akan terjadi?</strong>
                        </div>
                        <p style="color: #c7d5e0; font-size: 14px; margin: 0;">
                            Password user ini akan diubah menjadi <strong>password123</strong>.
                            Saat login berikutnya, user akan <strong>dipaksa untuk mengganti password</strong> dengan
                            yang baru.
                        </p>
                    </div>
                </div>

                <!-- Reset Information -->
                <div style="background: #1b2838; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
                    <h4 style="color: #66c0f4; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-info-circle"></i> Informasi Reset Password
                    </h4>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                        <div style="background: rgba(102, 192, 244, 0.1); padding: 15px; border-radius: 6px;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <div
                                    style="width: 36px; height: 36px; background: #66c0f4; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-key" style="color: #fff;"></i>
                                </div>
                                <strong style="color: #66c0f4;">Password Default</strong>
                            </div>
                            <p style="color: #c7d5e0; font-size: 13px; margin: 0;">
                                Setelah reset, password akan menjadi: <br>
                                <code
                                    style="background: #0b1320; padding: 5px 10px; border-radius: 4px; font-family: monospace; color: #f59e0b; margin-top: 5px; display: inline-block;">password123</code>
                            </p>
                        </div>

                        <div style="background: rgba(16, 185, 129, 0.1); padding: 15px; border-radius: 6px;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <div
                                    style="width: 36px; height: 36px; background: #10b981; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-shield-alt" style="color: #fff;"></i>
                                </div>
                                <strong style="color: #10b981;">Keamanan</strong>
                            </div>
                            <p style="color: #c7d5e0; font-size: 13px; margin: 0;">
                                User <strong>harus mengganti password</strong> saat login pertama kali setelah reset
                                untuk keamanan akun.
                            </p>
                        </div>

                        <div style="background: rgba(59, 130, 246, 0.1); padding: 15px; border-radius: 6px;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <div
                                    style="width: 36px; height: 36px; background: #3b82f6; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-user-clock" style="color: #fff;"></i>
                                </div>
                                <strong style="color: #3b82f6;">Proses Login</strong>
                            </div>
                            <p style="color: #c7d5e0; font-size: 13px; margin: 0;">
                                Setelah reset, user login dengan password default dan langsung diarahkan ke halaman
                                ganti password.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Confirmation -->
                <div style="background: #171a21; border-radius: 10px; padding: 25px; text-align: center;"
                    class="highlight-box">
                    <h3
                        style="color: #f59e0b; margin-bottom: 20px; display: flex; align-items: center; justify-content: center; gap: 10px;">
                        <i class="fas fa-exclamation-circle"></i> Konfirmasi Reset Password
                    </h3>

                    <p style="color: #c7d5e0; margin-bottom: 25px; font-size: 15px;">
                        Anda yakin ingin mereset password untuk user
                        <strong style="color: #fff;">
                            <?= htmlspecialchars($user['username']); ?>
                        </strong>?
                    </p>

                    <form method="POST" action="" id="resetForm">
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <a href="user_list.php" class="btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                            <button type="button" class="btn-primary" onclick="showConfirmModal()">
                                <i class="fas fa-check"></i> Ya, Reset Password
                            </button>
                        </div>
                    </form>

                    <p style="color: #8f98a0; font-size: 13px; margin-top: 20px; font-style: italic;">
                        <i class="fas fa-lightbulb"></i>
                        Reset password biasanya dilakukan ketika user lupa password atau akun perlu diakses ulang.
                    </p>
                </div>

                <!-- Admin Notes -->
                <div
                    style="margin-top: 30px; background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6; padding: 15px; border-radius: 4px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <i class="fas fa-user-shield" style="color: #3b82f6;"></i>
                        <strong style="color: #3b82f6;">Catatan untuk Administrator</strong>
                    </div>
                    <p style="color: #c7d5e0; font-size: 13px; margin: 0;">
                        Setelah mereset password, segera beritahu user tersebut tentang password default.
                        Pastikan user mengganti password saat login pertama kali untuk menjaga keamanan akun.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Confirmation Modal Functions
        function showConfirmModal() {
            console.log('Showing confirmation modal for password reset');
            document.getElementById('confirmModal').style.display = 'flex';
        }

        function closeConfirmModal() {
            console.log('Closing confirmation modal');
            document.getElementById('confirmModal').style.display = 'none';
        }

        function submitForm() {
            console.log('Submitting reset password form');
            const resetBtn = document.getElementById('finalResetBtn');

            // Disable button and show loading
            resetBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> MERESET...';
            resetBtn.disabled = true;
            resetBtn.style.opacity = '0.7';
            resetBtn.style.cursor = 'wait';

            // Submit the form
            document.getElementById('resetForm').submit();
        }

        // Close modal when clicking outside
        document.getElementById('confirmModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeConfirmModal();
            }
        });

        // Close modal with ESC key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeConfirmModal();
            }
        });

        // Optional: Add countdown before enabling reset button
        let resetEnabled = true; // Set to false if you want delay
        function enableResetAfterDelay() {
            const resetBtn = document.querySelector('.btn-primary');
            if (resetBtn && !resetEnabled) {
                resetBtn.disabled = true;
                resetBtn.innerHTML = '<i class="fas fa-clock"></i> TUNGGU 3 DETIK';

                let seconds = 3;
                const countdown = setInterval(() => {
                    seconds--;
                    resetBtn.innerHTML = `<i class="fas fa-clock"></i> TUNGGU ${seconds} DETIK`;

                    if (seconds <= 0) {
                        clearInterval(countdown);
                        resetBtn.disabled = false;
                        resetEnabled = true;
                        resetBtn.innerHTML = '<i class="fas fa-check"></i> Ya, Reset Password';
                        resetBtn.classList.add('highlight-box');
                    }
                }, 1000);
            }
        }

        // Uncomment to enable countdown timer
        // document.addEventListener('DOMContentLoaded', function() {
        //     enableResetAfterDelay();
        // });
    </script>

    <script src="../../assets/js/script.js"></script>
</body>

</html>