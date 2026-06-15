<?php
include 'db.php';
include 'auth.php';
include 'board.php';
include 'attachment.php';

require_login();
require_post();
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$userId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare('SELECT board_type FROM posts WHERE id = ? AND author_id = ?');
$stmt->bind_param('ii', $id, $userId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$post) {
    http_error(404, '삭제할 게시글을 찾을 수 없습니다.');
}

$stmt = $conn->prepare('SELECT stored_path FROM attachments WHERE post_id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$paths = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'stored_path');
$stmt->close();

try {
    $conn->begin_transaction();
    $stmt = $conn->prepare('DELETE FROM posts WHERE id = ? AND author_id = ?');
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->commit();
    remove_attachment_files($paths);
} catch (Throwable $exception) {
    $conn->rollback();
    error_log((string)$exception);
    http_error(500, '게시글 삭제에 실패했습니다.');
}

header('Location: ' . board_url($post['board_type']));
exit;
