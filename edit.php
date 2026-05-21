<?php
// DB 연결과 인증 함수 불러오기
include 'db.php';
include 'auth.php';

// 로그인한 사용자만 접근 가능
require_login();

// 게시글 번호 확인
if (!isset($_GET['id'])) {
    die('잘못된 접근입니다.');
}

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$error = '';

// 본인 글인지 확인
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? AND author_id = ?");
if (!$stmt) {
    die("게시글 조회 prepare 실패: " . $conn->error);
}

$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

if (!$post) {
    die('본인 글만 수정할 수 있습니다.');
}

// 수정 폼 제출 시 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $content === '') {
        $error = '모든 항목을 입력하세요.';
    } else {
        // 게시글 수정
        $stmt = $conn->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ? AND author_id = ?");
        if (!$stmt) {
            die("게시글 수정 prepare 실패: " . $conn->error);
        }

        $stmt->bind_param("ssii", $title, $content, $id, $user_id);

        if (!$stmt->execute()) {
            die("게시글 수정 execute 실패: " . $stmt->error);
        }

        $stmt->close();

        // 수정 완료 후 상세 페이지로 이동
        header('Location: view.php?id=' . $id);
        exit;
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>글 수정</title>
    <style>
        body { font-family: Arial, sans-serif; width: 900px; margin: 40px auto; }
        input, textarea { width: 100%; padding: 10px; margin-top: 8px; margin-bottom: 16px; box-sizing: border-box; }
        .error { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>글 수정</h1>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>제목</label>
        <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>" required>

        <label>내용</label>
        <textarea name="content" rows="10" required><?= htmlspecialchars($post['content']) ?></textarea>

        <button type="submit">수정 완료</button>
        <a href="view.php?id=<?= $post['id'] ?>">취소</a>
    </form>
</body>
</html>