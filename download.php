<?php
include 'db.php';
include 'auth.php';
include 'attachment.php';

require_login();
$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare('SELECT original_name, stored_path, size_bytes FROM attachments WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$file = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$file || !is_upload_path($file['stored_path']) || !is_readable($file['stored_path'])) {
    http_error(404, '파일을 찾을 수 없습니다.');
}

$downloadName = normalized_file_name($file['original_name']);
header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($file['stored_path']));
header("Content-Disposition: attachment; filename=\"download\"; filename*=UTF-8''" . rawurlencode($downloadName));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($file['stored_path']);
exit;
