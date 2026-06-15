<?php
// 로그인한 사용자가 새 댓글을 등록하는 처리 전용 엔드포인트입니다.
include 'db.php';
include 'auth.php';

require_login();
require_post();
verify_csrf();

$postId = (int)($_POST['post_id'] ?? 0);
$userId = (int)$_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');

if ($postId <= 0 || $content === '') {
    http_error(400, '댓글 내용을 입력하세요.');
}
if (strlen($content) > 65535) {
    http_error(400, '댓글 내용이 너무 깁니다.');
}

// 존재하지 않는 게시글에 댓글이 연결되지 않도록 먼저 게시글을 확인합니다.
$stmt = $conn->prepare('SELECT id FROM posts WHERE id = ?');
$stmt->bind_param('i', $postId);
$stmt->execute();
$postExists = $stmt->get_result()->fetch_assoc() !== null;
$stmt->close();
if (!$postExists) {
    http_error(404, '게시글을 찾을 수 없습니다.');
}

$stmt = $conn->prepare('INSERT INTO comments (post_id, author_id, content) VALUES (?, ?, ?)');
$stmt->bind_param('iis', $postId, $userId, $content);
$stmt->execute();
$stmt->close();

header('Location: view.php?id=' . $postId);
exit;
