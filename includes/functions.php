<?php
/**
 * Shared helper functions used across the site.
 */

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header('Location: ' . BASE_URL . $path);
    exit;
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function old($key, $default = '') {
    return e($_SESSION['old'][$key] ?? $default);
}

function clear_old() {
    unset($_SESSION['old']);
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Invalid or expired form submission. Please go back and try again.');
    }
}

/**
 * Validates and moves an uploaded image into the given target directory.
 * Returns the stored filename on success, or null on failure (with $error set).
 */
function handle_image_upload($fileField, $targetDir, &$error = null) {
    if (empty($_FILES[$fileField]) || $_FILES[$fileField]['error'] === UPLOAD_ERR_NO_FILE) {
        $error = null;
        return null;
    }
    $file = $_FILES[$fileField];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Image upload failed. Please try again.';
        return null;
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        $error = 'Image is too large. Maximum size is 5MB.';
        return null;
    }
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        $error = 'Only JPG, PNG, or WEBP images are allowed.';
        return null;
    }

    $ext = match ($mime) {
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg',
    };
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $targetDir . $filename)) {
        $error = 'Could not save the uploaded image.';
        return null;
    }
    return $filename;
}

function format_money($amount) {
    return 'Rs. ' . number_format((float) $amount, 2);
}

function format_date($date) {
    if (!$date) {
        return '-';
    }
    return date('d M Y', strtotime($date));
}
