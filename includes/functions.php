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

/**
 * Creates an in-app notification for a user (FR-MKT.5, FR-NOT.1 - FR-NOT.3).
 */
function notify($userId, $message, $link = null) {
    global $pdo;
    $pdo->prepare('INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)')
        ->execute([$userId, $message, $link]);
}

function unread_notification_count() {
    global $pdo;
    $user = current_user();
    if (!$user) {
        return 0;
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) c FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$user['id']]);
    return (int) $stmt->fetch()['c'];
}

/**
 * Records an administrative action for auditability (NFR-SEC.5).
 */
function log_admin_action($action) {
    global $pdo;
    $user = current_user();
    if ($user) {
        $pdo->prepare('INSERT INTO admin_logs (admin_id, action) VALUES (?, ?)')->execute([$user['id'], $action]);
    }
}

/**
 * Sends an email when PHP mail is configured; failures are ignored because
 * XAMPP has no mail server by default (DEMO_MODE shows codes on screen instead).
 */
function send_email($to, $subject, $body) {
    return @mail($to, $subject, $body, 'From: no-reply@mushroomdirect.lk');
}

/**
 * Generates a 6-digit OTP for the user, stores its hash, and emails it (FR-AUTH.2).
 * Returns the plain code so DEMO_MODE can display it.
 */
function issue_otp($userId, $email) {
    global $pdo;
    $code = (string) random_int(100000, 999999);
    $pdo->prepare('UPDATE users SET otp_code = ?, otp_expires_at = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?')
        ->execute([password_hash($code, PASSWORD_DEFAULT), OTP_TTL_MINUTES, $userId]);
    send_email($email, APP_NAME . ' verification code', "Your verification code is $code. It expires in " . OTP_TTL_MINUTES . ' minutes.');
    return $code;
}

/**
 * Text lookup for i18n readiness (NFR-LOC.2). UI strings live in lang/<code>.php
 * so Sinhala (si) and Tamil (ta) files can be added later without code changes.
 */
function t($key) {
    static $strings = null;
    if ($strings === null) {
        $lang = $_SESSION['lang'] ?? 'en';
        $file = __DIR__ . '/../lang/' . basename($lang) . '.php';
        $strings = is_file($file) ? require $file : require __DIR__ . '/../lang/en.php';
    }
    return $strings[$key] ?? $key;
}

function role_home($role) {
    return match ($role) {
        'farmer' => '/farmer/dashboard.php',
        'buyer' => '/buyer/dashboard.php',
        'admin', 'expert' => '/admin/dashboard.php',
        default => '/index.php',
    };
}
