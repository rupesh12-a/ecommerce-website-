<?php
/**
 * Central configuration, database connection and session bootstrap.
 * Every entry point (storefront, admin, API) includes this file.
 */

declare(strict_types=1);

/* ---------------------------------------------------------- Database */
// Edit these if your MySQL credentials differ (e.g. when using XAMPP defaults).
define('DB_HOST', getenv('WINE_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('WINE_DB_PORT') ?: '3306');
define('DB_NAME', getenv('WINE_DB_NAME') ?: 'wine_shop');
define('DB_USER', getenv('WINE_DB_USER') ?: 'root');
define('DB_PASS', getenv('WINE_DB_PASS') ?: '');

/* ---------------------------------------------------------- Paths */
define('APP_ROOT', dirname(__DIR__));

// Work out the URL path the app is served from, so links work whether it is
// opened as http://localhost/wine/ (Apache) or http://localhost:8000/ (php -S).
$docRoot = str_replace('\\', '/', rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));
$appRoot = str_replace('\\', '/', APP_ROOT);
$base    = '';
if ($docRoot !== '' && stripos($appRoot, $docRoot) === 0) {
    $base = substr($appRoot, strlen($docRoot));
}
$scriptDir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '')));
if ($base === '' && $scriptDir !== '' && $scriptDir !== '/' && $scriptDir !== '.') {
    // Fallback for servers that do not expose a usable DOCUMENT_ROOT.
    $base = preg_replace('#/(admin|api)$#', '', $scriptDir);
}
define('BASE_URL', rtrim($base, '/'));

/* ---------------------------------------------------------- Session */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/* ---------------------------------------------------------- Errors */
error_reporting(E_ALL);
ini_set('display_errors', '1'); // set to '0' in production

/* ---------------------------------------------------------- Bootstrap */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/database.php';

$pdo = db();
