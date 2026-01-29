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
    header("Location: admin_transaction_report.php?error=ID transaksi tidak valid");
    exit();
}

$transaction_id = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil data transaksi utama - PERBAIKAN QUERY
$transaction_query = mysqli_query($conn, "
    SELECT t.*, u.username
    FROM transactions t
    JOIN users u ON t.id_user = u.id_user
    WHERE t.id_transaction = '$transaction_id'
");

if (mysqli_num_rows($transaction_query) == 0) {
    header("Location: admin_transaction_report.php?error=Transaksi tidak ditemukan");
    exit();
}

$transaction = mysqli_fetch_assoc($transaction_query);

// Ambil detail item transaksi
$items_query = mysqli_query($conn, "
    SELECT td.*, g.title, g.title, g.description, g.image_url, g.price as unit_price
    FROM transaction_details td
    JOIN games g ON td.id_game = g.id_game
    WHERE td.id_transaction = '$transaction_id'
    ORDER BY td.id_detail
");

// Hitung statistik
$items_count = mysqli_num_rows($items_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi #<?= $transaction['id_transaction']; ?> | Midnight Play Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <!-- Navbar -->
        <div class="navbar">
            <h1><i class="fas fa-receipt"></i> Detail Transaksi</h1>
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
                    <a href="../game_management/game_list.php" class="sidebar-item">
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
                    <a href="sales_report.php" class="sidebar-item">
                        <i class="fas fa-chart-line"></i>
                        Analisis Penjualan
                    </a>
                </div>
            </div>

            <!-- Content Area -->
            <div style="flex: 1; margin-left: 20px; padding: 10px;">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <a href="admin_transaction_report.php" style="color: #66c0f4; text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Kembali ke Laporan Transaksi
                    </a>
                </div>

                <!-- Header -->
                <div style="text-align: center; margin-bottom: 30px;">
                    <div
                        style="width: 80px; height: 80px; background: linear-gradient(135deg, #10b981, #34d399); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <i class="fas fa-receipt" style="color: #fff; font-size: 32px;"></i>
                    </div>

                    <h2><i class="fas fa-file-invoice"></i> Invoice #<?= $transaction['id_transaction']; ?></h2>
                    <p style="color: #8f98a0; margin-top: 10px;">
                        Transaksi pada <?= date('d F Y H:i', strtotime($transaction['transaction_date'])); ?>
                    </p>
                </div>

                <!-- Transaction Summary -->
                <div style="background: #171a21; border-radius: 10px; padding: 25px; margin-bottom: 30px;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                        <div>
                            <h3 style="color: #66c0f4; margin-bottom: 15px;">
                                <i class="fas fa-info-circle"></i> Informasi Transaksi
                            </h3>
                            <div
                                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                                <div>
                                    <div style="color: #8f98a0; font-size: 13px; margin-bottom: 5px;">
                                        <i class="fas fa-hashtag"></i> ID Transaksi
                                    </div>
                                    <div style="color: #fff; font-size: 18px; font-weight: bold;">
                                        #<?= $transaction['id_transaction']; ?>
                                    </div>
                                </div>

                                <div>
                                    <div style="color: #8f98a0; font-size: 13px; margin-bottom: 5px;">
                                        <i class="far fa-calendar"></i> Tanggal & Waktu
                                    </div>
                                    <div style="color: #fff; font-size: 16px;">
                                        <?= date('d/m/Y', strtotime($transaction['transaction_date'])); ?>
                                        <span style="color: #8f98a0; margin-left: 10px;">
                                            <?= date('H:i', strtotime($transaction['transaction_date'])); ?>
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <div style="color: #8f98a0; font-size: 13px; margin-bottom: 5px;">
                                        <i class="fas fa-shopping-cart"></i> Jumlah Item
                                    </div>
                                    <div style="color: #fff; font-size: 18px; font-weight: bold;">
                                        <?= $items_count; ?> item
                                    </div>
                                </div>

                                <div>
                                    <div style="color: #8f98a0; font-size: 13px; margin-bottom: 5px;">
                                        <i class="fas fa-money-bill-wave"></i> Total Transaksi
                                    </div>
                                    <div style="color: #10b981; font-size: 24px; font-weight: bold;">
                                        Rp <?= number_format($transaction['total_price'], 0, ',', '.'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <span
                                style="background: #10b981; color: #fff; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: bold;">
                                <i class="fas fa-check-circle"></i> SELESAI
                            </span>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div style="background: rgba(102, 192, 244, 0.1); border-radius: 8px; padding: 20px;">
                        <h4 style="color: #66c0f4; margin-bottom: 15px;">
                            <i class="fas fa-user"></i> Informasi Customer
                        </h4>
                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <div>
                                <div style="color: #8f98a0; font-size: 13px; margin-bottom: 5px;">
                                    Username
                                </div>
                                <div style="color: #fff; font-size: 16px; font-weight: bold;">
                                    <i class="fas fa-user-circle"></i>
                                    <?= htmlspecialchars($transaction['username']); ?>
                                </div>
                            </div>

                            <div>
                                <div style="color: #8f98a0; font-size: 13px; margin-bottom: 5px;">
                                    ID User
                                </div>
                                <div style="color: #fff; font-size: 16px;">
                                    #<?= $transaction['id_user']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items List -->
                <div style="background: #171a21; border-radius: 10px; padding: 25px; margin-bottom: 30px;">
                    <h3 style="color: #66c0f4; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-gamepad"></i> Daftar Game yang Dibeli
                        <span style="color: #8f98a0; font-size: 14px; margin-left: 10px;">
                            (<?= $items_count; ?> game)
                        </span>
                    </h3>

                    <?php if ($items_count > 0): ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th>Game</th>
                                        <th style="width: 120px; text-align: center;">Harga Satuan</th>
                                        <th style="width: 140px; text-align: right;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    mysqli_data_seek($items_query, 0);
                                    $counter = 1;
                                    $total_calculated = 0;
                                    while ($item = mysqli_fetch_assoc($items_query)):
                                        $subtotal = $item['unit_price'] * $item['quantity'];
                                        $total_calculated += $subtotal;
                                        ?>
                                        <tr>
                                            <td style="padding: 15px; text-align: center; border-bottom: 1px solid #2a2f3a;">
                                                <span
                                                    style="background: #2a2f3a; color: #8f98a0; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px;">
                                                    <?= $counter; ?>
                                                </span>
                                            </td>
                                            <td style="padding: 15px; border-bottom: 1px solid #2a2f3a;">
                                                <div style="display: flex; align-items: center; gap: 15px;">
                                                    <?php if (!empty($item['image_url'])): ?>
                                                        <img src="../../assets/images/games/<?= $item['image_url']; ?>"
                                                            alt="<?= $item['title']; ?>"
                                                            style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                                                    <?php else: ?>
                                                        <div
                                                            style="width: 60px; height: 40px; background: #2a2f3a; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas fa-gamepad" style="color: #8f98a0;"></i>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div>
                                                        <div style="font-weight: bold; color: #fff; margin-bottom: 5px;">
                                                            <?= htmlspecialchars($item['title']); ?>
                                                        </div>
                                                        <div style="color: #8f98a0; font-size: 13px;">
                                                            <?= htmlspecialchars($item['title']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td
                                                style="padding: 15px; text-align: center; border-bottom: 1px solid #2a2f3a; color: #8f98a0;">
                                                Rp <?= number_format($item['unit_price'], 0, ',', '.'); ?>
                                            </td>
                                            <td
                                                style="padding: 15px; text-align: right; border-bottom: 1px solid #2a2f3a; color: #10b981; font-weight: bold;">
                                                Rp <?= number_format($subtotal, 0, ',', '.'); ?>
                                            </td>
                                        </tr>
                                        <?php
                                        $counter++;
                                    endwhile;
                                    ?>

                                    <!-- Total Row -->
                                    <tr>
                                        <td colspan="3"
                                            style="padding: 20px 15px; text-align: right; font-weight: bold; color: #fff;">
                                            TOTAL:
                                        </td>
                                        <td
                                            style="padding: 20px 15px; text-align: right; font-size: 18px; font-weight: bold; color: #10b981;">
                                            Rp <?= number_format($transaction['total_price'], 0, ',', '.'); ?>
                                        </td>
                                    </tr>

                                    <?php if ($total_calculated != $transaction['total_price']): ?>
                                        <tr>
                                            <td colspan="5"
                                                style="padding: 10px 15px; text-align: center; color: #f59e0b; font-size: 12px;">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                Note: Terdapat perbedaan antara total kalkulasi (Rp
                                                <?= number_format($total_calculated, 0, ',', '.'); ?>)
                                                dan total transaksi. Mungkin ada diskon atau penyesuaian.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #8f98a0;">
                            <i class="fas fa-exclamation-circle"
                                style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                            Tidak ada item dalam transaksi ini
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div style="background: #1b2838; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
                    <h4 style="color: #66c0f4; margin-bottom: 15px;">
                        <i class="fas fa-cogs"></i> Aksi
                    </h4>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <a href="../../invoices/invoice_user_detail.php?id=<?= $transaction['id_transaction']; ?>"
                            class="btn-edit" target="_blank"
                            style="text-align: center; padding: 15px; text-decoration: none;">
                            <i class="fas fa-eye"></i> Lihat Invoice User
                        </a>

                        <a href="transaction_pdf_single.php?id=<?= $transaction['id_transaction']; ?>" class="btn"
                            style="background: #ef4444; text-align: center; padding: 15px; text-decoration: none;"
                            target="_blank">
                            <i class="fas fa-file-pdf"></i> Download PDF
                        </a>

                        <a href="admin_transaction_report.php" class="btn"
                            style="text-align: center; padding: 15px; text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                        </a>
                    </div>
                </div>

                <!-- Additional Information -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <!-- Transaction Notes -->
                    <div style="background: #171a21; border-radius: 8px; padding: 20px;">
                        <h4 style="color: #66c0f4; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-sticky-note"></i> Catatan Transaksi
                        </h4>
                        <div style="color: #8f98a0; font-size: 14px; line-height: 1.6;">
                            <p><strong>Status:</strong> Transaksi telah berhasil diproses dan diselesaikan.</p>
                            <p><strong>Pembayaran:</strong> Pembayaran diterima melalui sistem Midnight Play.</p>
                            <p><strong>Lisensi:</strong> Game yang dibeli telah ditambahkan ke library user.</p>
                            <p><strong>Support:</strong> User dapat mengakses game selamanya.</p>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div style="background: #171a21; border-radius: 8px; padding: 20px;">
                        <h4 style="color: #66c0f4; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-chart-bar"></i> Statistik Singkat
                        </h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            <div
                                style="background: rgba(102, 192, 244, 0.1); padding: 12px; border-radius: 6px; text-align: center;">
                                <div style="color: #66c0f4; font-size: 20px; font-weight: bold;">
                                    <?= $items_count; ?>
                                </div>
                                <div style="color: #8f98a0; font-size: 12px;">
                                    Jumlah Game
                                </div>
                            </div>

                            <div
                                style="background: rgba(16, 185, 129, 0.1); padding: 12px; border-radius: 6px; text-align: center;">
                                <div style="color: #10b981; font-size: 20px; font-weight: bold;">
                                    Rp <?= number_format($transaction['total_price'], 0, ',', '.'); ?>
                                </div>
                                <div style="color: #8f98a0; font-size: 12px;">
                                    Nilai Transaksi
                                </div>
                            </div>

                            <div
                                style="background: rgba(245, 158, 11, 0.1); padding: 12px; border-radius: 6px; text-align: center;">
                                <div style="color: #f59e0b; font-size: 20px; font-weight: bold;">
                                    <?= date('H:i', strtotime($transaction['transaction_date'])); ?>
                                </div>
                                <div style="color: #8f98a0; font-size: 12px;">
                                    Waktu Transaksi
                                </div>
                            </div>

                            <div
                                style="background: rgba(139, 92, 246, 0.1); padding: 12px; border-radius: 6px; text-align: center;">
                                <div style="color: #8b5cf6; font-size: 20px; font-weight: bold;">
                                    #<?= $transaction['id_transaction']; ?>
                                </div>
                                <div style="color: #8f98a0; font-size: 12px;">
                                    ID Unik
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Navigation -->
                <div style="margin-top: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <a href="admin_transaction_report.php" class="back-link">
                            <i class="fas fa-arrow-left"></i> Kembali ke Laporan
                        </a>
                    </div>

                    <div style="color: #8f98a0; font-size: 14px;">
                        <i class="fas fa-info-circle"></i>
                        Transaksi #<?= $transaction['id_transaction']; ?> •
                        <?= $items_count; ?> item •
                        Rp <?= number_format($transaction['total_price'], 0, ',', '.'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; <?= date('Y'); ?> Midnight Play Store. All rights reserved.</p>
        </div>
    </div>

    <script src="../../assets/js/script.js"></script>

    <script>
        // Print functionality
        function printInvoice() {
            window.open('transaction_pdf_single.php?id=<?= $transaction['id_transaction']; ?>', '_blank');
        }

        // Copy transaction ID
        function copyTransactionID() {
            const transactionID = '<?= $transaction['id_transaction']; ?>';
            navigator.clipboard.writeText(transactionID).then(() => {
                alert('ID Transaksi berhasil disalin: ' + transactionID);
            });
        }
    </script>
</body>

</html>