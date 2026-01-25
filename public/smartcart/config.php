<?php
/**
 * SmartCart - Configuration
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Timezone
date_default_timezone_set('Europe/Moscow');

// Database path
define('DB_PATH', __DIR__ . '/data/smartcart.db');

// Base URL (auto-detect)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $protocol . '://' . $host . '/smartcart');

// API settings
define('API_VERSION', '1.0');

// CORS settings
define('CORS_ALLOWED_ORIGINS', '*');

// App info
define('APP_NAME', 'SmartCart');
define('APP_VERSION', '1.0.0');
