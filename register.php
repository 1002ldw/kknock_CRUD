<?php
include 'db.php';
include 'auth.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if ($username === '' || $password === '' || $confirm === '') {
        $error = "모든 항목을 입력하세요.";
    } elseif ($password !== $confirm) {
        $error = "비밀번호가 일치하지 않습니다.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->fetch_assoc()) {
            $error = "이미 존재하는 아이디입니다.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashedPassword);
            $stmt->execute();

            $success = "회원가입이 완료되었습니다. 로그인하세요.";
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>회원가입</title>
    <style>
        body { font-family: Arial, sans-serif; width: 500px; margin: 40px auto; }
        input { width: 100%; padding: 10px; margin: 8px 0 16px; box-sizing: border-box; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>회원가입</h1>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

    <form method="post">
        <label>아이디</label>
        <input type="text" name="username" required>

        <label>비밀번호</label>
        <input type="password" name="password" required>

        <label>비밀번호 확인</label>
        <input type="password" name="confirm_password" required>

        <button type="submit">회원가입</button>
        <a href="login.php">로그인</a>
    </form>
</body>
</html>