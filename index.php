<?php
session_start();
include "config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] != 'user') {
    header("Location:auth/auth_login.php");
    exit();
}

// Ambil semua genre unik dari database untuk filter
$genre_query = mysqli_query($conn, "
    SELECT DISTINCT genre 
    FROM games 
    WHERE status = 'active'
    ORDER BY genre
");

$genres = [];
while ($row = mysqli_fetch_assoc($genre_query)) {
    // Pisahkan genre jika ada multiple genre dalam satu field
    $genre_list = explode(',', $row['genre']);
    foreach ($genre_list as $genre) {
        $genre = trim($genre);
        if (!empty($genre) && !in_array($genre, $genres)) {
            $genres[] = $genre;
        }
    }
}
sort($genres);

// Query untuk NEW RELEASE (game terbaru 8 game)
$new_release_games = mysqli_query($conn, "
    SELECT g.*
    FROM games g
    WHERE g.status = 'active'
    ORDER BY g.created_at DESC
    LIMIT 8
");

// Query untuk TOP SELLERS (berdasarkan jumlah transaksi)
$top_sellers_games = mysqli_query($conn, "
    SELECT g.*, COUNT(td.id_detail) as transaction_count
    FROM games g
    LEFT JOIN transaction_details td ON g.id_game = td.id_game
    WHERE g.status = 'active'
    GROUP BY g.id_game
    ORDER BY transaction_count DESC
    LIMIT 8
");

// Query untuk MOST POPULAR (bisa berdasarkan views atau rating, sementara pakai transaction count juga)
$most_popular_games = mysqli_query($conn, "
    SELECT g.*, COUNT(td.id_detail) as purchase_count
    FROM games g
    LEFT JOIN transaction_details td ON g.id_game = td.id_game
    WHERE g.status = 'active'
    GROUP BY g.id_game
    ORDER BY purchase_count DESC
    LIMIT 8
");

// Check owned games
$user_id = $_SESSION['id_user'];
$owned_query = mysqli_query($conn, "
    SELECT td.id_game 
    FROM transaction_details td
    JOIN transactions t ON td.id_transaction = t.id_transaction
    WHERE t.id_user = '$user_id'
    GROUP BY td.id_game
");
$owned_games = [];
while ($row = mysqli_fetch_assoc($owned_query)) {
    $owned_games[] = $row['id_game'];
}

// Ambil parameter filter jika ada
$selected_genre = $_GET['genre'] ?? '';
$search_query = $_GET['search'] ?? '';
$price_filter = $_GET['price'] ?? '';

// Query untuk semua game dengan filter
$all_games_query = "SELECT * FROM games WHERE status = 'active'";

if (!empty($search_query)) {
    $all_games_query .= " AND (title LIKE '%$search_query%' OR description LIKE '%$search_query%')";
}

if (!empty($selected_genre)) {
    $all_games_query .= " AND genre LIKE '%$selected_genre%'";
}

if (!empty($price_filter)) {
    switch ($price_filter) {
        case 'free':
            $all_games_query .= " AND price = 0";
            break;
        case 'under-50':
            $all_games_query .= " AND price < 50000";
            break;
        case '50-200':
            $all_games_query .= " AND price BETWEEN 50000 AND 200000";
            break;
        case 'over-200':
            $all_games_query .= " AND price > 200000";
            break;
    }
}

$all_games_query .= " ORDER BY created_at DESC";
$all_games = mysqli_query($conn, $all_games_query);
$total_games = mysqli_num_rows($all_games);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Midnight Play | Game Store</title>

    <!-- USER CSS -->
    <link rel="stylesheet" href="assets/css/user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- FAVICON -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">

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

        /* Tambahan styling untuk kategori/genre */
        .genre-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
            padding: 15px;
            background: #171a21;
            border-radius: 8px;
        }

        .genre-filter {
            padding: 8px 16px;
            background: #2a2f3a;
            color: #c7d5e0;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            border: 1px solid transparent;
        }

        .genre-filter:hover {
            background: #323844;
            border-color: #66c0f4;
        }

        .genre-filter.active {
            background: #66c0f4;
            color: #171a21;
            font-weight: bold;
        }

        /* Game grid yang lebih baik */
        .game-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .horizontal-game-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
            overflow-x: auto;
            padding-bottom: 10px;
        }

        .game-card {
            background: #171a21;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #2a2f3a;
        }

        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            border-color: #66c0f4;
        }

        .game-image {
            height: 160px;
            background: #2a2f3a;
            overflow: hidden;
            position: relative;
        }

        .horizontal-game-card .game-image {
            height: 100px;
        }

        .game-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .game-card:hover .game-image img {
            transform: scale(1.05);
        }

        .game-content {
            padding: 15px;
        }

        .horizontal-game-card .game-content {
            padding: 10px;
        }

        .game-title {
            color: #c7d5e0;
            font-size: 16px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .horizontal-game-card .game-title {
            font-size: 14px;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .game-description {
            color: #8f98a0;
            font-size: 13px;
            margin-bottom: 12px;
            line-height: 1.4;
            height: 40px;
            overflow: hidden;
        }

        .game-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }

        .horizontal-game-card .game-meta {
            margin-top: 8px;
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }

        .game-price {
            color: #66c0f4;
            font-weight: bold;
            font-size: 16px;
        }

        .horizontal-game-card .game-price {
            font-size: 14px;
        }

        .owned-badge {
            background: #10b981;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1a9fff, #0066cc);
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .horizontal-game-card .btn-primary {
            padding: 5px 10px;
            font-size: 12px;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
        }

        /* Store header yang lebih baik */
        .store-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .search-container {
            position: relative;
            width: 300px;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #8f98a0;
            z-index: 1;
        }

        .search-input {
            width: 100%;
            padding: 12px 12px 12px 45px;
            background: #2a2f3a;
            border: 1px solid #3d4452;
            border-radius: 8px;
            color: #c7d5e0;
            font-size: 14px;
        }

        .search-input:focus {
            outline: none;
            border-color: #66c0f4;
            box-shadow: 0 0 0 2px rgba(102, 192, 244, 0.2);
        }

        /* Filter section */
        .store-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            background: #171a21;
            padding: 20px;
            border-radius: 8px;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: #c7d5e0;
            font-weight: 500;
            font-size: 14px;
        }

        .filter-group select {
            width: 100%;
            padding: 10px;
            background: #2a2f3a;
            border: 1px solid #3d4452;
            border-radius: 6px;
            color: #c7d5e0;
            font-size: 14px;
        }

        /* Game count */
        .game-count {
            color: #8f98a0;
            font-size: 14px;
            margin: 10px 0 20px 0;
        }

        /* Featured banner */
        .featured-banner {
            background: linear-gradient(135deg, #1a1f2e, #2a2f3a);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 2px solid #66c0f4;
        }

        .featured-content h2 {
            color: #66c0f4;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .featured-content p {
            color: #8f98a0;
            margin-bottom: 15px;
        }

        .btn-featured {
            background: linear-gradient(135deg, #66c0f4, #1a9fff);
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        /* Section headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px 0 15px 0;
        }

        .section-title {
            color: #66c0f4;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-link {
            color: #66c0f4;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .section-link:hover {
            text-decoration: underline;
        }

        /* Badge untuk top sellers */
        .top-seller-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            z-index: 2;
        }

        .new-release-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, #1a9fff, #66c0f4);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            z-index: 2;
        }

        .popular-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #171a21;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            z-index: 2;
        }

        /* Sale badge */
        .sale-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            z-index: 2;
        }

        /* Transaction count */
        .transaction-count {
            color: #8f98a0;
            font-size: 11px;
            margin-top: 3px;
        }

        /* Horizontal scroll untuk game sections */
        .horizontal-scroll-container {
            position: relative;
        }

        .scroll-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .scroll-button.left {
            left: -20px;
        }

        .scroll-button.right {
            right: -20px;
        }

        .scroll-button:hover {
            background: rgba(102, 192, 244, 0.8);
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
                <a href="#" class="btn-logout" onclick="showLogoutModal(event)">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            <?php } else { ?>
                <a href="auth/auth_login.php" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i> Login
                </a>
            <?php } ?>
        </div>
    </nav>

    <!-- MAIN LAYOUT -->
    <div class="epic-layout">
        <?php if (isset($_SESSION['login'])) { ?>
            <!-- SIDEBAR -->
            <aside class="epic-sidebar fade-in">
                <div class="sidebar-section">
                    <a href="index.php" class="sidebar-item active">
                        <i class="fa-solid fa-store"></i>
                        <span>Store</span>
                    </a>
                    <a href="library/library_user_games.php" class="sidebar-item">
                        <i class="fa-solid fa-gamepad"></i>
                        <span>Library</span>
                    </a>
                    <a href="invoices/invoice_user_list.php" class="sidebar-item">
                        <i class="fa-solid fa-receipt"></i>
                        <span>Purchases</span>
                    </a>
                    <a href="profile/user_profile.php" class="sidebar-item">
                        <i class="fa-solid fa-user"></i>
                        <span>Profile</span>
                    </a>
                </div>

                <!-- QUICK STATS -->
                <div style="margin-top: 30px; padding: 20px; background: rgba(102, 192, 244, 0.1); border-radius: 12px;">
                    <h4 style="color: #66c0f4; margin-bottom: 10px; font-size: 14px;">
                        <i class="fas fa-chart-line"></i> Quick Stats
                    </h4>
                    <p style="color: #8f98a0; font-size: 13px;">
                        Games owned: <strong><?= count($owned_games); ?></strong>
                    </p>
                    <p style="color: #8f98a0; font-size: 13px; margin-top: 5px;">
                        Total games: <strong><?= $total_games; ?></strong>
                    </p>
                </div>
            </aside>
        <?php } ?>

        <!-- MAIN CONTENT -->
        <main class="epic-content fade-in">
            <!-- FEATURED BANNER -->
            <div class="featured-banner">
                <div class="featured-content">
                    <h2><i class="fas fa-crown"></i> Welcome to Midnight Play!</h2>
                    <p>Discover amazing games and expand your collection. New deals every week!</p>
                    <a href="#all-games" class="btn-featured">
                        <i class="fas fa-gamepad"></i> Browse All Games
                    </a>
                </div>
                <div style="font-size: 48px; color: #66c0f4;">
                    <i class="fas fa-gamepad"></i>
                </div>
            </div>

            <!-- STORE HEADER -->
            <div class="store-header">
                <div>
                    <h1>Game Store</h1>
                    <p style="color: #8f98a0; margin-top: 5px;">
                        Discover amazing games and expand your collection
                    </p>
                </div>
                <div class="search-container">
                    <form method="GET" action="" id="searchForm">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" name="search" placeholder="Search games..."
                            value="<?= htmlspecialchars($search_query); ?>">
                    </form>
                </div>
            </div>

            <!-- NEW RELEASE SECTION -->
            <section style="margin-bottom: 40px;">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-rocket" style="color: #1a9fff;"></i> New Release
                    </h2>
                    <a href="?sort=newest" class="section-link">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="horizontal-game-grid">
                    <?php if (mysqli_num_rows($new_release_games) > 0): ?>
                        <?php mysqli_data_seek($new_release_games, 0); ?>
                        <?php while ($game = mysqli_fetch_assoc($new_release_games)):
                            $is_owned = in_array($game['id_game'], $owned_games);
                            ?>
                            <div class="game-card horizontal-game-card">
                                <div class="game-image">
                                    <div class="new-release-badge">NEW</div>
                                    <?php if (!empty($game['image_url'])): ?>
                                        <img src="assets/images/games/<?= htmlspecialchars($game['image_url']); ?>"
                                            alt="<?= htmlspecialchars($game['title']); ?>"
                                            onerror="this.src='https://via.placeholder.com/300x150/2a2f3a/66c0f4?text=No+Image'">
                                    <?php else: ?>
                                        <div
                                            style="display: flex; align-items: center; justify-content: center; height: 100%; background: #2a2f3a;">
                                            <i class="fas fa-gamepad" style="font-size: 24px; color: #66c0f4;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="game-content">
                                    <h3 class="game-title"><?= htmlspecialchars($game['title']); ?></h3>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div class="game-price">Rp <?= number_format($game['price'], 0, ',', '.'); ?></div>
                                        <?php if ($is_owned): ?>
                                            <div class="owned-badge" style="padding: 3px 8px; font-size: 10px;">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        <?php else: ?>
                                            <a href="store/store_game_detail.php?id=<?= $game['id_game']; ?>" class="btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div style="color: #8f98a0; font-size: 11px; margin-top: 5px;">
                                        <i class="far fa-calendar"></i> <?= date('M d', strtotime($game['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #8f98a0;">
                            <p>No new releases available.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- TOP SELLERS SECTION -->
            <section style="margin-bottom: 40px;">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-chart-line" style="color: #ff6b35;"></i> Top Sellers
                    </h2>
                    <a href="?sort=popular" class="section-link">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="horizontal-game-grid">
                    <?php if (mysqli_num_rows($top_sellers_games) > 0): ?>
                        <?php mysqli_data_seek($top_sellers_games, 0); ?>
                        <?php while ($game = mysqli_fetch_assoc($top_sellers_games)):
                            $is_owned = in_array($game['id_game'], $owned_games);
                            $transaction_count = $game['transaction_count'] ?? 0;
                            ?>
                            <div class="game-card horizontal-game-card">
                                <div class="game-image">
                                    <div class="top-seller-badge">TOP</div>
                                    <?php if (!empty($game['image_url'])): ?>
                                        <img src="assets/images/games/<?= htmlspecialchars($game['image_url']); ?>"
                                            alt="<?= htmlspecialchars($game['title']); ?>"
                                            onerror="this.src='https://via.placeholder.com/300x150/2a2f3a/66c0f4?text=No+Image'">
                                    <?php else: ?>
                                        <div
                                            style="display: flex; align-items: center; justify-content: center; height: 100%; background: #2a2f3a;">
                                            <i class="fas fa-gamepad" style="font-size: 24px; color: #66c0f4;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="game-content">
                                    <h3 class="game-title"><?= htmlspecialchars($game['title']); ?></h3>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div class="game-price">Rp <?= number_format($game['price'], 0, ',', '.'); ?></div>
                                        <?php if ($is_owned): ?>
                                            <div class="owned-badge" style="padding: 3px 8px; font-size: 10px;">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        <?php else: ?>
                                            <a href="store/store_game_detail.php?id=<?= $game['id_game']; ?>" class="btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($transaction_count > 0): ?>
                                        <div class="transaction-count">
                                            <i class="fas fa-shopping-cart"></i> <?= $transaction_count; ?> sold
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #8f98a0;">
                            <p>No top sellers data available.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- MOST POPULAR SECTION -->
            <section style="margin-bottom: 40px;">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-fire" style="color: #ffd700;"></i> Most Popular
                    </h2>
                    <a href="?sort=popular" class="section-link">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="horizontal-game-grid">
                    <?php if (mysqli_num_rows($most_popular_games) > 0): ?>
                        <?php mysqli_data_seek($most_popular_games, 0); ?>
                        <?php while ($game = mysqli_fetch_assoc($most_popular_games)):
                            $is_owned = in_array($game['id_game'], $owned_games);
                            $purchase_count = $game['purchase_count'] ?? 0;
                            ?>
                            <div class="game-card horizontal-game-card">
                                <div class="game-image">
                                    <div class="popular-badge">HOT</div>
                                    <?php if (!empty($game['image_url'])): ?>
                                        <img src="assets/images/games/<?= htmlspecialchars($game['image_url']); ?>"
                                            alt="<?= htmlspecialchars($game['title']); ?>"
                                            onerror="this.src='https://via.placeholder.com/300x150/2a2f3a/66c0f4?text=No+Image'">
                                    <?php else: ?>
                                        <div
                                            style="display: flex; align-items: center; justify-content: center; height: 100%; background: #2a2f3a;">
                                            <i class="fas fa-gamepad" style="font-size: 24px; color: #66c0f4;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="game-content">
                                    <h3 class="game-title"><?= htmlspecialchars($game['title']); ?></h3>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div class="game-price">Rp <?= number_format($game['price'], 0, ',', '.'); ?></div>
                                        <?php if ($is_owned): ?>
                                            <div class="owned-badge" style="padding: 3px 8px; font-size: 10px;">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        <?php else: ?>
                                            <a href="store/store_game_detail.php?id=<?= $game['id_game']; ?>" class="btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($purchase_count > 0): ?>
                                        <div class="transaction-count">
                                            <i class="fas fa-users"></i> <?= $purchase_count; ?> purchases
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #8f98a0;">
                            <p>No popular games data available.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- GENRE FILTERS -->
            <div class="genre-filters">
                <a href="index.php" class="genre-filter <?= empty($selected_genre) ? 'active' : ''; ?>">
                    All Games
                </a>
                <?php foreach ($genres as $genre): ?>
                    <a href="index.php?genre=<?= urlencode($genre); ?>"
                        class="genre-filter <?= $selected_genre == $genre ? 'active' : ''; ?>">
                        <?= htmlspecialchars($genre); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- FILTERS -->
            <div class="store-filters">
                <div class="filter-group">
                    <label><i class="fas fa-filter"></i> Sort by</label>
                    <select id="sortFilter">
                        <option value="newest">Newest</option>
                        <option value="popular">Most Popular</option>
                        <option value="price-low">Price: Low to High</option>
                        <option value="price-high">Price: High to Low</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-tag"></i> Price Range</label>
                    <select id="priceFilter">
                        <option value="all">All Prices</option>
                        <option value="free" <?= $price_filter == 'free' ? 'selected' : ''; ?>>Free Only</option>
                        <option value="under-50" <?= $price_filter == 'under-50' ? 'selected' : ''; ?>>Under Rp 50,000
                        </option>
                        <option value="50-200" <?= $price_filter == '50-200' ? 'selected' : ''; ?>>Rp 50,000 - 200,000
                        </option>
                        <option value="over-200" <?= $price_filter == 'over-200' ? 'selected' : ''; ?>>Over Rp 200,000
                        </option>
                    </select>
                </div>
            </div>

            <!-- GAME COUNT -->
            <div class="game-count">
                <i class="fas fa-gamepad"></i> Showing <?= $total_games; ?> games
                <?php if (!empty($selected_genre)): ?>
                    in <strong><?= htmlspecialchars($selected_genre); ?></strong>
                <?php endif; ?>
            </div>

            <!-- ALL GAMES SECTION -->
            <section id="all-games" style="margin-bottom: 40px;">
                <h2 style="color: #66c0f4; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-th-large"></i> All Games
                </h2>
                <div class="game-grid">
                    <?php if ($total_games > 0): ?>
                        <?php while ($game = mysqli_fetch_assoc($all_games)):
                            $is_owned = in_array($game['id_game'], $owned_games);
                            ?>
                            <div class="game-card">
                                <div class="game-image">
                                    <?php if (!empty($game['image_url'])): ?>
                                        <?php if (strpos($game['image_url'], 'http') === 0): ?>
                                            <img src="<?= htmlspecialchars($game['image_url']); ?>"
                                                alt="<?= htmlspecialchars($game['title']); ?>">
                                        <?php else: ?>
                                            <img src="assets/images/games/<?= htmlspecialchars($game['image_url']); ?>"
                                                alt="<?= htmlspecialchars($game['title']); ?>"
                                                onerror="this.src='https://via.placeholder.com/300x150/2a2f3a/66c0f4?text=No+Image'">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div
                                            style="display: flex; align-items: center; justify-content: center; height: 100%; background: #2a2f3a;">
                                            <i class="fas fa-gamepad" style="font-size: 48px; color: #66c0f4;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="game-content">
                                    <h3 class="game-title"><?= htmlspecialchars($game['title']); ?></h3>
                                    <div style="margin-bottom: 10px;">
                                        <?php
                                        $game_genres = explode(',', $game['genre']);
                                        foreach ($game_genres as $g):
                                            $g = trim($g);
                                            if (!empty($g)):
                                                ?>
                                                <span
                                                    style="display: inline-block; background: #2a2f3a; color: #8f98a0; 
                                                padding: 3px 8px; border-radius: 4px; font-size: 11px; margin-right: 5px; margin-bottom: 5px;">
                                                    <?= htmlspecialchars($g); ?>
                                                </span>
                                                <?php
                                            endif;
                                        endforeach;
                                        ?>
                                    </div>
                                    <p class="game-description">
                                        <?= htmlspecialchars(substr($game['description'] ?? '', 0, 100)); ?>...
                                    </p>
                                    <div class="game-meta">
                                        <div class="game-price">Rp <?= number_format($game['price'], 0, ',', '.'); ?></div>
                                        <?php if ($is_owned): ?>
                                            <div class="owned-badge">
                                                <i class="fas fa-check-circle"></i> Owned
                                            </div>
                                        <?php else: ?>
                                            <a href="store/store_game_detail.php?id=<?= $game['id_game']; ?>"
                                                class="btn btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #8f98a0;">
                            <i class="fas fa-gamepad" style="font-size: 48px; margin-bottom: 15px;"></i>
                            <p>No games found matching your criteria.</p>
                            <a href="index.php" class="btn-primary" style="margin-top: 15px;">
                                <i class="fas fa-redo"></i> Clear Filters
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- FOOTER -->
            <div class="user-footer">
                <p>&copy; <?= date('Y'); ?> Midnight Play. All rights reserved.</p>
            </div>
        </main>
    </div>

    <script>
        // Search functionality
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.querySelector('.search-input');

        // Auto-submit search when typing stops (with debounce)
        let searchTimeout;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchForm.submit();
            }, 800);
        });

        // Filter functionality
        const priceFilter = document.getElementById('priceFilter');
        const sortFilter = document.getElementById('sortFilter');

        // Redirect when filters change
        function applyFilters() {
            const params = new URLSearchParams(window.location.search);
            const price = priceFilter.value;
            const sort = sortFilter.value;

            // Update URL parameters
            if (price !== 'all') {
                params.set('price', price);
            } else {
                params.delete('price');
            }

            // For sorting, we'll handle with JavaScript sorting
            // since it's client-side

            // Redirect with new params
            window.location.href = 'index.php?' + params.toString();
        }

        priceFilter.addEventListener('change', applyFilters);

        // Client-side sorting
        sortFilter.addEventListener('change', function () {
            const container = document.querySelector('.game-grid');
            const games = Array.from(container.children);

            games.sort((a, b) => {
                const priceA = parseInt(a.querySelector('.game-price').textContent.replace(/[^0-9]/g, ''));
                const priceB = parseInt(b.querySelector('.game-price').textContent.replace(/[^0-9]/g, ''));

                switch (this.value) {
                    case 'price-low':
                        return priceA - priceB;
                    case 'price-high':
                        return priceB - priceA;
                    case 'newest':
                    default:
                        return 0; // Already sorted by newest from server
                }
            });

            // Re-append sorted games
            games.forEach(game => container.appendChild(game));
        });

        // Horizontal scroll functionality for game sections
        document.querySelectorAll('.horizontal-game-grid').forEach(grid => {
            const scrollLeft = grid.querySelector('.scroll-button.left');
            const scrollRight = grid.querySelector('.scroll-button.right');

            if (scrollLeft && scrollRight) {
                scrollLeft.addEventListener('click', () => {
                    grid.scrollBy({ left: -300, behavior: 'smooth' });
                });

                scrollRight.addEventListener('click', () => {
                    grid.scrollBy({ left: 300, behavior: 'smooth' });
                });
            }
        });

        // Keyboard shortcut for search (Ctrl+K)
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
            }
            if (e.key === 'Escape' && document.activeElement === searchInput) {
                searchInput.blur();
            }
        });

        // Highlight search term
        document.addEventListener('DOMContentLoaded', function () {
            const searchTerm = "<?= addslashes($search_query); ?>";
            if (searchTerm) {
                const gameTitles = document.querySelectorAll('.game-title');
                gameTitles.forEach(title => {
                    const html = title.innerHTML;
                    const regex = new RegExp(`(${searchTerm})`, 'gi');
                    title.innerHTML = html.replace(regex, '<mark>$1</mark>');
                });
            }
        });
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
                <a href="auth/auth_logout.php" class="btn-primary">
                    <i class="fas fa-sign-out-alt"></i> Yes, Logout
                </a>
            </div>
        </div>
    </div>
</body>

</html>