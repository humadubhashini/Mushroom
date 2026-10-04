<?php
/**
 * Application-wide constants and session bootstrap.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * BASE_URL is detected automatically from the folder the project sits in under
 * XAMPP's htdocs (e.g. htdocs/mushroom-system -> "/mushroom-system"), so the
 * project works whatever you name the folder. Set it manually only if
 * detection fails on your setup.
 */
function detect_base_url(): string {
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $appRoot = realpath(__DIR__ . '/..');
    if ($docRoot && $appRoot) {
        $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
        $appRoot = str_replace('\\', '/', $appRoot);
        if (stripos($appRoot, $docRoot) === 0) {
            return rtrim(substr($appRoot, strlen($docRoot)), '/');
        }
    }
    return '/mushroom-system';
}
define('BASE_URL', detect_base_url());
define('APP_NAME', 'Mushroom Direct');
define('APP_VERSION', '2.1');

// Demo mode: XAMPP has no mail server configured by default, so OTP codes and
// password-reset links are also shown on screen. Set to false once a real
// email/SMS service is connected (SRS 4.3 Email/SMS/OTP Service).
define('DEMO_MODE', true);
define('OTP_TTL_MINUTES', 10);
define('UPLOAD_LISTINGS', __DIR__ . '/../assets/uploads/listings/');
define('UPLOAD_DIAGNOSES', __DIR__ . '/../assets/uploads/diagnoses/');
define('UPLOAD_PROFILES', __DIR__ . '/../assets/uploads/profiles/');
define('UPLOAD_TUTORIALS', __DIR__ . '/../assets/uploads/tutorials/');
define('UPLOAD_MUSHROOMS', __DIR__ . '/../assets/uploads/mushrooms/');

define('MAX_UPLOAD_BYTES', 10 * 1024 * 1024); // 10MB - phone camera photos are usually 2-8MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// AI diagnostic confidence threshold below which the result is flagged (FR-AI.6)
define('AI_LOW_CONFIDENCE_THRESHOLD', 60.0);

// Optional URL of the trained CNN inference API (Python Flask, see ai_model/).
// Leave empty to use the built-in PHP fallback classifier.
define('AI_API_URL', '');  // e.g. 'http://127.0.0.1:5000/predict'
