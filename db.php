<?php

// MySQL 오류를 예외로 변환해 각 처리 코드에서 일관되게 다룰 수 있게 합니다.

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('DB_HOST') ?: 'localhost';
$port = (int)(getenv('DB_PORT') ?: 3306);
$user = getenv('DB_USER') ?: 'board_user';
$pass = getenv('DB_PASSWORD') ?: 'password';
$dbname = getenv('DB_NAME') ?: 'cruddb';

try {
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log((string)$exception);
    http_response_code(500);
    exit('데이터베이스 연결에 실패했습니다.');
}
