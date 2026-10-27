<?php
// CineBook uses Singapore-local cinema dates/times throughout the project.
date_default_timezone_set('Asia/Singapore');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cinebook_db';
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('CineBook database unavailable: ' . $e->getMessage());
    http_response_code(503);
    if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['status'=>'error','message'=>'Movie service is temporarily unavailable.']);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
           . '<title>Temporarily unavailable | CineBook</title><link rel="stylesheet" href="css/style.css"></head>'
           . '<body style="background:#0c0c0c;color:#fff;font-family:Arial,sans-serif;padding:12vh 24px;text-align:center">'
           . '<h1>We cannot connect to CineBook right now.</h1><p>Please check that XAMPP MySQL is running and try again.</p>'
           . '<p><a style="color:#eebc4d" href="index.php">Try again</a></p></body></html>';
    }
    exit;
}
require_once __DIR__ . '/functions.php';
// Catch unexpected SQL failures on movie/search/ticket pages; log details server-side.
set_exception_handler(static function (Throwable $e) {
    error_log('CineBook unhandled application error: ' . $e->getMessage());
    http_response_code(500);
    if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status'=>'error','message'=>'Service temporarily unavailable.']);
    } else {
        echo '<div class="error-msg" role="alert" style="max-width:700px;margin:32px auto;padding:24px;background:#251a16;color:#fff;">'
           . 'Sorry, something went wrong. Please try again later or return to <a href="index.php" style="color:#eebc4d;">CineBook home</a>.'
           . '</div>';
    }
});
