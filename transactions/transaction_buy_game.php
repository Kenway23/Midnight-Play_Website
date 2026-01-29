<?php
session_start();
include "../config/database.php";

/* Proteksi login */
if (!isset($_SESSION['login'])) {
    header("Location: /midnightplay_web/auth/auth_login.php");
    exit();
}

$id_user = $_SESSION['id_user'] ?? null;
$id_game = $_GET['id'] ?? '';

/* Validasi input */
if (!$id_game || !is_numeric($id_game)) {
    header("Location: /midnightplay_web/index.php");
    exit();
}

if (isset($_SESSION['success'])) {
    echo '<script>document.addEventListener("DOMContentLoaded", function() { setTimeout(showSuccessModal, 500); });</script>';
}

/* Ambil data game */
$stmt = mysqli_prepare($conn, "SELECT * FROM games WHERE id_game = ?");
mysqli_stmt_bind_param($stmt, "i", $id_game);
mysqli_stmt_execute($stmt);
$game_result = mysqli_stmt_get_result($stmt);
$game = mysqli_fetch_assoc($game_result);

if (!$game) {
    header("Location: /midnightplay_web/index.php");
    exit();
}
mysqli_stmt_close($stmt);

/* Cek apakah user sudah memiliki game ini */
$stmt = mysqli_prepare($conn, "SELECT * FROM library WHERE id_user = ? AND id_game = ?");
mysqli_stmt_bind_param($stmt, "ii", $id_user, $id_game);
mysqli_stmt_execute($stmt);
$check_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($check_result) > 0) {
    $_SESSION['error'] = "You already own this game!";
    header("Location: /midnightplay_web/library/library_user_games.php");
    exit();
}
mysqli_stmt_close($stmt);

/* Cek saldo user */
$stmt = mysqli_prepare($conn, "SELECT wallet_balance FROM users WHERE id_user = ?");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$balance_result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($balance_result);
$user_balance = $user_data['wallet_balance'] ?? 0;

if ($user_balance < $game['price']) {
    $_SESSION['error'] = "Insufficient wallet balance! Please top up your wallet.";
    header("Location: /midnightplay_web/store/store_game_detail.php?id=" . $id_game);
    exit();
}
mysqli_stmt_close($stmt);

/* Format harga */
$price_formatted = number_format($game['price'], 0, ',', '.');
$balance_formatted = number_format($user_balance, 0, ',', '.');
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Purchase - <?= htmlspecialchars($game['title']); ?> | Midnight Play</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            color: #f8fafc;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .payment-container {
            width: 100%;
            max-width: 500px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .payment-header {
            background: linear-gradient(90deg, #7c3aed 0%, #6366f1 100%);
            padding: 30px;
            text-align: center;
        }

        .payment-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .payment-header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .game-info {
            padding: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .game-thumbnail {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            overflow: hidden;
            background: #1e293b;
            flex-shrink: 0;
        }

        .game-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .game-details h2 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #f8fafc;
        }

        .game-details .genre {
            font-size: 14px;
            color: #94a3b8;
            background: rgba(148, 163, 184, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
        }

        .payment-details {
            padding: 30px;
        }

        .amount-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .amount-label {
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .amount-value {
            font-size: 48px;
            font-weight: 700;
            color: #10b981;
            font-family: 'Courier New', monospace;
        }

        .payment-method {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .payment-method i {
            font-size: 24px;
            color: #6366f1;
        }

        .payment-method span {
            font-weight: 500;
        }

        .wallet-balance {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .balance-label {
            color: #94a3b8;
            font-size: 14px;
        }

        .balance-amount {
            color: #f8fafc;
            font-weight: 600;
            font-size: 18px;
            font-family: 'Courier New', monospace;
        }

        .balance-amount.insufficient {
            color: #ef4444;
        }

        .balance-amount.sufficient {
            color: #10b981;
        }

        .benefits-list {
            list-style: none;
            margin-bottom: 30px;
        }

        .benefits-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            color: #cbd5e1;
            font-size: 14px;
        }

        .benefits-list li i {
            color: #10b981;
            font-size: 16px;
        }

        .terms-agreement {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .terms-agreement input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #6366f1;
        }

        .terms-agreement label {
            cursor: pointer;
            font-size: 14px;
            color: #cbd5e1;
            flex: 1;
        }

        .terms-agreement label a {
            color: #6366f1;
            text-decoration: none;
        }

        .terms-agreement label a:hover {
            text-decoration: underline;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .btn {
            padding: 18px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-confirm {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            color: white;
        }

        .btn-confirm:hover {
            background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }

        .btn-confirm:disabled {
            background: #475569;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
            opacity: 0.5;
        }

        .btn-cancel {
            background: rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .security-note {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
            font-size: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .security-note i {
            color: #10b981;
        }

        /* Modal styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-content {
            background: #1e293b;
            border-radius: 20px;
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: modalSlideIn 0.3s ease;
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
            padding: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 28px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #f8fafc;
        }

        .modal-body {
            padding: 30px;
        }

        .modal-footer {
            padding: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            gap: 15px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #7c3aed 100%);
            color: white;
            flex: 1;
            padding: 16px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
            flex: 1;
            padding: 16px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(99, 102, 241, 0.2);
            border-top: 4px solid #6366f1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Error Message */
        .error-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(220, 53, 69, 0.2);
            backdrop-filter: blur(10px);
            color: #f8d7da;
            padding: 15px 25px;
            border-radius: 12px;
            border-left: 4px solid #dc3545;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1001;
            max-width: 500px;
            animation: slideDown 0.3s ease;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        /* Success Modal */
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #10b981, #34d399);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            position: relative;
        }

        .success-icon::before {
            content: '';
            position: absolute;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.2);
            animation: pulse 2s infinite;
        }

        .success-icon i {
            font-size: 48px;
            color: white;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.7;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .payment-container {
                max-width: 100%;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }

            .payment-header h1 {
                font-size: 24px;
            }

            .amount-value {
                font-size: 36px;
            }

            .game-info {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>

<body>

    <!-- Error Message -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>Error:</strong> <?= $_SESSION['error'] ?>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Main Payment Container -->
    <div class="payment-container">
        <div class="payment-header">
            <h1>
                <i class="fas fa-shopping-cart"></i>
                PAYMENT
            </h1>
            <p>Your payment is secured with 256-bit SSL encryption</p>
        </div>

        <div class="game-info">
            <div class="game-thumbnail">
                <?php if (!empty($game['image_url'])): ?>
                    <img src="../assets/images/games/<?= htmlspecialchars($game['image_url']); ?>"
                        alt="<?= htmlspecialchars($game['title']); ?>">
                <?php else: ?>
                    <div
                        style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #7c3aed, #6366f1);">
                        <i class="fas fa-gamepad" style="font-size: 32px; color: white;"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="game-details">
                <h2><?= htmlspecialchars($game['title']); ?></h2>
                <span class="genre"><?= htmlspecialchars($game['genre']); ?></span>
            </div>
        </div>

        <div class="payment-details">
            <div class="amount-section">
                <div class="amount-label">Amount to Pay:</div>
                <div class="amount-value">Rp <?= $price_formatted; ?></div>
            </div>

            <div class="payment-method">
                <i class="fas fa-wallet"></i>
                <span>Payment Method: Midnight Play Wallet</span>
            </div>

            <div class="wallet-balance">
                <span class="balance-label">Your Wallet Balance:</span>
                <span class="balance-amount <?= ($user_balance >= $game['price']) ? 'sufficient' : 'insufficient'; ?>">
                    Rp <?= $balance_formatted; ?>
                </span>
            </div>

            <ul class="benefits-list">
                <li>
                    <i class="fas fa-check-circle"></i>
                    Game will be added to your library immediately
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    Accessible anytime from your library
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    Download and play at your convenience
                </li>
            </ul>

            <form id="purchaseForm" method="POST" action="/midnightplay_web/transactions/transaction_controller.php">
                <input type="hidden" name="id_game" value="<?= $id_game; ?>">
                <input type="hidden" name="payment_method" value="midnight_wallet">
                <input type="hidden" name="agree_terms" id="hiddenAgreeTerms" value="0">

                <div class="terms-agreement">
                    <input type="checkbox" id="agreeTerms" name="agree_terms_checkbox"
                        onchange="updateAgreeTerms(this)">
                    <label for="agreeTerms">
                        I have read and agree to the <a href="#" onclick="showTermsModal()">Terms & Conditions</a>
                    </label>
                </div>

                <div class="action-buttons">
                    <button type="button" onclick="showConfirmModal()" class="btn btn-confirm" id="purchaseBtn"
                        disabled>
                        <i class="fas fa-lock"></i>
                        Confirm & Pay
                    </button>

                    <a href="/midnightplay_web/store/store_game_detail.php?id=<?= $id_game; ?>" class="btn btn-cancel">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>

            <div class="security-note">
                <i class="fas fa-shield-alt"></i>
                Your payment is secured with 256-bit SSL encryption
            </div>
        </div>
    </div>

    <!-- Confirm Purchase Modal -->
    <div id="confirmModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-shopping-cart"></i> Confirm Purchase</h3>
                <button class="modal-close" onclick="closeConfirmModal()">&times;</button>
            </div>
            <div class="modal-body">
                <h4 style="margin-bottom: 20px; color: #f8fafc;">EA SPORTS FC 26</h4>

                <div
                    style="background: rgba(255, 193, 7, 0.1); padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 4px solid #ffc107;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="color: #94a3b8; font-size: 14px;">Amount to Pay:</span>
                        <span
                            style="color: #10b981; font-size: 28px; font-weight: bold; font-family: 'Courier New', monospace;">
                            Rp <?= $price_formatted; ?>
                        </span>
                    </div>
                    <div style="color: #94a3b8; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-wallet"></i> Payment Method: Midnight Play Wallet
                    </div>
                </div>

                <div style="color: #cbd5e1; font-size: 14px; line-height: 1.6;">
                    <p style="margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                        Game will be added to your library immediately
                    </p>
                    <p style="margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                        Accessible anytime from your library
                    </p>
                    <p style="margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                        Download and play at your convenience
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeConfirmModal()">
                    <i class="fas fa-arrow-left"></i> Review Order
                </button>
                <button class="btn-primary" onclick="processPurchase()">
                    <i class="fas fa-lock"></i> Confirm & Pay
                </button>
            </div>
        </div>
    </div>

    <!-- Processing Modal -->
    <div id="processingModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-body" style="text-align: center;">
                <div class="loading-spinner"></div>
                <h4 style="margin-bottom: 15px; color: #f8fafc;">Processing Purchase</h4>
                <p style="color: #94a3b8; margin-bottom: 20px;">
                    Please wait while we process your payment...
                </p>
                <div
                    style="display: flex; align-items: center; justify-content: center; gap: 10px; color: #94a3b8; font-size: 14px;">
                    <i class="fas fa-shield-alt" style="color: #10b981;"></i>
                    Securing your transaction with 256-bit SSL encryption
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-body" style="text-align: center;">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h4 style="color: #10b981; margin-bottom: 15px; font-size: 24px;">Purchase Successful!</h4>
                <p style="color: #cbd5e1; margin-bottom: 25px;">
                    <?= htmlspecialchars($game['title']); ?> has been added to your library.
                </p>

                <div
                    style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 12px; margin-bottom: 30px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="color: #94a3b8;">Transaction ID:</span>
                        <span
                            style="color: #f8fafc; font-family: monospace;">MP<?= date('Ymd') . rand(1000, 9999); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #94a3b8;">Date:</span>
                        <span style="color: #f8fafc;"><?= date('d M Y H:i:s'); ?></span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                    <a href="/midnightplay_web/library/library_user_games.php" class="btn-primary">
                        <i class="fas fa-gamepad"></i> Go to Library
                    </a>
                    <button onclick="closeSuccessModal()" class="btn-secondary">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div id="termsModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-contract"></i> Terms & Conditions</h3>
                <button class="modal-close" onclick="closeTermsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="max-height: 400px; overflow-y: auto; padding-right: 10px;">
                    <h4 style="margin-bottom: 15px; color: #f8fafc;">Purchase Terms</h4>
                    <ul style="color: #cbd5e1; line-height: 1.6; padding-left: 20px;">
                        <li style="margin-bottom: 10px;">All purchases are final and non-refundable</li>
                        <li style="margin-bottom: 10px;">Game license is tied to your account</li>
                        <li style="margin-bottom: 10px;">You must have a valid Midnight Play account</li>
                        <li style="margin-bottom: 10px;">Games are for personal use only</li>
                        <li style="margin-bottom: 10px;">We reserve the right to revoke access for violations</li>
                        <li style="margin-bottom: 10px;">Prices are subject to change without notice</li>
                    </ul>

                    <h4 style="margin-top: 25px; margin-bottom: 15px; color: #f8fafc;">Wallet Terms</h4>
                    <ul style="color: #cbd5e1; line-height: 1.6; padding-left: 20px;">
                        <li style="margin-bottom: 10px;">Wallet funds are non-transferable</li>
                        <li style="margin-bottom: 10px;">No cash refunds for wallet balances</li>
                        <li style="margin-bottom: 10px;">Wallet balance does not expire</li>
                        <li style="margin-bottom: 10px;">Fraudulent transactions will be investigated</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-primary" onclick="closeTermsModal()" style="flex: 1;">
                    I Understand
                </button>
            </div>
        </div>
    </div>

    <script>

        // Initialize
        document.addEventListener('DOMContentLoaded', function () {
            // Handle escape key untuk semua modal
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeAllModals();
                }
            });
        });

        // Update hidden field ketika checkbox dicentang
        function updateAgreeTerms(checkbox) {
            const purchaseBtn = document.getElementById('purchaseBtn');
            const hiddenField = document.getElementById('hiddenAgreeTerms');

            if (checkbox.checked) {
                purchaseBtn.disabled = false;
                purchaseBtn.style.opacity = '1';
                hiddenField.value = '1';
            } else {
                purchaseBtn.disabled = true;
                purchaseBtn.style.opacity = '0.5';
                hiddenField.value = '0';
            }
        }

        // Modal functions
        function showConfirmModal() {
            const modal = document.getElementById('confirmModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeConfirmModal() {
            const modal = document.getElementById('confirmModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function showProcessingModal() {
            const modal = document.getElementById('processingModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeProcessingModal() {
            const modal = document.getElementById('processingModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function showSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function showTermsModal() {
            const modal = document.getElementById('termsModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            return false; // Prevent default link behavior
        }

        function closeTermsModal() {
            const modal = document.getElementById('termsModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function closeAllModals() {
            closeConfirmModal();
            closeProcessingModal();
            closeSuccessModal();
            closeTermsModal();
        }

        // Process purchase
        function processPurchase() {
            // Final validation
            const agreeTerms = document.getElementById('agreeTerms').checked;
            if (!agreeTerms) {
                alert('Please agree to the Terms & Conditions to proceed with your purchase.');
                closeConfirmModal();
                document.getElementById('agreeTerms').focus();
                return;
            }

            // Show processing modal
            closeConfirmModal();
            showProcessingModal();

            // Submit form
            setTimeout(() => {
                document.getElementById('purchaseForm').submit();
            }, 1500);
        }

        // Close modal ketika klik di luar
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    const modalId = this.id;
                    switch (modalId) {
                        case 'confirmModal':
                            closeConfirmModal();
                            break;
                        case 'processingModal':
                            closeProcessingModal();
                            break;
                        case 'successModal':
                            closeSuccessModal();
                            break;
                        case 'termsModal':
                            closeTermsModal();
                            break;
                    }
                }
            });
        });

        // Auto-show success modal jika ada parameter success di URL
        <?php if (isset($_SESSION['success'])): ?>
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(showSuccessModal, 500);
            });
        <?php endif; ?>
    </script>

</body>

</html>