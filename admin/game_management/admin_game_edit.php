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

// Ambil data game berdasarkan ID
$query = mysqli_query($conn, "SELECT * FROM games WHERE id_game = '$id'");
$game = mysqli_fetch_assoc($query);

if (!$game) {
    header("Location: admin_game_list.php");
    exit();
}

// Proses update jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $genre = mysqli_real_escape_string($conn, $_POST['genre']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Handle file upload jika ada
    if (!empty($_FILES['image_file']['name'])) {
        $target_dir = "../../assets/images/games/";
        $image_name = time() . '_' . basename($_FILES["image_file"]["name"]);
        $target_file = $target_dir . $image_name;

        // Coba upload file
        if (move_uploaded_file($_FILES["image_file"]["tmp_name"], $target_file)) {
            // Hapus gambar lama jika ada dan bukan default
            if (!empty($game['image_url']) && $game['image_url'] != 'default.jpg') {
                $old_file = $target_dir . $game['image_url'];
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            $image_url = $image_name;
        }
    }

    $update_query = "UPDATE games SET 
                    title = '$title',
                    genre = '$genre',
                    price = '$price',
                    description = '$description',
                    image_url = '$image_url',
                    status = '$status'
                    WHERE id_game = '$id'";

    if (mysqli_query($conn, $update_query)) {
        $_SESSION['success'] = "Game berhasil diperbarui!";

        // =========== HANDLE SCREENSHOTS ===========
        $screenshot_dir = "../../assets/images/screenshots/";

        // Buat folder jika belum ada
        if (!is_dir($screenshot_dir)) {
            mkdir($screenshot_dir, 0777, true);
        }

        // 1. Handle deletion of existing screenshots
        if (!empty($_POST['delete_screenshots'])) {
            foreach ($_POST['delete_screenshots'] as $screenshot_id) {
                $safe_id = mysqli_real_escape_string($conn, $screenshot_id);

                // Get filename to delete
                $file_query = mysqli_query($conn, "SELECT image_url FROM game_screenshots WHERE id_screenshot = '$safe_id'");
                if ($file_data = mysqli_fetch_assoc($file_query)) {
                    $old_file = $screenshot_dir . $file_data['image_url'];
                    if (file_exists($old_file) && is_file($old_file)) {
                        unlink($old_file);
                    }
                }

                // Delete from database
                mysqli_query($conn, "DELETE FROM game_screenshots WHERE id_screenshot = '$safe_id'");
            }
        }

        // 2. Update existing screenshots captions and order
        if (!empty($_POST['screenshot_captions'])) {
            foreach ($_POST['screenshot_captions'] as $screenshot_id => $caption) {
                $safe_id = mysqli_real_escape_string($conn, $screenshot_id);
                $safe_caption = mysqli_real_escape_string($conn, $caption);
                $order = isset($_POST['screenshot_order'][$screenshot_id]) ? intval($_POST['screenshot_order'][$screenshot_id]) : 1;

                $update_screenshot = "UPDATE game_screenshots SET 
                                    caption = '$safe_caption',
                                    display_order = '$order'
                                    WHERE id_screenshot = '$safe_id'";
                mysqli_query($conn, $update_screenshot);
            }
        }

        // 3. Upload new screenshots
        if (!empty($_FILES['screenshots']['name'][0])) {
            // Count existing screenshots
            $count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM game_screenshots WHERE id_game = '$id'");
            $count_data = mysqli_fetch_assoc($count_query);
            $total_existing = $count_data['total'];

            $uploaded_count = 0;
            $max_screenshots = 5 - $total_existing;

            foreach ($_FILES['screenshots']['tmp_name'] as $key => $tmp_name) {
                if ($uploaded_count >= $max_screenshots)
                    break;

                if ($_FILES['screenshots']['error'][$key] == UPLOAD_ERR_OK) {
                    // Validate file size (5MB max)
                    if ($_FILES['screenshots']['size'][$key] > 5 * 1024 * 1024) {
                        $_SESSION['error'] = "Beberapa file terlalu besar (maks 5MB per screenshot)";
                        continue;
                    }

                    // Validate file type
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $file_type = mime_content_type($tmp_name);
                    if (!in_array($file_type, $allowed_types)) {
                        $_SESSION['error'] = "Format file tidak didukung: " . $_FILES['screenshots']['name'][$key];
                        continue;
                    }

                    // Generate unique filename
                    $file_extension = pathinfo($_FILES['screenshots']['name'][$key], PATHINFO_EXTENSION);
                    $filename = $id . '_screenshot_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $target_file = $screenshot_dir . $filename;

                    // Upload file
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        // Get caption for this file
                        $caption = isset($_POST['new_screenshot_captions'][$key]) ? mysqli_real_escape_string($conn, $_POST['new_screenshot_captions'][$key]) : '';

                        // Calculate order
                        $order = $total_existing + $uploaded_count + 1;

                        // Insert into database
                        $insert_query = "INSERT INTO game_screenshots (id_game, image_url, caption, display_order) 
                                       VALUES ('$id', '$filename', '$caption', '$order')";

                        if (mysqli_query($conn, $insert_query)) {
                            $uploaded_count++;
                        }
                    }
                }
            }

            if ($uploaded_count > 0) {
                $_SESSION['success'] .= " $uploaded_count screenshot(s) berhasil diupload.";
            }
        }
        // =========== END HANDLE SCREENSHOTS ===========

        header("Location: admin_game_list.php");
        exit();
    } else {
        $_SESSION['error'] = "Gagal memperbarui game: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Game | Midnight Play Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .screenshot-preview {
            background: #2a2f3a;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #3d4452;
        }

        .screenshot-preview img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .delete-checkbox {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .caption-input {
            width: 100%;
            padding: 8px;
            background: #1b2838;
            border: 1px solid #3d4452;
            border-radius: 4px;
            color: #c7d5e0;
            font-size: 13px;
            margin-top: 8px;
        }

        .order-input {
            width: 60px;
            padding: 3px;
            background: #1b2838;
            border: 1px solid #3d4452;
            border-radius: 3px;
            color: #c7d5e0;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Navbar -->
        <div class="navbar">
            <h1><i class="fas fa-gamepad"></i> Edit Game</h1>
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
                        <h2><i class="fas fa-edit"></i> Edit Game</h2>
                        <p style="color: #8f98a0; margin-top: 5px;">ID Game: #
                            <?= $game['id_game']; ?>
                        </p>
                    </div>
                    <a href="admin_game_list.php" class="button" style="background: #8f98a0;">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>

                <!-- Pesan Sukses/Error -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div style="background: #10b981; color: white; padding: 10px 15px; border-radius: 5px; margin: 15px 0;">
                        <i class="fas fa-check-circle"></i>
                        <?= $_SESSION['success']; ?>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div style="background: #ef4444; color: white; padding: 10px 15px; border-radius: 5px; margin: 15px 0;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= $_SESSION['error']; ?>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Form Edit -->
                <div style="background: #171a21; border-radius: 8px; padding: 30px; margin-top: 20px;">
                    <form method="POST" enctype="multipart/form-data" action="">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #c7d5e0;">
                                    <i class="fas fa-heading"></i> Judul Game
                                </label>
                                <input type="text" name="title" value="<?= htmlspecialchars($game['title']); ?>" style="width: 100%; padding: 12px; background: #2a2f3a; border: 1px solid #3d4452; 
                                    border-radius: 5px; color: #c7d5e0;" required>
                            </div>

                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #c7d5e0;">
                                    <i class="fas fa-tags"></i> Genre
                                </label>
                                <input type="text" name="genre" value="<?= htmlspecialchars($game['genre']); ?>"
                                    placeholder="Contoh: Simulation, Sports" style="width: 100%; padding: 12px; background: #2a2f3a; border: 1px solid #3d4452; 
                                    border-radius: 5px; color: #c7d5e0;" required>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #c7d5e0;">
                                    <i class="fas fa-dollar-sign"></i> Harga (Rp)
                                </label>
                                <input type="number" name="price" value="<?= $game['price']; ?>" min="0" style="width: 100%; padding: 12px; background: #2a2f3a; border: 1px solid #3d4452; 
                                    border-radius: 5px; color: #c7d5e0;" required>
                            </div>

                            <div>
                                <label style="display: block; margin-bottom: 8px; color: #c7d5e0;">
                                    <i class="fas fa-toggle-on"></i> Status
                                </label>
                                <select name="status" style="width: 100%; padding: 12px; background: #2a2f3a; border: 1px solid #3d4452; 
                                    border-radius: 5px; color: #c7d5e0;">
                                    <option value="active" <?= $game['status'] == 'active' ? 'selected' : ''; ?>>Active
                                    </option>
                                    <option value="inactive" <?= $game['status'] == 'inactive' ? 'selected' : ''; ?>>
                                        Inactive</option>
                                </select>
                            </div>
                        </div>

                        <!-- Gambar Saat Ini -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #c7d5e0;">
                                <i class="fas fa-image"></i> Gambar Saat Ini
                            </label>
                            <?php if (!empty($game['image_url'])): ?>
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <img src="../../assets/images/games/<?= $game['image_url']; ?>" alt="Current Image"
                                        style="width: 100px; height: 60px; object-fit: cover; border-radius: 5px; border: 2px solid #3d4452;">
                                    <div>
                                        <p style="color: #8f98a0; margin: 0; font-size: 14px;">
                                            <?= $game['image_url']; ?>
                                        </p>
                                        <p style="color: #66c0f4; margin: 5px 0 0 0; font-size: 12px;">
                                            <i class="fas fa-info-circle"></i> Biarkan kosong untuk menggunakan gambar ini
                                        </p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p style="color: #8f98a0; padding: 10px; background: #2a2f3a; border-radius: 5px;">
                                    <i class="fas fa-exclamation-triangle"></i> Belum ada gambar yang diunggah
                                </p>
                            <?php endif; ?>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #c7d5e0;">
                                <i class="fas fa-upload"></i> Upload Gambar Baru (Opsional)
                            </label>
                            <input type="file" name="image_file" accept="image/*" style="width: 100%; padding: 10px; background: #2a2f3a; border: 1px dashed #3d4452; 
                                border-radius: 5px; color: #c7d5e0;">
                            <p style="color: #8f98a0; font-size: 12px; margin-top: 5px;">
                                <i class="fas fa-info-circle"></i> Ukuran maksimal: 2MB. Format: JPG, PNG, GIF
                            </p>
                        </div>

                        <!-- SECTION SCREENSHOTS -->
                        <div
                            style="margin: 30px 0; padding: 25px; background: #1b2838; border-radius: 8px; border: 1px solid #2a2f3a;">
                            <h3
                                style="color: #66c0f4; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-images"></i> Game Screenshots
                            </h3>

                            <!-- Existing Screenshots -->
                            <div id="existingScreenshots" style="margin-bottom: 25px;">
                                <h4 style="color: #c7d5e0; margin-bottom: 15px; font-size: 16px;">
                                    <i class="fas fa-photo-video"></i> Current Screenshots
                                </h4>

                                <?php
                                // Query untuk mengambil screenshots game ini
                                $screenshots_query = mysqli_query($conn, "
                                    SELECT * FROM game_screenshots 
                                    WHERE id_game = '$id' 
                                    ORDER BY display_order
                                ");

                                if (mysqli_num_rows($screenshots_query) > 0):
                                    ?>
                                    <div
                                        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                                        <?php while ($screenshot = mysqli_fetch_assoc($screenshots_query)): ?>
                                            <div class="screenshot-preview">
                                                <div style="position: relative;">
                                                    <img src="../../assets/images/screenshots/<?= $screenshot['image_url']; ?>"
                                                        alt="Screenshot">
                                                    <div class="delete-checkbox">
                                                        <input type="checkbox" name="delete_screenshots[]"
                                                            value="<?= $screenshot['id_screenshot']; ?>"
                                                            style="margin: 0; cursor: pointer;">
                                                    </div>
                                                </div>
                                                <div>
                                                    <input type="text"
                                                        name="screenshot_captions[<?= $screenshot['id_screenshot']; ?>]"
                                                        placeholder="Caption (optional)"
                                                        value="<?= htmlspecialchars($screenshot['caption'] ?? ''); ?>"
                                                        class="caption-input">
                                                </div>
                                                <div
                                                    style="margin-top: 8px; display: flex; justify-content: space-between; align-items: center;">
                                                    <span style="color: #8f98a0; font-size: 11px;">
                                                        Order:
                                                        <input type="number"
                                                            name="screenshot_order[<?= $screenshot['id_screenshot']; ?>]"
                                                            value="<?= $screenshot['display_order']; ?>" min="1"
                                                            class="order-input">
                                                    </span>
                                                    <label style="color: #ef4444; font-size: 12px; cursor: pointer;">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div
                                        style="text-align: center; padding: 20px; background: #2a2f3a; border-radius: 5px;">
                                        <i class="fas fa-camera"
                                            style="font-size: 32px; color: #66c0f4; margin-bottom: 10px;"></i>
                                        <p style="color: #8f98a0;">No screenshots added yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Add New Screenshots -->
                            <div>
                                <h4 style="color: #c7d5e0; margin-bottom: 15px; font-size: 16px;">
                                    <i class="fas fa-plus-circle"></i> Add New Screenshots
                                </h4>

                                <div style="margin-bottom: 15px;">
                                    <input type="file" name="screenshots[]" id="screenshotsInput" multiple
                                        accept="image/*" style="display: none;">

                                    <button type="button" onclick="document.getElementById('screenshotsInput').click()"
                                        style="background: #2a2f3a; color: #66c0f4; padding: 10px 20px; 
                                               border: 2px dashed #3d4452; border-radius: 5px; cursor: pointer;
                                               display: flex; align-items: center; gap: 10px; font-size: 14px;">
                                        <i class="fas fa-folder-open"></i> Choose Files
                                    </button>

                                    <span id="screenshotsCount"
                                        style="margin-left: 15px; color: #8f98a0; font-size: 14px;">
                                        No files chosen
                                    </span>
                                </div>

                                <!-- Preview Area -->
                                <div id="screenshotsPreview" style="display: none; margin-top: 20px;">
                                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;"
                                        id="previewGrid"></div>
                                </div>

                                <p style="color: #8f98a0; font-size: 12px; margin-top: 10px;">
                                    <i class="fas fa-info-circle"></i> Max 5 screenshots. Format: JPG, PNG, GIF. Max 5MB
                                    each.
                                </p>
                            </div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: #c7d5e0;">
                                <i class="fas fa-align-left"></i> Deskripsi
                            </label>
                            <textarea name="description" rows="5"
                                style="width: 100%; padding: 12px; background: #2a2f3a; border: 1px solid #3d4452; 
                                border-radius: 5px; color: #c7d5e0; resize: vertical;"><?= htmlspecialchars($game['description']); ?></textarea>
                        </div>

                        <!-- Hidden field untuk image_url jika tidak diubah -->
                        <input type="hidden" name="image_url" value="<?= $game['image_url']; ?>">

                        <div style="display: flex; gap: 15px; margin-top: 30px;">
                            <button type="submit"
                                style="background: linear-gradient(135deg, #10b981, #059669); color: white; 
                                padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                            <a href="admin_game_list.php" style="background: #2a2f3a; color: #c7d5e0; padding: 12px 30px; 
                                border-radius: 5px; text-decoration: none; border: 1px solid #3d4452;">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Info Tambahan -->
                <div style="background: #1b2838; border-radius: 8px; padding: 15px; margin-top: 20px;">
                    <h4 style="color: #66c0f4; margin-bottom: 10px;">
                        <i class="fas fa-info-circle"></i> Informasi Game
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                        <div>
                            <small style="color: #8f98a0;">Dibuat Pada</small>
                            <p style="color: #c7d5e0; margin: 5px 0;">
                                <?= date('d F Y H:i', strtotime($game['created_at'])); ?>
                            </p>
                        </div>
                        <div>
                            <small style="color: #8f98a0;">ID Game</small>
                            <p style="color: #c7d5e0; margin: 5px 0;">#
                                <?= $game['id_game']; ?>
                            </p>
                        </div>
                        <div>
                            <small style="color: #8f98a0;">Status Saat Ini</small>
                            <p style="color: #c7d5e0; margin: 5px 0;">
                                <span style="background: <?= $game['status'] == 'active' ? '#10b981' : '#ef4444'; ?>; 
                                padding: 2px 10px; border-radius: 12px; font-size: 12px;">
                                    <?= ucfirst($game['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function previewScreenshots(input) {
            const preview = document.getElementById('screenshotsPreview');
            const grid = document.getElementById('previewGrid');
            const count = document.getElementById('screenshotsCount');

            // Clear previous preview
            grid.innerHTML = '';

            if (input.files && input.files.length > 0) {
                preview.style.display = 'block';
                count.textContent = input.files.length + ' file(s) selected';

                // Limit to 5 files
                const files = Array.from(input.files).slice(0, 5);

                files.forEach((file, index) => {
                    const reader = new FileReader();

                    reader.onload = function (e) {
                        const div = document.createElement('div');
                        div.className = 'screenshot-preview';

                        div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <div>
                            <input type="text" name="new_screenshot_captions[]" placeholder="Caption (optional)" 
                                   class="caption-input">
                        </div>
                        <div style="margin-top: 8px; color: #8f98a0; font-size: 12px;">
                            ${file.name} (${Math.round(file.size / 1024)} KB)
                        </div>
                    `;

                        grid.appendChild(div);
                    }

                    reader.readAsDataURL(file);
                });
            } else {
                preview.style.display = 'none';
                count.textContent = 'No files chosen';
            }
        }

        // Attach event listener
        document.getElementById('screenshotsInput').addEventListener('change', function () {
            previewScreenshots(this);
        });

        // Make delete labels clickable
        document.querySelectorAll('label[style*="color: #ef4444"]').forEach(label => {
            label.addEventListener('click', function () {
                const checkbox = this.parentElement.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
            });
        });
    </script>
</body>

</html>