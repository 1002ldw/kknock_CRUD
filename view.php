<?php
include 'db.php';
include 'auth.php';

if (!isset($_GET['id'])) {
    die('잘못된 접근입니다.');
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT posts.id, posts.author_id, posts.title, posts.content, posts.created_at, users.username
                        FROM posts
                        JOIN users ON posts.author_id = users.id
                        WHERE posts.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

if (!$post) {
    die('게시글이 존재하지 않습니다.');
}

$isOwner = is_logged_in() && ($_SESSION['user_id'] == $post['author_id']);

$stmt = $conn->prepare("SELECT comments.id, comments.post_id, comments.author_id, comments.content, comments.created_at, users.username
                        FROM comments
                        JOIN users ON comments.author_id = users.id
                        WHERE comments.post_id = ?
                        ORDER BY comments.id ASC");
$stmt->bind_param("i", $id);
$stmt->execute();
$comments = $stmt->get_result();
$stmt->close();
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>글 상세</title>
    <style>
        body { font-family: Arial, sans-serif; width: 900px; margin: 40px auto; }
        .box, .comment-box { border: 1px solid #ddd; padding: 20px; }
        .title { font-size: 28px; margin-bottom: 10px; }
        .meta { color: #666; margin-bottom: 20px; }
        .content { min-height: 220px; white-space: pre-wrap; }
        .actions, .comment-actions { margin-top: 20px; }
        .btn { display: inline-block; padding: 8px 14px; border: 1px solid #333; color: #111; text-decoration: none; margin-right: 8px; }
        .comment-box { margin-top: 16px; }
        .comment-meta { font-size: 14px; color: #666; margin-bottom: 8px; }
        textarea { width: 100%; padding: 10px; box-sizing: border-box; margin-top: 10px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>글 상세보기</h1>

    <div class="box">
        <div class="title"><?= htmlspecialchars($post['title']) ?></div>
        <div class="meta">
            작성자: <?= htmlspecialchars($post['username']) ?> |
            작성일: <?= $post['created_at'] ?>
        </div>
        <div class="content"><?= htmlspecialchars($post['content']) ?></div>
    </div>

    <div class="actions">
        <a class="btn" href="index.php">목록</a>
        <?php if ($isOwner): ?>
            <a class="btn" href="edit.php?id=<?= $post['id'] ?>">수정</a>
            <a class="btn" href="delete.php?id=<?= $post['id'] ?>" onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
        <?php endif; ?>
    </div>

    <h2 style="margin-top:40px;">댓글</h2>

    <?php if (is_logged_in()): ?>
        <form method="post" action="comment_create.php">
            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
            <textarea name="content" rows="4" required placeholder="댓글을 입력하세요."></textarea>
            <button type="submit">댓글 작성</button>
        </form>
    <?php else: ?>
        <p><a href="login.php">로그인</a> 후 댓글을 작성할 수 있습니다.</p>
    <?php endif; ?>

    <?php while ($comment = $comments->fetch_assoc()): ?>
        <?php $isCommentOwner = is_logged_in() && ($_SESSION['user_id'] == $comment['author_id']); ?>
        <div class="comment-box">
            <div class="comment-meta">
                작성자: <?= htmlspecialchars($comment['username']) ?> |
                작성일: <?= $comment['created_at'] ?>
            </div>
            <div><?= nl2br(htmlspecialchars($comment['content'])) ?></div>

            <?php if ($isCommentOwner): ?>
                <div class="comment-actions">
                    <a class="btn" href="comment_edit.php?id=<?= $comment['id'] ?>">댓글 수정</a>
                    <a class="btn" href="comment_delete.php?id=<?= $comment['id'] ?>&post_id=<?= $post['id'] ?>"
                       onclick="return confirm('댓글을 삭제하시겠습니까?');">댓글 삭제</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
</body>
</html>