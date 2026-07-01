<?php
// 게시글, 첨부파일, 댓글을 조회해 상세 화면을 구성합니다.
include 'db.php';
include 'auth.php';
include 'board.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_error(400, '잘못된 접근입니다.');
}

// 게시글과 작성자 정보를 한 번의 JOIN 조회로 가져옵니다.
$stmt = $conn->prepare('SELECT posts.id, posts.author_id, posts.board_type, posts.title, posts.content, posts.created_at, posts.updated_at, users.username FROM posts JOIN users ON posts.author_id = users.id WHERE posts.id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$post) {
    http_error(404, '게시글이 존재하지 않습니다.');
}
$isOwner = is_logged_in() && (int)$_SESSION['user_id'] === (int)$post['author_id'];

$stmt = $conn->prepare('SELECT id, original_name, size_bytes FROM attachments WHERE post_id = ? ORDER BY id ASC');
$stmt->bind_param('i', $id);
$stmt->execute();
$attachments = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare('SELECT comments.id, comments.author_id, comments.content, comments.created_at, users.username FROM comments JOIN users ON comments.author_id = users.id WHERE comments.post_id = ? ORDER BY comments.id ASC');
$stmt->bind_param('i', $id);
$stmt->execute();
$comments = $stmt->get_result();
$stmt->close();
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>글 상세</title>
    <style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:0 16px}.box,.comment-box{border:1px solid #ddd;padding:20px}.title{font-size:28px;margin-bottom:10px}.meta{color:#666;margin-bottom:20px}.content{min-height:220px;white-space:pre-wrap}.actions,.comment-actions{margin-top:20px}.btn,button{display:inline-block;padding:8px 14px;border:1px solid #333;color:#111;background:#fafafa;text-decoration:none;margin-right:8px}.comment-box{margin-top:16px}.comment-meta{font-size:14px;color:#666;margin-bottom:8px}textarea{width:100%;padding:10px;box-sizing:border-box;margin:10px 0}.files{border-top:1px solid #eee;padding-top:16px;margin-top:20px}.files li{margin:8px 0}.inline{display:inline}</style>
</head>
<body>
    <h1><?= htmlspecialchars(board_label($post['board_type'])) ?></h1>
    <div class="box">
        <div class="title"><?= htmlspecialchars($post['title']) ?></div>
        <div class="meta">
            작성자: <?= htmlspecialchars($post['username']) ?> |
            작성일: <?= htmlspecialchars(format_kst_datetime($post['created_at'])) ?>
            <?php if ($post['updated_at'] !== $post['created_at']): ?> | 수정일: <?= htmlspecialchars(format_kst_datetime($post['updated_at'])) ?><?php endif; ?>
        </div>
        <div class="content"><?= htmlspecialchars($post['content']) ?></div>
        <?php if ($attachments->num_rows > 0): ?>
            <div class="files"><strong>첨부파일</strong><ul>
                <?php while ($file = $attachments->fetch_assoc()): ?>
                    <li><a href="download.php?id=<?= (int)$file['id'] ?>"><?= htmlspecialchars($file['original_name']) ?></a> (<?= number_format((int)$file['size_bytes']) ?> bytes)</li>
                <?php endwhile; ?>
            </ul></div>
        <?php endif; ?>
    </div>

    <div class="actions">
        <a class="btn" href="<?= htmlspecialchars(board_url($post['board_type'])) ?>">목록</a>
        <?php if ($isOwner): ?>
            <a class="btn" href="edit.php?id=<?= (int)$post['id'] ?>">수정</a>
            <form class="inline" method="post" action="delete.php" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                <?= csrf_input() ?>
                <input type="hidden" name="id" value="<?= (int)$post['id'] ?>">
                <button type="submit">삭제</button>
            </form>
        <?php endif; ?>
    </div>

    <h2 style="margin-top:40px">댓글</h2>
    <?php if (is_logged_in()): ?>
        <form method="post" action="comment_create.php">
            <?= csrf_input() ?>
            <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
            <textarea name="content" rows="4" required placeholder="댓글을 입력하세요."></textarea>
            <button type="submit">댓글 작성</button>
        </form>
    <?php else: ?>
        <p><a href="login.php">로그인</a> 후 댓글을 작성할 수 있습니다.</p>
    <?php endif; ?>

    <?php while ($comment = $comments->fetch_assoc()): ?>
        <?php $isCommentOwner = is_logged_in() && (int)$_SESSION['user_id'] === (int)$comment['author_id']; ?>
        <div class="comment-box">
            <div class="comment-meta">작성자: <?= htmlspecialchars($comment['username']) ?> | 작성일: <?= htmlspecialchars(format_kst_datetime($comment['created_at'])) ?></div>
            <div><?= nl2br(htmlspecialchars($comment['content'])) ?></div>
            <?php if ($isCommentOwner): ?>
                <div class="comment-actions">
                    <a class="btn" href="comment_edit.php?id=<?= (int)$comment['id'] ?>">댓글 수정</a>
                    <form class="inline" method="post" action="comment_delete.php" onsubmit="return confirm('댓글을 삭제하시겠습니까?');">
                        <?= csrf_input() ?>
                        <input type="hidden" name="id" value="<?= (int)$comment['id'] ?>">
                        <button type="submit">댓글 삭제</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
</body>
</html>
