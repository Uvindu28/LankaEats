<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: includes/config.php
 *
 * Central configuration: database credentials, the site's base URL and
 * upload settings. Every other PHP file loads this (via functions.php),
 * so changing a value here changes it everywhere.
 *
 * Default values match a fresh XAMPP install (MySQL user "root" with an
 * empty password) with the project folder at htdocs/lankaeats/.
 *
 * To override values on your own machine without editing this file,
 * create includes/config.local.php and define() any constant there first
 * (that file is ignored by git).
 */

// Optional per-machine overrides (e.g. a different MySQL port).
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

/* ---------- Database ---------- */
defined('DB_HOST') || define('DB_HOST', '127.0.0.1');
defined('DB_PORT') || define('DB_PORT', '3306');
defined('DB_NAME') || define('DB_NAME', 'lanka_eats');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', 'root');

/* ---------- Site ---------- */
// Absolute URL of the project root – ALWAYS ends with a slash.
// All links and asset paths are built from this so pages inside
// auth/ and api/ resolve correctly.
defined('BASE_URL')  || define('BASE_URL', 'http://localhost/lankaeats/');
defined('SITE_NAME') || define('SITE_NAME', 'LankaEats');

/* ---------- Uploads ---------- */
define('UPLOAD_DIR', dirname(__DIR__) . '/images/uploads/');   // filesystem path
define('UPLOAD_URL', 'images/uploads/');                        // relative to BASE_URL
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024);                    // 2 MB
// Allowed MIME types mapped to the extension we save them with.
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
]);

/* ---------- PHP runtime ---------- */
date_default_timezone_set('Asia/Colombo');
error_reporting(E_ALL);
