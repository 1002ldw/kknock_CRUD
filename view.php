<?php
// DB 연결 파일을 불러옵니다.
// 이 파일 안에는 $conn 이라는 MySQL 연결 객체가 들어 있습니다.
include 'db.php';

// 로그인 관련 함수 파일을 불러옵니다.
// 예: is_logged_in(), require_login()
include 'auth.php';

// URL에 게시글 번호(id)가 없으면 잘못된 접근으로 처리합니다.
if (!isset($_GET['id'])) {
    die('잘못된 접근입니다.');
}

// GET으로 전달된 게시글 번호를 정수형으로 변환합니다.
$id = (int)$_GET['id'];

// 게시글 상세 정보를 가져오는 SQL문을 준비합니다.
// posts 테이블과 users 테이블을 JOIN하여 작성자 username도 함께 가져옵니다.
$stmt = $conn->prepare("SELECT posts.id, posts.author_id, posts.title, posts.content, posts.created_at, users.username
                        FROM posts
                        JOIN users ON posts.author_id = users.id
                        WHERE posts.id = ?");

// SQL 준비 실패 시 에러 메시지를 출력하고 종료합니다.
if (!$stmt) {
    die("게시글 조회 prepare 실패: " . $conn->error);
}

// SQL문의 ? 자리에 게시글 번호를 정수형(i)으로 바인딩합니다.
$stmt->bind_param("i", $id);

// SQL문을 실행합니다.
$stmt->execute();

// 실행 결과를 가져옵니다.
$result = $stmt->get_result();

// 게시글 1개의 정보를 연관배열 형태로 가져옵니다.
$post = $result->fetch_assoc();

// 사용이 끝난 statement를 닫습니다.
$stmt->close();

// 게시글이 존재하지 않으면 에러 메시지를 출력하고 종료합니다.
if (!$post) {
    die('게시글이 존재하지 않습니다.');
}

// 현재 로그인한 사용자가 이 글의 작성자인지 확인합니다.
// 로그인 상태이고, 세션의 user_id와 게시글의 author_id가 같으면 true입니다.
$isOwner = is_logged_in() && ($_SESSION['user_id'] == $post['author_id']);

// 댓글 목록을 가져오는 SQL문을 준비합니다.
// comments 테이블과 users 테이블을 JOIN하여 댓글 작성자의 username도 함께 가져옵니다.
$stmt = $conn->prepare("SELECT comments.id, comments.post_id, comments.author_id, comments.content, comments.created_at, users.username
                        FROM comments
                        JOIN users ON comments.author_id = users.id
                        WHERE comments.post_id = ?
                        ORDER BY comments.id ASC");

// SQL 준비 실패 시 에러 메시지를 출력하고 종료합니다.
if (!$stmt) {
    die("댓글 조회 prepare 실패: " . $conn->error);
}

// SQL문의 ? 자리에 현재 게시글 번호를 정수형(i)으로 바인딩합니다.
$stmt->bind_param("i", $id);

// SQL문을 실행합니다.
$stmt->execute();

// 댓글 목록 결과를 가져옵니다.
$comments = $stmt->get_result();

// 사용이 끝난 statement를 닫습니다.
$stmt->close();
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>글 상세</title>
    <style>
        /* 전체 페이지 기본 스타일 */
        body {
            font-family: Arial, sans-serif;
            width: 900px;
            margin: 40px auto;
        }

        /* 게시글 박스와 댓글 박스 공통 스타일 */
        .box, .comment-box {
            border: 1px solid #ddd;
            padding: 20px;
        }

        /* 게시글 제목 스타일 */
        .title {
            font-size: 28px;
            margin-bottom: 10px;
        }

        /* 게시글/댓글 메타 정보 스타일 */
        .meta, .comment-meta {
            color: #666;
            margin-bottom: 20px;
        }

        /* 댓글 메타 정보는 조금 더 작게 표시 */
        .comment-meta {
            font-size: 14px;
            margin-bottom: 8px;
        }

        /* 본문 내용은 줄바꿈을 유지해서 출력합니다. */
        .content {
            min-height: 220px;
            white-space: pre-wrap;
        }

        /* 버튼 영역 여백 */
        .actions, .comment-actions {
            margin-top: 20px;
        }

        /* 버튼처럼 보이는 링크 스타일 */
        .btn {
            display: inline-block;
            padding: 8px 14px;
            border: 1px solid #333;
            color: #111;
            text-decoration: none;
            margin-right: 8px;
        }

        /* 댓글 박스 간격 */
        .comment-box {
            margin-top: 16px;
        }

        /* 댓글 입력창 스타일 */
        textarea {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            margin-top: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <h1>글 상세보기</h1>

    <!-- 게시글 상세 내용을 출력하는 영역입니다. -->
    <div class="box">
        <!-- 게시글 제목 출력 -->
        <div class="title"><?= htmlspecialchars($post['title']) ?></div>

        <!-- 작성자와 작성일 출력 -->
        <div class="meta">
            작성자: <?= htmlspecialchars($post['username']) ?> |
            작성일: <?= htmlspecialchars($post['created_at']) ?>
        </div>

        <!-- 게시글 본문 출력 -->
        <div class="content"><?= htmlspecialchars($post['content']) ?></div>
    </div>

    <!-- 목록, 수정, 삭제 버튼 영역 -->
    <div class="actions">
        <a class="btn" href="index.php">목록</a>

        <!-- 현재 로그인한 사용자가 글 작성자인 경우에만 수정/삭제 버튼을 보여줍니다. -->
        <?php if ($isOwner): ?>
            <a class="btn" href="edit.php?id=<?= $post['id'] ?>">수정</a>
            <a class="btn" href="delete.php?id=<?= $post['id'] ?>" onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
        <?php endif; ?>
    </div>

    <h2 style="margin-top:40px;">댓글</h2>

    <!-- 로그인한 사용자만 댓글 작성 폼을 볼 수 있습니다. -->
    <?php if (is_logged_in()): ?>
        <form method="post" action="comment_create.php">
            <!-- 어떤 게시글에 대한 댓글인지 전달하기 위한 hidden 값 -->
            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">

            <!-- 댓글 내용 입력창 -->
            <textarea name="content" rows="4" required placeholder="댓글을 입력하세요."></textarea>

            <button type="submit">댓글 작성</button>
        </form>
    <?php else: ?>
        <!-- 비로그인 사용자는 로그인 후 댓글 작성 가능 -->
        <p><a href="login.php">로그인</a> 후 댓글을 작성할 수 있습니다.</p>
    <?php endif; ?>

    <!-- 댓글 목록을 하나씩 출력합니다. -->
    <?php while ($comment = $comments->fetch_assoc()): ?>
        <?php
        // 현재 로그인한 사용자가 이 댓글의 작성자인지 확인합니다.
        $isCommentOwner = is_logged_in() && ($_SESSION['user_id'] == $comment['author_id']);
        ?>
        <div class="comment-box">
            <!-- 댓글 작성자, 작성일 출력 -->
            <div class="comment-meta">
                작성자: <?= htmlspecialchars($comment['username']) ?> |
                작성일: <?= htmlspecialchars($comment['created_at']) ?>
            </div>

            <!-- 댓글 본문 출력, 줄바꿈은 <br>로 변환하여 표시 -->
            <div><?= nl2br(htmlspecialchars($comment['content'])) ?></div>

            <!-- 현재 로그인한 사용자가 댓글 작성자인 경우에만 수정/삭제 버튼을 보여줍니다. -->
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