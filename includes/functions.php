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
        die(e(t('form.expired')));
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

    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $file['size'] > MAX_UPLOAD_BYTES) {
        $error = t('upload.too_large', ['mb' => MAX_UPLOAD_BYTES / 1024 / 1024]);
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = t('upload.failed');
        return null;
    }

    // Detect the real image type from the file contents. getimagesize() is part
    // of core PHP, so this works even when XAMPP's fileinfo extension is off.
    $info = @getimagesize($file['tmp_name']);
    $mime = $info['mime'] ?? '';
    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        $error = t('upload.bad_type');
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
        $error = t('upload.save_failed');
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
 * $message is either plain text or a translation key from lang/*.php; keys
 * are stored with their values and translated when the recipient reads them,
 * so every user sees notifications in their own language.
 */
function notify($userId, $message, $link = null, array $vars = []) {
    global $pdo;
    if (preg_match('/^notif\.[a-z_]+$/', $message)) {
        $message = json_encode(['k' => $message, 'v' => $vars], JSON_UNESCAPED_UNICODE);
    }
    $pdo->prepare('INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)')
        ->execute([$userId, $message, $link]);
}

/** Text of a stored notification in the current language. */
function notification_text($stored) {
    $data = json_decode($stored, true);
    if (is_array($data) && isset($data['k'])) {
        $vars = $data['v'] ?? [];
        // Disease names are stored in English; show them translated.
        if (isset($vars['disease'])) {
            global $pdo;
            $q = $pdo->prepare('SELECT * FROM disease_types WHERE name = ?');
            $q->execute([$vars['disease']]);
            if ($row = $q->fetch()) {
                $vars['disease'] = tr_field($row, 'name');
            }
        }
        return t($data['k'], $vars);
    }
    return $stored;
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
 * Supported interface languages (NFR-LOC). The choice is kept in the session
 * and a cookie, and can be changed from any page with ?lang=en|si|ta.
 */
const LANGUAGES = ['en' => 'English', 'si' => 'සිංහල', 'ta' => 'தமிழ்'];

function current_lang() {
    static $lang = null;
    if ($lang === null) {
        $lang = $_SESSION['lang'] ?? ($_COOKIE['lang'] ?? 'en');
        if (!isset(LANGUAGES[$lang])) {
            $lang = 'en';
        }
    }
    return $lang;
}

function set_lang_from_request() {
    $lang = $_GET['lang'] ?? null;
    if ($lang !== null && isset(LANGUAGES[$lang])) {
        $_SESSION['lang'] = $lang;
        setcookie('lang', $lang, time() + 365 * 24 * 3600, '/');
    }
}

/**
 * Translated UI text. Strings live in lang/<code>.php; missing keys fall back
 * to English. {placeholders} are replaced from $vars.
 */
function t($key, array $vars = []) {
    static $strings = [];
    $lang = current_lang();
    if (!isset($strings[$lang])) {
        $strings['en'] = $strings['en'] ?? require __DIR__ . '/../lang/en.php';
        $strings[$lang] = $lang === 'en' ? $strings['en'] : require __DIR__ . '/../lang/' . $lang . '.php';
    }
    $text = $strings[$lang][$key] ?? ($strings['en'][$key] ?? $key);
    foreach ($vars as $name => $value) {
        $text = str_replace('{' . $name . '}', (string) $value, $text);
    }
    return $text;
}

/** Escaped translation for HTML output. */
function te($key, array $vars = []) {
    return e(t($key, $vars));
}

/**
 * Returns a database field in the current language, e.g. name_si / name_ta,
 * falling back to the English column when no translation is stored.
 */
function tr_field($row, $field) {
    $lang = current_lang();
    if ($lang !== 'en' && !empty($row[$field . '_' . $lang])) {
        return $row[$field . '_' . $lang];
    }
    return $row[$field] ?? '';
}

/** Current URL with a different ?lang= value, for the language switcher. */
function lang_url($code) {
    $query = $_GET;
    $query['lang'] = $code;
    return strtok($_SERVER['REQUEST_URI'] ?? '', '?') . '?' . http_build_query($query);
}

function role_home($role) {
    return match ($role) {
        'farmer' => '/farmer/dashboard.php',
        'buyer' => '/buyer/dashboard.php',
        'admin', 'expert' => '/admin/dashboard.php',
        default => '/index.php',
    };
}

/**
 * Picture for a mushroom variety: the uploaded photo if there is one,
 * otherwise a simple built-in drawing in the variety's colour.
 */
function mushroom_picture($type, $size = 150) {
    if (!empty($type['image'])) {
        return '<img src="' . BASE_URL . '/assets/uploads/mushrooms/' . e($type['image']) . '" alt="' . e(tr_field($type, 'name')) . '">';
    }
    $cap = preg_match('/^#[0-9a-f]{6}$/i', $type['color'] ?? '') ? $type['color'] : '#c9b79c';
    return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 120 120" role="img" aria-label="' . e(tr_field($type, 'name')) . '">'
        . '<ellipse cx="60" cy="108" rx="40" ry="6" fill="#000" opacity=".08"/>'
        . '<path d="M50 62 Q47 92 44 106 L76 106 Q73 92 70 62 Z" fill="#f4efe4" stroke="#d8cfbd" stroke-width="2"/>'
        . '<path d="M10 64 Q14 18 60 16 Q106 18 110 64 Q60 76 10 64 Z" fill="' . $cap . '" stroke="rgba(0,0,0,.18)" stroke-width="2"/>'
        . '<path d="M22 64 Q60 72 98 64" stroke="rgba(0,0,0,.12)" stroke-width="2" fill="none"/>'
        . '<ellipse cx="44" cy="36" rx="12" ry="6" fill="#fff" opacity=".35"/>'
        . '</svg>';
}

/** SQL columns to join from mushroom_types (alias mt) for translated listing names. */
const LISTING_TYPE_COLUMNS = 'mt.name_si AS mt_name_si, mt.name_ta AS mt_name_ta, mt.color AS mt_color, mt.image AS mt_image';

/** Listing title in the current language (catalogue name when the listing is linked to one). */
function listing_title($listing) {
    $lang = current_lang();
    if ($lang !== 'en' && !empty($listing['mt_name_' . $lang])) {
        return $listing['mt_name_' . $lang];
    }
    return $listing['mushroom_type'];
}

/**
 * Listing photo. When the farmer did not upload one, the photo of its mushroom
 * type (Admin > Mushroom Types) is used, and failing that the built-in drawing.
 */
function listing_picture($listing, $size = 140) {
    if (!empty($listing['image'])) {
        return '<img src="' . BASE_URL . '/assets/uploads/listings/' . e($listing['image']) . '" alt="' . e(listing_title($listing)) . '">';
    }
    if (!empty($listing['mt_image'])) {
        return '<img src="' . BASE_URL . '/assets/uploads/mushrooms/' . e($listing['mt_image']) . '" alt="' . e(listing_title($listing)) . '">';
    }
    return '<div class="pic-fallback">' . mushroom_picture(['name' => listing_title($listing), 'color' => $listing['mt_color'] ?? null], $size) . '</div>';
}

function status_label($status) {
    return t('status.' . $status);
}

/** Default profile picture (inline SVG, works offline). */
const AVATAR_PLACEHOLDER = "data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 96 96'%3E%3Crect width='96' height='96' fill='%23e4f5e9'/%3E%3Ccircle cx='48' cy='38' r='18' fill='%232e7d53'/%3E%3Cpath d='M14 92c4-20 18-30 34-30s30 10 34 30z' fill='%232e7d53'/%3E%3C/svg%3E";
