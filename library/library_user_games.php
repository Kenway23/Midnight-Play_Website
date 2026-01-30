<?php
session_start();
include "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] != 'user') {
    header("Location: /auth/auth_login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// Query untuk mengambil game dari library user - DIUBAH disini
$query = mysqli_query($conn, "
    SELECT g.*, l.purchased_at, l.id_library
    FROM `library` l
    JOIN games g ON l.id_game = g.id_game
    WHERE l.id_user = $id_user
    ORDER BY l.purchased_at DESC
");

$total_games = mysqli_num_rows($query);

// Hitung statistik - DIUBAH disini
$stats_query = mysqli_query($conn, "
    SELECT 
        COUNT(DISTINCT g.genre) as total_genres,
        SUM(g.price) as total_value,
        MIN(l.purchased_at) as first_added
    FROM `library` l
    JOIN games g ON l.id_game = g.id_game
    WHERE l.id_user = $id_user
");
$stats = mysqli_fetch_assoc($stats_query);

// Format total value
$total_value = $stats['total_value'] ? number_format($stats['total_value'], 0, ',', '.') : '0';
$total_genres = $stats['total_genres'] ? $stats['total_genres'] : '0';
$first_added = $stats['first_added'] ? date('M d, Y', strtotime($stats['first_added'])) : 'N/A';

// Ambil parameter filter jika ada
$search_query = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'recent';

// Modifikasi query jika ada filter - DIUBAH disini
$filter_query = "
    SELECT g.*, l.purchased_at, l.id_library
    FROM `library` l
    JOIN games g ON l.id_game = g.id_game
    WHERE l.id_user = $id_user
";

if (!empty($search_query)) {
    $filter_query .= " AND (g.title LIKE '%$search_query%' OR g.genre LIKE '%$search_query%' OR g.description LIKE '%$search_query%')";
}

switch ($sort_by) {
    case 'title':
        $filter_query .= " ORDER BY g.title ASC";
        break;
    case 'price':
        $filter_query .= " ORDER BY g.price DESC";
        break;
    case 'oldest':
        $filter_query .= " ORDER BY l.purchased_at ASC";
        break;
    case 'recent':
    default:
        $filter_query .= " ORDER BY l.purchased_at DESC";
        break;
}

$filtered_query = mysqli_query($conn, $filter_query);
$filtered_count = mysqli_num_rows($filtered_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Library | Midnight Play</title>

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

        /* Library specific styles */
        .library-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .library-stats {
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

        .library-filters {
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

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px;
            background: #2a2f3a;
            border: 1px solid #3d4452;
            border-radius: 6px;
            color: #c7d5e0;
            font-size: 14px;
        }

        .search-container {
            position: relative;
            flex: 2;
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
            padding: 10px 10px 10px 40px;
            background: #2a2f3a;
            border: 1px solid #3d4452;
            border-radius: 6px;
            color: #c7d5e0;
        }

        .game-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin: 20px 0;
        }

        .library-game-card {
            background: #171a21;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid #2a2f3a;
            position: relative;
        }

        .library-game-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
            border-color: #66c0f4;
        }

        .library-game-image {
            height: 180px;
            background: #2a2f3a;
            overflow: hidden;
            position: relative;
        }

        .library-game-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .library-game-card:hover .library-game-image img {
            transform: scale(1.05);
        }

        .library-game-content {
            padding: 20px;
        }

        .library-game-title {
            color: #c7d5e0;
            font-size: 18px;
            margin-bottom: 10px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .library-game-genre {
            color: #66c0f4;
            font-size: 13px;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .library-game-description {
            color: #8f98a0;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
            height: 60px;
            overflow: hidden;
        }

        .library-game-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #2a2f3a;
        }

        .library-game-price {
            color: #10b981;
            font-weight: bold;
            font-size: 16px;
        }

        .library-game-date {
            color: #8f98a0;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .library-game-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-play {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            padding: 8px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            flex: 1;
            justify-content: center;
        }

        .btn-play:hover {
            background: linear-gradient(135deg, #34d399, #10b981);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-details {
            background: #2a2f3a;
            color: #c7d5e0;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: 1px solid #3d4452;
        }

        .btn-details:hover {
            background: #323844;
            border-color: #66c0f4;
        }

        .empty-library {
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
        }

        .btn-store {
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

        .btn-store:hover {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 102, 204, 0.3);
        }

        .installed-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #10b981;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .recently-added-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            z-index: 2;
        }

        .last-played {
            color: #8f98a0;
            font-size: 12px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .play-time {
            color: #66c0f4;
            font-size: 12px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        @media (max-width: 768px) {
            .game-grid {
                grid-template-columns: 1fr;
            }

            .library-stats {
                grid-template-columns: 1fr;
            }

            .library-filters {
                flex-direction: column;
            }
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

                <a href="library_user_games.php" class="sidebar-item active">
                    <i class="fa-solid fa-gamepad"></i>
                    <span>Library</span>
                </a>

                <a href="/invoices/invoice_user_list.php" class="sidebar-item">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Purchases</span>
                </a>

                <a href="/profile/user_profile.php" class="sidebar-item">
                    <i class="fa-solid fa-user"></i>
                    <span>Profile</span>
                </a>
            </div>

            <!-- Quick Stats -->
            <div style="margin-top: 30px; padding: 20px; background: rgba(102, 192, 244, 0.1); border-radius: 12px;">
                <h4 style="color: #66c0f4; margin-bottom: 15px; font-size: 14px;">
                    <i class="fas fa-trophy"></i> Collection Stats
                </h4>
                <p style="color: #8f98a0; font-size: 13px; margin-bottom: 8px;">
                    <i class="fas fa-gamepad"></i> Games: <strong><?= $total_games; ?></strong>
                </p>
                <p style="color: #8f98a0; font-size: 13px; margin-bottom: 8px;">
                    <i class="fas fa-tags"></i> Genres: <strong><?= $total_genres; ?></strong>
                </p>
                <p style="color: #8f98a0; font-size: 13px;">
                    <i class="fas fa-coins"></i> Value: <strong>Rp <?= $total_value; ?></strong>
                </p>
            </div>
        </aside>

        <!-- CONTENT -->
        <main class="epic-content fade-in">
            <!-- Library Header -->
            <div class="library-header">
                <div>
                    <h1><i class="fa-solid fa-gamepad"></i> My Game Library</h1>
                    <p style="color: #8f98a0; margin-top: 5px;">
                        Your personal collection of purchased games
                    </p>
                </div>
                <div>
                    <?php if ($total_games > 0): ?>
                        <span style="color: #66c0f4; font-size: 14px; font-weight: 500;">
                            <i class="fas fa-cube"></i> <?= $total_games; ?> games
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Cards -->
            <?php if ($total_games > 0): ?>
                <div class="library-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-gamepad"></i>
                        </div>
                        <div class="stat-value"><?= $total_games; ?></div>
                        <div class="stat-label">Total Games</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-value"><?= $total_genres; ?></div>
                        <div class="stat-label">Unique Genres</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-value">Rp <?= $total_value; ?></div>
                        <div class="stat-label">Collection Value</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-value"><?= $first_added; ?></div>
                        <div class="stat-label">First Added</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="search-container">
                    <form method="GET" action="" id="searchForm">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" name="search" placeholder="Search in your library..."
                            value="<?= htmlspecialchars($search_query); ?>">
                    </form>
                </div>

                <div class="library-filters">


                    <div class="filter-group">
                        <label><i class="fas fa-sort"></i> Sort by</label>
                        <select id="sortFilter" onchange="applySort()">
                            <option value="recent" <?= $sort_by == 'recent' ? 'selected' : ''; ?>>Recently Added</option>
                            <option value="title" <?= $sort_by == 'title' ? 'selected' : ''; ?>>Title A-Z</option>
                            <option value="price" <?= $sort_by == 'price' ? 'selected' : ''; ?>>Highest Price</option>
                            <option value="oldest" <?= $sort_by == 'oldest' ? 'selected' : ''; ?>>Oldest Added</option>
                        </select>
                    </div>
                </div>

                <!-- Game Grid -->
                <div class="game-grid">
                    <?php while ($game = mysqli_fetch_assoc($filtered_query)):
                        // DIUBAH disini: purchased_at bukan added_date
                        $purchased_date = date('M d, Y', strtotime($game['purchased_at']));
                        $is_recent = (strtotime($game['purchased_at']) > strtotime('-7 days'));
                        ?>
                        <div class="library-game-card">
                            <?php if ($is_recent): ?>
                                <div class="recently-added-badge">
                                    <i class="fas fa-bolt"></i> NEW
                                </div>
                            <?php endif; ?>

                            <div class="installed-badge">
                                <i class="fas fa-check-circle"></i> INSTALLED
                            </div>

                            <div class="library-game-image">
                                <?php if (!empty($game['image_url'])): ?>
                                    <img src="/assets/images/games/<?= htmlspecialchars($game['image_url']); ?>"
                                        alt="<?= htmlspecialchars($game['title']); ?>"
                                        onerror="this.src='https://via.placeholder.com/400x180/2a2f3a/66c0f4?text=Game+Image'">
                                <?php else: ?>
                                    <div
                                        style="display: flex; align-items: center; justify-content: center; height: 100%; background: #2a2f3a;">
                                        <i class="fas fa-gamepad" style="font-size: 48px; color: #66c0f4;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="library-game-content">
                                <h3 class="library-game-title">
                                    <?= htmlspecialchars($game['title']); ?>
                                    <span style="color: #10b981; font-size: 14px;">
                                        <i class="fas fa-check-circle"></i> OWNED
                                    </span>
                                </h3>

                                <div class="library-game-genre">
                                    <i class="fas fa-tag"></i> <?= htmlspecialchars($game['genre']); ?>
                                </div>

                                <p class="library-game-description">
                                    <?= htmlspecialchars(substr($game['description'] ?? 'No description available', 0, 120)); ?>...
                                </p>

                                <!-- Simulated play stats (bisa dihubungkan dengan database jika ada) -->
                                <div class="last-played">
                                    <i class="far fa-clock"></i> Last played: <?= rand(1, 30); ?> days ago
                                </div>

                                <div class="play-time">
                                    <i class="fas fa-hourglass-half"></i> Total playtime: <?= rand(1, 100); ?> hours
                                </div>

                                <div class="library-game-meta">
                                    <div>
                                        <div class="library-game-price">
                                            Rp <?= number_format($game['price'], 0, ',', '.'); ?>
                                        </div>
                                        <div class="library-game-date">
                                            <!-- DIUBAH disini: purchased_at bukan added_date -->
                                            <i class="far fa-calendar-plus"></i> Purchased: <?= $purchased_date; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="library-game-actions">
                                    <a href="#" class="btn-play">
                                        <i class="fas fa-play"></i> PLAY NOW
                                    </a>
                                    <a href="/store/store_game_detail.php?id=<?= $game['id_game']; ?>"
                                        class="btn-details">
                                        <i class="fas fa-info-circle"></i> DETAILS
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <?php if ($filtered_count == 0 && !empty($search_query)): ?>
                    <div style="text-align: center; padding: 40px; color: #8f98a0;">
                        <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px;"></i>
                        <p>No games found matching "<?= htmlspecialchars($search_query); ?>"</p>
                        <a href="library_user_games.php" class="btn-store" style="background: #2a2f3a;">
                            <i class="fas fa-times"></i> Clear Search
                        </a>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Empty Library State -->
                <div class="empty-library">
                    <div class="empty-icon">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <h2 style="color: #c7d5e0; margin-bottom: 15px;">Your Library is Empty</h2>
                    <p style="color: #8f98a0; max-width: 500px; margin: 0 auto 25px; line-height: 1.6;">
                        You haven't purchased any games yet. Start building your collection by exploring our amazing games!
                    </p>
                    <a href="/index.php" class="btn-store">
                        <i class="fas fa-store"></i> Browse Store
                    </a>
                </div>

                <!-- Recommendations (jika ada game di store) -->
                <?php
                $recommendations = mysqli_query($conn, "
                    SELECT * FROM games 
                    WHERE status = 'active' 
                    ORDER BY RAND() 
                    LIMIT 3
                ");

                if (mysqli_num_rows($recommendations) > 0): ?>
                    <div style="margin-top: 50px;">
                        <h3 style="color: #66c0f4; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-star"></i> Recommended For You
                        </h3>
                        <div class="game-grid">
                            <?php while ($rec = mysqli_fetch_assoc($recommendations)): ?>
                                <div class="library-game-card">
                                    <div class="library-game-image">
                                        <?php if (!empty($rec['image_url'])): ?>
                                            <img src="/assets/images/games/<?= htmlspecialchars($rec['image_url']); ?>"
                                                alt="<?= htmlspecialchars($rec['title']); ?>">
                                        <?php else: ?>
                                            <div
                                                style="display: flex; align-items: center; justify-content: center; height: 100%; background: #2a2f3a;">
                                                <i class="fas fa-gamepad" style="font-size: 48px; color: #66c0f4;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="library-game-content">
                                        <h3 class="library-game-title"><?= htmlspecialchars($rec['title']); ?></h3>
                                        <div class="library-game-genre">
                                            <i class="fas fa-tag"></i> <?= htmlspecialchars($rec['genre']); ?>
                                        </div>
                                        <div style="margin: 15px 0;">
                                            <div class="library-game-price" style="color: #66c0f4;">
                                                Rp <?= number_format($rec['price'], 0, ',', '.'); ?>
                                            </div>
                                        </div>
                                        <a href="/store/store_game_detail.php?id=<?= $rec['id_game']; ?>"
                                            class="btn-play" style="width: 100%; text-align: center;">
                                            <i class="fas fa-shopping-cart"></i> BUY NOW
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>
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
        // Auto-submit search form
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.querySelector('.search-input');

        let searchTimeout;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchForm.submit();
            }, 800);
        });

        // Apply sort filter
        function applySort() {
            const sortFilter = document.getElementById('sortFilter');
            const params = new URLSearchParams(window.location.search);

            if (sortFilter.value !== 'recent') {
                params.set('sort', sortFilter.value);
            } else {
                params.delete('sort');
            }

            window.location.href = 'library_user_games.php?' + params.toString();
        }

        // Keyboard shortcut for search (Ctrl+K)
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
            }
        });

        // Highlight search term
        document.addEventListener('DOMContentLoaded', function () {
            const searchTerm = "<?= addslashes($search_query); ?>";
            if (searchTerm) {
                const gameTitles = document.querySelectorAll('.library-game-title');
                gameTitles.forEach(title => {
                    const html = title.innerHTML;
                    const regex = new RegExp(`(${searchTerm})`, 'gi');
                    title.innerHTML = html.replace(regex, '<mark style="background: #ffd700; color: #171a21; padding: 2px;">$1</mark>');
                });
            }
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