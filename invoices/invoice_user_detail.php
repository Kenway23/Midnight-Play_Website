<?php
session_start();
include "../config/database.php";

if (!isset($_SESSION['login'])) {
    header("Location: /auth/auth_login.php");
    exit();
}

$transaction_id = $_GET['id'] ?? '';
$user_id = $_SESSION['id_user'];

if (!$transaction_id || !is_numeric($transaction_id)) {
    header("Location: /index.php");
    exit();
}

// Ambil data transaksi - FIX: Gunakan prepared statement
$stmt = mysqli_prepare($conn, "
    SELECT t.*, td.*, g.title, g.genre, g.price
    FROM transactions t
    JOIN transaction_details td ON t.id_transaction = td.id_transaction
    JOIN games g ON td.id_game = g.id_game
    WHERE t.id_transaction = ? 
    AND t.id_user = ?
");
mysqli_stmt_bind_param($stmt, "ii", $transaction_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$transaction = mysqli_fetch_assoc($result);

if (!$transaction) {
    $_SESSION['error'] = "Transaction not found.";
    header("Location: /index.php");
    exit();
}
mysqli_stmt_close($stmt);

// Ambil semua item dalam transaksi (jika ada lebih dari 1)
$stmt_details = mysqli_prepare($conn, "
    SELECT td.*, g.title, g.genre, g.price
    FROM transaction_details td
    JOIN games g ON td.id_game = g.id_game
    WHERE td.id_transaction = ?
    ORDER BY g.title
");
mysqli_stmt_bind_param($stmt_details, "i", $transaction_id);
mysqli_stmt_execute($stmt_details);
$details_result = mysqli_stmt_get_result($stmt_details);
$items_count = mysqli_num_rows($details_result);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= $transaction_id; ?> | Midnight Play</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .invoice-container {
            max-width: 900px;
            margin: 50px auto;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .invoice-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #66c0f4, #10b981);
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eef2f7;
        }
        
        .invoice-header-left h1 {
            color: #2d3748;
            margin: 0;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .invoice-header-left p {
            color: #718096;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        
        .invoice-header-right {
            text-align: right;
        }
        
        .invoice-number {
            font-size: 18px;
            font-weight: bold;
            color: #66c0f4;
            margin-bottom: 5px;
        }
        
        .invoice-status {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .transaction-info {
            background: #f8fafc;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            color: #4a5568;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            color: #1a202c;
            font-size: 15px;
            font-weight: 500;
        }
        
        .games-section {
            margin-bottom: 30px;
        }
        
        .games-section h3 {
            color: #2d3748;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eef2f7;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .games-list {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        
        .game-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.3s;
        }
        
        .game-item:hover {
            background: #f8fafc;
        }
        
        .game-item:last-child {
            border-bottom: none;
        }
        
        .game-info h4 {
            margin: 0 0 8px 0;
            color: #2d3748;
            font-size: 16px;
        }
        
        .game-info p {
            margin: 0;
            color: #718096;
            font-size: 14px;
        }
        
        .game-price {
            text-align: right;
        }
        
        .game-price .amount {
            font-size: 18px;
            font-weight: bold;
            color: #10b981;
        }
        
        .game-price .quantity {
            color: #4a5568;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .total-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 25px;
            border-radius: 10px;
            color: white;
            margin-bottom: 30px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .total-label {
            font-size: 16px;
        }
        
        .total-value {
            font-size: 16px;
            font-weight: 600;
        }
        
        .grand-total {
            font-size: 28px;
            font-weight: bold;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            min-width: 180px;
        }
        
        .btn-primary {
            background: #66c0f4;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4299e1;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 192, 244, 0.3);
        }
        
        .btn-secondary {
            background: #10b981;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #0da271;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }
        
        .btn-tertiary {
            background: #6c757d;
            color: white;
        }
        
        .btn-tertiary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }
        
        .btn-quaternary {
            background: #8b5cf6;
            color: white;
        }
        
        .btn-quaternary:hover {
            background: #7c3aed;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);
        }
        
        .footer-message {
            text-align: center;
            color: #718096;
            font-size: 14px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .store-info {
            text-align: center;
            color: #4a5568;
            font-size: 12px;
            margin-top: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .print-only {
            display: none;
        }
        
        /* Print Styles */
        @media print {
            body {
                background: white !important;
                padding: 0;
            }
            
            .invoice-container {
                box-shadow: none !important;
                margin: 0 !important;
                border-radius: 0 !important;
                padding: 20px !important;
            }
            
            .action-buttons,
            .btn,
            .footer-message .no-print {
                display: none !important;
            }
            
            .print-only {
                display: block !important;
            }
            
            .total-section {
                background: #f8fafc !important;
                color: #1a202c !important;
            }
            
            .total-section .grand-total {
                border-top: 2px solid #e2e8f0 !important;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .invoice-container {
                padding: 25px;
                margin: 20px auto;
            }
            
            .invoice-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .invoice-header-right {
                text-align: left;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .game-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .game-price {
                text-align: left;
                width: 100%;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="invoice-header-left">
                <h1><i class="fas fa-receipt"></i> Invoice</h1>
                <p>Thank you for your purchase!</p>
            </div>
            <div class="invoice-header-right">
                <div class="invoice-number">#INV-<?= str_pad($transaction_id, 6, '0', STR_PAD_LEFT); ?></div>
                <span class="invoice-status"><i class="fas fa-check-circle"></i> COMPLETED</span>
            </div>
        </div>

        <!-- Transaction Information -->
        <div class="transaction-info">
            <h3 style="color: #66c0f4; margin-top: 0; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-info-circle"></i> Transaction Details
            </h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Transaction ID</div>
                    <div class="info-value">#TRX-<?= str_pad($transaction_id, 6, '0', STR_PAD_LEFT); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Transaction Date</div>
                    <div class="info-value"><?= date('F d, Y H:i:s', strtotime($transaction['transaction_date'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value"><?= htmlspecialchars($transaction['payment_method'] ?? 'Digital Payment'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span style="color: #10b981; font-weight: bold;">PAID & COMPLETED</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Games Purchased -->
        <div class="games-section">
            <h3><i class="fas fa-gamepad"></i> Games Purchased (<?= $items_count; ?> item<?= $items_count > 1 ? 's' : ''; ?>)</h3>
            <div class="games-list">
                <?php
                mysqli_data_seek($details_result, 0);
                $total_items_price = 0;
                while ($item = mysqli_fetch_assoc($details_result)):
                    $subtotal = $item['price'] * ($item['quantity'] ?? 1);
                    $total_items_price += $subtotal;
                ?>
                    <div class="game-item">
                        <div class="game-info">
                            <h4><?= htmlspecialchars($item['title']); ?></h4>
                            <p>Genre: <?= htmlspecialchars($item['genre']); ?></p>
                        </div>
                        <div class="game-price">
                            <div class="amount">Rp <?= number_format($item['price'], 0, ',', '.'); ?></div>
                            <?php if (($item['quantity'] ?? 1) > 1): ?>
                                <div class="quantity">Quantity: <?= $item['quantity']; ?> × Rp <?= number_format($item['price'], 0, ',', '.'); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Total Section -->
        <div class="total-section">
            <div class="total-row">
                <span class="total-label">Subtotal:</span>
                <span class="total-value">Rp <?= number_format($total_items_price, 0, ',', '.'); ?></span>
            </div>
            <div class="total-row">
                <span class="total-label">Tax (0%):</span>
                <span class="total-value">Rp 0</span>
            </div>
            <div class="total-row grand-total">
                <span>GRAND TOTAL:</span>
                <span>Rp <?= number_format($transaction['total_price'], 0, ',', '.'); ?></span>
            </div>
        </div>

        <!-- Store Info -->
        <div class="store-info">
            <p><strong>Midnight Play Store</strong> | Jl. Digital No. 123, Jakarta | Telp: (021) 1234-5678</p>
            <p class="print-only">Invoice generated on: <?= date('d/m/Y H:i'); ?></p>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Invoice
            </button>
            <a href="generate_pdf_user.php?id=<?= $transaction_id; ?>" class="btn btn-secondary" target="_blank">
                <i class="fas fa-file-pdf"></i> Export to PDF
            </a>
            <a href="/library/library_user_games.php" class="btn btn-tertiary">
                <i class="fas fa-gamepad"></i> Go to Library
            </a>
            <a href="/index.php" class="btn btn-quaternary">
                <i class="fas fa-store"></i> Back to Store
            </a>
        </div>

        <!-- Footer Message -->
        <div class="footer-message">
            <p>Thank you for your purchase! The game(s) have been added to your library and are accessible forever.</p>
            <p class="no-print">Need help? Contact us at support@midnightplay.com</p>
        </div>
    </div>

    <script>
        // Print optimization
        document.addEventListener('DOMContentLoaded', function() {
            // Auto print if URL has print parameter
            if (window.location.search.includes('print=true')) {
                window.print();
            }
            
            // Add print event listener
            document.querySelector('.btn-primary').addEventListener('click', function() {
                window.print();
            });
            
            // Smooth hover effects
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Add confirmation for PDF export
            const pdfButton = document.querySelector('.btn-secondary');
            if (pdfButton) {
                pdfButton.addEventListener('click', function(e) {
                    // Optional: Add confirmation message
                    console.log('Exporting to PDF...');
                    // The link will naturally open in new tab
                });
            }
        });
        
        // Print dialog event listeners
        window.addEventListener('beforeprint', function() {
            console.log('Printing invoice...');
        });
        
        window.addEventListener('afterprint', function() {
            console.log('Print completed');
        });
    </script>
</body>
</html>