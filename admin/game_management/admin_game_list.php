<?php
session_start();
include "../../config/database.php";

/* Proteksi admin */
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/auth_login.php");
    exit();
}

// Query untuk mendapatkan semua game
$games = mysqli_query($conn, "SELECT * FROM games ORDER BY id_game DESC");
$totalGames = mysqli_num_rows($games);

// Hitung statistik
$avgPrice = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(price) as avg FROM games"))['avg'];
$avgPrice = number_format($avgPrice, 0, ',', '.');
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Game | Midnight Play Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .btn-delete {
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: background 0.3s;
        }

        .btn-delete:hover {
            background: #b91c1c;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: background 0.3s;
        }

        .btn-edit:hover {
            background: #2563eb;
        }

        /* Modal Logout */
        .logout-modal,
        .delete-modal {
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
            font-size: 40px;
            margin-bottom: 15px;
        }

        .logout-modal .modal-icon {
            color: #ff6b6b;
        }

        .delete-modal .modal-icon {
            color: #ff6b6b;
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
            color: #ff6b6b;
        }

        .modal-message {
            color: #c7d5e0;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .modal-btn {
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            min-width: 100px;
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

        .game-details {
            background: #1b1f27;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            text-align: left;
            border: 1px solid #2a475e;
        }

        .game-details p {
            margin: 8px 0;
            color: #c7d5e0;
            font-size: 14px;
        }

        .game-details strong {
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
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 class="modal-title">Konfirmasi Hapus Game</h2>
            <p class="modal-message">
                Anda akan menghapus game berikut:
            </p>

            <div class="game-details" id="deleteGameDetails">
                <!-- Game details will be inserted here by JavaScript -->
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
                    <i class="fas fa-trash"></i> Hapus Game
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Navbar -->
        <div class="navbar">
            <h1><i class="fas fa-gamepad"></i> Game Management</h1>
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
                    <a href="admin_game_list.php" class="sidebar-item active">
                        <i class="fas fa-gamepad"></i>
                        Kelola Game
                    </a>
                    <a href="../user_management/user_list.php" class="sidebar-item">
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
                        <h2><i class="fas fa-list"></i> Daftar Game</h2>
                        <p style="color: #8f98a0; margin-top: 5px;">Total: <?= $totalGames; ?> game</p>
                    </div>
                    <a href="admin_game_form.php" class="button">
                        <i class="fas fa-plus-circle"></i> Tambah Game Baru
                    </a>
                </div>

                <!-- Stats Cards -->
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0 30px 0;">
                    <div class="dashboard-card" style="background: linear-gradient(135deg, #065f46, #10b981);">
                        <div class="card-icon">
                            <i class="fas fa-tag fa-lg"></i>
                        </div>
                        <h3>Harga Rata-rata</h3>
                        <p>Rp <?= $avgPrice; ?></p>
                    </div>

                    <div class="dashboard-card" style="background: linear-gradient(135deg, #7c3aed, #8b5cf6);">
                        <div class="card-icon">
                            <i class="fas fa-layer-group fa-lg"></i>
                        </div>
                        <h3>Total Game</h3>
                        <p><?= $totalGames; ?> Game</p>
                    </div>
                </div>

                <!-- Table -->
                <div style="background: #171a21; border-radius: 8px; padding: 20px; margin-top: 20px;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Thumbnail</th>
                                <th>Judul Game</th>
                                <th>Genre</th>
                                <th>Harga</th>
                                <th style="width: 180px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($totalGames > 0): ?>
                                <?php while ($game = mysqli_fetch_assoc($games)): ?>
                                    <tr>
                                        <td data-label="ID">#<?= $game['id_game']; ?></td>
                                        <td data-label="Thumbnail">
                                            <?php if (!empty($game['image_url'])): ?>
                                                <img src="../../assets/images/games/<?= $game['image_url']; ?>"
                                                    alt="<?= $game['title']; ?>"
                                                    style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                                            <?php else: ?>
                                                <div
                                                    style="width: 60px; height: 40px; background: #2a2f3a; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-image" style="color: #8f98a0;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Judul">
                                            <strong><?= htmlspecialchars($game['title']); ?></strong>
                                            <?php if (!empty($game['developer'])): ?>
                                                <br>
                                                <small style="color: #8f98a0; font-size: 12px;">
                                                    <i class="fas fa-code"></i> <?= htmlspecialchars($game['developer']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Genre">
                                            <span
                                                style="background: #2a2f3a; padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                                                <?= htmlspecialchars($game['genre']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Harga" style="color: #66c0f4; font-weight: bold;">
                                            Rp <?= number_format($game['price'], 0, ',', '.'); ?>
                                        </td>
                                        <td data-label="Aksi">
                                            <div style="display: flex; gap: 8px;">
                                                <a href="admin_game_edit.php?id=<?= $game['id_game']; ?>" class="btn-edit"
                                                    title="Edit Game">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <button type="button" class="btn-delete" onclick='showDeleteModal(
                                                        "<?= $game['id_game']; ?>",
                                                        "<?= addslashes($game['title']); ?>",
                                                        "<?= addslashes($game['genre']); ?>",
                                                        "Rp <?= number_format($game['price'], 0, ',', '.'); ?>",
                                                        "<?= addslashes($game['developer'] ?? ''); ?>"
                                                    )' title="Hapus Game">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #8f98a0;">
                                        <i class="fas fa-gamepad"
                                            style="font-size: 48px; margin-bottom: 15px; display: block; color: #2a2f3a;"></i>
                                        Belum ada game yang tersedia
                                        <br>
                                        <a href="admin_game_form.php" class="button" style="margin-top: 15px;">
                                            <i class="fas fa-plus"></i> Tambah Game Pertama
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Navigation -->
                <div style="margin-top: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <a href="../admin_dashboard.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                    <div style="color: #8f98a0; font-size: 14px;">
                        <i class="fas fa-info-circle"></i> Menampilkan <?= $totalGames; ?> game
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Debug: Cek apakah script berjalan
        console.log('Admin Game List script loaded');

        // Logout Modal Functions
        function showLogoutModal() {
            console.log('Opening logout modal');
            document.getElementById('logoutModal').style.display = 'flex';
            return false; // Mencegah link default behavior
        }

        function closeLogoutModal() {
            console.log('Closing logout modal');
            document.getElementById('logoutModal').style.display = 'none';
        }

        // Delete Modal Functions
        function showDeleteModal(id, title, genre, price, developer) {
            console.log('Opening delete modal for:', title);

            // Set game details
            const detailsDiv = document.getElementById('deleteGameDetails');
            if (!detailsDiv) {
                console.error('deleteGameDetails element not found!');
                alert('Error: Modal element not found');
                return false;
            }

            // Bersihkan developer jika kosong
            const devInfo = developer && developer !== '' ?
                `<p><strong>Developer:</strong> ${developer}</p>` : '';

            detailsDiv.innerHTML = `
                <p><strong>ID Game:</strong> #${id}</p>
                <p><strong>Judul:</strong> ${title}</p>
                <p><strong>Genre:</strong> ${genre}</p>
                <p><strong>Harga:</strong> ${price}</p>
                ${devInfo}
            `;

            // Set delete URL
            const deleteBtn = document.getElementById('deleteConfirmBtn');
            if (deleteBtn) {
                deleteBtn.href = `admin_game_delete.php?id=${id}`;
                console.log('Delete URL set to:', deleteBtn.href);
            } else {
                console.error('deleteConfirmBtn element not found!');
            }

            // Show modal
            const modal = document.getElementById('deleteModal');
            if (modal) {
                modal.style.display = 'flex';
                console.log('Delete modal displayed successfully');
            } else {
                console.error('deleteModal element not found!');
                alert('Error: Delete modal not found');
            }

            return false; // Mencegah default behavior
        }

        function closeDeleteModal() {
            console.log('Closing delete modal');
            const modal = document.getElementById('deleteModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Close modal ketika klik di luar modal
        document.addEventListener('DOMContentLoaded', function () {
            console.log('DOM loaded, setting up modal listeners');

            const logoutModal = document.getElementById('logoutModal');
            const deleteModal = document.getElementById('deleteModal');

            if (logoutModal) {
                logoutModal.addEventListener('click', function (e) {
                    if (e.target === this) {
                        closeLogoutModal();
                    }
                });
            }

            if (deleteModal) {
                deleteModal.addEventListener('click', function (e) {
                    if (e.target === this) {
                        closeDeleteModal();
                    }
                });
            }
        });

        // Close modal dengan ESC key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeLogoutModal();
                closeDeleteModal();
            }
        });

        // Testing function
        function testDelete(id, title) {
            alert('Testing delete for: ' + title + ' (ID: ' + id + ')');
            return false;
        }
    </script>

    <script src="../../assets/js/script.js"></script>
</body>

</html>