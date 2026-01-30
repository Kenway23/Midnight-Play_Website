<?php
session_start();
include "../config/database.php";

// Fungsi untuk redirect yang benar
function redirect_login()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $path = '/midnightplay_web/auth/auth_login.php';

    // Tambahkan return_url jika ada
    $return_url = urlencode($_SERVER['REQUEST_URI']);
    $redirect_url = $protocol . $host . $path . '?return_url=' . $return_url;

    header("Location: " . $redirect_url);
    exit();
}

$id = $_GET['id'] ?? '';
if (!$id) {
    header("Location: ../index.php");
    exit();
}

// Ambil data game
$game_query = mysqli_query($conn, "SELECT * FROM games WHERE id_game = '$id'");
$game = mysqli_fetch_assoc($game_query);

if (!$game) {
    header("Location: ../index.php");
    exit();
}

// Cek apakah user sudah memiliki game ini
$owned = false;
$in_cart = false;

if (isset($_SESSION['login'])) {
    $user_id = $_SESSION['id_user'];

    // Cek di library
    $check_library = mysqli_query($conn, "
        SELECT * FROM 'library'
        WHERE id_user = '$user_id'
        AND id_game = '$id'
    ");
    if (mysqli_num_rows($check_library) > 0) {
        $owned = true;
    }

    // Cek di cart (jika ada fitur cart)
    $in_cart = false; // Default false karena tabel carts belum ada
}

// Ambil screenshots dari database
$screenshots_query = mysqli_query($conn, "
    SELECT * FROM game_screenshots 
    WHERE id_game = '$id' 
    ORDER BY display_order
");

// Ambil game-game lain dari genre yang sama (recommendations)
$genre = $game['genre'];
$similar_games = mysqli_query($conn, "
    SELECT * FROM games 
    WHERE genre LIKE '%$genre%' 
    AND id_game != '$id'
    AND status = 'active'
    ORDER BY RAND()
    LIMIT 6
");

// Format harga
$price_formatted = number_format($game['price'], 0, ',', '.');
$release_date = date('F d, Y', strtotime($game['created_at']));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($game['title']); ?> | Midnight Play</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* Game Detail Styles */
        .game-detail-container {
            background: #171a21;
            min-height: 100vh;
        }

        .game-hero {
            position: relative;
            height: 500px;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                url('../assets/images/games/<?= $game['image_url'] ?? 'default.jpg'; ?>');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
            padding: 60px 80px;
        }

        .game-hero-content {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            color: white;
            position: relative;
            z-index: 2;
        }

        .game-title-hero {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 15px;
            text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.7);
        }

        .game-subtitle {
            font-size: 18px;
            color: #c7d5e0;
            margin-bottom: 25px;
            max-width: 600px;
            text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.5);
        }

        .game-meta-hero {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .meta-label {
            color: #8f98a0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .meta-value {
            color: #c7d5e0;
            font-size: 16px;
            font-weight: 600;
        }

        .game-hero-actions {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .game-price-hero {
            font-size: 32px;
            color: #66c0f4;
            font-weight: 700;
        }

        .btn-action {
            padding: 15px 35px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-buy {
            background: linear-gradient(135deg, #66c0f4, #1a9fff);
            color: white;
        }

        .btn-buy:hover {
            background: linear-gradient(135deg, #1a9fff, #0066cc);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 192, 244, 0.4);
        }

        .btn-owned {
            background: #10b981;
            color: white;
            cursor: default;
        }

        .btn-owned i {
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

        .btn-cart {
            background: #2a2f3a;
            color: #c7d5e0;
            border: 2px solid #66c0f4;
        }

        .btn-cart:hover {
            background: #323844;
        }

        /* Back button - PASTIKAN DI ATAS */
        .back-btn {
            position: absolute;
            top: 30px;
            left: 30px;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            background: rgba(0, 0, 0, 0.5);
            padding: 10px 20px;
            border-radius: 6px;
            transition: all 0.3s;
            z-index: 4;
        }

        .back-btn:hover {
            background: rgba(102, 192, 244, 0.8);
            transform: translateX(-5px);
        }

        /* Main Content */
        .game-main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 50px 20px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
        }

        @media (max-width: 968px) {
            .game-main-content {
                grid-template-columns: 1fr;
            }
        }

        /* Left Column - Game Description */
        .game-description-section {
            background: #1b2838;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .section-title {
            color: #66c0f4;
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2a2f3a;
        }

        .game-description-full {
            color: #c7d5e0;
            font-size: 16px;
            line-height: 1.8;
            white-space: pre-line;
        }

        .game-screenshots {
            background: #1b2838;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .screenshots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .screenshot-item {
            height: 200px;
            background: #2a2f3a;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }

        .screenshot-item:hover {
            transform: scale(1.03);
            box-shadow: 0 5px 15px rgba(102, 192, 244, 0.3);
        }

        .screenshot-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .screenshot-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px;
            font-size: 14px;
            text-align: center;
        }

        .no-screenshots {
            text-align: center;
            padding: 40px 20px;
        }

        .screenshot-placeholder {
            color: #66c0f4;
            font-size: 48px;
        }

        .screenshot-placeholder p {
            color: #8f98a0;
            margin-top: 10px;
            font-size: 16px;
        }

        /* Right Column - Game Info */
        .game-info-sidebar {
            position: sticky;
            top: 20px;
        }

        .info-card {
            background: #1b2838;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid #2a2f3a;
        }

        .info-list {
            list-style: none;
            padding: 0;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #2a2f3a;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #8f98a0;
            font-size: 14px;
        }

        .info-value {
            color: #c7d5e0;
            font-weight: 500;
            text-align: right;
            max-width: 200px;
        }

        .genre-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .genre-tag {
            background: #2a2f3a;
            color: #66c0f4;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }

        /* System Requirements */
        .requirements-list {
            margin-top: 15px;
        }

        .req-item {
            margin-bottom: 10px;
        }

        .req-label {
            color: #66c0f4;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .req-value {
            color: #c7d5e0;
            font-size: 14px;
        }

        /* Similar Games */
        .similar-games-section {
            margin-top: 60px;
            padding: 0 20px;
            max-width: 1200px;
            margin: 60px auto 0;
        }

        .similar-games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .similar-game-card {
            background: #1b2838;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .similar-game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            border-color: #66c0f4;
        }

        .similar-game-image {
            height: 120px;
            background: #2a2f3a;
            overflow: hidden;
        }

        .similar-game-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .similar-game-content {
            padding: 15px;
        }

        .similar-game-title {
            color: #c7d5e0;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .similar-game-price {
            color: #66c0f4;
            font-weight: bold;
            font-size: 16px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .game-hero {
                height: 400px;
                padding: 40px 20px;
            }

            .game-title-hero {
                font-size: 32px;
            }

            .game-hero-actions {
                flex-direction: column;
                align-items: flex-start;
            }

            .game-meta-hero {
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .game-hero {
                height: 350px;
                padding: 30px 15px;
            }

            .game-title-hero {
                font-size: 28px;
            }

            .game-price-hero {
                font-size: 24px;
            }

            .btn-action {
                padding: 12px 25px;
                width: 100%;
                justify-content: center;
            }
        }

        /* Developer Info */
        .developer-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #2a2f3a;
        }

        .developer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #66c0f4;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: bold;
        }

        .developer-text {
            flex: 1;
        }

        .developer-name {
            color: #c7d5e0;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .developer-label {
            color: #8f98a0;
            font-size: 14px;
        }

        /* Rating */
        .rating-section {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .rating-stars {
            color: #ffd700;
            font-size: 18px;
        }

        .rating-text {
            color: #8f98a0;
            font-size: 14px;
        }

        /* Lightbox Styles */
        .lightbox {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            padding: 20px;
            box-sizing: border-box;
        }

        .lightbox-content {
            position: relative;
            margin: auto;
            max-width: 90%;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .lightbox-content img {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
        }

        .close-lightbox {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1001;
        }

        .close-lightbox:hover {
            color: #66c0f4;
        }

        #lightbox-caption {
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 16px;
            margin-top: 10px;
        }

        .lightbox-nav {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            transform: translateY(-50%);
        }

        .lightbox-btn {
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 15px;
            cursor: pointer;
            font-size: 24px;
            transition: background 0.3s;
        }

        .lightbox-btn:hover {
            background: rgba(102, 192, 244, 0.8);
        }
    </style>
</head>

<body>
    <!-- Game Hero Section -->
    <div class="game-hero">
        <!-- Back Button -->
        <a href="../index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Store
        </a>

        <div class="game-hero-content">
            <h1 class="game-title-hero"><?= htmlspecialchars($game['title']); ?></h1>
            <p class="game-subtitle">
                <?= htmlspecialchars(substr($game['description'] ?? '', 0, 200)); ?>...
            </p>

            <div class="game-meta-hero">
                <div class="meta-item">
                    <span class="meta-label">Release Date</span>
                    <span class="meta-value"><?= $release_date; ?></span>
                </div>

                <div class="meta-item">
                    <span class="meta-label">Genre</span>
                    <div class="genre-tags">
                        <?php
                        $genres = explode(',', $game['genre']);
                        foreach ($genres as $genre_item):
                            $genre_item = trim($genre_item);
                            if (!empty($genre_item)):
                                ?>
                                <span class="genre-tag"><?= htmlspecialchars($genre_item); ?></span>
                                <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>

                <div class="meta-item">
                    <span class="meta-label">Price</span>
                    <div class="game-price-hero">Rp <?= $price_formatted; ?></div>
                </div>
            </div>

            <div class="game-hero-actions">
                <?php if ($owned): ?>
                    <div class="btn-action btn-owned">
                        <i class="fas fa-check-circle"></i> OWNED IN LIBRARY
                    </div>
                    <a href="../library/library_user_games.php" class="btn-action"
                        style="background: #2a2f3a; color: #c7d5e0;">
                        <i class="fas fa-gamepad"></i> GO TO LIBRARY
                    </a>
                <?php else: ?>
                    <a href="../transactions/transaction_buy_game.php?id=<?= $game['id_game']; ?>"
                        class="btn-action btn-buy">
                        <i class="fas fa-shopping-cart"></i> BUY NOW
                    </a>
                <?php endif; ?>

                <!-- Rating Section -->
                <div class="rating-section">
                    <div class="rating-stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="far fa-star"></i>
                    </div>
                    <span class="rating-text">4.0 (250 reviews)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="game-main-content">
        <!-- Left Column -->
        <div class="left-column">
            <!-- Game Description -->
            <section class="game-description-section">
                <h2 class="section-title">
                    <i class="fas fa-align-left"></i> About This Game
                </h2>
                <div class="game-description-full">
                    <?= nl2br(htmlspecialchars($game['description'] ?? 'No description available.')); ?>
                </div>

                <!-- Developer Info (static for now) -->
                <div class="developer-info">
                    <div class="developer-avatar">
                        <i class="fas fa-code"></i>
                    </div>
                    <div class="developer-text">
                        <div class="developer-name">Midnight Play Studios</div>
                        <div class="developer-label">Developer & Publisher</div>
                    </div>
                </div>
            </section>

            <!-- Screenshots Section -->
            <section class="game-screenshots">
                <h2 class="section-title">
                    <i class="fas fa-images"></i> Screenshots
                </h2>

                <?php if (mysqli_num_rows($screenshots_query) > 0): ?>
                    <div class="screenshots-grid" id="screenshotsGallery">
                        <?php while ($screenshot = mysqli_fetch_assoc($screenshots_query)): ?>
                            <div class="screenshot-item"
                                onclick="openLightbox('<?= $screenshot['image_url']; ?>', '<?= htmlspecialchars($screenshot['caption'] ?? ''); ?>')">
                                <img src="../assets/images/screenshots/<?= $screenshot['image_url']; ?>"
                                    alt="<?= htmlspecialchars($screenshot['caption'] ?? 'Game Screenshot'); ?>" loading="lazy">
                                <?php if (!empty($screenshot['caption'])): ?>
                                    <div class="screenshot-caption"><?= htmlspecialchars($screenshot['caption']); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-screenshots">
                        <div class="screenshot-placeholder">
                            <i class="fas fa-camera"></i>
                            <p>No screenshots available for this game</p>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Right Column -->
        <div class="right-column">
            <div class="game-info-sidebar">
                <!-- Game Information Card -->
                <div class="info-card">
                    <h3 style="color: #66c0f4; margin-bottom: 20px; font-size: 18px;">
                        <i class="fas fa-info-circle"></i> Game Information
                    </h3>
                    <ul class="info-list">
                        <li class="info-item">
                            <span class="info-label">Title</span>
                            <span class="info-value"><?= htmlspecialchars($game['title']); ?></span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">Release Date</span>
                            <span class="info-value"><?= $release_date; ?></span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">Genre</span>
                            <span class="info-value"><?= htmlspecialchars($game['genre']); ?></span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">Price</span>
                            <span class="info-value" style="color: #66c0f4; font-weight: bold;">Rp
                                <?= $price_formatted; ?></span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">Game ID</span>
                            <span class="info-value">#<?= $game['id_game']; ?></span>
                        </li>
                    </ul>
                </div>

                <!-- System Requirements (placeholder) -->
                <div class="info-card">
                    <h3 style="color: #66c0f4; margin-bottom: 20px; font-size: 18px;">
                        <i class="fas fa-desktop"></i> System Requirements
                    </h3>
                    <div class="requirements-list">
                        <div class="req-item">
                            <div class="req-label">OS</div>
                            <div class="req-value">Windows 10 64-bit</div>
                        </div>
                        <div class="req-item">
                            <div class="req-label">Processor</div>
                            <div class="req-value">Intel Core i5-4460</div>
                        </div>
                        <div class="req-item">
                            <div class="req-label">Memory</div>
                            <div class="req-value">8 GB RAM</div>
                        </div>
                        <div class="req-item">
                            <div class="req-label">Graphics</div>
                            <div class="req-value">NVIDIA GeForce GTX 760</div>
                        </div>
                        <div class="req-item">
                            <div class="req-label">Storage</div>
                            <div class="req-value">20 GB available space</div>
                        </div>
                    </div>
                </div>

                <!-- Game Features (placeholder) -->
                <div class="info-card">
                    <h3 style="color: #66c0f4; margin-bottom: 20px; font-size: 18px;">
                        <i class="fas fa-star"></i> Features
                    </h3>
                    <ul style="color: #c7d5e0; padding-left: 20px;">
                        <li>Single Player</li>
                        <li>Multiplayer</li>
                        <li>Controller Support</li>
                        <li>Achievements</li>
                        <li>Cloud Saves</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Lightbox Modal -->
    <div id="lightbox" class="lightbox" onclick="closeLightbox()">
        <span class="close-lightbox" onclick="closeLightbox()">&times;</span>
        <div class="lightbox-content">
            <img id="lightbox-img" src="" alt="">
            <div id="lightbox-caption"></div>
            <div class="lightbox-nav">
                <button class="lightbox-btn prev" onclick="changeSlide(-1)">❮</button>
                <button class="lightbox-btn next" onclick="changeSlide(1)">❯</button>
            </div>
        </div>
    </div>

    <!-- Similar Games Section -->
    <?php if (mysqli_num_rows($similar_games) > 0): ?>
        <section class="similar-games-section">
            <h2 class="section-title" style="padding: 0 20px;">
                <i class="fas fa-gamepad"></i> More Games Like This
            </h2>
            <div class="similar-games-grid">
                <?php while ($similar = mysqli_fetch_assoc($similar_games)): ?>
                    <a href="store_game_detail.php?id=<?= $similar['id_game']; ?>" class="similar-game-card">
                        <div class="similar-game-image">
                            <?php if (!empty($similar['image_url'])): ?>
                                <img src="../assets/images/games/<?= htmlspecialchars($similar['image_url']); ?>"
                                    alt="<?= htmlspecialchars($similar['title']); ?>">
                            <?php else: ?>
                                <div
                                    style="display: flex; align-items: center; justify-content: center; height: 100%; background: #2a2f3a;">
                                    <i class="fas fa-gamepad" style="color: #66c0f4;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="similar-game-content">
                            <h4 class="similar-game-title"><?= htmlspecialchars($similar['title']); ?></h4>
                            <div class="similar-game-price">Rp <?= number_format($similar['price'], 0, ',', '.'); ?></div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Footer -->
    <div class="user-footer" style="margin-top: 60px;">
        <p>&copy; <?= date('Y'); ?> Midnight Play. All rights reserved.</p>
    </div>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Add to cart animation
        document.querySelectorAll('.btn-cart').forEach(btn => {
            btn.addEventListener('click', function (e) {
                if (!this.classList.contains('disabled')) {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i> ADDED!';
                    this.classList.add('disabled');

                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.classList.remove('disabled');
                    }, 2000);
                }
            });
        });

        // Update page title with game name
        document.addEventListener('DOMContentLoaded', function () {
            const gameTitle = "<?= addslashes($game['title']); ?>";
            document.title = gameTitle + " | Midnight Play";
        });

        // Lightbox functionality
        let currentScreenshots = [];
        let currentIndex = 0;

        function openLightbox(imageUrl, caption) {
            // Collect all screenshots
            const screenshotItems = document.querySelectorAll('.screenshot-item img');
            currentScreenshots = Array.from(screenshotItems).map(img => ({
                src: img.src,
                caption: img.alt
            }));

            // Find current index
            currentIndex = currentScreenshots.findIndex(img => img.src.includes(imageUrl));

            // Show lightbox
            const lightbox = document.getElementById('lightbox');
            const lightboxImg = document.getElementById('lightbox-img');
            const lightboxCaption = document.getElementById('lightbox-caption');

            lightboxImg.src = currentScreenshots[currentIndex].src;
            lightboxCaption.textContent = currentScreenshots[currentIndex].caption;
            lightbox.style.display = 'block';

            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            document.getElementById('lightbox').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function changeSlide(direction) {
            currentIndex += direction;

            // Loop around
            if (currentIndex < 0) currentIndex = currentScreenshots.length - 1;
            if (currentIndex >= currentScreenshots.length) currentIndex = 0;

            const lightboxImg = document.getElementById('lightbox-img');
            const lightboxCaption = document.getElementById('lightbox-caption');

            lightboxImg.src = currentScreenshots[currentIndex].src;
            lightboxCaption.textContent = currentScreenshots[currentIndex].caption;
        }

        // Keyboard navigation
        document.addEventListener('keydown', function (e) {
            const lightbox = document.getElementById('lightbox');
            if (lightbox.style.display === 'block') {
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowLeft') changeSlide(-1);
                if (e.key === 'ArrowRight') changeSlide(1);
            }
        });
    </script>
</body>

</html>