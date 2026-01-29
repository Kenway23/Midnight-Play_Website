<?php
session_start();
include "../../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../index.php");
    exit();
}

$aksi = $_GET['aksi'];

if ($aksi == "tambah") {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $genre = mysqli_real_escape_string($conn, $_POST['genre']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Handle main image upload
    $image_url = '';

    // Jika ada upload file gambar utama
    if (!empty($_FILES['image_file']['name'])) {
        $target_dir = "../../assets/images/games/";
        $image_name = time() . '_' . basename($_FILES["image_file"]["name"]);
        $target_file = $target_dir . $image_name;

        // Validate image file
        $check = getimagesize($_FILES["image_file"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["image_file"]["tmp_name"], $target_file)) {
                $image_url = $image_name;
            }
        }
    }
    // Jika menggunakan URL gambar
    elseif (!empty($_POST['image_url'])) {
        $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
    }

    // Insert game ke database
    $insert_query = "INSERT INTO games (title, genre, price, description, image_url, status, created_at)
                     VALUES ('$title', '$genre', '$price', '$description', '$image_url', '$status', NOW())";

    if (mysqli_query($conn, $insert_query)) {
        // Get the last inserted game ID
        $new_game_id = mysqli_insert_id($conn);

        // Handle screenshots upload for new game
        if (!empty($_FILES['screenshots']['name'][0])) {
            $target_dir = "../../assets/images/screenshots/";

            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            foreach ($_FILES['screenshots']['tmp_name'] as $index => $tmp_name) {
                if ($_FILES['screenshots']['error'][$index] === UPLOAD_ERR_OK) {
                    $original_name = basename($_FILES["screenshots"]["name"][$index]);
                    // Sanitize filename
                    $image_name = time() . '_' . $new_game_id . '_' . $index . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $original_name);
                    $target_file = $target_dir . $image_name;

                    // Validate file
                    $check = getimagesize($tmp_name);

                    if ($check !== false && move_uploaded_file($tmp_name, $target_file)) {
                        $caption = !empty($_POST['new_screenshot_captions'][$index])
                            ? mysqli_real_escape_string($conn, $_POST['new_screenshot_captions'][$index])
                            : '';

                        // Use index as order for new screenshots
                        $display_order = $index + 1;

                        mysqli_query($conn, "
                            INSERT INTO game_screenshots (id_game, image_url, caption, display_order)
                            VALUES ('$new_game_id', '$image_name', '$caption', '$display_order')
                        ");
                    }
                }
            }
        }

        $_SESSION['success'] = "Game berhasil ditambahkan!";
        header("Location: admin_game_list.php");
        exit();
    } else {
        $_SESSION['error'] = "Gagal menambahkan game: " . mysqli_error($conn);
        header("Location: admin_game_form.php");
        exit();
    }

} elseif ($aksi == "update") {
    $id_game = mysqli_real_escape_string($conn, $_POST['id_game']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $genre = mysqli_real_escape_string($conn, $_POST['genre']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Get current game data
    $current_game_query = mysqli_query($conn, "SELECT image_url FROM games WHERE id_game = '$id_game'");
    $current_game = mysqli_fetch_assoc($current_game_query);
    $current_image_url = $current_game['image_url'];

    // Handle main image upload
    $image_url = $current_image_url; // Default to current image

    // Jika ada upload file gambar utama baru
    if (!empty($_FILES['image_file']['name'])) {
        $target_dir = "../../assets/images/games/";
        $image_name = time() . '_' . basename($_FILES["image_file"]["name"]);
        $target_file = $target_dir . $image_name;

        // Validate image file
        $check = getimagesize($_FILES["image_file"]["tmp_name"]);
        if ($check !== false && move_uploaded_file($_FILES["image_file"]["tmp_name"], $target_file)) {
            // Delete old image if it exists and not empty
            if (!empty($current_image_url) && $current_image_url != 'default.jpg' && file_exists($target_dir . $current_image_url)) {
                unlink($target_dir . $current_image_url);
            }
            $image_url = $image_name;
        }
    }
    // Jika menggunakan URL gambar baru (dan tidak upload file)
    elseif (!empty($_POST['image_url']) && empty($_FILES['image_file']['name'])) {
        $new_image_url = mysqli_real_escape_string($conn, $_POST['image_url']);

        // Jika URL berbeda dengan yang sekarang, update
        if ($new_image_url != $current_image_url) {
            // Jika ada gambar lama, hapus
            if (!empty($current_image_url) && $current_image_url != 'default.jpg' && file_exists($target_dir . $current_image_url)) {
                unlink($target_dir . $current_image_url);
            }
            $image_url = $new_image_url;
        }
    }

    $update_query = "UPDATE games SET
                    title = '$title',
                    genre = '$genre',
                    price = '$price',
                    description = '$description',
                    image_url = '$image_url',
                    status = '$status'
                    WHERE id_game = '$id_game'";

    if (mysqli_query($conn, $update_query)) {
        // Handle screenshots

        // 1. Delete selected screenshots
        if (!empty($_POST['delete_screenshots'])) {
            foreach ($_POST['delete_screenshots'] as $screenshot_id) {
                $screenshot_id = mysqli_real_escape_string($conn, $screenshot_id);

                // Get image filename first
                $img_query = mysqli_query($conn, "SELECT image_url FROM game_screenshots WHERE id_screenshot = '$screenshot_id'");
                if ($img_row = mysqli_fetch_assoc($img_query)) {
                    $img_path = "../../assets/images/screenshots/" . $img_row['image_url'];
                    if (file_exists($img_path)) {
                        unlink($img_path);
                    }
                }

                // Delete from database
                mysqli_query($conn, "DELETE FROM game_screenshots WHERE id_screenshot = '$screenshot_id'");
            }
        }

        // 2. Update existing screenshot captions and order
        if (!empty($_POST['screenshot_captions'])) {
            foreach ($_POST['screenshot_captions'] as $screenshot_id => $caption) {
                $screenshot_id = mysqli_real_escape_string($conn, $screenshot_id);
                $caption = mysqli_real_escape_string($conn, $caption);
                $order = !empty($_POST['screenshot_order'][$screenshot_id])
                    ? mysqli_real_escape_string($conn, $_POST['screenshot_order'][$screenshot_id])
                    : 0;

                mysqli_query($conn, "
                    UPDATE game_screenshots 
                    SET caption = '$caption', display_order = '$order' 
                    WHERE id_screenshot = '$screenshot_id'
                ");
            }
        }

        // 3. Upload new screenshots
        if (!empty($_FILES['screenshots']['name'][0])) {
            $target_dir = "../../assets/images/screenshots/";

            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            foreach ($_FILES['screenshots']['tmp_name'] as $index => $tmp_name) {
                if ($_FILES['screenshots']['error'][$index] === UPLOAD_ERR_OK) {
                    $original_name = basename($_FILES["screenshots"]["name"][$index]);
                    $image_name = time() . '_' . $id_game . '_' . $index . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $original_name);
                    $target_file = $target_dir . $image_name;

                    // Validate file
                    $check = getimagesize($tmp_name);

                    if ($check !== false && move_uploaded_file($tmp_name, $target_file)) {
                        $caption = !empty($_POST['new_screenshot_captions'][$index])
                            ? mysqli_real_escape_string($conn, $_POST['new_screenshot_captions'][$index])
                            : '';

                        // Get next display order
                        $order_query = mysqli_query($conn, "SELECT MAX(display_order) as max_order FROM game_screenshots WHERE id_game = '$id_game'");
                        $order_row = mysqli_fetch_assoc($order_query);
                        $display_order = ($order_row['max_order'] ?? 0) + 1;

                        mysqli_query($conn, "
                            INSERT INTO game_screenshots (id_game, image_url, caption, display_order)
                            VALUES ('$id_game', '$image_name', '$caption', '$display_order')
                        ");
                    }
                }
            }
        }

        $_SESSION['success'] = "Game berhasil diperbarui!";
        header("Location: admin_game_list.php");
        exit();
    } else {
        $_SESSION['error'] = "Gagal memperbarui game: " . mysqli_error($conn);
        header("Location: game_edit.php?id=$id_game");
        exit();
    }

} elseif ($aksi == "hapus") {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    // Get game data first
    $game_query = mysqli_query($conn, "SELECT image_url FROM games WHERE id_game = '$id'");
    $game = mysqli_fetch_assoc($game_query);

    // Delete main image
    if (!empty($game['image_url']) && $game['image_url'] != 'default.jpg') {
        $image_path = "../../assets/images/games/" . $game['image_url'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // Delete screenshots
    $screenshots_query = mysqli_query($conn, "SELECT image_url FROM game_screenshots WHERE id_game = '$id'");
    while ($screenshot = mysqli_fetch_assoc($screenshots_query)) {
        $screenshot_path = "../../assets/images/screenshots/" . $screenshot['image_url'];
        if (file_exists($screenshot_path)) {
            unlink($screenshot_path);
        }
    }

    // Delete from game_screenshots table
    mysqli_query($conn, "DELETE FROM game_screenshots WHERE id_game = '$id'");

    // Delete from library (if exists)
    mysqli_query($conn, "DELETE FROM library WHERE id_game = '$id'");

    // Delete from transaction_details (if exists)
    mysqli_query($conn, "DELETE FROM transaction_details WHERE id_game = '$id'");

    // Finally delete the game
    $delete_result = mysqli_query($conn, "DELETE FROM games WHERE id_game = '$id'");

    if ($delete_result) {
        $_SESSION['success'] = "Game berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Gagal menghapus game: " . mysqli_error($conn);
    }

    header("Location: admin_game_list.php");
    exit();
}

// Jika aksi tidak dikenali
header("Location: admin_game_list.php");
exit();
?>