<?php
session_start();
include "../../config/database.php";

/* Proteksi admin */
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/auth_login.php");
    exit();
}

// Default filter: bulan ini
$current_month = date('m');
$current_year = date('Y');
$filter_month = $_GET['month'] ?? $current_month;
$filter_year = $_GET['year'] ?? $current_year;

// Validasi filter
if (!is_numeric($filter_month) || $filter_month < 1 || $filter_month > 12) {
    $filter_month = $current_month;
}
if (!is_numeric($filter_year) || $filter_year < 2020 || $filter_year > date('Y')) {
    $filter_year = $current_year;
}

// Hitung total pendapatan bulan ini
$revenue_query = mysqli_query($conn, "
    SELECT COALESCE(SUM(total_price), 0) as total_revenue 
    FROM transactions 
    WHERE MONTH(transaction_date) = '$filter_month' 
    AND YEAR(transaction_date) = '$filter_year'
");
$total_revenue = mysqli_fetch_assoc($revenue_query)['total_revenue'];

// Hitung total transaksi bulan ini
$transaction_query = mysqli_query($conn, "
    SELECT COUNT(*) as total_transactions 
    FROM transactions 
    WHERE MONTH(transaction_date) = '$filter_month' 
    AND YEAR(transaction_date) = '$filter_year'
");
$total_transactions = mysqli_fetch_assoc($transaction_query)['total_transactions'];

// Hitung rata-rata transaksi
$avg_transaction = $total_transactions > 0 ? $total_revenue / $total_transactions : 0;

// Data untuk chart (pendapatan per hari)
$daily_revenue_query = mysqli_query($conn, "
    SELECT 
        DAY(transaction_date) as day,
        SUM(total_price) as daily_revenue,
        COUNT(*) as transaction_count
    FROM transactions 
    WHERE MONTH(transaction_date) = '$filter_month' 
    AND YEAR(transaction_date) = '$filter_year'
    GROUP BY DAY(transaction_date)
    ORDER BY day
");

$daily_data = [];
$max_revenue = 0;
while ($row = mysqli_fetch_assoc($daily_revenue_query)) {
    $daily_data[$row['day']] = [
        'revenue' => $row['daily_revenue'],
        'transactions' => $row['transaction_count']
    ];
    if ($row['daily_revenue'] > $max_revenue) {
        $max_revenue = $row['daily_revenue'];
    }
}

// Game terlaris bulan ini
$best_selling_query = mysqli_query($conn, "
    SELECT 
        g.title,
        COUNT(td.id_detail) as total_sold,
        SUM(g.price) as total_revenue
    FROM transaction_details td
    JOIN games g ON td.id_game = g.id_game
    JOIN transactions t ON td.id_transaction = t.id_transaction
    WHERE MONTH(t.transaction_date) = '$filter_month' 
    AND YEAR(t.transaction_date) = '$filter_year'
    GROUP BY td.id_game
    ORDER BY total_sold DESC
    LIMIT 10
");

// Data tahun untuk dropdown
$years_query = mysqli_query($conn, "
    SELECT DISTINCT YEAR(transaction_date) as year 
    FROM transactions 
    ORDER BY year DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Penjualan | Midnight Play Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <!-- Navbar -->
        <div class="navbar">
            <h1><i class="fas fa-chart-line"></i> Analisis Penjualan</h1>
            <div class="nav-right">
                <span class="nav-user"><i class="fas fa-user-shield"></i>
                    <?= $_SESSION['username']; ?>
                </span>
                <a href="../admin_dashboard.php" class="btn-login"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
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
                    <a href="admin_transaction_report.php" class="sidebar-item">
                        <i class="fas fa-file-invoice"></i>
                        Laporan Transaksi
                    </a>
                    <a href="sales_report.php" class="sidebar-item active">
                        <i class="fas fa-chart-line"></i>
                        Analisis Penjualan
                    </a>
                </div>
            </div>

            <!-- Content Area -->
            <div style="flex: 1; margin-left: 20px; padding: 10px;">
                <!-- Header & Filter -->
                <div class="button-container">
                    <div>
                        <h2><i class="fas fa-chart-bar"></i> Analisis Penjualan</h2>
                        <p style="color: #8f98a0; margin-top: 5px;">
                            <?= date('F Y', mktime(0, 0, 0, $filter_month, 1, $filter_year)); ?>
                        </p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="sales_pdf.php?month=<?= $filter_month; ?>&year=<?= $filter_year; ?>" class="button"
                            style="background: #ef4444;" target="_blank">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                    </div>
                </div>

                <!-- Filter Form -->
                <div style="background: #171a21; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                    <h3 style="color: #66c0f4; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-filter"></i> Filter Laporan
                    </h3>

                    <form method="GET" action=""
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                        <div>
                            <label style="color: #c7d5e0; font-size: 14px; margin-bottom: 8px; display: block;">
                                <i class="fas fa-calendar"></i> Bulan
                            </label>
                            <select name="month"
                                style="width: 100%; padding: 10px; background: #0b1320; border: 1px solid #2a2f3a; border-radius: 4px; color: #fff;">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i; ?>" <?= $i == $filter_month ? 'selected' : ''; ?>>
                                        <?= date('F', mktime(0, 0, 0, $i, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div>
                            <label style="color: #c7d5e0; font-size: 14px; margin-bottom: 8px; display: block;">
                                <i class="fas fa-calendar-alt"></i> Tahun
                            </label>
                            <select name="year"
                                style="width: 100%; padding: 10px; background: #0b1320; border: 1px solid #2a2f3a; border-radius: 4px; color: #fff;">
                                <?php while ($year_row = mysqli_fetch_assoc($years_query)): ?>
                                    <option value="<?= $year_row['year']; ?>" <?= $year_row['year'] == $filter_year ? 'selected' : ''; ?>>
                                        <?= $year_row['year']; ?>
                                    </option>
                                <?php endwhile; ?>
                                <?php mysqli_data_seek($years_query, 0); // Reset pointer ?>
                            </select>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn" style="flex: 1;">
                                <i class="fas fa-search"></i> Terapkan Filter
                            </button>
                            <a href="sales_report.php" class="btn" style="padding: 10px 15px; text-decoration: none;">
                                <i class="fas fa-redo"></i>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- PDF Export Info -->
                <div
                    style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-file-pdf" style="color: #ef4444; font-size: 20px;"></i>
                        <div>
                            <h4 style="color: #ef4444; margin: 0 0 5px 0;">Export Laporan Analisis</h4>
                            <p style="color: #c7d5e0; font-size: 13px; margin: 0;">
                                Klik tombol <strong>"Export PDF"</strong> untuk mendownload laporan analisis penjualan
                                dalam format PDF.
                                Laporan akan berisi statistik lengkap dan analisis untuk periode
                                <strong><?= date('F Y', mktime(0, 0, 0, $filter_month, 1, $filter_year)); ?></strong>.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0 30px 0;">
                    <!-- Total Revenue -->
                    <div class="dashboard-card" style="background: linear-gradient(135deg, #065f46, #10b981);">
                        <div class="card-icon">
                            <i class="fas fa-money-bill-wave fa-lg"></i>
                        </div>
                        <h3>Total Pendapatan</h3>
                        <p style="font-size: 24px; font-weight: bold; margin-top: 10px;">
                            Rp
                            <?= number_format($total_revenue, 0, ',', '.'); ?>
                        </p>
                    </div>

                    <!-- Total Transactions -->
                    <div class="dashboard-card" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6);">
                        <div class="card-icon">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                        </div>
                        <h3>Total Transaksi</h3>
                        <p style="font-size: 24px; font-weight: bold; margin-top: 10px;">
                            <?= number_format($total_transactions, 0, ',', '.'); ?>
                        </p>
                    </div>

                    <!-- Average Transaction -->
                    <div class="dashboard-card" style="background: linear-gradient(135deg, #7c3aed, #8b5cf6);">
                        <div class="card-icon">
                            <i class="fas fa-chart-pie fa-lg"></i>
                        </div>
                        <h3>Rata-rata Transaksi</h3>
                        <p style="font-size: 24px; font-weight: bold; margin-top: 10px;">
                            Rp
                            <?= number_format($avg_transaction, 0, ',', '.'); ?>
                        </p>
                    </div>

                    <!-- Daily Average -->
                    <div class="dashboard-card trx-card">
                        <div class="card-icon">
                            <i class="fas fa-calendar-day fa-lg"></i>
                        </div>
                        <h3>Rata-rata Harian</h3>
                        <p style="font-size: 24px; font-weight: bold; margin-top: 10px;">
                            Rp
                            <?= number_format($total_revenue / date('t', mktime(0, 0, 0, $filter_month, 1, $filter_year)), 0, ',', '.'); ?>
                        </p>
                    </div>
                </div>

                <!-- Chart Section -->
                <div style="background: #171a21; border-radius: 10px; padding: 25px; margin-bottom: 30px;">
                    <h3 style="color: #66c0f4; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-chart-bar"></i> Grafik Pendapatan Harian
                    </h3>

                    <div style="height: 400px; position: relative;">
                        <canvas id="revenueChart"></canvas>
                    </div>

                    <div
                        style="margin-top: 20px; display: flex; justify-content: center; gap: 20px; color: #8f98a0; font-size: 13px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 12px; height: 12px; background: rgba(102, 192, 244, 0.8);"></div>
                            <span>Pendapatan (Rp)</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 12px; height: 12px; background: rgba(16, 185, 129, 0.8);"></div>
                            <span>Jumlah Transaksi</span>
                        </div>
                    </div>
                </div>

                <!-- Two Columns Layout -->
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 25px; margin-top: 20px;">
                    <!-- Best Selling Games -->
                    <div style="background: #171a21; border-radius: 10px; padding: 20px;">
                        <h3 style="color: #66c0f4; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-trophy"></i> Game Terlaris
                        </h3>

                        <?php if (mysqli_num_rows($best_selling_query) > 0): ?>
                            <table style="width: 100%; font-size: 14px;">
                                <thead>
                                    <tr>
                                        <th style="padding: 10px; text-align: left; border-bottom: 1px solid #2a2f3a;">Game
                                        </th>
                                        <th style="padding: 10px; text-align: center; border-bottom: 1px solid #2a2f3a;">
                                            Terjual</th>
                                        <th style="padding: 10px; text-align: right; border-bottom: 1px solid #2a2f3a;">
                                            Pendapatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $counter = 0;
                                    mysqli_data_seek($best_selling_query, 0); // Reset pointer
                                    while ($game = mysqli_fetch_assoc($best_selling_query)):
                                        $counter++;
                                        ?>
                                        <tr>
                                            <td style="padding: 10px; border-bottom: 1px solid #2a2f3a;">
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div
                                                        style="width: 24px; height: 24px; background: linear-gradient(135deg, #1f80ff, #66c0f4); border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                        <span style="color: #fff; font-size: 12px; font-weight: bold;">
                                                            <?= $counter; ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <div style="font-weight: bold;">
                                                            <?= htmlspecialchars($game['title']); ?>
                                                        </div>
                                                        <div style="color: #8f98a0; font-size: 12px;">
                                                            <?= htmlspecialchars($game['title']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="padding: 10px; text-align: center; border-bottom: 1px solid #2a2f3a;">
                                                <span
                                                    style="background: rgba(59, 130, 246, 0.2); color: #3b82f6; padding: 4px 8px; border-radius: 12px; font-weight: bold;">
                                                    <?= $game['total_sold']; ?>
                                                </span>
                                            </td>
                                            <td
                                                style="padding: 10px; text-align: right; border-bottom: 1px solid #2a2f3a; color: #10b981; font-weight: bold;">
                                                Rp
                                                <?= number_format($game['total_revenue'], 0, ',', '.'); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: #8f98a0;">
                                <i class="fas fa-chart-bar"
                                    style="font-size: 48px; margin-bottom: 15px; display: block; color: #2a2f3a;"></i>
                                Tidak ada data penjualan untuk periode ini
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Month Comparison -->
                    <div style="background: #171a21; border-radius: 10px; padding: 20px;">
                        <h3 style="color: #66c0f4; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-balance-scale"></i> Perbandingan Bulanan
                        </h3>

                        <?php
                        // Data bulan sebelumnya
                        $prev_month = $filter_month - 1;
                        $prev_year = $filter_year;
                        if ($prev_month < 1) {
                            $prev_month = 12;
                            $prev_year--;
                        }

                        // Hitung pendapatan bulan sebelumnya
                        $prev_revenue_query = mysqli_query($conn, "
                            SELECT COALESCE(SUM(total_price), 0) as total_revenue 
                            FROM transactions 
                            WHERE MONTH(transaction_date) = '$prev_month' 
                            AND YEAR(transaction_date) = '$prev_year'
                        ");
                        $prev_revenue = mysqli_fetch_assoc($prev_revenue_query)['total_revenue'];

                        // Hitung persentase perubahan
                        $percentage_change = 0;
                        if ($prev_revenue > 0) {
                            $percentage_change = (($total_revenue - $prev_revenue) / $prev_revenue) * 100;
                        } elseif ($total_revenue > 0) {
                            $percentage_change = 100; // Dari 0 ke ada pendapatan
                        }
                        ?>

                        <div style="text-align: center; padding: 20px;">
                            <div style="font-size: 14px; color: #8f98a0; margin-bottom: 10px;">
                                <i class="fas fa-calendar"></i>
                                <?= date('F Y', mktime(0, 0, 0, $filter_month, 1, $filter_year)); ?>
                            </div>

                            <div style="font-size: 32px; font-weight: bold; color: #66c0f4; margin: 15px 0;">
                                Rp
                                <?= number_format($total_revenue, 0, ',', '.'); ?>
                            </div>

                            <div
                                style="display: flex; align-items: center; justify-content: center; gap: 10px; margin: 20px 0;">
                                <div style="text-align: right;">
                                    <div style="font-size: 12px; color: #8f98a0;">Bulan Sebelumnya</div>
                                    <div style="font-size: 16px; color: #c7d5e0;">
                                        Rp
                                        <?= number_format($prev_revenue, 0, ',', '.'); ?>
                                    </div>
                                </div>

                                <div style="font-size: 24px; color: #8f98a0;">
                                    <i class="fas fa-arrow-right"></i>
                                </div>

                                <div style="text-align: left;">
                                    <div style="font-size: 12px; color: #8f98a0;">Perubahan</div>
                                    <div
                                        style="font-size: 16px; font-weight: bold; color: <?= $percentage_change >= 0 ? '#10b981' : '#ef4444'; ?>;">
                                        <?= $percentage_change >= 0 ? '+' : ''; ?>
                                        <?= number_format($percentage_change, 1); ?>%
                                    </div>
                                </div>
                            </div>

                            <div
                                style="height: 10px; background: #2a2f3a; border-radius: 5px; overflow: hidden; margin: 20px 0;">
                                <?php
                                $max_value = max($total_revenue, $prev_revenue);
                                $current_width = $max_value > 0 ? ($total_revenue / $max_value) * 100 : 0;
                                $prev_width = $max_value > 0 ? ($prev_revenue / $max_value) * 100 : 0;
                                ?>
                                <div
                                    style="height: 100%; width: <?= $prev_width; ?>%; background: #8f98a0; float: left;">
                                </div>
                                <div
                                    style="height: 100%; width: <?= $current_width; ?>%; background: #66c0f4; float: left;">
                                </div>
                            </div>

                            <div
                                style="display: flex; justify-content: space-between; font-size: 12px; color: #8f98a0;">
                                <span>
                                    <?= date('M', mktime(0, 0, 0, $prev_month, 1)); ?>
                                </span>
                                <span>
                                    <?= date('M', mktime(0, 0, 0, $filter_month, 1)); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div style="margin-top: 25px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            <div
                                style="background: rgba(102, 192, 244, 0.1); padding: 12px; border-radius: 6px; text-align: center;">
                                <div style="color: #66c0f4; font-size: 18px; font-weight: bold;">
                                    <?= $total_transactions; ?>
                                </div>
                                <div style="color: #8f98a0; font-size: 12px;">
                                    Transaksi
                                </div>
                            </div>

                            <div
                                style="background: rgba(16, 185, 129, 0.1); padding: 12px; border-radius: 6px; text-align: center;">
                                <div style="color: #10b981; font-size: 18px; font-weight: bold;">
                                    <?= $max_revenue > 0 ? number_format($max_revenue, 0, ',', '.') : 0; ?>
                                </div>
                                <div style="color: #8f98a0; font-size: 12px;">
                                    Tertinggi
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export Buttons -->
                <div style="background: #1b2838; border-radius: 8px; padding: 20px; margin-top: 30px;">
                    <h4 style="color: #66c0f4; margin-bottom: 15px;">
                        <i class="fas fa-download"></i> Download Laporan
                    </h4>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <a href="sales_pdf.php?month=<?= $filter_month; ?>&year=<?= $filter_year; ?>" class="btn"
                            style="background: #ef4444; text-align: center; padding: 15px; text-decoration: none;"
                            target="_blank">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>

                        <a href="../admin_dashboard.php" class="btn"
                            style="text-align: center; padding: 15px; text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>

                    <p style="color: #8f98a0; font-size: 13px; margin-top: 15px; text-align: center;">
                        <i class="fas fa-info-circle"></i>
                        PDF berisi laporan lengkap analisis penjualan periode
                        <?= date('F Y', mktime(0, 0, 0, $filter_month, 1, $filter_year)); ?>
                    </p>
                </div>

                <!-- Export & Summary -->
                <div style="margin-top: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <a href="../admin_dashboard.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>

                    <div style="color: #8f98a0; font-size: 14px;">
                        <i class="fas fa-info-circle"></i>
                        Periode:
                        <?= date('F Y', mktime(0, 0, 0, $filter_month, 1, $filter_year)); ?>
                        •
                        <?= $total_transactions; ?> transaksi
                        • Rp
                        <?= number_format($total_revenue, 0, ',', '.'); ?> pendapatan
                    </div>
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

        // Prepare data for chart
        const monthDays = <?= date('t', mktime(0, 0, 0, $filter_month, 1, $filter_year)); ?>;
        const dailyRevenue = [];
        const dailyTransactions = [];
        const days = [];

        for (let day = 1; day <= monthDays; day++) {
            days.push(day);
            if (<?= json_encode(isset($daily_data[1])); ?> && <?= json_encode($daily_data); ?>[day]) {
                dailyRevenue.push(<?= json_encode($daily_data); ?>[day].revenue || 0);
                dailyTransactions.push(<?= json_encode($daily_data); ?>[day].transactions || 0);
            } else {
                dailyRevenue.push(0);
                dailyTransactions.push(0);
            }
        }

        // Chart.js Configuration
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: days,
                datasets: [
                    {
                        label: 'Pendapatan (Rp)',
                        data: dailyRevenue,
                        backgroundColor: 'rgba(102, 192, 244, 0.8)',
                        borderColor: 'rgba(102, 192, 244, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Jumlah Transaksi',
                        data: dailyTransactions,
                        type: 'line',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        backgroundColor: 'rgba(16, 185, 129, 0.2)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(42, 47, 58, 0.5)'
                        },
                        ticks: {
                            color: '#8f98a0'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: {
                            color: 'rgba(42, 47, 58, 0.5)'
                        },
                        ticks: {
                            color: '#8f98a0',
                            callback: function (value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        },
                        title: {
                            display: true,
                            text: 'Pendapatan (Rp)',
                            color: '#8f98a0'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            color: '#8f98a0'
                        },
                        title: {
                            display: true,
                            text: 'Jumlah Transaksi',
                            color: '#8f98a0'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#8f98a0'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(23, 26, 33, 0.9)',
                        titleColor: '#66c0f4',
                        bodyColor: '#c7d5e0',
                        borderColor: '#2a2f3a',
                        borderWidth: 1,
                        callbacks: {
                            label: function (context) {
                                let label = context.dataset.label || '';
                                if (label.includes('Pendapatan')) {
                                    return label + ': Rp ' + context.parsed.y.toLocaleString('id-ID');
                                }
                                return label + ': ' + context.parsed.y;
                            }
                        }
                    }
                }
            }
        });

        // Auto-refresh chart on filter change
        document.querySelector('form').addEventListener('submit', function () {
            document.getElementById('revenueChart').style.opacity = '0.5';
        });
    </script>
</body>

</html>