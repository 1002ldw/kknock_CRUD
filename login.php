<?php
// 사용자 자격 증명을 확인하고 로그인 세션을 생성합니다.
include 'db.php';
include 'auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = '아이디와 비밀번호를 입력하세요.';
    } else {
        $stmt = $conn->prepare('SELECT id, username, password FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 인증 성공 시 세션 ID를 교체해 세션 고정 공격 가능성을 줄입니다.
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            unset($_SESSION['csrf_token']);
            header('Location: index.php');
            exit;
        }

        $error = '로그인 정보가 올바르지 않습니다.';
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>로그인</title>
    <style>body{font-family:Arial,sans-serif;max-width:500px;margin:40px auto;padding:0 16px}input{width:100%;padding:10px;margin:8px 0 16px;box-sizing:border-box}.error{color:#b00020}</style>
</head>
<body>
    <h1>로그인</h1>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
        <?= csrf_input() ?>
        <label for="username">아이디</label>
        <input id="username" type="text" name="username" value="<?= htmlspecialchars($username) ?>" maxlength="50" autocomplete="username" required>
        <label for="password">비밀번호</label>
        <input id="password" type="password" name="password" maxlength="255" autocomplete="current-password" required>
        <button type="submit">로그인</button>
        <a href="register.php">회원가입</a>
    </form>
</body>
</html>
