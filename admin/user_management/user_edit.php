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

$error = '';
$success = '';

// Proses update user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
    $role = mysqli_real_escape_string($conn, $_POST['role'] ?? 'user');

    // Validasi
    if (empty($username)) {
        $error = "Username harus diisi!";
    } elseif (strlen($username) < 3) {
        $error = "Username minimal 3 karakter!";
    } elseif (!in_array($role, ['admin', 'user'])) {
        $error = "Role tidak valid!";
    } else {
        // Cek jika username sudah digunakan (kecuali oleh user ini)
        $check_query = mysqli_query($conn, "SELECT id_user FROM users WHERE username = '$username' AND id_user != '$user_id'");
        if (mysqli_num_rows($check_query) > 0) {
            $error = "Username '$username' sudah digunakan!";
        } else {
            // Update user
            $update_query = mysqli_query($conn, "UPDATE users SET username = '$username', role = '$role' WHERE id_user = '$user_id'");

            if ($update_query) {
                $success = "Data user berhasil diupdate!";

                // Jika mengedit diri sendiri, update session
                if ($user_id == $_SESSION['id_user']) {
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $role;
                }

                // Refresh data user
                $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id_user = '$user_id'");
                $user = mysqli_fetch_assoc($user_query);
            } else {
                $error = "Gagal mengupdate data user!";
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
    <title>Edit User | Midnight Play Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <!-- Navbar -->
        <div class="navbar">
            <h1><i class="fas fa-user-edit"></i> Edit User</h1>
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
                    <a href="../game_management/game_list.php" class="sidebar-item">
                        <i class="fas fa-gamepad"></i>
                        Kelola Game
                    </a>
                    <a href="user_list.php" class="sidebar-item">
                        <i class="fas fa-users"></i>
                        Kelola User
                    </a>
                    <a href="../reports/transaction_report.php" class="sidebar-item">
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
                    <div
                        style="width: 80px; height: 80px; background: linear-gradient(135deg, 
                        <?= $user['role'] == 'admin' ? '#1e3a8a' : '#065f46'; ?>, 
                        <?= $user['role'] == 'admin' ? '#3b82f6' : '#10b981'; ?>); 
                        border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <i class="fas fa-user-edit" style="color: #fff; font-size: 32px;"></i>
                    </div>

                    <h2><i class="fas fa-user-cog"></i> Edit Data User</h2>
                    <p style="color: #8f98a0; margin-top: 10px;">Mengedit data untuk user #
                        <?= $user['id_user']; ?>
                    </p>
                </div>

                <!-- Alert Messages -->
                <?php if ($error): ?>
                    <div
                        style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-exclamation-circle" style="color: #ef4444;"></i>
                            <span style="color: #ef4444;">
                                <?= $error; ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div
                        style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-check-circle" style="color: #10b981;"></i>
                            <span style="color: #10b981;">
                                <?= $success; ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- User Info -->
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
                                    <i class="fas fa-user-tag"></i> Current Role:
                                    <?php if ($user['role'] == 'admin'): ?>
                                        <span style="color: #3b82f6;">Administrator</span>
                                    <?php else: ?>
                                        <span style="color: #10b981;">Customer</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div style="background: #171a21; border-radius: 10px; padding: 25px;">
                    <h3 style="color: #66c0f4; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-edit"></i> Form Edit User
                    </h3>

                    <form method="POST" action="">
                        <!-- Username -->
                        <div style="margin-bottom: 20px;">
                            <label style="color: #c7d5e0; font-size: 14px; margin-bottom: 8px; display: block;">
                                <i class="fas fa-user"></i> Username
                            </label>
                            <div class="input-wrapper">
                                <input type="text" name="username" value="<?= htmlspecialchars($user['username']); ?>"
                                    placeholder="Masukkan username" minlength="3" required style="padding-left: 40px;">
                                <i class="fas fa-user input-icon"></i>
                            </div>
                            <p style="color: #8f98a0; font-size: 12px; margin-top: 5px;">
                                <i class="fas fa-info-circle"></i> Username minimal 3 karakter
                            </p>
                        </div>

                        <!-- Role Selection -->
                        <div style="margin-bottom: 25px;">
                            <label style="color: #c7d5e0; font-size: 14px; margin-bottom: 8px; display: block;">
                                <i class="fas fa-user-tag"></i> Role
                            </label>
                            <div
                                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                <div>
                                    <label
                                        style="display: flex; align-items: center; gap: 10px; padding: 15px; background: <?= $user['role'] == 'user' ? 'rgba(16, 185, 129, 0.2)' : '#1b2838'; ?>; border: 2px solid <?= $user['role'] == 'user' ? '#10b981' : 'transparent'; ?>; border-radius: 8px; cursor: pointer;">
                                        <input type="radio" name="role" value="user" <?= $user['role'] == 'user' ? 'checked' : ''; ?>
                                        style="margin: 0;">
                                        <div>
                                            <div style="color: #10b981; font-weight: bold;">
                                                <i class="fas fa-user"></i> Customer
                                            </div>
                                            <div style="color: #8f98a0; font-size: 12px; margin-top: 5px;">
                                                Hanya bisa beli dan main game
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <div>
                                    <label
                                        style="display: flex; align-items: center; gap: 10px; padding: 15px; background: <?= $user['role'] == 'admin' ? 'rgba(59, 130, 246, 0.2)' : '#1b2838'; ?>; border: 2px solid <?= $user['role'] == 'admin' ? '#3b82f6' : 'transparent'; ?>; border-radius: 8px; cursor: pointer;">
                                        <input type="radio" name="role" value="admin" <?= $user['role'] == 'admin' ? 'checked' : ''; ?>
                                        style="margin: 0;">
                                        <div>
                                            <div style="color: #3b82f6; font-weight: bold;">
                                                <i class="fas fa-user-shield"></i> Administrator
                                            </div>
                                            <div style="color: #8f98a0; font-size: 12px; margin-top: 5px;">
                                                Akses penuh ke admin panel
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div style="display: flex; gap: 15px; margin-top: 30px;">
                            <a href="user_list.php" class="btn"
                                style="padding: 12px 25px; text-decoration: none; flex: 1; text-align: center;">
                                <i class="fas fa-times"></i> Batal
                            </a>
                            <button type="submit" name="submit" class="btn" style="flex: 2; padding: 12px;">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>

                    <!-- Warning if editing yourself -->
                    <?php if ($user_id == $_SESSION['id_user']): ?>
                        <div
                            style="margin-top: 20px; background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b; padding: 12px; border-radius: 4px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                                <span style="color: #f59e0b; font-size: 14px;">
                                    <strong>Perhatian:</strong> Anda sedang mengedit akun Anda sendiri.
                                    Perubahan akan langsung berlaku pada sesi Anda.
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Additional Actions -->
                <div style="margin-top: 30px; background: #1b2838; border-radius: 8px; padding: 20px;">
                    <h4 style="color: #66c0f4; margin-bottom: 15px;">
                        <i class="fas fa-cogs"></i> Aksi Lainnya
                    </h4>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <a href="user_reset_password.php?id=<?= $user['id_user']; ?>" class="btn"
                            style="background: #f59e0b; text-align: center; padding: 15px; text-decoration: none;">
                            <i class="fas fa-key"></i> Reset Password
                        </a>

                        <?php if ($user_id != $_SESSION['id_user']): ?>
                            <a href="user_delete.php?id=<?= $user['id_user']; ?>" class="btn"
                                style="background: #ef4444; text-align: center; padding: 15px; text-decoration: none;"
                                onclick="return confirm('Hapus user <?= addslashes($user['username']); ?>?')">
                                <i class="fas fa-trash"></i> Hapus User
                            </a>
                        <?php else: ?>
                            <a href="change_password.php" class="btn"
                                style="background: #10b981; text-align: center; padding: 15px; text-decoration: none;">
                                <i class="fas fa-lock"></i> Ganti Password Saya
                            </a>
                        <?php endif; ?>

                        <a href="user_list.php" class="btn"
                            style="background: #d900ff;text-align: center; padding: 15px; text-decoration: none;">
                            <i class="fas fa-list"></i> Lihat Semua User
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/script.js"></script>
</body>

</html>