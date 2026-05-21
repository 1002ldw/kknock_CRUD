<?php
// DB 연결과 인증 함수 불러오기
include 'db.php';
include 'auth.php';

// 로그인한 사용자만 접근 가능
require_login();

// 댓글 번호 확인
if (!isset($_GET['id'])) {
    die('잘못된 접근입니다.');
}

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$error = '';

// 본인 댓글인지 확인
$stmt = $conn->prepare("SELECT * FROM comments WHERE id = ? AND author_id = ?");
if (!$stmt) {
    die("댓글 조회 prepare 실패: " . $conn->error);
}

$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$comment = $result->fetch_assoc();
$stmt->close();

if (!$comment) {
    die('본인 댓글만 수정할 수 있습니다.');
}

// 수정 폼 제출 시 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');

    if ($content === '') {
        $error = '댓글 내용을 입력하세요.';
    } else {
        // 댓글 수정
        $stmt = $conn->prepare("UPDATE comments SET content = ? WHERE id = ? AND author_id = ?");
        if (!$stmt) {
            die("댓글 수정 prepare 실패: " . $conn->error);
        }

        $stmt->bind_param("sii", $content, $id, $user_id);

        if (!$stmt->execute()) {
            die("댓글 수정 execute 실패: " . $stmt->error);
        }

        $stmt->close();

        // 수정 완료 후 게시글 상세 페이지로 이동
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
        body { font-family: Arial, sans-serif; width: 900px; margin: 40px auto; }
        textarea { width: 100%; padding: 10px; box-sizing: border-box; margin-top: 10px; margin-bottom: 10px; }
        .error { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>댓글 수정</h1>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <textarea name="content" rows="5" required><?= htmlspecialchars($comment['content']) ?></textarea>
        <button type="submit">수정 완료</button>
        <a href="view.php?id=<?= $comment['post_id'] ?>">취소</a>
    </form>
</body>
</html>