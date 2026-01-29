<?php
session_start();
include "../../config/database.php";

/* Proteksi admin */
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/auth_login.php");
    exit();
}

$id = $_GET['id'] ?? '';
if (!$id) {
    header("Location: admin_game_list.php");
    exit();
}

// Ambil data game sebelum dihapus untuk konfirmasi
$query = mysqli_query($conn, "SELECT * FROM games WHERE id_game = '$id'");
$game = mysqli_fetch_assoc($query);

if (!$game) {
    header("Location: admin_game_list.php");
    exit();
}

// Proses penghapusan jika dikonfirmasi
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    // Hapus gambar terkait jika ada
    if (!empty($game['image_url']) && $game['image_url'] != 'default.jpg') {
        $image_path = "../../assets/images/games/" . $game['image_url'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // Hapus dari database
    $delete_query = "DELETE FROM games WHERE id_game = '$id'";

    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['success'] = "Game <strong>" . htmlspecialchars($game['title']) . "</strong> berhasil dihapus!";
        header("Location: admin_game_list.php");
        exit();
    } else {
        $_SESSION['error'] = "Gagal menghapus game: " . mysqli_error($conn);
        header("Location: admin_game_list.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Game | Midnight Play Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .warning-box {
            background: linear-gradient(135deg, #7f1d1d, #dc2626);
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
            border: 2px solid #f87171;
        }

        .game-info {
            background: #2a2f3a;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc2626, #7f1d1d);
            color: white;
            padding: 12px 40px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #b91c1c, #7f1d1d);
        }

        .btn-secondary {
            background: #4b5563;
            color: white;
            padding: 12px 40px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary:hover {
            background: #374151;
        }

        /* Modal Styles */
        .confirm-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .modal-content {
            background: #171a21;
            width: 450px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #dc2626;
        }

        .modal-header {
            background: linear-gradient(135deg, #7f1d1d, #dc2626);
            padding: 20px;
            text-align: center;
        }

        .modal-header-icon {
            font-size: 48px;
            color: #fff;
            margin-bottom: 10px;
        }

        .modal-title {
            color: white;
            margin: 0;
            font-size: 22px;
        }

        .modal-body {
            padding: 25px;
            color: #c7d5e0;
        }

        .modal-message {
            text-align: center;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .game-details-modal {
            background: #1b1f27;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
            border: 1px solid #2a475e;
        }

        .game-details-modal p {
            margin: 8px 0;
            font-size: 14px;
        }

        .game-details-modal strong {
            color: #66c0f4;
        }

        .modal-final-warning {
            background: rgba(220, 38, 38, 0.1);
            border: 1px solid #dc2626;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
            text-align: center;
        }

        .modal-final-warning strong {
            color: #fbbf24;
            font-size: 16px;
        }

        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .modal-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            min-width: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .modal-cancel {
            background: #4b5563;
            color: white;
        }

        .modal-cancel:hover {
            background: #374151;
        }

        .modal-confirm {
            background: linear-gradient(135deg, #dc2626, #7f1d1d);
            color: white;
        }

        .modal-confirm:hover {
            background: linear-gradient(135deg, #b91c1c, #7f1d1d);
        }
    </style>
</head>

<body>
    <!-- Final Confirmation Modal -->
    <div id="finalConfirmModal" class="confirm-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-icon">
                    <i class="fas fa-skull-crossbones"></i>
                </div>
                <h2 class="modal-title">KONFIRMASI AKHIR</h2>
            </div>
            <div class="modal-body">
                <div class="modal-message">
                    <p style="font-size: 16px; margin-bottom: 10px;">
                        <strong>PERINGATAN TERTINGGI!</strong>
                    </p>
                    <p>Anda akan menghapus game ini secara <strong>PERMANEN</strong>.</p>
                </div>

                <div class="game-details-modal">
                    <p><strong>Game:</strong> <?= htmlspecialchars($game['title']); ?></p>
                    <p><strong>ID:</strong> #<?= $game['id_game']; ?></p>
                    <p><strong>Genre:</strong> <?= htmlspecialchars($game['genre']); ?></p>
                    <p><strong>Harga:</strong> Rp <?= number_format($game['price'], 0, ',', '.'); ?></p>
                </div>

                <div class="modal-final-warning">
                    <p><strong>⚠️ TINDAKAN INI TIDAK DAPAT DIBATALKAN! ⚠️</strong></p>
                    <p style="margin-top: 8px; font-size: 14px;">
                        Semua data akan hilang selamanya dan tidak dapat dikembalikan.
                    </p>
                </div>

                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-cancel" onclick="closeFinalModal()">
                        <i class="fas fa-times"></i> BATAL
                    </button>
                    <a href="admin_game_delete.php?id=<?= $id; ?>&confirm=yes" class="modal-btn modal-confirm"
                        id="finalDeleteBtn">
                        <i class="fas fa-trash"></i> HAPUS
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Navbar -->
        <div class="navbar">
            <h1><i class="fas fa-gamepad"></i> Hapus Game</h1>
            <div class="nav-right">
                <span class="nav-user"><i class="fas fa-user-shield"></i>
                    <?= $_SESSION['username'] ?? 'Admin'; ?>
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
                        <h2><i class="fas fa-trash"></i> Konfirmasi Penghapusan</h2>
                        <p style="color: #8f98a0; margin-top: 5px;">Tindakan ini tidak dapat dibatalkan</p>
                    </div>
                    <a href="admin_game_list.php" class="button" style="background: #8f98a0;">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>

                <!-- Warning Box -->
                <div class="warning-box">
                    <div style="font-size: 48px; margin-bottom: 20px; color: #fca5a5;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h2 style="color: white; margin-bottom: 10px;">PERINGATAN!</h2>
                    <p style="color: #fecaca; font-size: 18px; line-height: 1.6;">
                        Anda akan menghapus game ini secara permanen.<br>
                        Semua data termasuk gambar akan dihapus dan tidak dapat dikembalikan.
                    </p>
                </div>

                <!-- Game Info -->
                <div class="game-info">
                    <h3 style="color: #66c0f4; margin-bottom: 15px;">
                        <i class="fas fa-info-circle"></i> Informasi Game yang Akan Dihapus
                    </h3>

                    <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                        <?php if (!empty($game['image_url'])): ?>
                            <img src="../../assets/images/games/<?= $game['image_url']; ?>" alt="<?= $game['title']; ?>"
                                style="width: 80px; height: 50px; object-fit: cover; border-radius: 5px; border: 2px solid #3d4452;">
                        <?php endif; ?>
                        <div>
                            <h4 style="color: #c7d5e0; margin: 0 0 5px 0;">
                                <?= htmlspecialchars($game['title']); ?>
                            </h4>
                            <p style="color: #8f98a0; margin: 0; font-size: 14px;">
                                ID: #
                                <?= $game['id_game']; ?> |
                                Genre:
                                <?= htmlspecialchars($game['genre']); ?>
                            </p>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div>
                            <small style="color: #8f98a0;">Harga</small>
                            <p style="color: #c7d5e0; margin: 5px 0; font-size: 18px;">
                                Rp
                                <?= number_format($game['price'], 0, ',', '.'); ?>
                            </p>
                        </div>
                        <div>
                            <small style="color: #8f98a0;">Status</small>
                            <p style="color: #c7d5e0; margin: 5px 0;">
                                <span style="background: <?= $game['status'] == 'active' ? '#10b981' : '#ef4444'; ?>; 
                                padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                                    <?= ucfirst($game['status']); ?>
                                </span>
                            </p>
                        </div>
                        <div>
                            <small style="color: #8f98a0;">Dibuat Pada</small>
                            <p style="color: #c7d5e0; margin: 5px 0;">
                                <?= date('d/m/Y', strtotime($game['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                    <?php if (!empty($game['description'])): ?>
                        <div style="margin-top: 15px;">
                            <small style="color: #8f98a0;">Deskripsi</small>
                            <p style="color: #c7d5e0; margin: 5px 0; font-size: 14px; line-height: 1.5;">
                                <?= htmlspecialchars(substr($game['description'], 0, 200)); ?>
                                <?= strlen($game['description']) > 200 ? '...' : ''; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Dampak Penghapusan -->
                <div style="background: #1b2838; border-radius: 8px; padding: 20px; margin: 20px 0;">
                    <h4 style="color: #f87171; margin-bottom: 15px;">
                        <i class="fas fa-exclamation-circle"></i> Dampak Penghapusan
                    </h4>
                    <ul style="color: #c7d5e0; padding-left: 20px; margin: 0;">
                        <li>Game akan hilang dari katalog</li>
                        <li>Gambar game akan dihapus dari server</li>
                        <li>Transaksi yang terkait dengan game ini tetap tersimpan</li>
                        <li>User tidak dapat membeli game ini lagi</li>
                        <li>Tindakan ini <strong>PERMANEN</strong> dan tidak dapat dibatalkan</li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="button-group">
                    <button type="button" class="btn-danger" onclick="showFinalModal()">
                        <i class="fas fa-trash"></i> YA, HAPUS PERMANEN
                    </button>

                    <a href="admin_game_list.php" class="btn-secondary">
                        <i class="fas fa-times"></i> BATALKAN
                    </a>
                </div>

                <!-- Catatan -->
                <div style="text-align: center; margin-top: 30px; color: #8f98a0; font-size: 14px;">
                    <p>
                        <i class="fas fa-lightbulb"></i>
                        <strong>Saran:</strong> Jika ingin menyembunyikan game tanpa menghapus,
                        ubah status menjadi "inactive" di halaman edit.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Final Confirmation Modal Functions
        function showFinalModal() {
            console.log('Showing final confirmation modal');
            document.getElementById('finalConfirmModal').style.display = 'flex';
        }

        function closeFinalModal() {
            console.log('Closing final confirmation modal');
            document.getElementById('finalConfirmModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('finalConfirmModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeFinalModal();
            }
        });

        // Close modal with ESC key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeFinalModal();
            }
        });

        // Disable the final delete button after click to prevent double click
        document.getElementById('finalDeleteBtn').addEventListener('click', function (e) {
            console.log('Final delete button clicked');
            // Disable button and show loading
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> MENGHAPUS...';
            this.style.opacity = '0.7';
            this.style.cursor = 'wait';
            this.disabled = true;

            // Allow the click to proceed
            return true;
        });

        // Optional: Add countdown timer for extra confirmation
        let deleteTimer = null;
        let timeLeft = 5;

        function startDeleteCountdown() {
            const deleteBtn = document.getElementById('finalDeleteBtn');
            const originalText = deleteBtn.innerHTML;

            deleteBtn.innerHTML = '<i class="fas fa-clock"></i> TUNGGU (' + timeLeft + ')';
            deleteBtn.disabled = true;

            deleteTimer = setInterval(function () {
                timeLeft--;
                deleteBtn.innerHTML = '<i class="fas fa-clock"></i> TUNGGU (' + timeLeft + ')';

                if (timeLeft <= 0) {
                    clearInterval(deleteTimer);
                    deleteBtn.innerHTML = originalText;
                    deleteBtn.disabled = false;
                }
            }, 1000);
        }

        // Uncomment to enable countdown timer
        // document.addEventListener('DOMContentLoaded', function() {
        //     startDeleteCountdown();
        // });
    </script>

    <script src="../../assets/js/script.js"></script>
</body>

</html>