<?php
/**
 * Gopi Travels - Database Connection Helper
 * PDO-based MySQL connection with automatic database selection and error reporting
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration settings
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gopi_travels');
define('DB_PORT', '3306');
define('DB_SOCKET', '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');

// Site Configuration
define('SITE_NAME', 'Gopi Travels');
define('SITE_TAGLINE', 'Gujarat State Deluxe & Luxury Bus Service');
define('SITE_URL', 'http://localhost/bus');
define('HELPLINE_NUMBER', '1800-233-6666');
define('SUPPORT_EMAIL', 'support@gopitravels.com');

/**
 * Returns a singleton PDO database connection instance
 * @return PDO|null
 */
function get_db_connection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    // Connection attempts: 1. TCP 127.0.0.1:3306, 2. XAMPP unix_socket, 3. localhost
    $dsns = [
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        "mysql:unix_socket=" . DB_SOCKET . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        "mysql:host=localhost;port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4"
    ];

    foreach ($dsns as $dsn) {
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $pdo->exec("SET NAMES utf8mb4");
            return $pdo;
        } catch (PDOException $e) {
            // If error is unknown database (1049), try to create it
            if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
                try {
                    $rootDsn = str_replace(";dbname=" . DB_NAME, "", $dsn);
                    $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
                    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                    $pdo->exec("SET NAMES utf8mb4");
                    return $pdo;
                } catch (Exception $ex2) {
                    continue;
                }
            }
            continue;
        }
    }

    return null;
}
