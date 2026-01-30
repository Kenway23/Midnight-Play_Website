<?php
session_start();
include "../config/database.php";

if (!isset($_SESSION['login'])) {
    header("Location: /midnightplay_web/auth/auth_login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// Ambil data user
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id_user = ?");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($stmt);

// Hitung statistik user
$stats_query = mysqli_query($conn, "
    SELECT 
        (SELECT COUNT(*) FROM `library` WHERE id_user = $id_user) as total_games,
        (SELECT COUNT(*) FROM transactions WHERE id_user = $id_user) as total_purchases,
        (SELECT SUM(total_price) FROM transactions WHERE id_user = $id_user) as total_spent
");
$stats = mysqli_fetch_assoc($stats_query);

// Format data
$join_date = date('F d, Y', strtotime($stats['join_date'] ?? $user['created_at'] ?? 'now'));
$total_spent_formatted = number_format($stats['total_spent'] ?? 0, 0, ',', '.');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Profile | Midnight Play</title>

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

        /* Profile specific styles */
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #2a475e, #1b2838);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #66c0f4;
            border: 4px solid #66c0f4;
            margin-bottom: 20px;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #1a1f2e, #2a2f3a);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid #3d4452;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #66c0f4;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #66c0f4, #10b981);
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: 15px;
            color: #66c0f4;
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #c7d5e0;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
        }

        .stat-label {
            color: #8f98a0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .profile-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 30px 0;
        }

        @media (max-width: 992px) {
            .profile-content {
                grid-template-columns: 1fr;
            }
        }

        .profile-card {
            background: #171a21;
            border-radius: 15px;
            padding: 30px;
            border: 1px solid #2a475e;
            position: relative;
            overflow: hidden;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #66c0f4, transparent);
        }

        .card-title {
            color: #66c0f4;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 2px solid #2a475e;
        }

        .info-grid {
            display: grid;
            gap: 20px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(42, 71, 94, 0.5);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #8f98a0;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-value {
            color: #c7d5e0;
            font-weight: 500;
            font-size: 16px;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-admin {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .badge-user {
            background: rgba(102, 192, 244, 0.15);
            color: #66c0f4;
            border: 1px solid rgba(102, 192, 244, 0.3);
        }

        .badge-premium {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.15), rgba(255, 193, 7, 0.05));
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .security-status {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .security-status.good {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .security-status.warning {
            background: rgba(255, 193, 7, 0.1);
            border-color: rgba(255, 193, 7, 0.3);
        }

        .achievement-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .achievement-item {
            background: #2a2f3a;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid #3d4452;
        }

        .achievement-item:hover {
            transform: translateY(-5px);
            border-color: #66c0f4;
        }

        .achievement-icon {
            font-size: 32px;
            margin-bottom: 10px;
            color: #66c0f4;
        }

        .achievement-title {
            color: #c7d5e0;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .achievement-desc {
            color: #8f98a0;
            font-size: 12px;
        }

        .achievement-item.locked {
            opacity: 0.5;
            filter: grayscale(100%);
        }

        .achievement-item.locked .achievement-icon {
            color: #8f98a0;
        }

        .recent-activity {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid rgba(42, 71, 94, 0.5);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background: #2a2f3a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #66c0f4;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            color: #c7d5e0;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .activity-time {
            color: #8f98a0;
            font-size: 13px;
        }

        .empty-activity {
            text-align: center;
            padding: 40px 20px;
            color: #8f98a0;
        }

        .profile-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .profile-stats {
                grid-template-columns: 1fr;
            }

            .profile-actions {
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
                <a href="/midnightplay_web/auth/auth_login.php" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i> Login
                </a>
            <?php } ?>
        </div>
    </nav>

    <div class="epic-layout">

        <!-- SIDEBAR -->
        <aside class="epic-sidebar">
            <div class="sidebar-section fade-in">
                <a href="/midnightplay_web/index.php" class="sidebar-item">
                    <i class="fa-solid fa-store"></i>
                    <span>Store</span>
                </a>

                <a href="/midnightplay_web/library/library_user_games.php" class="sidebar-item">
                    <i class="fa-solid fa-gamepad"></i>
                    <span>Library</span>
                </a>

                <a href="/midnightplay_web/invoices/invoice_user_list.php" class="sidebar-item">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Purchases</span>
                </a>

                <a href="user_profile.php" class="sidebar-item active">
                    <i class="fa-solid fa-user"></i>
                    <span>Profile</span>
                </a>

            </div>

            <!-- Quick Profile Stats -->
            <div style="margin-top: 30px; padding: 20px; background: rgba(102, 192, 244, 0.1); border-radius: 12px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <div style="
                        width: 50px;
                        height: 50px;
                        background: linear-gradient(135deg, #2a475e, #1b2838);
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 20px;
                        color: #66c0f4;
                    ">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div style="color: #66c0f4; font-weight: 500;">
                            <?= htmlspecialchars($user['username']); ?>
                        </div>
                        <div style="color: #8f98a0; font-size: 12px;">
                            Member since
                            <?= date('M Y', strtotime($join_date)); ?>
                        </div>
                    </div>
                </div>

                <div style="color: #8f98a0; font-size: 13px; margin-top: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span>Games Owned:</span>
                        <span style="color: #c7d5e0; font-weight: 500;">
                            <?= $stats['total_games'] ?? 0; ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Total Spent:</span>
                        <span style="color: #10b981; font-weight: 500;">Rp
                            <?= $total_spent_formatted; ?>
                        </span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- CONTENT -->
        <main class="epic-content fade-in">
            <!-- Header -->
            <div class="profile-header">
                <div>
                    <h1><i class="fa-solid fa-user"></i> My Profile</h1>
                    <p style="color: #8f98a0; margin-top: 5px;">
                        Manage your account information and preferences
                    </p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="profile-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <div class="stat-value">
                        <?= $stats['total_games'] ?? 0; ?>
                    </div>
                    <div class="stat-label">Games Owned</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-value">
                        <?= $stats['total_purchases'] ?? 0; ?>
                    </div>
                    <div class="stat-label">Total Purchases</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-value">Rp
                        <?= $total_spent_formatted; ?>
                    </div>
                    <div class="stat-label">Total Spent</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-value">
                        <?= $join_date; ?>
                    </div>
                    <div class="stat-label">Member Since</div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="profile-content">
                <!-- Personal Information -->
                <div class="profile-card">
                    <h3 class="card-title">
                        <i class="fas fa-user-circle"></i>
                        Personal Information
                    </h3>

                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-user"></i>
                                Username
                            </div>
                            <div class="info-value">
                                <?= htmlspecialchars($user['username']); ?>
                                <span class="badge <?= ($user['role'] == 'admin') ? 'badge-admin' : 'badge-user'; ?>">
                                    <?= strtoupper($user['role']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-envelope"></i>
                                Email
                            </div>
                            <div class="info-value">
                                <?= htmlspecialchars($user['email'] ?? 'Not set'); ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-id-card"></i>
                                Full Name
                            </div>
                            <div class="info-value">
                                <?= htmlspecialchars($user['full_name'] ?? 'Not set'); ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-birthday-cake"></i>
                                Date of Birth
                            </div>
                            <div class="info-value">
                                <?= !empty($user['date_of_birth']) ? date('F d, Y', strtotime($user['date_of_birth'])) : 'Not set'; ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-phone"></i>
                                Phone Number
                            </div>
                            <div class="info-value">
                                <?= htmlspecialchars($user['phone_number'] ?? 'Not set'); ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-map-marker-alt"></i>
                                Location
                            </div>
                            <div class="info-value">
                                <?= htmlspecialchars($user['location'] ?? 'Not set'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Security -->
                <div class="profile-card">
                    <h3 class="card-title">
                        <i class="fas fa-shield-alt"></i>
                        Account Security
                    </h3>

                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-key"></i>
                                Account Status
                            </div>
                            <div class="info-value" style="color: #10b981;">
                                <i class="fas fa-check-circle"></i> Active
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-calendar-check"></i>
                                Last Login
                            </div>
                            <div class="info-value">
                                <?= date('F d, Y H:i', strtotime($user['last_login'] ?? 'Never')); ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-calendar-plus"></i>
                                Account Created
                            </div>
                            <div class="info-value">
                                <?= date('F d, Y', strtotime($user['created_at'] ?? 'Unknown')); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Security Status -->
                    <div
                        class="security-status <?= (empty($user['email']) || empty($user['phone_number'])) ? 'warning' : 'good'; ?>">
                        <i class="fas fa-<?= (empty($user['email']) || empty($user['phone_number'])) ? 'exclamation-triangle' : 'check-circle'; ?>"
                            style="color: <?= (empty($user['email']) || empty($user['phone_number'])) ? '#ffc107' : '#10b981'; ?>;">
                        </i>
                        <div>
                            <div style="color: #c7d5e0; font-weight: 500;">
                                <?= (empty($user['email']) || empty($user['phone_number'])) ? 'Security Level: Medium' : 'Security Level: Strong'; ?>
                            </div>
                            <div style="color: #8f98a0; font-size: 13px;">
                                <?= (empty($user['email']) || empty($user['phone_number']))
                                    ? 'Add email and phone for better security'
                                    : 'Your account is well protected'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- JavaScript -->
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
        // Toggle password visibility (for future use)
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('currentPassword');
            const toggleIcon = document.querySelector('.toggle-password i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        // Copy user ID to clipboard
        function copyUserId() {
            const userId = '<?= $id_user; ?>';
            navigator.clipboard.writeText(userId).then(() => {
                // Show notification
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background: #10b981;
                    color: white;
                    padding: 12px 20px;
                    border-radius: 8px;
                    z-index: 1000;
                    animation: slideIn 0.3s ease-out;
                    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
                `;
                notification.innerHTML = '<i class="fas fa-check-circle"></i> User ID copied to clipboard!';
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease-out';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            });
        }

        // Add CSS for notifications
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            @keyframes slideOut {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(20px);
                }
            }
        `;
        document.head.appendChild(style);

        // Export data functionality
        function exportUserData() {
            if (confirm('Export your personal data?\nThis will generate a JSON file with your account information.')) {
                const userData = {
                    username: '<?= $user['username']; ?>',
                    email: '<?= $user['email'] ?? ''; ?>',
                    full_name: '<?= $user['full_name'] ?? ''; ?>',
                    join_date: '<?= $join_date; ?>',
                    games_owned: <?= $stats['total_games'] ?? 0; ?>,
                    total_purchases: <?= $stats['total_purchases'] ?? 0; ?>,
                    total_spent: <?= $stats['total_spent'] ?? 0; ?>
                };

                const dataStr = JSON.stringify(userData, null, 2);
                const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);

                const exportFileDefaultName = 'midnightplay_user_data.json';

                const linkElement = document.createElement('a');
                linkElement.setAttribute('href', dataUri);
                linkElement.setAttribute('download', exportFileDefaultName);
                linkElement.click();
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
                <a href="/midnightplay_web/auth/auth_logout.php" class="btn-primary">
                    <i class="fas fa-sign-out-alt"></i> Yes, Logout
                </a>
            </div>
        </div>
    </div>
</body>

</html>