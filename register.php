<?php
// PHP의 모든 에러를 표시하도록 설정합니다.
// 개발 중에는 오류 원인을 찾기 쉬워지므로 유용합니다.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB 연결 파일을 불러옵니다.
// 이 파일 안에는 $conn 이라는 MySQL 연결 객체가 들어 있습니다.
include 'db.php';

// 로그인 관련 함수 파일을 불러옵니다.
// 예: is_logged_in()
include 'auth.php';

// 이미 로그인한 사용자가 회원가입 페이지에 접근하면
// 게시판 메인(index.php)으로 이동시킵니다.
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

// 에러 메시지를 저장할 변수입니다.
// 입력값 문제나 중복 아이디가 있을 경우 이 변수에 메시지를 담습니다.
$error = "";

// 현재 요청이 POST 방식이면,
// 사용자가 회원가입 폼을 제출한 것이므로 회원가입 처리를 진행합니다.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 사용자가 입력한 아이디, 비밀번호, 비밀번호 확인 값을 가져옵니다.
    // trim()은 앞뒤 공백을 제거합니다.
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    // 하나라도 비어 있으면 에러 메시지를 저장합니다.
    if ($username === '' || $password === '' || $confirm === '') {
        $error = "모든 항목을 입력하세요.";
    }
    // 비밀번호와 비밀번호 확인 값이 다르면 에러 메시지를 저장합니다.
    elseif ($password !== $confirm) {
        $error = "비밀번호가 일치하지 않습니다.";
    } else {
        // 입력한 아이디가 이미 존재하는지 확인하는 SQL문을 준비합니다.
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");

        // SQL 준비에 실패하면 에러 메시지를 출력하고 종료합니다.
        if (!$checkStmt) {
            die("중복확인 prepare 실패: " . $conn->error);
        }

        // SQL문의 ? 자리에 사용자 아이디를 문자열(s) 타입으로 바인딩합니다.
        $checkStmt->bind_param("s", $username);

        // SQL문을 실행합니다.
        $checkStmt->execute();

        // 실행 결과를 가져옵니다.
        $result = $checkStmt->get_result();

        // 이미 같은 아이디가 존재하면 회원가입을 막습니다.
        if ($result->fetch_assoc()) {
            $error = "이미 존재하는 아이디입니다.";
            $checkStmt->close();
        } else {
            // 중복 확인용 statement를 닫습니다.
            $checkStmt->close();

            // 비밀번호를 해시로 변환합니다.
            // DB에는 평문 비밀번호가 아니라 해시된 값이 저장됩니다.
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // 회원 정보를 users 테이블에 저장하는 SQL문을 준비합니다.
            $insertStmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");

            // SQL 준비에 실패하면 에러 메시지를 출력하고 종료합니다.
            if (!$insertStmt) {
                die("INSERT prepare 실패: " . $conn->error);
            }

            // SQL문의 ? 자리에 아이디와 해시 비밀번호를 바인딩합니다.
            $insertStmt->bind_param("ss", $username, $hashedPassword);

            // SQL 실행에 실패하면 에러 메시지를 출력하고 종료합니다.
            if (!$insertStmt->execute()) {
                die("INSERT execute 실패: " . $insertStmt->error);
            }

            // 사용이 끝난 statement를 닫습니다.
            $insertStmt->close();

            // 회원가입이 완료되면 로그인 페이지로 이동합니다.
            // 상대 경로를 사용하면 현재 프로젝트 구조에서 더 안전하게 동작합니다.
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
        /* 전체 페이지 기본 스타일 */
        body {
            font-family: Arial, sans-serif;
            width: 500px;
            margin: 40px auto;
        }

        /* 입력창 스타일 */
        input {
            width: 100%;
            padding: 10px;
            margin: 8px 0 16px;
            box-sizing: border-box;
        }

        /* 에러 메시지 스타일 */
        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <h1>회원가입</h1>

    <!-- 에러 메시지가 있을 때만 화면에 출력합니다. -->
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- 회원가입 폼입니다.
         method="post" 이므로 제출 시 현재 페이지로 POST 요청이 전송됩니다. -->
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