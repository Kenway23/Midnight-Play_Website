<?php
session_start();
include "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/auth_login.php");
    exit();
}

/* Data ringkasan */
$totalUser = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM users")
)['total'];

$totalGame = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM games")
)['total'];

$totalTrx = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM transactions")
)['total'];

/* Total pendapatan */
$totalRevenue = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT SUM(total_price) as total FROM transactions")
)['total'];
$totalRevenue = $totalRevenue ?: 0;

/* Game terbaru (5 game) */
$latestGames = mysqli_query($conn, "SELECT * FROM games ORDER BY id_game DESC LIMIT 5");

/* Transaksi terbaru (5 transaksi) */
$latestTransactions = mysqli_query($conn, "
    SELECT t.*, u.username 
    FROM transactions t 
    JOIN users u ON t.id_user = u.id_user 
    ORDER BY t.transaction_date DESC LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Midnight Play</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Modal Logout */
        .logout-modal {
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
            width: 400px;
            border: 1px solid #2a475e;
        }

        .modal-icon {
            color: #ff6b6b;
            font-size: 40px;
            margin-bottom: 15px;
        }

        .modal-title {
            color: #ff6b6b;
            margin-bottom: 10px;
        }

        .modal-message {
            color: #c7d5e0;
            margin-bottom: 20px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .modal-btn {
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
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
                <a href="../auth/auth_logout.php" class="modal-btn btn-logout">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Navbar -->
        <div class="navbar">
            <h1><i class="fas fa-crown"></i> Midnight Play Admin</h1>
            <div class="nav-right">
                <span class="nav-user"><i class="fas fa-user-shield"></i>
                    <?= $_SESSION['username'] ?? 'Admin'; ?></span>
                <a href="#" class="btn-logout" onclick="showLogoutModal()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Layout -->
        <div class="epic-layout">
            <!-- Sidebar -->
            <div class="epic-sidebar">
                <div class="sidebar-section">
                    <a href="admin_dashboard.php" class="sidebar-item active">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="game_management/admin_game_list.php" class="sidebar-item">
                        <i class="fas fa-gamepad"></i>
                        Kelola Game
                    </a>
                    <a href="user_management/user_list.php" class="sidebar-item">
                        <i class="fas fa-users"></i>
                        Kelola User
                    </a>
                    <a href="reports/admin_transaction_report.php" class="sidebar-item">
                        <i class="fas fa-file-invoice"></i>
                        Laporan Transaksi
                    </a>
                    <a href="reports/sales_report.php" class="sidebar-item">
                        <i class="fas fa-chart-line"></i>
                        Analisis Penjualan
                    </a>
                </div>
            </div>

            <!-- Content Area -->
            <div style="flex: 1; margin-left: 20px; padding: 10px;">
                <!-- Welcome Section -->
                <div
                    style="background: linear-gradient(135deg, #1b2838, #171a21); padding: 20px; border-radius: 10px; margin-bottom: 25px;">
                    <h2><i class="fas fa-gem"></i> Selamat Datang, <?= $_SESSION['username'] ?? 'Admin'; ?>!</h2>
                    <p style="color: #8f98a0; margin-top: 10px;">Halaman admin untuk mengelola seluruh sistem
                        Midnight Play.</p>
                </div>

                <!-- Statistik Cards -->
                <h3><i class="fas fa-chart-bar"></i> Ringkasan Sistem</h3>
                <div class="game-grid"
                    style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin: 20px 0 30px 0;">
                    <!-- Total Users -->
                    <div class="dashboard-card users-card">
                        <div class="card-icon">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                        <h3>Total Users</h3>
                        <p><?= number_format($totalUser, 0, ',', '.'); ?> User</p>
                        <a href="user_management/user_list.php"
                            style="color: #fff; font-size: 14px; margin-top: 10px; display: inline-block;">
                            <i class="fas fa-arrow-right"></i> Lihat Detail
                        </a>
                    </div>

                    <!-- Total Games -->
                    <div class="dashboard-card games-card">
                        <div class="card-icon">
                            <i class="fas fa-gamepad fa-2x"></i>
                        </div>
                        <h3>Total Games</h3>
                        <p><?= number_format($totalGame, 0, ',', '.'); ?> Game</p>
                        <a href="game_management/admin_game_list.php"
                            style="color: #fff; font-size: 14px; margin-top: 10px; display: inline-block;">
                            <i class="fas fa-arrow-right"></i> Kelola Game
                        </a>
                    </div>

                    <!-- Total Transactions -->
                    <div class="dashboard-card trx-card">
                        <div class="card-icon">
                            <i class="fas fa-shopping-cart fa-2x"></i>
                        </div>
                        <h3>Total Transaksi</h3>
                        <p><?= number_format($totalTrx, 0, ',', '.'); ?> Transaksi</p>
                        <a href="reports/admin_transaction_report.php"
                            style="color: #fff; font-size: 14px; margin-top: 10px; display: inline-block;">
                            <i class="fas fa-arrow-right"></i> Lihat Laporan
                        </a>
                    </div>

                    <!-- Total Revenue -->
                    <div class="dashboard-card" style="background: linear-gradient(135deg, #7c3aed, #8b5cf6);">
                        <div class="card-icon">
                            <i class="fas fa-money-bill-wave fa-2x"></i>
                        </div>
                        <h3>Total Pendapatan</h3>
                        <p>Rp <?= number_format($totalRevenue, 0, ',', '.'); ?></p>
                        <a href="reports/sales_report.php"
                            style="color: #fff; font-size: 14px; margin-top: 10px; display: inline-block;">
                            <i class="fas fa-arrow-right"></i> Analisis
                        </a>
                    </div>
                </div>

                <!-- Quick Actions -->
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0 30px 0;">
                    <a href="game_management/admin_game_form.php" class="btn"
                        style="text-align: center; padding: 15px;">
                        <i class="fas fa-plus-circle fa-lg"></i><br>
                        <span style="margin-top: 8px; display: inline-block;">Tambah Game</span>
                    </a>
                    <a href="reports/admin_transaction_report.php" class="btn"
                        style="background: linear-gradient(135deg, #059669, #10b981); text-align: center; padding: 15px;">
                        <i class="fas fa-file-export fa-lg"></i><br>
                        <span style="margin-top: 8px; display: inline-block;">Export Laporan</span>
                    </a>
                </div>

                <!-- Recent Data -->
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 25px; margin-top: 30px;">
                    <!-- Game Terbaru -->
                    <div style="background: #171a21; border-radius: 10px; padding: 20px;">
                        <h3 style="color: #66c0f4; margin-bottom: 15px; display: flex; align-items: center;">
                            <i class="fas fa-star" style="margin-right: 10px;"></i> Game Terbaru
                        </h3>
                        <table style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Nama Game</th>
                                    <th>Harga</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($latestGames) > 0): ?>
                                    <?php while ($game = mysqli_fetch_assoc($latestGames)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($game['title']); ?></td>
                                            <td style="color: #66c0f4;">Rp <?= number_format($game['price'], 0, ',', '.'); ?>
                                            </td>
                                            <td>
                                                <a href="game_management/admin_game_edit.php?id=<?= $game['id_game']; ?>"
                                                    class="btn-edit" style="padding: 10px 15px; font-size: 12px;">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; padding: 15px; color: #8f98a0;">
                                            Tidak ada game
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Transaksi Terbaru -->
                    <div style="background: #171a21; border-radius: 10px; padding: 20px;">
                        <h3 style="color: #66c0f4; margin-bottom: 15px; display: flex; align-items: center;">
                            <i class="fas fa-history" style="margin-right: 10px;"></i> Transaksi Terbaru
                        </h3>
                        <table style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Total</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($latestTransactions) > 0): ?>
                                    <?php while ($trx = mysqli_fetch_assoc($latestTransactions)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($trx['username']); ?></td>
                                            <td style="color: #10b981;">Rp
                                                <?= number_format($trx['total_price'], 0, ',', '.'); ?>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($trx['transaction_date'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; padding: 15px; color: #8f98a0;">
                                            Tidak ada transaksi
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer -->
                <div class="footer"
                    style="margin-top: 50px; padding: 20px; text-align: center; border-top: 1px solid #2a2f3a;">
                    <p>Midnight Play Admin Panel &copy; <?= date("Y"); ?> | Sistem Version 1.0.0</p>
                    <p style="font-size: 12px; color: #8f98a0; margin-top: 5px;">
                        <i class="fas fa-server"></i>
                        <?= mysqli_num_rows(mysqli_query($conn, "SHOW TABLES")); ?> tabel aktif |
                        <i class="fas fa-database"></i>
                        <?= round(mysqli_fetch_assoc(mysqli_query($conn, "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size FROM information_schema.tables WHERE table_schema = DATABASE()"))['size'] ?? 0, 2); ?>
                        MB
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showLogoutModal() {
            document.getElementById('logoutModal').style.display = 'flex';
            return false;
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        // Close modal ketika klik di luar modal
        document.getElementById('logoutModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeLogoutModal();
            }
        });

        // Close modal dengan ESC key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeLogoutModal();
            }
        });
    </script>

    <script src="../assets/js/script.js"></script>
</body>

</html>