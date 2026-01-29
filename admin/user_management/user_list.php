<?php
session_start();
include "../../config/database.php";

/* Proteksi admin */
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/auth_login.php");
    exit();
}

// Query untuk mendapatkan semua user
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id_user DESC");
$totalUsers = mysqli_num_rows($users);

// Hitung statistik
$totalAdmins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'admin'"))['total'];
$totalCustomers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'user'"))['total'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User | Midnight Play Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Modal Styles */
        .logout-modal,
        .delete-modal,
        .role-modal,
        .reset-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-box {
            background: #171a21;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            width: 450px;
            border: 1px solid #2a475e;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .modal-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .logout-modal .modal-icon {
            color: #ff6b6b;
        }

        .delete-modal .modal-icon {
            color: #dc2626;
        }

        .role-modal .modal-icon {
            color: #3b82f6;
        }

        .reset-modal .modal-icon {
            color: #f59e0b;
        }

        .modal-title {
            margin-bottom: 10px;
            font-size: 20px;
            font-weight: bold;
        }

        .logout-modal .modal-title {
            color: #ff6b6b;
        }

        .delete-modal .modal-title {
            color: #dc2626;
        }

        .role-modal .modal-title {
            color: #3b82f6;
        }

        .reset-modal .modal-title {
            color: #f59e0b;
        }

        .modal-message {
            color: #c7d5e0;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .modal-btn {
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            font-size: 14px;
            transition: all 0.3s;
            min-width: 100px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-cancel {
            background: #2a2f3a;
            color: #c7d5e0;
        }

        .btn-cancel:hover {
            background: #3d4452;
        }

        .btn-logout {
            background: #ff6b6b;
            color: white;
        }

        .btn-logout:hover {
            background: #ff4757;
        }

        .btn-delete-confirm {
            background: #dc2626;
            color: white;
        }

        .btn-delete-confirm:hover {
            background: #b91c1c;
        }

        .btn-role-confirm {
            background: #3b82f6;
            color: white;
        }

        .btn-role-confirm:hover {
            background: #2563eb;
        }

        .btn-reset-confirm {
            background: #f59e0b;
            color: white;
        }

        .btn-reset-confirm:hover {
            background: #d97706;
        }

        .user-details {
            background: #1b1f27;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            text-align: left;
            border: 1px solid #2a475e;
        }

        .user-details p {
            margin: 8px 0;
            color: #c7d5e0;
            font-size: 14px;
        }

        .user-details strong {
            color: #66c0f4;
        }

        .warning-text {
            color: #fbbf24;
            background: rgba(251, 191, 36, 0.1);
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-size: 13px;
            border-left: 3px solid #fbbf24;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-text {
            color: #60a5fa;
            background: rgba(96, 165, 250, 0.1);
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-size: 13px;
            border-left: 3px solid #60a5fa;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .success-text {
            color: #10b981;
            background: rgba(16, 185, 129, 0.1);
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-size: 13px;
            border-left: 3px solid #10b981;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s;
        }

        .btn-edit:hover {
            background: #2563eb;
        }

        .btn-delete {
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s;
        }

        .btn-delete:hover {
            background: #b91c1c;
        }

        .btn-role-toggle {
            background: #10b981;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s;
        }

        .btn-role-toggle:hover {
            background: #059669;
        }

        .btn-reset {
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 4px 10px;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s;
        }

        .btn-reset:hover {
            background: #d97706;
        }

        .btn-disabled {
            padding: 8px 12px;
            background: #374151;
            border-radius: 4px;
            color: #9ca3af;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .password-display {
            font-family: monospace;
            background: #0b1320;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
            color: #fbbf24;
            letter-spacing: 1px;
            margin: 10px 0;
            text-align: center;
            border: 1px solid #f59e0b;
        }

        .password-note {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 4px;
            padding: 12px;
            margin: 10px 0;
        }

        .password-note p {
            margin: 5px 0;
            font-size: 13px;
            color: #c7d5e0;
        }

        .password-note strong {
            color: #fbbf24;
        }
    </style>
</head>

<body>
    <!-- Logout Modal -->
    <div id="logoutModal" class="logout-modal">
        <div class="modal-box">
            <div class="modal-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h2 class="modal-title">Konfirmasi Logout</h2>
            <p class="modal-message">
                Apakah Anda yakin ingin keluar dari admin panel?
            </p>
            <div class="modal-buttons">
                <button class="modal-btn btn-cancel" onclick="closeLogoutModal()">Batal</button>
                <a href="../../auth/auth_logout.php" class="modal-btn btn-logout">Logout</a>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="delete-modal">
        <div class="modal-box">
            <div class="modal-icon">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h2 class="modal-title">Konfirmasi Hapus User</h2>
            <p class="modal-message">
                Anda akan menghapus user berikut:
            </p>

            <div class="user-details" id="deleteUserDetails">
                <!-- User details will be inserted here by JavaScript -->
            </div>

            <div class="warning-text">
                <i class="fas fa-exclamation-circle"></i>
                <span>Perhatian: Tindakan ini tidak dapat dibatalkan!</span>
            </div>

            <p class="modal-message">
                Apakah Anda yakin ingin melanjutkan?
            </p>

            <div class="modal-buttons">
                <button class="modal-btn btn-cancel" onclick="closeDeleteModal()">Batal</button>
                <a href="#" id="deleteConfirmBtn" class="modal-btn btn-delete-confirm">
                    <i class="fas fa-trash"></i> Hapus User
                </a>
            </div>
        </div>
    </div>

    <!-- Change Role Modal -->
    <div id="roleModal" class="role-modal">
        <div class="modal-box">
            <div class="modal-icon">
                <i class="fas fa-user-cog"></i>
            </div>
            <h2 class="modal-title" id="roleModalTitle">Ubah Role User</h2>
            <p class="modal-message" id="roleModalMessage">
                <!-- Message will be inserted here by JavaScript -->
            </p>

            <div class="user-details" id="roleUserDetails">
                <!-- User details will be inserted here by JavaScript -->
            </div>

            <div id="roleWarning" class="warning-text">
                <!-- Warning message will be inserted here by JavaScript -->
            </div>

            <div id="roleSuccess" class="success-text" style="display: none;">
                <i class="fas fa-info-circle"></i>
                <span>User akan mendapatkan akses baru sesuai role yang dipilih</span>
            </div>

            <div class="modal-buttons">
                <button class="modal-btn btn-cancel" onclick="closeRoleModal()">Batal</button>
                <a href="#" id="roleConfirmBtn" class="modal-btn btn-role-confirm">
                    <i class="fas fa-sync-alt"></i> Ubah Role
                </a>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetModal" class="reset-modal">
        <div class="modal-box">
            <div class="modal-icon">
                <i class="fas fa-key"></i>
            </div>
            <h2 class="modal-title">Reset Password User</h2>
            <p class="modal-message">
                Anda akan mereset password untuk user berikut:
            </p>

            <div class="user-details" id="resetUserDetails">
                <!-- User details will be inserted here by JavaScript -->
            </div>

            <div class="info-text">
                <i class="fas fa-info-circle"></i>
                <span>Password baru akan direset ke default:</span>
            </div>

            <div class="password-display" id="newPasswordDisplay">
                <!-- New password will be inserted here by JavaScript -->
            </div>

            <div class="password-note">
                <p><strong>Catatan Penting:</strong></p>
                <p>1. User harus login dengan password default ini</p>
                <p>2. User harus mengganti password setelah login pertama</p>
                <p>3. Password default akan ditampilkan sekali saja</p>
                <p>4. Simpan password ini dan berikan ke user</p>
            </div>

            <div class="warning-text">
                <i class="fas fa-exclamation-circle"></i>
                <span>Pastikan Anda memberikan password baru ke user dengan aman!</span>
            </div>

            <div class="modal-buttons">
                <button class="modal-btn btn-cancel" onclick="closeResetModal()">Batal</button>
                <a href="#" id="resetConfirmBtn" class="modal-btn btn-reset-confirm">
                    <i class="fas fa-redo"></i> Reset Password
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Navbar -->
        <div class="navbar">
            <h1><i class="fas fa-users-cog"></i> User Management</h1>
            <div class="nav-right">
                <span class="nav-user"><i class="fas fa-user-shield"></i>
                    <?= $_SESSION['username'] ?? 'Admin'; ?></span>
                <a href="../admin_dashboard.php" class="btn-login"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="#" class="btn-logout" onclick="return showLogoutModal()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
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
                    <a href="user_list.php" class="sidebar-item active">
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
                <!-- Header -->
                <div class="button-container">
                    <div>
                        <h2><i class="fas fa-user-friends"></i> Manajemen Pengguna</h2>
                        <p style="color: #8f98a0; margin-top: 5px;">Total: <?= $totalUsers; ?> pengguna terdaftar</p>
                    </div>
                    <a href="user_add.php" class="button">
                        <i class="fas fa-user-plus"></i> Tambah User Baru
                    </a>
                </div>

                <!-- Stats Cards -->
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0 30px 0;">
                    <div class="dashboard-card users-card">
                        <div class="card-icon">
                            <i class="fas fa-users fa-lg"></i>
                        </div>
                        <h3>Total Users</h3>
                        <p><?= $totalUsers; ?> Akun</p>
                    </div>

                    <div class="dashboard-card" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6);">
                        <div class="card-icon">
                            <i class="fas fa-user-shield fa-lg"></i>
                        </div>
                        <h3>Admin</h3>
                        <p><?= $totalAdmins; ?> Admin</p>
                    </div>

                    <div class="dashboard-card games-card">
                        <div class="card-icon">
                            <i class="fas fa-user fa-lg"></i>
                        </div>
                        <h3>Customers</h3>
                        <p><?= $totalCustomers; ?> Customer</p>
                    </div>
                </div>

                <!-- Table -->
                <div style="background: #171a21; border-radius: 8px; padding: 20px;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Password</th>
                                <th style="width: 220px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($totalUsers > 0): ?>
                                <?php while ($user = mysqli_fetch_assoc($users)): ?>
                                    <tr>
                                        <td data-label="ID">#<?= $user['id_user']; ?></td>
                                        <td data-label="Username">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div
                                                    style="width: 36px; height: 36px; background: linear-gradient(135deg, 
                                                    <?= $user['role'] == 'admin' ? '#1e3a8a' : '#065f46'; ?>, 
                                                    <?= $user['role'] == 'admin' ? '#3b82f6' : '#10b981'; ?>); 
                                                    border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-user" style="color: #fff; font-size: 16px;"></i>
                                                </div>
                                                <div>
                                                    <strong><?= htmlspecialchars($user['username']); ?></strong>
                                                    <?php if ($user['role'] == 'admin'): ?>
                                                        <br>
                                                        <small style="color: #3b82f6; font-size: 11px;">
                                                            <i class="fas fa-crown"></i> Administrator
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="Role">
                                            <?php if ($user['role'] == 'admin'): ?>
                                                <span
                                                    style="background: rgba(59, 130, 246, 0.2); color: #3b82f6; padding: 6px 15px; border-radius: 12px; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-user-shield"></i> Admin
                                                </span>
                                            <?php else: ?>
                                                <span
                                                    style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 6px 15px; border-radius: 12px; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-user"></i> Customer
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Password">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span
                                                    style="font-family: monospace; background: #0b1320; padding: 4px 8px; border-radius: 4px; font-size: 12px; color: #8f98a0;">
                                                    ********
                                                </span>
                                                <button type="button" class="btn-reset" onclick="event.preventDefault(); showResetModal(
                                                        '<?= $user['id_user']; ?>',
                                                        '<?= addslashes($user['username']); ?>',
                                                        '<?= $user['role']; ?>',
                                                        '<?= !empty($user['email']) ? htmlspecialchars($user['email']) : 'N/A'; ?>'
                                                    )" title="Reset Password">
                                                    <i class="fas fa-redo"></i> Reset
                                                </button>
                                            </div>
                                        </td>
                                        <td data-label="Aksi">
                                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                <a href="user_edit.php?id=<?= $user['id_user']; ?>" class="btn-edit"
                                                    title="Edit User">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>

                                                <?php if ($user['id_user'] != $_SESSION['id_user']): ?>
                                                    <?php
                                                    // Format tanggal untuk modal
                                                    $createdDate = 'N/A';
                                                    if (!empty($user['created_at'])) {
                                                        $timestamp = strtotime($user['created_at']);
                                                        $createdDate = $timestamp !== false ? date('d/m/Y', $timestamp) : 'N/A';
                                                    }
                                                    ?>

                                                    <!-- Delete Button -->
                                                    <button type="button" class="btn-delete" onclick="event.preventDefault(); showDeleteModal(
                                                            '<?= $user['id_user']; ?>',
                                                            '<?= addslashes($user['username']); ?>',
                                                            '<?= $user['role']; ?>',
                                                            '<?= $createdDate; ?>'
                                                        )" title="Hapus User">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </button>

                                                    <!-- Role Toggle Button -->
                                                    <?php if ($user['role'] == 'admin'): ?>
                                                        <button type="button" class="btn-role-toggle" onclick="event.preventDefault(); showRoleModal(
                                                                '<?= $user['id_user']; ?>',
                                                                '<?= addslashes($user['username']); ?>',
                                                                'admin',
                                                                'user'
                                                            )" title="Ubah ke Customer">
                                                            <i class="fas fa-user"></i> Customer
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn-role-toggle" onclick="event.preventDefault(); showRoleModal(
                                                                '<?= $user['id_user']; ?>',
                                                                '<?= addslashes($user['username']); ?>',
                                                                'user',
                                                                'admin'
                                                            )" title="Jadikan Admin">
                                                            <i class="fas fa-user-shield"></i> Admin
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="btn-disabled">
                                                        <i class="fas fa-info-circle"></i> Akun Anda
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #8f98a0;">
                                        <i class="fas fa-user-slash"
                                            style="font-size: 48px; margin-bottom: 15px; display: block; color: #2a2f3a;"></i>
                                        Belum ada user yang terdaftar
                                        <br>
                                        <a href="user_add.php" class="button" style="margin-top: 15px;">
                                            <i class="fas fa-user-plus"></i> Tambah User Pertama
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Information Box -->
                <div
                    style="margin-top: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                    <div
                        style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6; padding: 15px; border-radius: 4px;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <i class="fas fa-user-shield" style="color: #3b82f6;"></i>
                            <strong style="color: #3b82f6;">Role Administrator</strong>
                        </div>
                        <p style="color: #c7d5e0; font-size: 13px; margin: 0;">
                            User dengan role <strong>Admin</strong> memiliki akses penuh ke semua fitur sistem termasuk
                            panel admin ini.
                            Hanya berikan role admin kepada user yang benar-benar terpercaya.
                        </p>
                    </div>

                    <div
                        style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; padding: 15px; border-radius: 4px;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <i class="fas fa-key" style="color: #10b981;"></i>
                            <strong style="color: #10b981;">Manajemen Password</strong>
                        </div>
                        <p style="color: #c7d5e0; font-size: 13px; margin: 0;">
                            Reset password akan mengubah password user menjadi <strong>password123</strong> (default).
                            User harus mengganti password setelah login pertama kali.
                        </p>
                    </div>
                </div>

                <!-- Footer Navigation -->
                <div style="margin-top: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <a href="../admin_dashboard.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                    <div style="color: #8f98a0; font-size: 14px;">
                        <i class="fas fa-info-circle"></i>
                        <?= $totalUsers; ?> user • <?= $totalAdmins; ?> admin • <?= $totalCustomers; ?> customer
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Logout Modal Functions
        function showLogoutModal() {
            console.log('Opening logout modal');
            document.getElementById('logoutModal').style.display = 'flex';
            return false;
        }

        function closeLogoutModal() {
            console.log('Closing logout modal');
            document.getElementById('logoutModal').style.display = 'none';
        }

        // Delete Modal Functions
        function showDeleteModal(id, username, role, createdDate) {
            console.log('Opening delete modal for user:', username);

            const roleText = role === 'admin' ? 'Admin' : 'Customer';
            const roleColor = role === 'admin' ? '#3b82f6' : '#10b981';

            // Set user details
            const detailsDiv = document.getElementById('deleteUserDetails');
            detailsDiv.innerHTML = `
                <p><strong>ID User:</strong> #${id}</p>
                <p><strong>Username:</strong> ${username}</p>
                <p><strong>Role:</strong> <span style="color: ${roleColor}">${roleText}</span></p>
                <p><strong>Bergabung:</strong> ${createdDate}</p>
            `;

            // Set delete URL
            const deleteBtn = document.getElementById('deleteConfirmBtn');
            deleteBtn.href = `user_delete.php?id=${id}`;

            // Show modal
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            console.log('Closing delete modal');
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Role Change Modal Functions
        function showRoleModal(id, username, currentRole, newRole) {
            console.log('Opening role modal for user:', username);

            const currentRoleText = currentRole === 'admin' ? 'Admin' : 'Customer';
            const newRoleText = newRole === 'admin' ? 'Admin' : 'Customer';
            const currentRoleColor = currentRole === 'admin' ? '#3b82f6' : '#10b981';
            const newRoleColor = newRole === 'admin' ? '#3b82f6' : '#10b981';

            // Set modal title and message
            document.getElementById('roleModalTitle').textContent = `Ubah Role ke ${newRoleText}`;
            document.getElementById('roleModalMessage').innerHTML = `
                Anda akan mengubah role user dari <strong style="color: ${currentRoleColor}">${currentRoleText}</strong> 
                menjadi <strong style="color: ${newRoleColor}">${newRoleText}</strong>.
            `;

            // Set user details
            const detailsDiv = document.getElementById('roleUserDetails');
            detailsDiv.innerHTML = `
                <p><strong>Username:</strong> ${username}</p>
                <p><strong>Role Saat Ini:</strong> <span style="color: ${currentRoleColor}">${currentRoleText}</span></p>
                <p><strong>Role Baru:</strong> <span style="color: ${newRoleColor}">${newRoleText}</span></p>
            `;

            // Set warning message
            const warningDiv = document.getElementById('roleWarning');
            if (newRole === 'admin') {
                warningDiv.innerHTML = `
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><strong>PERINGATAN:</strong> User akan memiliki akses penuh ke panel admin!</span>
                `;
                document.getElementById('roleSuccess').style.display = 'none';
            } else {
                warningDiv.innerHTML = `
                    <i class="fas fa-exclamation-circle"></i>
                    <span><strong>PERINGATAN:</strong> User akan kehilangan akses ke panel admin!</span>
                `;
                document.getElementById('roleSuccess').style.display = 'flex';
            }

            // Set role change URL
            const roleBtn = document.getElementById('roleConfirmBtn');
            roleBtn.href = `user_change_role.php?id=${id}&role=${newRole}`;

            // Show modal
            document.getElementById('roleModal').style.display = 'flex';
        }

        function closeRoleModal() {
            console.log('Closing role modal');
            document.getElementById('roleModal').style.display = 'none';
        }

        // Reset Password Modal Functions
        function showResetModal(id, username, role, email) {
            console.log('Opening reset modal for user:', username);

            const roleText = role === 'admin' ? 'Admin' : 'Customer';
            const roleColor = role === 'admin' ? '#3b82f6' : '#10b981';
            const defaultPassword = 'password123';

            // Set user details
            const detailsDiv = document.getElementById('resetUserDetails');
            detailsDiv.innerHTML = `
                <p><strong>Username:</strong> ${username}</p>
                <p><strong>Role:</strong> <span style="color: ${roleColor}">${roleText}</span></p>
                ${email !== 'N/A' ? `<p><strong>Email:</strong> ${email}</p>` : ''}
            `;

            // Set new password display
            const passwordDiv = document.getElementById('newPasswordDisplay');
            passwordDiv.textContent = defaultPassword;

            // Set reset URL
            const resetBtn = document.getElementById('resetConfirmBtn');
            resetBtn.href = `user_reset_password.php?id=${id}`;

            // Show modal
            document.getElementById('resetModal').style.display = 'flex';
        }

        function closeResetModal() {
            console.log('Closing reset modal');
            document.getElementById('resetModal').style.display = 'none';
        }

        // Close modals when clicking outside
        document.addEventListener('DOMContentLoaded', function () {
            const modals = ['logoutModal', 'deleteModal', 'roleModal', 'resetModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.addEventListener('click', function (e) {
                        if (e.target === this) {
                            if (modalId === 'logoutModal') closeLogoutModal();
                            if (modalId === 'deleteModal') closeDeleteModal();
                            if (modalId === 'roleModal') closeRoleModal();
                            if (modalId === 'resetModal') closeResetModal();
                        }
                    });
                }
            });
        });

        // Close modals with ESC key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeLogoutModal();
                closeDeleteModal();
                closeRoleModal();
                closeResetModal();
            }
        });

        // Disable buttons after click to prevent double click
        document.addEventListener('DOMContentLoaded', function () {
            const deleteBtn = document.getElementById('deleteConfirmBtn');
            const roleBtn = document.getElementById('roleConfirmBtn');
            const resetBtn = document.getElementById('resetConfirmBtn');

            if (deleteBtn) {
                deleteBtn.addEventListener('click', function (e) {
                    console.log('Delete confirmed');
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
                    this.style.opacity = '0.7';
                    this.style.cursor = 'wait';
                });
            }

            if (roleBtn) {
                roleBtn.addEventListener('click', function (e) {
                    console.log('Role change confirmed');
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengubah...';
                    this.style.opacity = '0.7';
                    this.style.cursor = 'wait';
                });
            }

            if (resetBtn) {
                resetBtn.addEventListener('click', function (e) {
                    console.log('Reset password confirmed');
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mereset...';
                    this.style.opacity = '0.7';
                    this.style.cursor = 'wait';
                });
            }
        });

        // Optional: Copy password to clipboard
        function copyPassword() {
            const password = document.getElementById('newPasswordDisplay').textContent;
            navigator.clipboard.writeText(password).then(() => {
                alert('Password berhasil disalin ke clipboard!');
            }).catch(err => {
                console.error('Gagal menyalin password:', err);
            });
        }
    </script>

    <script src="../../assets/js/script.js"></script>
</body>

</html>