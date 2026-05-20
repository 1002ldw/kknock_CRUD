<?php
include 'db.php';
include 'auth.php';
require_login();

if (!isset($_GET['id'])) {
    die('잘못된 접근입니다.');
}

$id = (int)$_GET['id'];
$author_id = $_SESSION['user_id'];

$stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND author_id = ?");
$stmt->bind_param("ii", $id, $author_id);
$stmt->execute();
$stmt->close();

header('Location: index.php');
exit;
?>