<?php
include 'db.php';
include 'auth.php';
require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$post_id = (int)($_POST['post_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$user_id = $_SESSION['user_id'];

if ($post_id <= 0 || $content === '') {
    header("Location: view.php?id=" . $post_id);
    exit;
}

$stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $post_id, $user_id, $content);
$stmt->execute();
$stmt->close();

header("Location: view.php?id=" . $post_id);
exit;
?>