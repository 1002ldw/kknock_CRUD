<?php
// 게시글 작성자에게 본문, 게시판, 첨부파일 수정 기능을 제공합니다.
include 'db.php';
include 'auth.php';
include 'board.php';
include 'attachment.php';

require_login();
$id = (int)($_GET['id'] ?? 0);
$userId = (int)$_SESSION['user_id'];
$error = '';

$stmt = $conn->prepare('SELECT id, board_type, title, content FROM posts WHERE id = ? AND author_id = ?');
$stmt->bind_param('ii', $id, $userId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$post) {
    http_error(404, '수정할 게시글을 찾을 수 없습니다.');
}

$attachments = load_post_attachments($conn, $id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $board = board_type($_POST['board'] ?? $post['board_type']);
    $removeInput = $_POST['remove_attachments'] ?? [];
    // 체크박스 값은 양의 정수만 남기고 중복된 첨부파일 ID를 제거합니다.
    $removeIds = is_array($removeInput)
        ? array_values(array_unique(array_filter(array_map('intval', $removeInput), fn($value) => $value > 0)))
        : [];
    $savedPaths = [];
    $removedPaths = [];

    if ($title === '' || $content === '') {
        $error = '모든 항목을 입력하세요.';
    } elseif (text_length($title) > 200) {
        $error = '제목은 200자 이하로 입력하세요.';
    } elseif (strlen($content) > 65535) {
        $error = '내용이 너무 깁니다.';
    } else {
        // 본문, 게시판, 첨부파일 변경이 모두 성공할 때만 DB에 반영합니다.
        try {
            $conn->begin_transaction();
            $stmt = $conn->prepare('UPDATE posts SET board_type = ?, title = ?, content = ? WHERE id = ? AND author_id = ?');
            $stmt->bind_param('sssii', $board, $title, $content, $id, $userId);
            $stmt->execute();
            $stmt->close();

            // 삭제할 파일 경로를 먼저 보관하고 DB 커밋이 완료된 뒤 실제 파일을 삭제합니다.
            if ($removeIds) {
                $placeholders = implode(',', array_fill(0, count($removeIds), '?'));
                $types = str_repeat('i', count($removeIds) + 1);
                $params = array_merge([$id], $removeIds);

                $stmt = $conn->prepare("SELECT stored_path FROM attachments WHERE post_id = ? AND id IN ($placeholders)");
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $removedPaths = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'stored_path');
                $stmt->close();

                $stmt = $conn->prepare("DELETE FROM attachments WHERE post_id = ? AND id IN ($placeholders)");
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();
            }

            // 기존 파일 삭제와 신규 파일 등록을 같은 DB 트랜잭션으로 처리합니다.
            $savedPaths = save_attachments($conn, $id);
            $conn->commit();
            remove_attachment_files($removedPaths);
            header('Location: view.php?id=' . $id);
            exit;
        } catch (Throwable $exception) {
            $conn->rollback();
            remove_attachment_files($savedPaths);
            error_log((string)$exception);
            $error = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : '게시글 수정 중 오류가 발생했습니다.';
        }
    }

    $post['title'] = $title;
    $post['content'] = $content;
    $post['board_type'] = $board;
    $attachments = load_post_attachments($conn, $id);
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>글 수정</title>
    <style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:0 16px}input[type=text],textarea,select{width:100%;padding:10px;margin:8px 0 16px;box-sizing:border-box}.error{color:#b00020}.file{margin:10px 0}.help{color:#666;font-size:14px}fieldset{margin-bottom:16px}</style>
</head>
<body>
    <h1>글 수정</h1>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_input() ?>
        <label for="board">게시판</label>
        <select id="board" name="board">
            <?php foreach (BOARD_TYPES as $type => $label): ?>
                <option value="<?= $type ?>" <?= $post['board_type'] === $type ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="title">제목</label>
        <input id="title" type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>" maxlength="200" required>
        <label for="content">내용</label>
        <textarea id="content" name="content" rows="10" required><?= htmlspecialchars($post['content']) ?></textarea>
        <?php if ($attachments): ?>
            <fieldset><legend>기존 첨부파일</legend>
                <?php foreach ($attachments as $file): ?>
                    <div class="file"><label><input type="checkbox" name="remove_attachments[]" value="<?= $file['id'] ?>"> 삭제: <?= htmlspecialchars($file['original_name']) ?> (<?= number_format((int)$file['size_bytes']) ?> bytes)</label></div>
                <?php endforeach; ?>
            </fieldset>
        <?php endif; ?>
        <label for="attachments">새 첨부파일 추가</label>
        <input id="attachments" type="file" name="attachments[]" multiple>
        <p class="help">파일당 최대 10MB, 최대 20개까지 선택할 수 있습니다.</p>
        <button type="submit">수정 완료</button>
        <a href="view.php?id=<?= $post['id'] ?>">취소</a>
    </form>
</body>
</html>
