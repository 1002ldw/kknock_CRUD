<?php
include 'db.php';
include 'auth.php';
include 'board.php';
include 'attachment.php';

require_login();

$board = board_type($_POST['board'] ?? $_GET['board'] ?? 'general');
$error = '';
$title = '';
$content = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $userId = (int)$_SESSION['user_id'];
    $savedPaths = [];

    if ($title === '' || $content === '') {
        $error = '모든 항목을 입력하세요.';
    } elseif (text_length($title) > 200) {
        $error = '제목은 200자 이하로 입력하세요.';
    } elseif (strlen($content) > 65535) {
        $error = '내용이 너무 깁니다.';
    } else {
        try {
            $conn->begin_transaction();
            $stmt = $conn->prepare('INSERT INTO posts (board_type, author_id, title, content) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('siss', $board, $userId, $title, $content);
            $stmt->execute();
            $postId = $stmt->insert_id;
            $stmt->close();

            $savedPaths = save_attachments($conn, $postId);
            $conn->commit();
            header('Location: view.php?id=' . $postId);
            exit;
        } catch (Throwable $exception) {
            $conn->rollback();
            remove_attachment_files($savedPaths);
            error_log((string)$exception);
            $error = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : '게시글 등록 중 오류가 발생했습니다.';
        }
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>글 작성</title>
    <style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:0 16px}input,textarea{width:100%;padding:10px;margin:8px 0 16px;box-sizing:border-box}.error{color:#b00020}.help{color:#666;font-size:14px}</style>
</head>
<body>
    <h1><?= htmlspecialchars(board_label($board)) ?> 글 작성</h1>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_input() ?>
        <input type="hidden" name="board" value="<?= htmlspecialchars($board) ?>">
        <label for="title">제목</label>
        <input id="title" type="text" name="title" value="<?= htmlspecialchars($title) ?>" maxlength="200" required>
        <label for="content">내용</label>
        <textarea id="content" name="content" rows="10" required><?= htmlspecialchars($content) ?></textarea>
        <label for="attachments">첨부파일</label>
        <input id="attachments" type="file" name="attachments[]" multiple>
        <p class="help">파일당 최대 10MB, 최대 20개까지 선택할 수 있습니다.</p>
        <button type="submit">등록</button>
        <a href="<?= htmlspecialchars(board_url($board)) ?>">목록</a>
    </form>
</body>
</html>
