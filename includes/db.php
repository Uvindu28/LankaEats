<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: includes/db.php
 *
 * Creates ONE shared PDO connection to MySQL (lazily, on first use).
 *  - Error mode is set to exceptions so failed queries never pass silently.
 *  - Emulated prepares are OFF, so every prepared statement is prepared by
 *    MySQL itself (true parameter binding – protection against SQL injection).
 *  - The connection uses utf8mb4 so Sinhala/Tamil text and emoji are stored safely.
 *
 * Usage anywhere:  $stmt = db()->prepare('SELECT ... WHERE id = ?');
 */

require_once __DIR__ . '/config.php';

/**
 * Return the shared PDO instance, connecting on the first call.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $ex) {
        // Log the real reason for the developer, show a friendly message to visitors.
        error_log('[LankaEats] DB connection failed: ' . $ex->getMessage());
        db_connection_failed();
    }

    return $pdo;
}

/**
 * Stop the request with a helpful message when MySQL is unreachable.
 * The JSON API gets a JSON error; normal pages get a small HTML notice.
 */
function db_connection_failed(): void
{
    http_response_code(500);

    if (defined('API_REQUEST')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
        exit;
    }

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Database unavailable – LankaEats</title></head>'
        . '<body style="font-family:system-ui,sans-serif;background:#fbf6ec;color:#2b1d14;padding:3rem 1rem;">'
        . '<div style="max-width:560px;margin:auto;background:#fff;border-radius:16px;padding:2rem;'
        . 'box-shadow:0 10px 30px rgba(0,0,0,.08);border-top:6px solid #b5542c;">'
        . '<h1 style="margin-top:0;">We can\'t reach the kitchen 🍛</h1>'
        . '<p>The database connection failed. If you are setting up the project:</p>'
        . '<ol><li>Start <strong>MySQL</strong> in the XAMPP Control Panel.</li>'
        . '<li>Import <code>database.sql</code> using phpMyAdmin.</li>'
        . '<li>Check the credentials in <code>includes/config.php</code>.</li></ol>'
        . '</div></body></html>';
    exit;
}
