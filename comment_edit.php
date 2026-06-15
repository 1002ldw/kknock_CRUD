<?php
include 'db.php';
include 'auth.php';

require_login();
$id = (int)($_GET['id'] ?? 0);
$userId = (int)$_SESSION['user_id'];
$error = '';

$stmt = $conn->prepare('SELECT id, post_id, content FROM comments WHERE id = ? AND author_id = ?');
$stmt->bind_param('ii', $id, $userId);
$stmt->execute();
$comment = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$comment) {
    http_error(404, '수정할 댓글을 찾을 수 없습니다.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $content = trim($_POST['content'] ?? '');

    if ($content === '') {
        $error = '댓글 내용을 입력하세요.';
    } elseif (strlen($content) > 65535) {
        $error = '댓글 내용이 너무 깁니다.';
    } else {
        $stmt = $conn->prepare('UPDATE comments SET content = ? WHERE id = ? AND author_id = ?');
        $stmt->bind_param('sii', $content, $id, $userId);
        $stmt->execute();
        $stmt->close();
        header('Location: view.php?id=' . $comment['post_id']);
        exit;
    }

    $comment['content'] = $content;
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>댓글 수정</title>
    <style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:0 16px}textarea{width:100%;padding:10px;box-sizing:border-box;margin:10px 0}.error{color:#b00020}</style>
</head>
<body>
    <h1>댓글 수정</h1>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
        <?= csrf_input() ?>
        <textarea name="content" rows="5" required><?= htmlspecialchars($comment['content']) ?></textarea>
        <button type="submit">수정 완료</button>
        <a href="view.php?id=<?= $comment['post_id'] ?>">취소</a>
    </form>
</body>
</html>
