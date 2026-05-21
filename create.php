<?php
// DB 연결 파일을 불러옵니다.
// 이 파일 안에는 $conn 이라는 MySQL 연결 객체가 들어 있습니다.
include 'db.php';

// 로그인 관련 함수 파일을 불러옵니다.
// 예: require_login(), is_logged_in()
include 'auth.php';

// 로그인하지 않은 사용자는 글 작성 페이지에 접근할 수 없도록 합니다.
require_login();

// 에러 메시지를 저장할 변수입니다.
// 입력값이 비어 있거나 DB 처리에 문제가 생기면 이 변수에 메시지를 담습니다.
$error = "";

// 현재 요청이 POST 방식이면,
// 사용자가 글 작성 폼을 제출한 것이므로 게시글 등록 처리를 시작합니다.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 사용자가 입력한 제목과 내용을 가져옵니다.
    // trim()은 앞뒤 공백을 제거합니다.
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    // 현재 로그인한 사용자의 id를 세션에서 가져옵니다.
    // 이 값이 posts 테이블의 author_id에 저장됩니다.
    $user_id = $_SESSION['user_id'];

    // 제목이나 내용이 비어 있으면 에러 메시지를 저장합니다.
    if ($title === '' || $content === '') {
        $error = '모든 항목을 입력하세요.';
    } else {
        // 게시글을 DB에 저장하는 SQL문을 준비합니다.
        // author_id, title, content 값을 posts 테이블에 INSERT 합니다.
        $stmt = $conn->prepare("INSERT INTO posts (author_id, title, content) VALUES (?, ?, ?)");

        // SQL 준비에 실패하면 에러 메시지를 출력하고 종료합니다.
        if (!$stmt) {
            die("prepare 실패: " . $conn->error);
        }

        // SQL의 ? 자리에 값을 바인딩합니다.
        // i : 정수형(author_id)
        // s : 문자열(title)
        // s : 문자열(content)
        $stmt->bind_param("iss", $user_id, $title, $content);

        // SQL문 실행에 실패하면 에러 메시지를 출력하고 종료합니다.
        if (!$stmt->execute()) {
            die("execute 실패: " . $stmt->error);
        }

        // 사용이 끝난 statement를 닫습니다.
        $stmt->close();

        // 글 작성이 완료되면 게시판 목록 페이지로 이동합니다.
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
        /* 전체 페이지 기본 스타일 */
        body {
            font-family: Arial, sans-serif;
            width: 900px;
            margin: 40px auto;
        }

        /* 입력창과 textarea 공통 스타일 */
        input, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            margin-bottom: 16px;
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
    <h1>글 작성</h1>

    <!-- 에러 메시지가 있을 경우에만 화면에 출력합니다. -->
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- 게시글 작성 폼입니다.
         method="post" 이므로 제출 시 현재 페이지로 POST 요청이 전송됩니다. -->
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