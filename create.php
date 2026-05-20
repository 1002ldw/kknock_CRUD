<?php
include 'db.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title'] ?? '');
    $writer = trim($_POST['writer'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $writer === '' || $content === '') {
        $error = '모든 항목을 입력하세요.';
    } else {
        $stmt = $conn->prepare("INSERT INTO posts (title, writer, content) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $writer, $content);
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
        .btn { padding: 8px 14px; }
        .error { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>글 작성</h1>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
        <label>제목</label>
        <input type="text" name="title" required>

        <label>작성자</label>
        <input type="text" name="writer" required>

        <label>내용</label>
        <textarea name="content" rows="10" required></textarea>

        <button class="btn" type="submit">등록</button>
        <a class="btn" href="index.php">목록</a>
    </form>
</body>
</html>