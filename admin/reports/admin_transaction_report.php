<?php
session_start();
include "../../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/auth_login.php");
    exit();
}

$transactions = mysqli_query($conn, "
    SELECT t.id_transaction, t.transaction_date, t.total_price, u.username
    FROM transactions t
    JOIN users u ON t.id_user = u.id_user
    ORDER BY t.transaction_date DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Transaction Report</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <a href="../../auth/auth_logout.php" class="modal-btn btn-logout">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="navbar">
            <h1><i class="fas fa-file-invoice"></i> Laporan Transaksi</h1>
            <div class="nav-right">
                <span class="nav-user"><i class="fas fa-user-shield"></i>
                    <?= $_SESSION['username'] ?? 'Admin'; ?></span>
                <a href="../admin_dashboard.php" class="btn-login"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="#" class="btn-logout" onclick="showLogoutModal()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div class="epic-layout">
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
                    <a href="../user_management/user_list.php" class="sidebar-item">
                        <i class="fas fa-users"></i>
                        Kelola User
                    </a>
                    <a href="admin_transaction_report.php" class="sidebar-item active">
                        <i class="fas fa-file-invoice"></i>
                        Laporan Transaksi
                    </a>
                    <a href="sales_report.php" class="sidebar-item">
                        <i class="fas fa-chart-line"></i>
                        Analisis Penjualan
                    </a>
                </div>
            </div>

            <div style="flex: 1; margin-left: 20px;">
                <div class="button-container">
                    <h2><i class="fas fa-chart-bar"></i> Daftar Semua Transaksi</h2>
                    <a href="generate_pdf_transaction.php" class="button" target="_blank"
                        style="background-color: #d9534f; margin-right: 10px;">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID Transaksi</th>
                            <th>Username</th>
                            <th>Tanggal Transaksi</th>
                            <th>Total Harga</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($transactions) > 0): ?>
                            <?php while ($t = mysqli_fetch_assoc($transactions)): ?>
                                <tr>
                                    <td data-label="ID">#<?= $t['id_transaction']; ?></td>
                                    <td data-label="Username">
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($t['username']); ?>
                                    </td>
                                    <td data-label="Tanggal">
                                        <i class="far fa-calendar"></i>
                                        <?= date('d/m/Y H:i', strtotime($t['transaction_date'])); ?>
                                    </td>
                                    <td data-label="Total" style="color: #66c0f4; font-weight: bold;">
                                        <i class="fas fa-tag"></i> Rp <?= number_format($t['total_price'], 0, ',', '.'); ?>
                                    </td>
                                    <td data-label="Aksi">
                                        <a href="transaction_detail.php?id=<?= $t['id_transaction']; ?>" class="btn">
                                            <i class="fas fa-info-circle"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: #8f98a0;">
                                    <i class="fas fa-inbox"
                                        style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                    Belum ada transaksi
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php
                $totalQuery = mysqli_query($conn, "SELECT COUNT(*) as total_transactions, SUM(total_price) as total_revenue FROM transactions");
                $stats = mysqli_fetch_assoc($totalQuery);
                ?>
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 30px;">
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="fas fa-shopping-cart" style="color: #66c0f4;"></i>
                        </div>
                        <h3>Total Transaksi</h3>
                        <p><?= $stats['total_transactions'] ?? 0; ?> Transaksi</p>
                    </div>
                    <div class="dashboard-card trx-card">
                        <div class="card-icon">
                            <i class="fas fa-money-bill-wave" style="color: #fff;"></i>
                        </div>
                        <h3>Total Pendapatan</h3>
                        <p>Rp <?= number_format($stats['total_revenue'] ?? 0, 0, ',', '.'); ?></p>
                    </div>
                </div>

                <div style="margin-top: 30px; text-align: center;">
                    <a href="../admin_dashboard.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>&copy; <?= date('Y'); ?> Steam-like Store. All rights reserved.</p>
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

    <script src="../../assets/js/script.js"></script>
</body>

</html>