<?php
/**
 * Application-wide constants and session bootstrap.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', '/mushroom-system'); // change if you place the project in a different htdocs subfolder
define('UPLOAD_LISTINGS', __DIR__ . '/../assets/uploads/listings/');
define('UPLOAD_DIAGNOSES', __DIR__ . '/../assets/uploads/diagnoses/');
define('UPLOAD_PROFILES', __DIR__ . '/../assets/uploads/profiles/');
define('UPLOAD_TUTORIALS', __DIR__ . '/../assets/uploads/tutorials/');

define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024); // 5MB, NFR-PERF friendly limit for uploads
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// AI diagnostic confidence threshold below which the result is flagged (FR-AI.6)
define('AI_LOW_CONFIDENCE_THRESHOLD', 60.0);
