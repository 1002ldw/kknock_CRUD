<?php
// DB 연결과 인증 함수 불러오기
include 'db.php';
include 'auth.php';

// 이미 로그인한 사용자는 메인 페이지로 이동
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

// 에러 메시지 저장 변수
$error = "";

// 로그인 폼 제출 시 처리
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 입력값 가져오기
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // 입력값 검증
    if ($username === '' || $password === '') {
        $error = "아이디와 비밀번호를 입력하세요.";
    } else {
        // 사용자 조회
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        if (!$stmt) {
            die("prepare 실패: " . $conn->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // 비밀번호 검증
        if ($user && password_verify($password, $user['password'])) {
            // 세션 보안을 위해 세션 ID 재발급
            session_regenerate_id(true);

            // 세션에 로그인 정보 저장
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // 메인 페이지로 이동
            header("Location: index.php");
            exit;
        } else {
            $error = "로그인 정보가 올바르지 않습니다.";
        }
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그인</title>
    <style>
        body { font-family: Arial, sans-serif; width: 500px; margin: 40px auto; }
        input { width: 100%; padding: 10px; margin: 8px 0 16px; box-sizing: border-box; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>로그인</h1>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>아이디</label>
        <input type="text" name="username" required>

        <label>비밀번호</label>
        <input type="password" name="password" required>

        <button type="submit">로그인</button>
        <a href="register.php">회원가입</a>
    </form>
</body>
</html>