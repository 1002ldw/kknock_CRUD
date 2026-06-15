<?php

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

$connection->multi_query($schema);

do {
    if ($result = $connection->store_result()) {
        $result->free();
    }
} while ($connection->more_results() && $connection->next_result());

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

$connection->query("UPDATE posts SET board_type = 'general' WHERE board_type NOT IN ('general', 'free')");

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
