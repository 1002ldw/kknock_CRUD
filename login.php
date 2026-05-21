<?php
// DB 연결 파일을 불러옵니다.
// 이 파일 안에는 $conn 이라는 MySQL 연결 객체가 들어 있습니다.
include 'db.php';

// 로그인 관련 함수들을 불러옵니다.
// 예: is_logged_in()
include 'auth.php';

// 이미 로그인한 사용자가 로그인 페이지에 다시 오면
// 게시판 메인(index.php)으로 이동시킵니다.
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

// 에러 메시지를 저장할 변수입니다.
// 로그인 실패 시 이 변수에 문구를 넣어서 화면에 출력합니다.
$error = "";

// 현재 요청이 POST 방식이면,
// 사용자가 로그인 폼을 제출했다는 뜻이므로 로그인 처리를 시작합니다.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 사용자가 입력한 아이디와 비밀번호를 가져옵니다.
    // trim()은 앞뒤 공백을 제거합니다.
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // 아이디나 비밀번호가 비어 있으면 에러 메시지를 저장합니다.
    if ($username === '' || $password === '') {
        $error = "아이디와 비밀번호를 입력하세요.";
    } else {
        // 입력한 아이디와 일치하는 사용자를 조회하는 SQL문을 준비합니다.
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");

        // SQL 준비에 실패하면 에러 메시지를 출력하고 종료합니다.
        if (!$stmt) {
            die("prepare 실패: " . $conn->error);
        }

        // SQL의 ? 자리에 $username 값을 문자열(s) 타입으로 바인딩합니다.
        $stmt->bind_param("s", $username);

        // SQL문을 실행합니다.
        $stmt->execute();

        // 실행 결과를 가져옵니다.
        $result = $stmt->get_result();

        // 조회된 사용자 한 명의 정보를 연관배열 형태로 가져옵니다.
        // 사용자가 없으면 false가 들어갑니다.
        $user = $result->fetch_assoc();

        // 사용이 끝난 statement를 닫습니다.
        $stmt->close();

        // 사용자가 존재하고,
        // 입력한 비밀번호가 DB에 저장된 해시 비밀번호와 일치하면 로그인 성공입니다.
        if ($user && password_verify($password, $user['password'])) {
            // 세션 고정 공격을 방지하기 위해 로그인 성공 후 세션 ID를 새로 발급합니다.
            session_regenerate_id(true);

            // 세션에 로그인한 사용자 정보를 저장합니다.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // 로그인 성공 후 메인 페이지로 이동합니다.
            header("Location: index.php");
            exit;
        } else {
            // 아이디가 없거나 비밀번호가 틀리면 에러 메시지를 저장합니다.
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
        /* 페이지 전체 기본 스타일 */
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

        /* 에러 메시지 색상 */
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <h1>로그인</h1>

    <!-- 에러 메시지가 있을 때만 화면에 출력합니다. -->
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- 로그인 폼입니다.
         method="post" 이므로 제출 시 