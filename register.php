<?php
// 모든 에러 출력
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// 회원가입 폼 제출 시 처리
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 입력값 가져오기
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    // 입력값 검증
    if ($username === '' || $password === '' || $confirm === '') {
        $error = "모든 항목을 입력하세요.";
    } elseif ($password !== $confirm) {
        $error = "비밀번호가 일치하지 않습니다.";
    } else {
        // 중복 아이디 확인
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        if (!$checkStmt) {
            die("중복확인 prepare 실패: " . $conn->error);
        }

        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->fetch_assoc()) {
            $error = "이미 존재하는 아이디입니다.";
            $checkStmt->close();
        } else {
            $checkStmt->close();

            // 비밀번호 해시 처리
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // 회원 정보 저장
            $insertStmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            if (!$insertStmt) {
                die("INSERT prepare 실패: " . $conn->error);
            }

            $insertStmt->bind_param("ss", $username, $hashedPassword);

            if (!$insertStmt->execute()) {
                die("INSERT execute 실패: " . $insertStmt->error);
            }

            $insertStmt->close();

            // 회원가입 완료 후 로그인 페이지로 이동
            header("Location: login.php");
            exit;
        }
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
        .error { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>회원가입</h1>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="register.php">
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