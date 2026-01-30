<?php
session_start();
include "../config/database.php";

if (!isset($_SESSION['login'])) {
    header("Location: /auth/auth_login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// Ambil data transaksi dengan detail game
$transactions = mysqli_query($conn, "
    SELECT 
        t.*,
        td.id_game,
        g.title as game_title,
        g.genre as game_genre,
        td.price as item_price
    FROM transactions t
    JOIN transaction_details td ON t.id_transaction = td.id_transaction
    JOIN games g ON td.id_game = g.id_game
    WHERE t.id_user = $id_user
    ORDER BY t.transaction_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Purchases | Midnight Play</title>

    <!-- GLOBAL CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user_style.css">

    <!-- ICON -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* Logout Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: linear-gradient(135deg, #171a21, #1b2028);
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            border: 1px solid #2a2f3a;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid #2a2f3a;
        }

        .modal-header h3 {
            color: #66c0f4;
            margin: 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            color: #8f98a0;
            font-size: 24px;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .modal-close:hover {
            background: #2a2f3a;
            color: #c7d5e0;
        }

        .modal-body {
            padding: 30px 20px;
            text-align: center;
        }

        .modal-icon {
            font-size: 48px;
            color: #66c0f4;
            margin-bottom: 20px;
        }

        .modal-icon i {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        .modal-message h4 {
            color: #c7d5e0;
            margin: 0 0 10px 0;
            font-size: 18px;
        }

        .modal-message p {
            color: #8f98a0;
            margin: 0;
            font-size: 14px;
            line-height: 1.5;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            border-top: 1px solid #2a2f3a;
        }

        .modal-footer .btn-secondary {
            flex: 1;
            background: #2a2f3a;
            color: #c7d5e0;
            border: 1px solid #3d4452;
            padding: 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .modal-footer .btn-secondary:hover {
            background: #323844;
            border-color: #66c0f4;
        }

        .modal-footer .btn-primary {
            flex: 1;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .modal-footer .btn-primary:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        /* Purchase specific styles */
        .purchases-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
            background: #171a21;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #2a475e;
        }

        .header-left h1 {
            color: #66c0f4;
            margin-bottom: 5px;
            font-size: 24px;
        }

        .header-left p {
            color: #8f98a0;
            font-size: 14px;
            margin: 0;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .transactions-count {
            background: #2a2f3a;
            color: #c7d5e0;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 14px;
            border: 1px solid #3d4452;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-export {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .btn-export:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .purchases-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #1a1f2e, #2a2f3a);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #3d4452;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #66c0f4;
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
            color: #66c0f4;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #c7d5e0;
            margin: 5px 0;
        }

        .stat-label {
            color: #8f98a0;
            font-size: 14px;
        }

        .search-section {
            background: #171a21;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 1px solid #2a2f3a;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 300px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #8f98a0;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            background: #2a2f3a;
            border: 1px solid #3d4452;
            border-radius: 8px;
            color: #c7d5e0;
            font-size: 14px;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #66c0f4;
            box-shadow: 0 0 0 3px rgba(102, 192, 244, 0.2);
        }

        .filter-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: #2a2f3a;
            color: #c7d5e0;
            padding: 10px 15px;
            border-radius: 6px;
            border: 1px solid #3d4452;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn.active {
            background: #66c0f4;
            color: white;
            border-color: #66c0f4;
        }

        .filter-btn:hover {
            background: #323844;
            border-color: #66c0f4;
        }

        .purchases-table-container {
            background: #171a21;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #2a2f3a;
            margin-top: 20px;
        }

        .purchases-table {
            width: 100%;
            border-collapse: collapse;
        }

        .purchases-table thead {
            background: linear-gradient(135deg, #2a475e, #1b2838);
        }

        .purchases-table th {
            padding: 18px 20px;
            text-align: left;
            color: #66c0f4;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #2a475e;
        }

        .purchases-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #2a2f3a;
            color: #c7d5e0;
            vertical-align: middle;
        }

        .purchases-table tbody tr {
            transition: all 0.3s;
        }

        .purchases-table tbody tr:hover {
            background: rgba(102, 192, 244, 0.05);
        }

        .purchases-table tbody tr:last-child td {
            border-bottom: none;
        }

        .transaction-id {
            color: #66c0f4;
            font-weight: 600;
            font-family: 'Courier New', monospace;
            font-size: 15px;
        }

        .transaction-date {
            color: #8f98a0;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .transaction-game {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .game-icon {
            width: 40px;
            height: 40px;
            background: #2a2f3a;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #66c0f4;
        }

        .game-info {
            flex: 1;
        }

        .game-title {
            color: #c7d5e0;
            font-weight: 500;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .game-genre {
            color: #8f98a0;
            font-size: 12px;
        }

        .transaction-amount {
            color: #10b981;
            font-weight: 700;
            font-size: 16px;
            font-family: 'Courier New', monospace;
        }

        .transaction-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .btn-invoice {
            background: linear-gradient(135deg, #2a475e, #1b2838);
            color: #66c0f4;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: 1px solid #2a475e;
        }

        .btn-invoice:hover {
            background: linear-gradient(135deg, #1b2838, #2a475e);
            border-color: #66c0f4;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 192, 244, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #171a21;
            border-radius: 12px;
            margin: 40px 0;
        }

        .empty-icon {
            font-size: 64px;
            color: #66c0f4;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .btn-explore {
            background: linear-gradient(135deg, #1a9fff, #0066cc);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            transition: all 0.3s;
        }

        .btn-explore:hover {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 102, 204, 0.3);
        }

        .recent-badge {
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 8px;
            display: inline-block;
            vertical-align: middle;
        }

        @media (max-width: 768px) {
            .purchases-header {
                flex-direction: column;
                align-items: stretch;
            }

            .header-right {
                justify-content: space-between;
                width: 100%;
            }

            .search-box {
                min-width: 100%;
            }

            .filter-options {
                justify-content: center;
                width: 100%;
            }

            .purchases-stats {
                grid-template-columns: 1fr;
            }

            .purchases-table-container {
                overflow-x: auto;
            }

            .purchases-table {
                min-width: 800px;
            }
        }

        /* Loading overlay */
        .export-loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            flex-direction: column;
            gap: 20px;
        }

        .export-loading i {
            font-size: 48px;
            color: #66c0f4;
            animation: spin 1s linear infinite;
        }

        .export-loading span {
            color: #c7d5e0;
            font-size: 18px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .purchases-table tbody tr {
            animation: fadeIn 0.5s ease-out;
        }

        .purchases-table tbody tr:nth-child(1) {
            animation-delay: 0.1s;
        }

        .purchases-table tbody tr:nth-child(2) {
            animation-delay: 0.2s;
        }

        .purchases-table tbody tr:nth-child(3) {
            animation-delay: 0.3s;
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <h2>Midnight Play</h2>

        <div class="nav-right">
            <?php if (isset($_SESSION['login'])) { ?>
                <span class="nav-user">
                    <?= htmlspecialchars($_SESSION['username']); ?>
                </span>

                <a href="" class="btn-logout" onclick="showLogoutModal(event)">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            <?php } else { ?>
                <a href="/auth/auth_login.php" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i> Login
                </a>
            <?php } ?>
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

                <a href="invoice_user_list.php" class="sidebar-item active">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Purchases</span>
                </a>

                <a href="/profile/user_profile.php" class="sidebar-item">
                    <i class="fa-solid fa-user"></i>
                    <span>Profile</span>
                </a>
            </div>

            <!-- Quick Stats -->
            <?php
            // Hitung statistik
            $stats_query = mysqli_query($conn, "
                SELECT 
                    COUNT(*) as total_purchases,
                    SUM(total_price) as total_spent,
                    MAX(transaction_date) as last_purchase
                FROM transactions 
                WHERE id_user = $id_user
            ");
            $stats = mysqli_fetch_assoc($stats_query);

            $total_purchases = $stats['total_purchases'] ?? 0;
            $total_spent = $stats['total_spent'] ? number_format($stats['total_spent'], 0, ',', '.') : '0';
            $last_purchase = $stats['last_purchase'] ? date('M d, Y', strtotime($stats['last_purchase'])) : 'Never';
            ?>

            <div style="margin-top: 30px; padding: 20px; background: rgba(102, 192, 244, 0.1); border-radius: 12px;">
                <h4 style="color: #66c0f4; margin-bottom: 15px; font-size: 14px;">
                    <i class="fas fa-chart-line"></i> Purchase Stats
                </h4>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <i class="fas fa-shopping-cart" style="color: #8f98a0; font-size: 12px;"></i>
                    <span style="color: #8f98a0; font-size: 13px; flex: 1;">Total Purchases:</span>
                    <strong style="color: #c7d5e0; font-size: 14px;"><?= $total_purchases; ?></strong>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <i class="fas fa-coins" style="color: #8f98a0; font-size: 12px;"></i>
                    <span style="color: #8f98a0; font-size: 13px; flex: 1;">Total Spent:</span>
                    <strong style="color: #10b981; font-size: 14px;">Rp <?= $total_spent; ?></strong>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-calendar-alt" style="color: #8f98a0; font-size: 12px;"></i>
                    <span style="color: #8f98a0; font-size: 13px; flex: 1;">Last Purchase:</span>
                    <strong style="color: #c7d5e0; font-size: 14px;"><?= $last_purchase; ?></strong>
                </div>
            </div>
        </aside>

        <!-- CONTENT -->
        <main class="epic-content fade-in">
            <!-- Header -->
            <div class="purchases-header">
                <div class="header-left">
                    <h1><i class="fa-solid fa-receipt"></i> Purchase History</h1>
                    <p>View all your past purchases and invoices</p>
                </div>
                <div class="header-right">
                    <?php if (mysqli_num_rows($transactions) > 0): ?>
                        <span class="transactions-count">
                            <i class="fas fa-cube"></i> <?= mysqli_num_rows($transactions); ?> transactions
                        </span>
                        <!-- Tombol Export PDF -->
                        <a href="invoice_user_pdf.php" class="btn-export" onclick="showExportLoading()">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Cards -->
            <?php if ($total_purchases > 0): ?>
                <div class="purchases-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-value"><?= $total_purchases; ?></div>
                        <div class="stat-label">Total Purchases</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-value">Rp <?= $total_spent; ?></div>
                        <div class="stat-label">Total Spent</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-gamepad"></i>
                        </div>
                        <?php
                        $games_query = mysqli_query($conn, "
                            SELECT COUNT(DISTINCT id_game) as total_games 
                            FROM transaction_details td
                            JOIN transactions t ON td.id_transaction = t.id_transaction
                            WHERE t.id_user = $id_user
                        ");
                        $games = mysqli_fetch_assoc($games_query);
                        $total_games = $games['total_games'] ?? 0;
                        ?>
                        <div class="stat-value"><?= $total_games; ?></div>
                        <div class="stat-label">Games Purchased</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-value"><?= $last_purchase; ?></div>
                        <div class="stat-label">Last Purchase</div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-section">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search purchases by game name...">
                    </div>

                    <div class="filter-options">
                        <button class="filter-btn active" data-filter="all">
                            <i class="fas fa-list"></i> All
                        </button>
                        <button class="filter-btn" data-filter="recent">
                            <i class="fas fa-clock"></i> Recent
                        </button>
                        <button class="filter-btn" data-filter="high">
                            <i class="fas fa-sort-amount-down"></i> High
                        </button>
                        <button class="filter-btn" data-filter="low">
                            <i class="fas fa-sort-amount-up"></i> Low
                        </button>
                    </div>
                </div>

                <!-- Purchases Table -->
                <div class="purchases-table-container">
                    <table class="purchases-table">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Date & Time</th>
                                <th>Game</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $counter = 0;
                            mysqli_data_seek($transactions, 0);
                            while ($t = mysqli_fetch_assoc($transactions)):
                                $counter++;
                                $is_recent = (strtotime($t['transaction_date']) > strtotime('-7 days'));
                                $formatted_date = date('M d, Y', strtotime($t['transaction_date']));
                                $formatted_time = date('H:i', strtotime($t['transaction_date']));
                                ?>
                                <tr>
                                    <td>
                                        <span class="transaction-id">#<?= $t['id_transaction']; ?></span>
                                        <?php if ($counter <= 3): ?>
                                            <span class="recent-badge">NEW</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="transaction-date">
                                            <i class="far fa-calendar"></i>
                                            <div>
                                                <div style="color: #c7d5e0;"><?= $formatted_date; ?></div>
                                                <div style="color: #8f98a0; font-size: 12px;"><?= $formatted_time; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="transaction-game">
                                            <div class="game-icon">
                                                <i class="fas fa-gamepad"></i>
                                            </div>
                                            <div class="game-info">
                                                <div class="game-title">
                                                    <?= htmlspecialchars($t['game_title']); ?>
                                                </div>
                                                <div class="game-genre">
                                                    <?= htmlspecialchars($t['game_genre']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="transaction-amount">
                                            Rp <?= number_format($t['total_price'], 0, ',', '.'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="transaction-status status-completed">
                                            <i class="fas fa-check-circle"></i> COMPLETED
                                        </span>
                                    </td>
                                    <td>
                                        <a href="invoice_user_detail.php?id=<?= $t['id_transaction']; ?>" class="btn-invoice">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h2 style="color: #c7d5e0; margin-bottom: 15px;">No Purchases Yet</h2>
                    <p style="color: #8f98a0; max-width: 500px; margin: 0 auto 25px; line-height: 1.6;">
                        You haven't made any purchases yet. Start building your game collection by exploring our amazing
                        games!
                    </p>
                    <a href="/index.php" class="btn-explore">
                        <i class="fas fa-store"></i> Browse Store
                    </a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Logout Modal Functions
        function showLogoutModal(event) {
            event.preventDefault();
            const modal = document.getElementById('logoutModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeLogoutModal() {
            const modal = document.getElementById('logoutModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Re-enable scrolling
        }

        // Close modal when clicking outside
        document.getElementById('logoutModal').addEventListener('click', function (event) {
            if (event.target === this) {
                closeLogoutModal();
            }
        });

        // Keyboard shortcuts for modal
        document.addEventListener('keydown', function (event) {
            const modal = document.getElementById('logoutModal');

            // Escape key closes modal
            if (event.key === 'Escape' && modal.style.display === 'flex') {
                closeLogoutModal();
            }

            // Ctrl+L shortcut to open logout modal
            if ((event.ctrlKey || event.metaKey) && event.key === 'l') {
                event.preventDefault();
                showLogoutModal(event);
            }
        });

        // Auto-focus on cancel button when modal opens
        function focusOnCancelButton() {
            const cancelBtn = document.querySelector('.modal-footer .btn-secondary');
            if (cancelBtn) {
                cancelBtn.focus();
            }
        }
        // Data purchases dari PHP
        const purchasesData = [
            <?php
            mysqli_data_seek($transactions, 0);
            $first = true;
            while ($t = mysqli_fetch_assoc($transactions)):
                if (!$first)
                    echo ',';
                $first = false;
                echo json_encode([
                    'id_transaction' => $t['id_transaction'],
                    'transaction_date' => $t['transaction_date'],
                    'game_title' => $t['game_title'],
                    'game_genre' => $t['game_genre'],
                    'total_price' => $t['total_price']
                ]);
            endwhile;
            ?>
        ];

        // Function untuk show loading
        function showExportLoading() {
            const loadingOverlay = document.createElement('div');
            loadingOverlay.className = 'export-loading';
            loadingOverlay.innerHTML = `
                <i class="fas fa-spinner"></i>
                <span>Generating PDF report...</span>
                <p style="color: #8f98a0; font-size: 14px; text-align: center; max-width: 400px; margin-top: 10px;">
                    Please wait while we prepare your complete purchase history.
                </p>
            `;

            document.body.appendChild(loadingOverlay);

            // Auto remove setelah 30 detik
            setTimeout(() => {
                if (document.body.contains(loadingOverlay)) {
                    loadingOverlay.remove();
                    showNotification('PDF generation is taking longer than expected. Please try again.', 'warning');
                }
            }, 30000);
        }

        // Filter functionality
        function filterPurchases(filterType) {
            const filterBtns = document.querySelectorAll('.filter-btn');
            const tableBody = document.querySelector('.purchases-table tbody');

            // Update active button
            filterBtns.forEach(btn => {
                btn.classList.remove('active');
                if (btn.getAttribute('data-filter') === filterType) {
                    btn.classList.add('active');
                }
            });

            // Apply filter
            let filteredData = [...purchasesData];

            switch (filterType) {
                case 'recent':
                    const thirtyDaysAgo = new Date();
                    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                    filteredData = filteredData.filter(purchase => {
                        const purchaseDate = new Date(purchase.transaction_date);
                        return purchaseDate >= thirtyDaysAgo;
                    });
                    filteredData.sort((a, b) => new Date(b.transaction_date) - new Date(a.transaction_date));
                    break;

                case 'high':
                    filteredData.sort((a, b) => b.total_price - a.total_price);
                    break;

                case 'low':
                    filteredData.sort((a, b) => a.total_price - b.total_price);
                    break;

                case 'all':
                default:
                    filteredData.sort((a, b) => new Date(b.transaction_date) - new Date(a.transaction_date));
                    break;
            }

            // Update table
            updateTable(filteredData);
        }

        // Update table dengan filtered data
        function updateTable(purchases) {
            const tableBody = document.querySelector('.purchases-table tbody');

            if (purchases.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #8f98a0;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 15px;">
                                <i class="fas fa-search" style="font-size: 32px;"></i>
                                <span>No purchases found</span>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            purchases.forEach((purchase, index) => {
                const isRecent = index < 3;
                const formattedDate = new Date(purchase.transaction_date).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
                const formattedTime = new Date(purchase.transaction_date).toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                });

                html += `
                    <tr style="animation: fadeIn 0.5s ease-out ${index * 0.1}s;">
                        <td>
                            <span class="transaction-id">#${purchase.id_transaction}</span>
                            ${isRecent ? '<span class="recent-badge">NEW</span>' : ''}
                        </td>
                        <td>
                            <div class="transaction-date">
                                <i class="far fa-calendar"></i>
                                <div>
                                    <div style="color: #c7d5e0;">${formattedDate}</div>
                                    <div style="color: #8f98a0; font-size: 12px;">${formattedTime}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="transaction-game">
                                <div class="game-icon">
                                    <i class="fas fa-gamepad"></i>
                                </div>
                                <div class="game-info">
                                    <div class="game-title">
                                        ${escapeHtml(purchase.game_title)}
                                    </div>
                                    <div class="game-genre">
                                        ${escapeHtml(purchase.game_genre)}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="transaction-amount">
                                Rp ${formatNumber(purchase.total_price)}
                            </span>
                        </td>
                        <td>
                            <span class="transaction-status status-completed">
                                <i class="fas fa-check-circle"></i> COMPLETED
                            </span>
                        </td>
                        <td>
                            <a href="invoice_user_detail.php?id=${purchase.id_transaction}" class="btn-invoice">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                `;
            });

            tableBody.innerHTML = html;
        }

        // Helper functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        function showNotification(message, type = 'info') {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notification => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            });

            // Create new notification
            const notification = document.createElement('div');
            notification.className = 'notification';

            let icon = 'info-circle';
            let bgColor = '#66c0f4';

            switch (type) {
                case 'success':
                    icon = 'check-circle';
                    bgColor = '#10b981';
                    break;
                case 'error':
                    icon = 'exclamation-circle';
                    bgColor = '#ef4444';
                    break;
                case 'warning':
                    icon = 'exclamation-triangle';
                    bgColor = '#f59e0b';
                    break;
            }

            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${bgColor};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                z-index: 1000;
                animation: slideIn 0.3s ease-out;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
                display: flex;
                align-items: center;
                gap: 10px;
                max-width: 300px;
                cursor: pointer;
            `;

            notification.innerHTML = `
                <i class="fas fa-${icon}"></i>
                <span style="font-size: 14px;">${message}</span>
            `;

            document.body.appendChild(notification);

            // Auto-remove after 3 seconds
            const removeTimeout = setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);

            // Click to dismiss
            notification.addEventListener('click', () => {
                clearTimeout(removeTimeout);
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            });
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateX(100px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            @keyframes slideOut {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(100px);
                }
            }
        `;
        document.head.appendChild(style);

        // Initialize page
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize filter buttons
            const filterButtons = document.querySelectorAll('.filter-btn');
            filterButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    filterPurchases(this.getAttribute('data-filter'));
                });
            });

            // Search functionality
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('input', function (e) {
                const searchTerm = e.target.value.toLowerCase().trim();

                if (searchTerm === '') {
                    const activeBtn = document.querySelector('.filter-btn.active');
                    if (activeBtn) {
                        filterPurchases(activeBtn.getAttribute('data-filter'));
                    }
                    return;
                }

                // Filter berdasarkan search
                let filteredData = [...purchasesData];
                filteredData = filteredData.filter(purchase =>
                    purchase.game_title.toLowerCase().includes(searchTerm) ||
                    purchase.game_genre.toLowerCase().includes(searchTerm) ||
                    purchase.id_transaction.toString().includes(searchTerm)
                );

                // Update table
                updateTable(filteredData);
            });

            // Highlight newest purchases
            const rows = document.querySelectorAll('.purchases-table tbody tr');
            rows.forEach((row, index) => {
                if (index < 3) {
                    row.style.background = 'rgba(102, 192, 244, 0.05)';
                    row.style.borderLeft = '3px solid #66c0f4';
                }
            });
        });
    </script>
    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-sign-out-alt"></i> Logout Confirmation</h3>
                <button class="modal-close" onclick="closeLogoutModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="modal-message">
                    <h4>Are you sure you want to logout?</h4>
                    <p>You'll need to login again to access your account.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeLogoutModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <a href="/auth/auth_logout.php" class="btn-primary">
                    <i class="fas fa-sign-out-alt"></i> Yes, Logout
                </a>
            </div>
        </div>
    </div>
</body>

</html>