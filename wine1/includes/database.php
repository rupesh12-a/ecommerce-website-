<?php
/**
 * Thin PDO wrapper — one shared connection for the whole request.
 */

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        if (is_ajax()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Database connection failed.']);
        } else {
            echo '<h1 style="font-family:sans-serif">Database connection failed</h1>';
            echo '<p style="font-family:sans-serif">' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p style="font-family:sans-serif">Check config/config.php — is MySQL running and is the <code>wine_shop</code> database imported?</p>';
        }
        exit;
    }

    return $pdo;
}
