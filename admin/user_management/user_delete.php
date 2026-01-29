<?php
session_start();
include "../../config/database.php";

/* Proteksi admin */
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/auth_login.php");
    exit();
}

// Cek parameter ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: user_list.php?error=ID tidak valid");
    exit();
}

$user_id = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil data user
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id_user = '$user_id'");
if (mysqli_num_rows($user_query) == 0) {
    header("Location: user_list.php?error=User tidak ditemukan");
    exit();
}

$user = mysqli_fetch_assoc($user_query);

// Cek jika mencoba menghapus diri sendiri
if ($user_id == $_SESSION['id_user']) {
    header("Location: user_list.php?error=Tidak bisa menghapus akun sendiri");
    exit();
}

// Proses delete jika konfirmasi
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    // Hapus user
    $delete_query = mysqli_query($conn, "DELETE FROM users WHERE id_user = '$user_id'");

    if ($delete_query) {
        header("Location: user_list.php?success=User " . $user['username'] . " berhasil dihapus");
        exit();
    } else {
        header("Location: user_list.php?error=Gagal menghapus user");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus User | Midnight Play Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
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
            width: 500px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #dc2626;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .modal-header {
            background: linear-gradient(135deg, #7f1d1d, #dc2626);
            padding: 25px;
            text-align: center;
        }

        .modal-header-icon {
            font-size: 48px;
            color: #fff;
            margin-bottom: 15px;
        }

        .modal-title {
            color: white;
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .modal-body {
            padding: 30px;
            color: #c7d5e0;
        }

        .modal-message {
            text-align: center;
            margin-bottom: 20px;
            line-height: 1.6;
            font-size: 16px;
        }

        .user-details-modal {
            background: #1b1f27;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #2a475e;
        }

        .user-details-modal p {
            margin: 10px 0;
            font-size: 14px;
        }

        .user-details-modal strong {
            color: #66c0f4;
        }

        .modal-final-warning {
            background: rgba(220, 38, 38, 0.1);
            border: 1px solid #dc2626;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .modal-final-warning strong {
            color: #fbbf24;
            font-size: 17px;
        }

        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 25px;
        }

        .modal-btn {
            padding: 14px 35px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            min-width: 130px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
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

        /* Button Styles */
        .btn-danger {
            background: linear-gradient(135deg, #dc2626, #7f1d1d);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #b91c1c, #7f1d1d);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 38, 38, 0.3);
        }

        .btn-secondary {
            background: #4b5563;
            color: white;
            padding: 15px 40px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: #374151;
            transform: translateY(-2px);
        }

        .btn-disabled {
            background: #374151;
            color: #9ca3af;
            padding: 15px 40px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            opacity: 0.7;
            cursor: not-allowed;
        }

        .danger-zone {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(220, 38, 38, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(220, 38, 38, 0);
            }
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
                    <p style="font-size: 18px; margin-bottom: 10px; color: #fbbf24;">
                        <strong>PERINGATAN TERTINGGI!</strong>
                    </p>
                    <p>Anda akan menghapus user ini secara <strong>PERMANEN</strong>.</p>
                </div>

                <div class="user-details-modal">
                    <p><strong>Username:</strong> <?= htmlspecialchars($user['username']); ?></p>
                    <p><strong>ID User:</strong> #<?= $user['id_user']; ?></p>
                    <p><strong>Role:</strong> 
                        <?php if ($user['role'] == 'admin'): ?>
                                <span style="color: #3b82f6; font-weight: bold;">Administrator</span>
                        <?php else: ?>
                                <span style="color: #10b981; font-weight: bold;">Customer</span>
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($user['created_at'])): ?>
                            <p><strong>Bergabung:</strong> <?= date('d/m/Y', strtotime($user['created_at'])); ?></p>
                    <?php endif; ?>
                </div>

                <div class="modal-final-warning">
                    <p><strong>⚠️ TINDAKAN INI TIDAK DAPAT DIBATALKAN! ⚠️</strong></p>
                    <p style="margin-top: 10px; font-size: 14px;">
                        Semua data user akan hilang selamanya dan tidak dapat dikembalikan.
                    </p>
                    <p style="margin-top: 5px; font-size: 13px; color: #fca5a5;">
                        <i class="fas fa-exclamation-circle"></i>
                        User tidak akan bisa login lagi ke sistem.
                    </p>
                </div>

                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-cancel" onclick="closeFinalModal()">
                        <i class="fas fa-times"></i> BATAL
                    </button>
                    <a href="user_delete.php?id=<?= $user['id_user']; ?>&confirm=yes" 
                       class="modal-btn modal-confirm"
                       id="finalDeleteBtn"
                       onclick="disableButton()">
                        <i class="fas fa-trash"></i> HAPUS USER
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Navbar -->
        <div class="navbar">
            <h1><i class="fas fa-user-times"></i> Hapus User</h1>
            <div class="nav-right">
                <span class="nav-user"><i class="fas fa-user-shield"></i> <?= $_SESSION['username']; ?></span>
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
                    <a href="../game_management/admin_game_list.php" class="sidebar-item">
                        <i class="fas fa-gamepad"></i>
                        Kelola Game
                    </a>
                    <a href="user_list.php" class="sidebar-item">
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
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <a href="user_list.php" style="color: #66c0f4; text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar User
                    </a>
                </div>

                <!-- Header -->
                <div style="text-align: center; margin-bottom: 30px;">
                    <div
                        style="width: 80px; height: 80px; background: linear-gradient(135deg, #ef4444, #b91c1c); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;"
                        class="danger-zone">
                        <i class="fas fa-exclamation-triangle" style="color: #fff; font-size: 32px;"></i>
                    </div>

                    <h2 style="color: #ef4444;"><i class="fas fa-trash-alt"></i> Konfirmasi Penghapusan</h2>
                    <p style="color: #8f98a0; margin-top: 10px;">Tindakan ini tidak dapat dibatalkan</p>
                </div>

                <!-- Warning Box -->
                <div
                    style="background: rgba(239, 68, 68, 0.1); border: 2px solid #ef4444; border-radius: 10px; padding: 25px; margin-bottom: 30px;">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle" style="color: #ef4444; font-size: 32px;"></i>
                        <div>
                            <h3 style="color: #ef4444; margin: 0;">PERINGATAN!</h3>
                            <p style="color: #c7d5e0; margin-top: 5px;">Anda akan menghapus user secara permanen</p>
                        </div>
                    </div>

                    <div style="background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 6px;">
                        <p style="color: #c7d5e0; margin: 0; line-height: 1.6;">
                            <strong>Semua data user ini akan dihapus secara permanen</strong> dan tidak dapat
                            dikembalikan.
                            Pastikan ini adalah tindakan yang benar-benar diperlukan.
                        </p>
                    </div>
                </div>

                <!-- User Info Card -->
                <div style="background: #171a21; border-radius: 10px; padding: 25px; margin-bottom: 30px;">
                    <h3 style="color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-user"></i> Detail User yang akan Dihapus
                    </h3>

                    <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                        <div style="width: 70px; height: 70px; background: linear-gradient(135deg, 
                            <?= $user['role'] == 'admin' ? '#1e3a8a' : '#065f46'; ?>, 
                            <?= $user['role'] == 'admin' ? '#3b82f6' : '#10b981'; ?>); 
                            border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user" style="color: #fff; font-size: 28px;"></i>
                        </div>

                        <div style="flex: 1;">
                            <div
                                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                <div>
                                    <div style="color: #8f98a0; font-size: 13px; margin-bottom: 5px;">
                                        <i class="fas fa-id-card"></i> ID User
                                    </div>
                                    <div style="color: #fff; font-size: 16px; font-weight: bold;">
                                        #<?= $user['id_user']; ?>
                                    </div>
                                </div>

                                <div>
                                    <div style="color: #8f98a0; font-size: 13px; margin-bottom: 5px;">
                                        <i class="fas fa-user"></i> Username
                                    </div>
                                    <div style="color: #fff; font-size: 16px; font-weight: bold;">
                                        <?= htmlspecialchars($user['username']); ?>
                                    </div>
                                </div>

                                <div>
                                    <div style="color: #8f98a0; font-size: 13px; margin-bottom: 5px;">
                                        <i class="fas fa-user-tag"></i> Role
                                    </div>
                                    <div>
                                        <?php if ($user['role'] == 'admin'): ?>
                                                <span
                                                    style="background: #3b82f6; color: #fff; padding: 4px 12px; border-radius: 12px; font-size: 13px;">
                                                    <i class="fas fa-user-shield"></i> Administrator
                                                </span>
                                        <?php else: ?>
                                                <span
                                                    style="background: #10b981; color: #fff; padding: 4px 12px; border-radius: 12px; font-size: 13px;">
                                                    <i class="fas fa-user"></i> Customer
                                                </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Checks -->
                    <?php
                    // Cek jika user punya transaksi (opsional, jika ada tabel transactions)
                    $has_transactions = false;
                    if (mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'transactions'")) > 0) {
                        $transaction_check = mysqli_query($conn, "SELECT COUNT(*) as total FROM transactions WHERE id_user = '$user_id'");
                        $transaction_count = mysqli_fetch_assoc($transaction_check)['total'];
                        $has_transactions = $transaction_count > 0;
                    }
                    ?>

                    <?php if ($has_transactions): ?>
                            <div
                                style="margin-top: 20px; background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b; padding: 12px; border-radius: 4px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                                    <span style="color: #f59e0b; font-size: 14px;">
                                        <strong>Catatan:</strong> User ini memiliki <?= $transaction_count; ?> transaksi.
                                        Transaksi akan tetap ada di sistem meskipun user dihapus.
                                    </span>
                                </div>
                            </div>
                    <?php endif; ?>
                </div>

                <!-- Confirmation -->
                <div style="background: #1b2838; border-radius: 10px; padding: 25px; text-align: center;" class="danger-zone">
                    <h3 style="color: #ef4444; margin-bottom: 20px;">
                        <i class="fas fa-question-circle"></i> Apakah Anda yakin?
                    </h3>

                    <p style="color: #c7d5e0; margin-bottom: 25px; font-size: 15px; line-height: 1.6;">
                        Anda akan menghapus user <strong
                            style="color: #fff;"><?= htmlspecialchars($user['username']); ?></strong>
                        secara permanen. Tindakan ini <strong style="color: #ef4444;">tidak dapat dibatalkan</strong>.
                    </p>

                    <div style="display: flex; gap: 15px; justify-content: center; margin-bottom: 20px;">
                        <a href="user_list.php" class="btn-secondary">
                            <i class="fas fa-times"></i> BATALKAN
                        </a>
                        <button type="button" class="btn-danger" onclick="showFinalModal()">
                            <i class="fas fa-trash"></i> YA, HAPUS PERMANEN
                        </button>
                    </div>

                    <p style="color: #8f98a0; font-size: 13px; font-style: italic;">
                        <i class="fas fa-lightbulb"></i>
                        Pertimbangkan untuk menonaktifkan user terlebih dahulu daripada menghapusnya.
                    </p>
                </div>

                <!-- Safety Notes -->
                <div
                    style="margin-top: 30px; background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6; padding: 15px; border-radius: 4px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <i class="fas fa-user-shield" style="color: #3b82f6;"></i>
                        <strong style="color: #3b82f6;">Pertimbangan Keamanan</strong>
                    </div>
                    <p style="color: #c7d5e0; font-size: 13px; margin: 0;">
                        Sebelum menghapus user, pastikan:
                        1. User tidak lagi aktif menggunakan sistem
                        2. Tidak ada transaksi yang sedang diproses
                        3. Anda telah membuat backup jika diperlukan
                        4. Ini adalah keputusan final yang disetujui
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Final Confirmation Modal Functions
        function showFinalModal() {
            console.log('Showing final confirmation modal for user deletion');
            document.getElementById('finalConfirmModal').style.display = 'flex';
        }

        function closeFinalModal() {
            console.log('Closing final confirmation modal');
            document.getElementById('finalConfirmModal').style.display = 'none';
        }

        // Disable button after click
        function disableButton() {
            const deleteBtn = document.getElementById('finalDeleteBtn');
            if (deleteBtn) {
                console.log('Disabling delete button');
                deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> MENGHAPUS...';
                deleteBtn.style.opacity = '0.7';
                deleteBtn.style.cursor = 'wait';
                deleteBtn.onclick = function(e) {
                    e.preventDefault();
                    return false;
                };
                
                // Allow the original link to work after a small delay
                setTimeout(() => {
                    window.location.href = deleteBtn.href;
                }, 100);
            }
        }

        // Close modal when clicking outside
        document.getElementById('finalConfirmModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeFinalModal();
            }
        });

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeFinalModal();
            }
        });

        // Optional: Add countdown before enabling delete button
        let deleteEnabled = false;
        function enableDeleteAfterDelay() {
            const deleteBtn = document.querySelector('.btn-danger');
            if (deleteBtn && !deleteEnabled) {
                deleteBtn.disabled = true;
                deleteBtn.innerHTML = '<i class="fas fa-clock"></i> TUNGGU 5 DETIK';
                
                let seconds = 5;
                const countdown = setInterval(() => {
                    seconds--;
                    deleteBtn.innerHTML = `<i class="fas fa-clock"></i> TUNGGU ${seconds} DETIK`;
                    
                    if (seconds <= 0) {
                        clearInterval(countdown);
                        deleteBtn.disabled = false;
                        deleteEnabled = true;
                        deleteBtn.innerHTML = '<i class="fas fa-trash"></i> YA, HAPUS PERMANEN';
                        deleteBtn.classList.add('danger-zone');
                    }
                }, 1000);
            }
        }

        // Uncomment to enable countdown timer
        // document.addEventListener('DOMContentLoaded', function() {
        //     enableDeleteAfterDelay();
        // });
    </script>

    <script src="../../assets/js/script.js"></script>
</body>

</html>