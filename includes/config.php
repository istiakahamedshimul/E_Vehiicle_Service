<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'vehicle_service_platform');

// Site Configuration
define('SITE_NAME', 'Vehicle Service Platform');
define('SITE_URL', 'http://localhost');
define('ADMIN_EMAIL', 'admin@vehicleservice.com');

// Security Configuration
define('JWT_SECRET', 'your-secret-key-here');
define('ENCRYPTION_KEY', 'your-encryption-key-here');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
ini_set('session.use_only_cookies', 1);

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');

// Database Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Start session
session_start();
?>