<?php
// Database connection. Credentials come from config.php (DB_HOST, DB_NAME, DB_USER, DB_PASS).
// This file is loaded by config.php - do not edit the credentials here.

if (!isset($pdo) || $pdo === null) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 10,
            ]
        );
        $pdo->exec("SET time_zone = '+02:00'"); // Rwanda time (CAT), so NOW()/CURDATE() match the site clock
    } catch (PDOException $e) {
        error_log('URUMURI DB connection failed: ' . $e->getMessage());
        http_response_code(503);
        $hint = '';
        $msg = $e->getMessage();
        if (stripos($msg, 'getaddrinfo') !== false || stripos($msg, 'not known') !== false)
            $hint = 'The database HOST is wrong. It must look like sql313.infinityfree.com (no https://, no slashes).';
        elseif (stripos($msg, 'Access denied') !== false)
            $hint = 'The database USERNAME or PASSWORD is wrong. Copy them again from InfinityFree > MySQL Databases.';
        elseif (stripos($msg, 'Unknown database') !== false)
            $hint = 'The database NAME is wrong. Use the full name shown under "List of MySQL Databases" (for example if0_43264006_urumuri_news).';
        echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>URUMURI NEWS</title>'
           . '<body style="font-family:Arial,sans-serif;max-width:560px;margin:12vh auto;padding:0 20px;color:#0b1b3a">'
           . '<h1 style="font-size:1.6rem">URUMURI NEWS</h1><p>The site cannot reach its database right now. Please try again in a few minutes.</p>';
        if (defined('APP_DEBUG') && APP_DEBUG)
            echo '<pre style="background:#fff4f4;border:1px solid #f1b4b4;padding:12px;border-radius:8px;white-space:pre-wrap">'
               . htmlspecialchars($msg) . '</pre>' . ($hint ? '<p><b>Fix:</b> ' . htmlspecialchars($hint) . '</p>' : '');
        echo '</body>';
        exit;
    }
}
