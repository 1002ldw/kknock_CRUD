<?php
include 'db.php';
include 'auth.php';
require_login();

if (!isset($_GET['id']) || !isset($_GET['post_id'])) {
    die('잘못된 접근입니다.');
}

$comment_id = (int)$_GET['id'];
$post_id = (int)$_GET['post_id'];
$author_id = $_SESSION['user_id'];

$stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND author_id = ?");
$stmt->bind_param("ii", $comment_id, $author_id);
$stmt->execute();
$stmt->close();

header("Location: view.php?id=" . $post_id);
exit;
?>