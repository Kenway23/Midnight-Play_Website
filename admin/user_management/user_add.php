<?php
session_start();
include "../../config/database.php";

/* Proteksi admin */
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/auth_login.php");
    exit();
}

$error = '';
$success = '';

// Proses tambah user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
    $password = mysqli_real_escape_string($conn, $_POST['password'] ?? '');
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password'] ?? '');
    $role = mysqli_real_escape_string($conn, $_POST['role'] ?? 'user');
    
    // Validasi
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Semua field harus diisi!";
    } 
    elseif (strlen($username) < 3) {
        $error = "Username minimal 3 karakter!";
    }
    elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    }
    elseif ($password != $confirm_password) {
        $error = "Password dan konfirmasi password tidak sama!";
    }
    elseif (!in_array($role, ['admin', 'user'])) {
        $error = "Role tidak valid!";
    }
    else {
        // Cek jika username sudah digunakan
        $check_query = mysqli_query($conn, "SELECT id_user FROM users WHERE username = '$username'");
        if (mysqli_num_rows($check_query) > 0) {
            $error = "Username '$username' sudah digunakan!";
        } else {
            // Hash password
            $hashed_password = md5($password);
            
            // Insert user baru
            $insert_query = mysqli_query($conn, "INSERT INTO users (username, password, role) VALUES ('$username', '$hashed_password', '$role')");
            
            if ($insert_query) {
                $new_user_id = mysqli_insert_id($conn);
                $success = "User <strong>" . htmlspecialchars($username) . "</strong> berhasil ditambahkan!";
                
                // Reset form atau redirect
                if (isset($_POST['add_another'])) {
                    // Kosongkan form untuk tambah lagi
                    $_POST = array();
                } else {
                    // Redirect ke user_list setelah 2 detik
                    header("refresh:2;url=user_list.php");
                }
            } else {
                $error = "Gagal menambahkan user! Silakan coba lagi.";
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
    <title>Tambah User Baru | Midnight Play Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <!-- Navbar -->
        <div class="navbar">
            <h1><i class="fas fa-user-plus"></i> Tambah User Baru</h1>
            <div class="nav-right">
                <span class="nav-user"><i class="fas fa-user-shield"></i> <?= $_SESSION['username']; ?></span>
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
                    <a href="../reports/sales_report.php" class="sidebar-item">
                        <i class="fas fa-chart-line"></i>
                        Analisis Penjualan
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
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #10b981, #34d399); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <i class="fas fa-user-plus" style="color: #fff; font-size: 32px;"></i>
                    </div>
                    
                    <h2><i class="fas fa-user-plus"></i> Tambah User Baru</h2>
                    <p style="color: #8f98a0; margin-top: 10px;">Buat akun user baru untuk sistem Midnight Play</p>
                </div>

                <!-- Alert Messages -->
                <?php if ($error): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-exclamation-circle" style="color: #ef4444;"></i>
                            <span style="color: #ef4444;"><?= $error; ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-check-circle" style="color: #10b981;"></i>
                            <span style="color: #10b981;"><?= $success; ?></span>
                        </div>
                        <?php if (!isset($_POST['add_another'])): ?>
                            <p style="color: #10b981; font-size: 13px; margin-top: 5px; margin-bottom: 0;">
                                <i class="fas fa-sync-alt"></i> Mengalihkan ke daftar user...
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Information Card -->
                <div style="background: #171a21; border-radius: 10px; padding: 20px; margin-bottom: 30px;">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                        <i class="fas fa-info-circle" style="color: #66c0f4; font-size: 20px;"></i>
                        <h3 style="color: #66c0f4; margin: 0;">Informasi Penting</h3>
                    </div>
                    <ul style="color: #c7d5e0; font-size: 14px; line-height: 1.6; padding-left: 20px; margin: 0;">
                        <li>Username harus <strong>unik</strong> dan tidak boleh duplikat</li>
                        <li>Password minimal 6 karakter</li>
                        <li>Role <strong>Admin</strong> memiliki akses penuh ke sistem</li>
                        <li>Role <strong>User</strong> hanya bisa membeli dan memainkan game</li>
                        <li>Pastikan untuk menyimpan informasi login dengan aman</li>
                    </ul>
                </div>

                <!-- Add User Form -->
                <div style="background: #171a21; border-radius: 10px; padding: 25px;">
                    <h3 style="color: #66c0f4; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-user-edit"></i> Form Tambah User
                    </h3>
                    
                    <form method="POST" action="">
                        <!-- Username -->
                        <div style="margin-bottom: 20px;">
                            <label style="color: #c7d5e0; font-size: 14px; margin-bottom: 8px; display: block;">
                                <i class="fas fa-user"></i> Username <span style="color: #ef4444;">*</span>
                            </label>
                            <div class="input-wrapper">
                                <input type="text" 
                                       name="username" 
                                       value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>"
                                       placeholder="Masukkan username (min. 3 karakter)"
                                       minlength="3"
                                       required
                                       style="padding-left: 40px;">
                                <i class="fas fa-user input-icon"></i>
                            </div>
                            <p style="color: #8f98a0; font-size: 12px; margin-top: 5px;">
                                <i class="fas fa-info-circle"></i> Gunakan username yang mudah diingat
                            </p>
                        </div>

                        <!-- Password -->
                        <div style="margin-bottom: 20px;">
                            <label style="color: #c7d5e0; font-size: 14px; margin-bottom: 8px; display: block;">
                                <i class="fas fa-lock"></i> Password <span style="color: #ef4444;">*</span>
                            </label>
                            <div class="input-wrapper">
                                <input type="password" 
                                       name="password" 
                                       placeholder="Masukkan password (min. 6 karakter)"
                                       minlength="6"
                                       required
                                       style="padding-left: 40px;">
                                <i class="fas fa-key input-icon"></i>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div style="margin-bottom: 20px;">
                            <label style="color: #c7d5e0; font-size: 14px; margin-bottom: 8px; display: block;">
                                <i class="fas fa-lock"></i> Konfirmasi Password <span style="color: #ef4444;">*</span>
                            </label>
                            <div class="input-wrapper">
                                <input type="password" 
                                       name="confirm_password" 
                                       placeholder="Ketik ulang password"
                                       minlength="6"
                                       required
                                       style="padding-left: 40px;">
                                <i class="fas fa-lock input-icon"></i>
                            </div>
                            <div id="password-match" style="color: #8f98a0; font-size: 12px; margin-top: 5px;"></div>
                        </div>

                        <!-- Role Selection -->
                        <div style="margin-bottom: 25px;">
                            <label style="color: #c7d5e0; font-size: 14px; margin-bottom: 12px; display: block;">
                                <i class="fas fa-user-tag"></i> Role User <span style="color: #ef4444;">*</span>
                            </label>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                                <div>
                                    <label style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #1b2838; border: 2px solid <?= ($_POST['role'] ?? 'user') == 'user' ? '#10b981' : 'transparent'; ?>; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                                        <input type="radio" name="role" value="user" <?= ($_POST['role'] ?? 'user') == 'user' ? 'checked' : ''; ?> 
                                               style="margin: 0;">
                                        <div style="flex: 1;">
                                            <div style="color: #10b981; font-weight: bold; font-size: 15px;">
                                                <i class="fas fa-user"></i> Customer
                                            </div>
                                            <div style="color: #8f98a0; font-size: 13px; margin-top: 8px; line-height: 1.5;">
                                                • Hanya bisa membeli game<br>
                                                • Hanya bisa memainkan game<br>
                                                • Tidak bisa akses admin panel
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                
                                <div>
                                    <label style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #1b2838; border: 2px solid <?= ($_POST['role'] ?? 'user') == 'admin' ? '#3b82f6' : 'transparent'; ?>; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                                        <input type="radio" name="role" value="admin" <?= ($_POST['role'] ?? 'user') == 'admin' ? 'checked' : ''; ?> 
                                               style="margin: 0;">
                                        <div style="flex: 1;">
                                            <div style="color: #3b82f6; font-weight: bold; font-size: 15px;">
                                                <i class="fas fa-user-shield"></i> Administrator
                                            </div>
                                            <div style="color: #8f98a0; font-size: 13px; margin-top: 8px; line-height: 1.5;">
                                                • Akses penuh ke admin panel<br>
                                                • Bisa kelola user dan game<br>
                                                • Bisa lihat laporan transaksi
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <p style="color: #8f98a0; font-size: 12px; margin-top: 10px;">
                                <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                                Hanya berikan role <strong>Admin</strong> kepada user yang benar-benar dipercaya
                            </p>
                        </div>

                        <!-- Submit Buttons -->
                        <div style="display: flex; gap: 15px; margin-top: 30px;">
                            <a href="user_list.php" class="btn" style="padding: 12px 25px; text-decoration: none; flex: 1; text-align: center;">
                                <i class="fas fa-times"></i> Batal
                            </a>
                            
                            <button type="submit" name="add_another" value="1" class="btn" style="background: #8b5cf6; padding: 12px; flex: 1;">
                                <i class="fas fa-plus-circle"></i> Simpan & Tambah Lagi
                            </button>
                            
                            <button type="submit" name="submit" class="btn" style="padding: 12px; flex: 1;">
                                <i class="fas fa-save"></i> Simpan & Kembali
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Quick User Stats -->
                <div style="margin-top: 30px; background: #1b2838; border-radius: 8px; padding: 20px;">
                    <h4 style="color: #66c0f4; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-chart-bar"></i> Statistik User
                    </h4>
                    
                    <?php
                    // Hitung statistik
                    $total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];
                    $total_admins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'admin'"))['total'];
                    $total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'user'"))['total'];
                    ?>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <div style="background: rgba(102, 192, 244, 0.1); padding: 15px; border-radius: 6px; text-align: center;">
                            <div style="color: #66c0f4; font-size: 24px; font-weight: bold;">
                                <?= $total_users; ?>
                            </div>
                            <div style="color: #8f98a0; font-size: 13px;">
                                Total User
                            </div>
                        </div>
                        
                        <div style="background: rgba(59, 130, 246, 0.1); padding: 15px; border-radius: 6px; text-align: center;">
                            <div style="color: #3b82f6; font-size: 24px; font-weight: bold;">
                                <?= $total_admins; ?>
                            </div>
                            <div style="color: #8f98a0; font-size: 13px;">
                                Admin
                            </div>
                        </div>
                        
                        <div style="background: rgba(16, 185, 129, 0.1); padding: 15px; border-radius: 6px; text-align: center;">
                            <div style="color: #10b981; font-size: 24px; font-weight: bold;">
                                <?= $total_customers; ?>
                            </div>
                            <div style="color: #8f98a0; font-size: 13px;">
                                Customer
                            </div>
                        </div>
                        
                        <div style="background: rgba(139, 92, 246, 0.1); padding: 15px; border-radius: 6px; text-align: center;">
                            <div style="color: #8b5cf6; font-size: 24px; font-weight: bold;">
                                +1
                            </div>
                            <div style="color: #8f98a0; font-size: 13px;">
                                User Baru
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Tips -->
                <div style="margin-top: 20px; background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b; padding: 15px; border-radius: 4px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <i class="fas fa-lightbulb" style="color: #f59e0b;"></i>
                        <strong style="color: #f59e0b;">Tips untuk Administrator</strong>
                    </div>
                    <p style="color: #c7d5e0; font-size: 13px; margin: 0; line-height: 1.5;">
                        • Simpan username dan password user baru di tempat yang aman<br>
                        • Berikan informasi login kepada user melalui komunikasi yang aman<br>
                        • Sarankan user untuk segera mengganti password setelah login pertama<br>
                        • Review secara berkala user dengan role admin untuk keamanan sistem
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password match checker
        const passwordInput = document.querySelector('input[name="password"]');
        const confirmInput = document.querySelector('input[name="confirm_password"]');
        const matchText = document.getElementById('password-match');

        passwordInput.addEventListener('input', checkPasswordMatch);
        confirmInput.addEventListener('input', checkPasswordMatch);

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
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
        }

        // Auto focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="username"]').focus();
        });

        // Role selection styling
        document.querySelectorAll('input[name="role"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('input[name="role"]').forEach(r => {
                    const label = r.closest('label');
                    if (r.checked) {
                        if (r.value === 'admin') {
                            label.style.borderColor = '#3b82f6';
                            label.style.background = 'rgba(59, 130, 246, 0.2)';
                        } else {
                            label.style.borderColor = '#10b981';
                            label.style.background = 'rgba(16, 185, 129, 0.2)';
                        }
                    } else {
                        label.style.borderColor = 'transparent';
                        label.style.background = '#1b2838';
                    }
                });
            });
        });
    </script>
</body>

</html>