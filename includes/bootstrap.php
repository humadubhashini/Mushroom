<?php
/**
 * Single entry point included at the top of every page.
 */

// Buffer output so redirects keep working even if XAMPP prints a PHP notice
// (XAMPP shows warnings on screen by default).
ob_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

ensure_approval_column();
set_lang_from_request();
