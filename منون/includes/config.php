<?php
/**
 * MANON Luxury Fashion Configuration
 * No database required — uses JSON flat files
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// === PATHS ===
define('ROOT_PATH', dirname(__DIR__) . '/');
define('DATA_PATH', ROOT_PATH . 'data/');

// === STORE CONSTANTS ===
define('SITE_NAME',        'MANON | مانون');
define('SITE_SLOGAN',      'عبايتك فخامة تليق بك');
define('WHATSAPP_RAW',     '+20 12 73572887');
define('WHATSAPP_PHONE',   '201273572887');
define('STORE_PHONE',      '+20 12 73572887');
define('STORE_EMAIL',      'contact@manon-abaya.com');
define('STORE_ADDRESS',    'القاهرة - مصر');
define('DEFAULT_CURRENCY', 'ج.م');

// === AUTO-DETECT BASE URL ===
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath  = preg_replace('#/(admin|api)(/.*)?$#i', '', $scriptDir);
if ($basePath === '/' || $basePath === '') {
    $basePath = '';
}
define('BASE_URL', rtrim($protocol . $host . $basePath, '/') . '/');

// === TIMEZONE ===
date_default_timezone_set('Africa/Cairo');
