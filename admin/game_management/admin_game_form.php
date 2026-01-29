<?php
session_start();
include "../../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../index.php");
    exit();
}

$id = $_GET['id'] ?? '';
$aksi = "tambah";

$data = [
    'title' => '',
    'genre' => '',
    'price' => '',
    'description' => '',
    'image_url' => '',
    'status' => 'active'
];

if ($id != '') {
    $q = mysqli_query($conn, "SELECT * FROM games WHERE id_game=$id");
    if (mysqli_num_rows($q) > 0) {
        $data = mysqli_fetch_assoc($q);
    }
    $aksi = "update";
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Admin | Game Form</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        /* Style untuk form game */
        .game-form-wrapper {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }

        .game-form-box {
            background: #171a21;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }

        .game-form-box h2 {
            color: #66c0f4;
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
        }

        .input-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .input-wrapper input,
        .input-wrapper textarea,
        .input-wrapper select {
            width: 100%;
            padding: 12px 12px 12px 45px;
            background: #2a2f3a;
            border: 1px solid #3d4452;
            border-radius: 6px;
            color: #c7d5e0;
            font-size: 16px;
            transition: all 0.3s;
        }

        .input-wrapper input:focus,
        .input-wrapper textarea:focus,
        .input-wrapper select:focus {
            outline: none;
            border-color: #66c0f4;
            box-shadow: 0 0 0 2px rgba(102, 192, 244, 0.2);
        }

        .input-wrapper textarea {
            min-height: 120px;
            resize: vertical;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #66c0f4;
            font-size: 18px;
        }

        .input-wrapper textarea+.input-icon {
            top: 20px;
            transform: none;
        }

        .input-wrapper label {
            display: block;
            margin-bottom: 8px;
            color: #c7d5e0;
            font-weight: 500;
        }

        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a9fff, #0066cc);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        button[type="submit"]:hover {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #66c0f4;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #1a9fff;
            text-decoration: underline;
        }

        /* Custom file upload styles */
        .file-upload-container {
            margin-bottom: 25px;
        }

        .file-upload-label {
            display: block;
            margin-bottom: 8px;
            color: #c7d5e0;
            font-weight: 500;
        }

        .file-upload-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .custom-file-btn {
            background: #2a2f3a;
            color: #66c0f4;
            padding: 12px 25px;
            border: 2px solid #3d4452;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .custom-file-btn:hover {
            background: #323844;
            border-color: #66c0f4;
        }

        #fileName {
            color: #8f98a0;
            font-size: 14px;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #imagePreview {
            margin-top: 15px;
            padding: 15px;
            background: #2a2f3a;
            border-radius: 6px;
            border: 1px solid #3d4452;
        }

        .preview-label {
            color: #c7d5e0;
            margin-bottom: 8px;
            display: block;
        }

        #preview {
            max-width: 200px;
            max-height: 120px;
            border-radius: 4px;
            border: 2px solid #3d4452;
            object-fit: cover;
        }

        .file-info {
            color: #8f98a0;
            font-size: 12px;
            margin-top: 8px;
        }

        .url-input-wrapper {
            margin-top: 15px;
        }

        .url-input-wrapper input {
            width: 100%;
            padding: 12px;
            background: #2a2f3a;
            border: 1px solid #3d4452;
            border-radius: 6px;
            color: #c7d5e0;
            font-size: 14px;
        }

        .url-input-wrapper input::placeholder {
            color: #666;
        }

        /* Screenshots styles */
        .screenshot-existing-item {
            margin-bottom: 10px;
            padding: 10px;
            background: #2a2f3a;
            border-radius: 5px;
            border: 1px solid #3d4452;
        }

        .screenshot-preview-item {
            background: #2a2f3a;
            border-radius: 5px;
            padding: 10px;
            border: 1px solid #3d4452;
            position: relative;
        }

        .screenshot-remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            border: none;
        }

        .screenshot-remove-btn:hover {
            background: rgba(220, 38, 38, 0.9);
        }
    </style>
</head>

<body>

    <div class="game-form-wrapper">
        <div class="game-form-box">
            <h2><i class="fas fa-gamepad"></i> <?= $id ? "Edit Game" : "Tambah Game Baru"; ?></h2>

            <form method="POST" action="admin_game_controller.php?aksi=<?= $aksi; ?>" enctype="multipart/form-data">
                <input type="hidden" name="id_game" value="<?= $id; ?>">

                <div class="input-wrapper">
                    <input type="text" name="title" placeholder="Game Title"
                        value="<?= htmlspecialchars($data['title']); ?>" required>
                    <i class="fas fa-heading input-icon"></i>
                </div>

                <div class="input-wrapper">
                    <input type="text" name="genre" placeholder="Genre (contoh: Action, Adventure, RPG)"
                        value="<?= htmlspecialchars($data['genre']); ?>" required>
                    <i class="fas fa-tags input-icon"></i>
                </div>

                <div class="input-wrapper">
                    <input type="number" name="price" placeholder="Harga (contoh: 799000)"
                        value="<?= htmlspecialchars($data['price']); ?>" required min="0">
                    <i class="fas fa-dollar-sign input-icon"></i>
                </div>

                <!-- File Upload Section -->
                <div class="file-upload-container">
                    <label class="file-upload-label">
                        <i class="fas fa-image"></i> Gambar Game
                    </label>

                    <div class="file-upload-wrapper">
                        <!-- Hidden file input -->
                        <input type="file" name="image_file" id="imageFile" accept="image/*" style="display: none;"
                            onchange="previewImage(this)">

                        <!-- Custom button -->
                        <button type="button" class="custom-file-btn"
                            onclick="document.getElementById('imageFile').click()">
                            <i class="fas fa-folder-open"></i> Choose File
                        </button>

                        <span id="fileName">No file chosen</span>
                    </div>

                    <!-- Image Preview -->
                    <div id="imagePreview" style="display: none;">
                        <span class="preview-label">Preview:</span>
                        <img id="preview" src="#" alt="Preview">
                    </div>

                    <div class="file-info">
                        <i class="fas fa-info-circle"></i> Ukuran maksimal: 2MB. Format: JPG, PNG, GIF
                    </div>

                    <!-- URL Input -->
                    <div class="url-input-wrapper">
                        <input type="text" name="image_url"
                            placeholder="Atau masukkan URL gambar (contoh: game_image.jpg)"
                            value="<?= htmlspecialchars($data['image_url']); ?>">
                    </div>
                </div>

                <!-- Screenshots Section -->
                <div class="file-upload-container">
                    <label class="file-upload-label">
                        <i class="fas fa-images"></i> Game Screenshots (Max 5 images)
                    </label>

                    <!-- Existing Screenshots -->
                    <div id="existingScreenshots">
                        <?php if ($id != ''):
                            $screenshots_query = mysqli_query($conn, "
                                SELECT * FROM game_screenshots 
                                WHERE id_game = '$id' 
                                ORDER BY display_order
                            ");

                            if (mysqli_num_rows($screenshots_query) > 0):
                                while ($screenshot = mysqli_fetch_assoc($screenshots_query)):
                                    ?>
                                    <div class="screenshot-existing-item">
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                            <img src="../../assets/images/screenshots/<?= $screenshot['image_url']; ?>"
                                                alt="Screenshot"
                                                style="width: 80px; height: 60px; object-fit: cover; border-radius: 3px;">
                                            <div style="flex: 1;">
                                                <input type="text" name="screenshot_captions[<?= $screenshot['id_screenshot']; ?>]"
                                                    placeholder="Caption (optional)"
                                                    value="<?= htmlspecialchars($screenshot['caption'] ?? ''); ?>"
                                                    style="width: 100%; padding: 8px; background: #1b2838; border: 1px solid #3d4452; border-radius: 4px; color: #c7d5e0; margin-bottom: 5px;">
                                                <label
                                                    style="color: #8f98a0; font-size: 12px; display: flex; align-items: center; gap: 5px;">
                                                    <input type="checkbox" name="delete_screenshots[]"
                                                        value="<?= $screenshot['id_screenshot']; ?>">
                                                    Delete this screenshot
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                endwhile;
                            endif;
                        endif;
                        ?>
                    </div>

                    <!-- New Screenshots Upload -->
                    <div style="margin-top: 15px;">
                        <input type="file" name="screenshots[]" id="screenshotsInput" multiple accept="image/*"
                            onchange="previewScreenshots(this)" style="display: none;">

                        <button type="button" class="custom-file-btn"
                            onclick="document.getElementById('screenshotsInput').click()">
                            <i class="fas fa-plus-circle"></i> Add Screenshots
                        </button>

                        <span id="screenshotsCount" style="margin-left: 15px; color: #8f98a0;">
                            No files chosen
                        </span>
                    </div>

                    <!-- New Screenshots Preview -->
                    <div id="screenshotsPreview" style="margin-top: 15px; display: none;">
                        <div id="previewGrid"
                            style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px;">
                        </div>
                    </div>

                    <div class="file-info">
                        <i class="fas fa-info-circle"></i> Max 5 screenshots. Format: JPG, PNG. Max 5MB each.
                    </div>
                </div>

                <div class="input-wrapper">
                    <i class="fas fa-toggle-on input-icon"></i>
                    <select name="status" id="status" required>
                        <option value="active" <?= $data['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?= $data['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="input-wrapper">
                    <textarea name="description" placeholder="Deskripsi game..."
                        rows="5"><?= htmlspecialchars($data['description']); ?></textarea>
                    <i class="fas fa-align-left input-icon"></i>
                </div>

                <button type="submit">
                    <i class="fas fa-save"></i> <?= $id ? "Update Game" : "Simpan Game"; ?>
                </button>
            </form>

            <a href="admin_game_list.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Game
            </a>
        </div>
    </div>

    <script>
        // Function untuk preview gambar utama
        function previewImage(input) {
            const fileName = document.getElementById('fileName');
            const preview = document.getElementById('preview');
            const previewContainer = document.getElementById('imagePreview');

            if (input.files && input.files[0]) {
                const file = input.files[0];
                fileName.textContent = file.name;
                fileName.style.color = '#66c0f4';

                // Validasi ukuran file (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar! Maksimal 2MB.');
                    input.value = '';
                    fileName.textContent = 'No file chosen';
                    fileName.style.color = '#8f98a0';
                    previewContainer.style.display = 'none';
                    return;
                }

                // Validasi tipe file
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                if (!validTypes.includes(file.type)) {
                    alert('Format file tidak didukung! Gunakan JPG, PNG, atau GIF.');
                    input.value = '';
                    fileName.textContent = 'No file chosen';
                    fileName.style.color = '#8f98a0';
                    previewContainer.style.display = 'none';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    previewContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                fileName.textContent = 'No file chosen';
                fileName.style.color = '#8f98a0';
                previewContainer.style.display = 'none';
            }
        }

        // Jika edit mode dan sudah ada gambar URL, tampilkan preview
        window.onload = function () {
            const imageUrl = "<?= $data['image_url'] ?>";
            if (imageUrl && imageUrl.trim() !== '') {
                document.getElementById('fileName').textContent = 'Using URL: ' + imageUrl;
                document.getElementById('fileName').style.color = '#66c0f4';

                const preview = document.getElementById('preview');
                const previewContainer = document.getElementById('imagePreview');

                // Coba load gambar dari local assets
                const localImage = '../../assets/images/games/' + imageUrl;
                preview.onload = function () {
                    previewContainer.style.display = 'block';
                }
                preview.onerror = function () {
                    // Jika gambar tidak ditemukan di local, tampilkan dari URL langsung
                    if (imageUrl.startsWith('http')) {
                        preview.src = imageUrl;
                    } else {
                        previewContainer.style.display = 'none';
                    }
                }

                // Set source untuk preview
                if (imageUrl.startsWith('http')) {
                    preview.src = imageUrl;
                } else {
                    preview.src = localImage;
                }
            }
        }

        // Function untuk preview screenshots baru
        function previewScreenshots(input) {
            const previewContainer = document.getElementById('screenshotsPreview');
            const previewGrid = document.getElementById('previewGrid');
            const countSpan = document.getElementById('screenshotsCount');

            // Clear previous previews
            previewGrid.innerHTML = '';

            if (input.files && input.files.length > 0) {
                const files = Array.from(input.files);

                // Limit to 5 files
                if (files.length > 5) {
                    alert('Maximum 5 screenshots allowed. Only the first 5 will be uploaded.');
                    // Reset files to first 5
                    const dt = new DataTransfer();
                    for (let i = 0; i < 5; i++) {
                        dt.items.add(files[i]);
                    }
                    input.files = dt.files;
                    files.length = 5;
                }

                countSpan.textContent = files.length + ' file(s) selected';
                countSpan.style.color = '#66c0f4';

                files.forEach((file, index) => {
                    // Validate file size (5MB max)
                    if (file.size > 5 * 1024 * 1024) {
                        alert(`File "${file.name}" is too large. Maximum size is 5MB.`);
                        return;
                    }

                    // Validate file type
                    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                    if (!validTypes.includes(file.type)) {
                        alert(`File "${file.name}" is not a valid image type.`);
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'screenshot-preview-item';
                        previewItem.innerHTML = `
                            <img src="${e.target.result}" 
                                 alt="Preview ${index + 1}"
                                 style="width: 100%; height: 100px; object-fit: cover; border-radius: 4px;">
                            <button type="button" class="screenshot-remove-btn" onclick="removeScreenshotPreview(this, ${index})">
                                ×
                            </button>
                            <input type="text" 
                                   name="new_screenshot_captions[]" 
                                   placeholder="Caption (optional)"
                                   style="width: 100%; margin-top: 5px; padding: 5px; 
                                          background: #1b2838; border: 1px solid #3d4452; 
                                          border-radius: 3px; color: #c7d5e0; font-size: 12px;">
                        `;
                        previewGrid.appendChild(previewItem);
                    };
                    reader.readAsDataURL(file);
                });

                previewContainer.style.display = 'block';
            } else {
                countSpan.textContent = 'No files chosen';
                countSpan.style.color = '#8f98a0';
                previewContainer.style.display = 'none';
            }
        }

        // Function untuk remove screenshot preview
        function removeScreenshotPreview(button, index) {
            const input = document.getElementById('screenshotsInput');
            const dt = new DataTransfer();
            const files = Array.from(input.files);

            // Remove file from files array
            files.splice(index, 1);

            // Update input files
            files.forEach(file => dt.items.add(file));
            input.files = dt.files;

            // Remove preview
            button.closest('.screenshot-preview-item').remove();

            // Update count
            const countSpan = document.getElementById('screenshotsCount');
            const remainingPreviews = document.querySelectorAll('.screenshot-preview-item').length;

            countSpan.textContent = remainingPreviews > 0 ? remainingPreviews + ' file(s) selected' : 'No files chosen';

            if (remainingPreviews === 0) {
                document.getElementById('screenshotsPreview').style.display = 'none';
            }
        }
    </script>
</body>

</html>