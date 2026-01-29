<?php
session_start();
include "../config/database.php";

/* Proteksi - hanya untuk user yang login */
if (!isset($_SESSION['login'])) {
    header("Location: auth_login.php");
    exit();
}

$user_id = $_SESSION['id_user'];
$error = '';
$success = '';

// Ambil data user
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id_user = '$user_id'");
$user = mysqli_fetch_assoc($user_query);

// Cek jika user perlu ganti password (jika dari reset admin)
$force_change = isset($_SESSION['need_password_change']) && $_SESSION['need_password_change'] === true;

// Proses perubahan password - HANYA JIKA FORM DISUBMIT
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    // Jika force change, tidak perlu password lama
    if ($force_change) {
        $new_password = mysqli_real_escape_string($conn, $_POST['new_password'] ?? '');
        $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password'] ?? '');

        $current_password = ''; // Tidak diperlukan untuk force change
    } else {
        $current_password = mysqli_real_escape_string($conn, $_POST['current_password'] ?? '');
        $new_password = mysqli_real_escape_string($conn, $_POST['new_password'] ?? '');
        $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password'] ?? '');
    }

    // Validasi
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Password baru dan konfirmasi password harus diisi!";
    } elseif (!$force_change && empty($current_password)) {
        $error = "Password saat ini harus diisi!";
    } elseif ($new_password != $confirm_password) {
        $error = "Password baru dan konfirmasi password tidak sama!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password baru minimal 6 karakter!";
    } else {
        // Jika force change (dari reset admin), skip validasi password lama
        if ($force_change) {
            // Langsung update password
            $hashed_password = md5($new_password);
            $update_query = mysqli_query($conn, "UPDATE users SET password = '$hashed_password' WHERE id_user = '$user_id'");

            if ($update_query) {
                // Hapus session force change
                unset($_SESSION['need_password_change']);
                $success = "Password berhasil diubah! Anda akan dialihkan ke halaman utama...";

                // Redirect setelah 3 detik
                header("refresh:3;url=../index.php");
            } else {
                $error = "Gagal mengubah password. Silakan coba lagi.";
            }
        } else {
            // Validasi password lama untuk perubahan normal
            if (md5($current_password) != $user['password']) {
                $error = "Password saat ini salah!";
            } elseif ($current_password == $new_password) {
                $error = "Password baru tidak boleh sama dengan password lama!";
            } else {
                // Update password
                $hashed_password = md5($new_password);
                $update_query = mysqli_query($conn, "UPDATE users SET password = '$hashed_password' WHERE id_user = '$user_id'");

                if ($update_query) {
                    $success = "Password berhasil diubah!";
                    // Optional: Log user out after password change
                    // session_destroy();
                    // header("Location: auth_login.php");
                } else {
                    $error = "Gagal mengubah password. Silakan coba lagi.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $force_change ? 'Ganti Password Wajib' : 'Ubah Password'; ?> | Midnight Play</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div class="auth-wrapper">
        <div class="auth-box" style="max-width: 500px;">
            <!-- Header -->
            <div style="text-align: center; margin-bottom: 25px;">
                <div
                    style="width: 60px; height: 60px; background: linear-gradient(135deg, #1f80ff, #66c0f4); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fas fa-key" style="color: #fff; font-size: 24px;"></i>
                </div>

                <h2>
                    <?php if ($force_change): ?>
                        <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Ganti Password Wajib
                    <?php else: ?>
                        <i class="fas fa-user-lock"></i> Ubah Password
                    <?php endif; ?>
                </h2>

                <p style="color: #8f98a0; margin-top: 10px;">
                    <?php if ($force_change): ?>
                        <strong>Password Anda telah direset.</strong> Harap buat password baru untuk keamanan akun.
                    <?php else: ?>
                        Buat password baru untuk akun Anda
                    <?php endif; ?>
                </p>
            </div>

            <!-- Alert Messages -->
            <?php if ($error): ?>
                <div
                    style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-exclamation-circle" style="color: #ef4444;"></i>
                        <span style="color: #ef4444;"><?= $error; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div
                    style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                        <span style="color: #10b981;"><?= $success; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Information Box for Force Change -->
            <?php if ($force_change): ?>
                <div
                    style="background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: flex-start; gap: 10px;">
                        <i class="fas fa-info-circle" style="color: #f59e0b; font-size: 18px; margin-top: 2px;"></i>
                        <div>
                            <h4 style="color: #f59e0b; margin-bottom: 8px; margin-top: 0;">
                                <i class="fas fa-shield-alt"></i> Keamanan Akun
                            </h4>
                            <p style="color: #c7d5e0; font-size: 14px; margin: 0; line-height: 1.5;">
                                Password Anda telah direset oleh administrator. Untuk melindungi akun Anda,
                                <strong>wajib membuat password baru</strong> yang hanya Anda ketahui.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Password Form -->
            <form method="POST" action="">
                <!-- Current Password (only for normal change) -->
                <?php if (!$force_change): ?>
                    <div style="margin-bottom: 20px;">
                        <label style="color: #c7d5e0; font-size: 14px; margin-bottom: 8px; display: block;">
                            <i class="fas fa-lock"></i> Password Saat Ini
                        </label>
                        <div class="input-wrapper">
                            <input type="password" name="current_password" placeholder="Masukkan password saat ini" required
                                style="padding-left: 40px;">
                            <i class="fas fa-key input-icon"></i>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- New Password -->
                <div style="margin-bottom: 20px;">
                    <label style="color: #c7d5e0; font-size: 14px; margin-bottom: 8px; display: block;">
                        <i class="fas fa-lock"></i> Password Baru
                        <?php if ($force_change): ?>
                            <span style="color: #f59e0b; font-size: 12px;"> *Wajib</span>
                        <?php endif; ?>
                    </label>
                    <div class="input-wrapper">
                        <input type="password" name="new_password"
                            placeholder="Masukkan password baru (min. 6 karakter)" minlength="6" required
                            style="padding-left: 40px;">
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    <div style="margin-top: 8px;">
                        <div id="password-strength"
                            style="height: 4px; background: #2a2f3a; border-radius: 2px; overflow: hidden;">
                            <div id="password-strength-bar"
                                style="height: 100%; width: 0%; background: #ef4444; transition: width 0.3s;"></div>
                        </div>
                        <div id="password-hints" style="color: #8f98a0; font-size: 12px; margin-top: 5px;"></div>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div style="margin-bottom: 25px;">
                    <label style="color: #c7d5e0; font-size: 14px; margin-bottom: 8px; display: block;">
                        <i class="fas fa-lock"></i> Konfirmasi Password Baru
                    </label>
                    <div class="input-wrapper">
                        <input type="password" name="confirm_password" placeholder="Ketik ulang password baru"
                            minlength="6" required style="padding-left: 40px;">
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    <div id="password-match" style="color: #8f98a0; font-size: 12px; margin-top: 5px;"></div>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="submit" class="btn" style="width: 100%; padding: 12px; font-size: 16px;">
                    <?php if ($force_change): ?>
                        <i class="fas fa-check-circle"></i> Simpan Password Baru
                    <?php else: ?>
                        <i class="fas fa-save"></i> Ubah Password
                    <?php endif; ?>
                </button>

                <!-- Cancel/Back Link -->
                <div style="text-align: center; margin-top: 20px;">
                    <?php if ($force_change): ?>
                        <a href="auth_logout.php" style="color: #66c0f4; font-size: 14px; text-decoration: none;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    <?php else: ?>
                        <a href="../index.php" style="color: #66c0f4; font-size: 14px; text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Password Tips -->
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #2a2f3a;">
                <h4 style="color: #66c0f4; font-size: 15px; margin-bottom: 10px;">
                    <i class="fas fa-lightbulb"></i> Tips Password Aman:
                </h4>
                <ul style="color: #8f98a0; font-size: 13px; line-height: 1.6; padding-left: 20px; margin: 0;">
                    <li>Gunakan minimal 8 karakter</li>
                    <li>Kombinasikan huruf besar, kecil, angka, dan simbol</li>
                    <li>Hindari informasi pribadi (nama, tanggal lahir)</li>
                    <li>Jangan gunakan password yang sama dengan akun lain</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Password strength checker
        const passwordInput = document.querySelector('input[name="new_password"]');
        const confirmInput = document.querySelector('input[name="confirm_password"]');
        const strengthBar = document.getElementById('password-strength-bar');
        const hints = document.getElementById('password-hints');
        const matchText = document.getElementById('password-match');

        passwordInput.addEventListener('input', function () {
            const password = this.value;
            let strength = 0;
            let hintsArray = [];

            // Length check
            if (password.length >= 8) {
                strength += 25;
            } else {
                hintsArray.push('Minimal 8 karakter');
            }

            // Has lowercase
            if (/[a-z]/.test(password)) {
                strength += 25;
            } else {
                hintsArray.push('Gunakan huruf kecil');
            }

            // Has uppercase
            if (/[A-Z]/.test(password)) {
                strength += 25;
            } else {
                hintsArray.push('Gunakan huruf besar');
            }

            // Has numbers
            if (/\d/.test(password)) {
                strength += 15;
            } else {
                hintsArray.push('Tambahkan angka');
            }

            // Has special characters
            if (/[^A-Za-z0-9]/.test(password)) {
                strength += 10;
            } else {
                hintsArray.push('Tambahkan simbol (!@#$% dll)');
            }

            // Update strength bar
            strengthBar.style.width = strength + '%';

            // Set color based on strength
            if (strength < 50) {
                strengthBar.style.background = '#ef4444'; // Red
            } else if (strength < 75) {
                strengthBar.style.background = '#f59e0b'; // Yellow
            } else {
                strengthBar.style.background = '#10b981'; // Green
            }

            // Update hints
            if (password.length === 0) {
                hints.textContent = '';
            } else {
                hints.textContent = hintsArray.length > 0 ? hintsArray.join(' • ') : 'Password kuat!';
            }
        });

        // Password match checker
        confirmInput.addEventListener('input', function () {
            const password = passwordInput.value;
            const confirm = this.value;

            if (confirm.length === 0) {
                matchText.textContent = '';
                matchText.style.color = '#8f98a0';
            } else if (password === confirm) {
                matchText.innerHTML = '<i class="fas fa-check"></i> Password cocok';
                matchText.style.color = '#10b981';
            } else {
                matchText.innerHTML = '<i class="fas fa-times"></i> Password tidak cocok';
                matchText.style.color = '#ef4444';
            }
        });

        // Auto focus for force change
        <?php if ($force_change): ?>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelector('input[name="new_password"]').focus();
            });
        <?php endif; ?>
    </script>
</body>

</html>