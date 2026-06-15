<?php

// 컨테이너 시작 시 스키마와 마이그레이션을 적용하고 초기 관리자를 준비합니다.

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('DB_HOST') ?: 'db';
$port = (int)(getenv('DB_PORT') ?: 3306);
$user = getenv('DB_USER') ?: 'board_user';
$password = getenv('DB_PASSWORD') ?: 'password';
$database = getenv('DB_NAME') ?: 'cruddb';
$adminUsername = getenv('ADMIN_USERNAME') ?: 'admin';
$adminPassword = getenv('ADMIN_PASSWORD') ?: 'change_me_now';
$schemaPath = '/opt/kknock/schema.sql';

if (!is_file($schemaPath)) {
    fwrite(STDERR, "Schema file is missing: {$schemaPath}\n");
    exit(1);
}

$connection = null;

// MySQL 헬스체크 직후 연결이 지연될 수 있어 최대 30회 재시도합니다.
for ($attempt = 1; $attempt <= 30; $attempt++) {
    try {
        $connection = new mysqli($host, $user, $password, $database, $port);
        break;
    } catch (mysqli_sql_exception $exception) {
        if ($attempt === 30) {
            throw $exception;
        }

        sleep(2);
    }
}

$connection->set_charset('utf8mb4');
$schema = file_get_contents($schemaPath);

if ($schema === false) {
    fwrite(STDERR, "Could not read schema file: {$schemaPath}\n");
    exit(1);
}

// CREATE TABLE IF NOT EXISTS 문으로 신규 설치에 필요한 기본 테이블을 생성합니다.
$connection->multi_query($schema);

do {
    if ($result = $connection->store_result()) {
        $result->free();
    }
} while ($connection->more_results() && $connection->next_result());

// 기존 설치에 board_type 컬럼이 없으면 데이터를 보존한 채 컬럼을 추가합니다.
$columnCheck = $connection->prepare(
    'SELECT COUNT(*) AS column_count FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = "posts" AND COLUMN_NAME = "board_type"'
);
$columnCheck->bind_param('s', $database);
$columnCheck->execute();
$hasBoardType = (int)$columnCheck->get_result()->fetch_assoc()['column_count'] > 0;
$columnCheck->close();

if (!$hasBoardType) {
    $connection->query(
        "ALTER TABLE posts ADD COLUMN board_type VARCHAR(20) NOT NULL DEFAULT 'general' AFTER id"
    );
}

// 컬럼은 있지만 인덱스가 누락된 중간 상태도 자동으로 복구합니다.
$indexCheck = $connection->prepare(
    'SELECT COUNT(*) AS index_count FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = "posts" AND INDEX_NAME = "idx_posts_board_type_id"'
);
$indexCheck->bind_param('s', $database);
$indexCheck->execute();
$hasBoardIndex = (int)$indexCheck->get_result()->fetch_assoc()['index_count'] > 0;
$indexCheck->close();

if (!$hasBoardIndex) {
    $connection->query('ALTER TABLE posts ADD INDEX idx_posts_board_type_id (board_type, id)');
}

// 정의되지 않은 게시판 값은 일반 게시판으로 정규화합니다.
$connection->query("UPDATE posts SET board_type = 'general' WHERE board_type NOT IN ('general', 'free')");

// 초기 관리자는 같은 사용자명이 없을 때만 생성하며 기존 비밀번호는 변경하지 않습니다.
$select = $connection->prepare('SELECT id FROM users WHERE username = ?');
$select->bind_param('s', $adminUsername);
$select->execute();
$adminExists = $select->get_result()->fetch_assoc() !== null;
$select->close();

if (!$adminExists) {
    $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $insert = $connection->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
    $insert->bind_param('ss', $adminUsername, $passwordHash);
    $insert->execute();
    $insert->close();
    fwrite(STDOUT, "Created initial user '{$adminUsername}'.\n");
} else {
    fwrite(STDOUT, "Initial user '{$adminUsername}' already exists; leaving it unchanged.\n");
}

fwrite(STDOUT, "Database schema is ready.\n");
