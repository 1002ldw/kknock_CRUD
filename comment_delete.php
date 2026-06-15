<?php
include 'db.php';
include 'auth.php';

require_login();
require_post();
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$userId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare('SELECT post_id FROM comments WHERE id = ? AND author_id = ?');
$stmt->bind_param('ii', $id, $userId);
$stmt->execute();
$comment = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$comment) {
    http_error(404, '삭제할 댓글을 찾을 수 없습니다.');
}

$stmt = $conn->prepare('DELETE FROM comments WHERE id = ? AND author_id = ?');
$stmt->bind_param('ii', $id, $userId);
$stmt->execute();
$stmt->close();

header('Location: view.php?id=' . $comment['post_id']);
exit;
