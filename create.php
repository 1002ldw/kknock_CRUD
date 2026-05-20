<?php
include 'db.php';
include 'auth.php';
require_login();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $user_id = $_SESSION['user_id'];

    if ($title === '' || $content === '') {
        $error = '모든 항목을 입력하세요.';
    } else {
        $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $title, $content);
        $stmt->execute();
        $stmt->close();

        header("Location: index.php");
        exit;
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>글 작성</title>
    <style>
        body { font-family: Arial, sans-serif; width: 900px; margin: 40px auto; }
        input, textarea { width: 100%; padding: 10px; margin-top: 8px; margin-bottom: 16px; box-sizing: border-box; }
        .error { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>글 작성</h1>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <form method="post">
        <label>제목</label>
        <input type="text" name="title" required>

        <label>내용</label>
        <textarea name="content" rows="10" required></textarea>

        <button type="submit">등록</button>
        <a href="index.php">목록</a>
    </form>
</body>
</html>