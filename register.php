<?php
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
    $confirm = $_POST['confirm_password'] ?? '';

    if ($username === '' || $password === '' || $confirm === '') {
        $error = '모든 항목을 입력하세요.';
    } elseif (!preg_match('/^[\p{L}\p{N}_.-]{2,50}$/u', $username)) {
        $error = '아이디는 2~50자의 문자, 숫자, 밑줄, 점, 하이픈만 사용할 수 있습니다.';
    } elseif (strlen($password) < 8 || strlen($password) > 255) {
        $error = '비밀번호는 8자 이상 255바이트 이하로 입력하세요.';
    } elseif ($password !== $confirm) {
        $error = '비밀번호가 일치하지 않습니다.';
    } else {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
            $stmt->bind_param('ss', $username, $hashedPassword);
            $stmt->execute();
            $stmt->close();
            header('Location: login.php');
            exit;
        } catch (mysqli_sql_exception $exception) {
            if ($exception->getCode() === 1062) {
                $error = '이미 존재하는 아이디입니다.';
            } else {
                error_log((string)$exception);
                $error = '회원가입 처리 중 오류가 발생했습니다.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>회원가입</title>
    <style>body{font-family:Arial,sans-serif;max-width:500px;margin:40px auto;padding:0 16px}input{width:100%;padding:10px;margin:8px 0 16px;box-sizing:border-box}.error{color:#b00020}</style>
</head>
<body>
    <h1>회원가입</h1>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
        <?= csrf_input() ?>
        <label for="username">아이디</label>
        <input id="username" type="text" name="username" value="<?= htmlspecialchars($username) ?>" minlength="2" maxlength="50" autocomplete="username" required>
        <label for="password">비밀번호</label>
        <input id="password" type="password" name="password" minlength="8" maxlength="255" autocomplete="new-password" required>
        <label for="confirm_password">비밀번호 확인</label>
        <input id="confirm_password" type="password" name="confirm_password" minlength="8" maxlength="255" autocomplete="new-password" required>
        <button type="submit">회원가입</button>
        <a href="login.php">로그인</a>
    </form>
</body>
</html>
