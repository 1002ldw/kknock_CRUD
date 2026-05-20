<?php
include 'db.php';
include 'auth.php';
require_login();

if (!isset($_GET['id'])) {
    die('잘못된 접근입니다.');
}

$comment_id = (int)$_GET['id'];
$author_id = $_SESSION['user_id'];
$error = '';

$stmt = $conn->prepare("SELECT * FROM comments WHERE id = ? AND author_id = ?");
$stmt->bind_param("ii", $comment_id, $author_id);
$stmt->execute();
$result = $stmt->get_result();
$comment = $result->fetch_assoc();
$stmt->close();

if (!$comment) {
    die('본인 댓글만 수정할 수 있습니다.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');

    if ($content === '') {
        $error = '댓글 내용을 입력하세요.';
    } else {
        $stmt = $conn->prepare("UPDATE comments SET content = ? WHERE id = ? AND author_id = ?");
        $stmt->bind_param("sii", $content, $comment_id, $author_id);
        $stmt->execute();
        $stmt->close();

        header("Location: view.php?id=" . $comment['post_id']);
        exit;
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>댓글 수정</title>
    <style>
        body { font-family: Arial, sans-serif; width: 800px; margin: 40px auto; }
        textarea { width: 100%; padding: 10px; box-sizing: border-box; margin-bottom: 16px; }
        .error { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>댓글 수정</h1>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <form method="post">
        <textarea name="content" rows="6" required><?= htmlspecialchars($comment['content']) ?></textarea>
        <button type="submit">수정 완료</button>
        <a href="view.php?id=<?= $comment['post_id'] ?>">취소</a>
    </form>
</body>
</html>