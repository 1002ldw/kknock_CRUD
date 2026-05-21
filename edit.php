<?php
// DB 연결 파일을 불러옵니다.
// 이 파일 안에는 $conn 이라는 MySQL 연결 객체가 들어 있습니다.
include 'db.php';

// 로그인 관련 함수 파일을 불러옵니다.
// 예: require_login(), is_logged_in()
include 'auth.php';

// 로그인하지 않은 사용자는 접근할 수 없도록 합니다.
require_login();

// URL에 수정할 게시글 번호(id)가 없으면 잘못된 접근으로 처리합니다.
if (!isset($_GET['id'])) {
    die('잘못된 접근입니다.');
}

// GET으로 전달된 게시글 번호를 정수형으로 변환합니다.
$id = (int)$_GET['id'];

// 현재 로그인한 사용자의 id를 세션에서 가져옵니다.
$user_id = $_SESSION['user_id'];

// 에러 메시지를 저장할 변수입니다.
$error = '';

// 수정하려는 게시글이 존재하는지,
// 그리고 현재 로그인한 사용자가 그 글의 작성자인지 확인하는 SQL문입니다.
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? AND author_id = ?");

// SQL 준비에 실패하면 에러 메시지를 출력하고 종료합니다.
if (!$stmt) {
    die("게시글 조회 prepare 실패: " . $conn->error);
}

// SQL문의 ? 자리에 게시글 번호와 현재 로그인한 사용자 번호를 정수형(i)으로 바인딩합니다.
$stmt->bind_param("ii", $id, $user_id);

// SQL문을 실행합니다.
$stmt->execute();

// 실행 결과를 가져옵니다.
$result = $stmt->get_result();

// 조건에 맞는 게시글 1개의 정보를 가져옵니다.
$post = $result->fetch_assoc();

// 사용이 끝난 statement를 닫습니다.
$stmt->close();

// 게시글이 없으면 본인 글이 아니거나 존재하지 않는 글이므로 수정할 수 없습니다.
if (!$post) {
    die('본인 글만 수정할 수 있습니다.');
}

// 현재 요청이 POST 방식이면,
// 사용자가 수정 폼을 제출한 것이므로 수정 처리를 진행합니다.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 사용자가 입력한 제목과 내용을 가져오고 앞뒤 공백을 제거합니다.
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    // 제목이나 내용이 비어 있으면 에러 메시지를 저장합니다.
    if ($title === '' || $content === '') {
        $error = '모든 항목을 입력하세요.';
    } else {
        // 게시글 제목과 내용을 수정하는 SQL문을 준비합니다.
        // 현재 글 번호(id)와 작성자(author_id)가 모두 일치할 때만 수정됩니다.
        $stmt = $conn->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ? AND author_id = ?");

        // SQL 준비에 실패하면 에러 메시지를 출력하고 종료합니다.
        if (!$stmt) {
            die("게시글 수정 prepare 실패: " . $conn->error);
        }

        // SQL문의 ? 자리에 제목, 내용, 글 번호, 작성자 번호를 바인딩합니다.
        // s : 문자열(title)
        // s : 문자열(content)
        // i : 정수형(id)
        // i : 정수형(author_id)
        $stmt->bind_param("ssii", $title, $content, $id, $user_id);

        // SQL 실행에 실패하면 에러 메시지를 출력하고 종료합니다.
        if (!$stmt->execute()) {
            die("게시글 수정 execute 실패: " . $stmt->error);
        }

        // 사용이 끝난 statement를 닫습니다.
        $stmt->close();

        // 수정이 완료되면 게시글 상세보기 페이지로 이동합니다.
        header('Location: view.php?id=' . $id);
        exit;
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>글 수정</title>
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
    <h1>글 수정</h1>

    <!-- 에러 메시지가 있을 때만 화면에 출력합니다. -->
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- 게시글 수정 폼입니다.
         method="post" 이므로 제출 시 현재 페이지로 POST 요청이 전송됩니다. -->
    <form method="post">
        <label>제목</label>
        <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>" required>

        <label>내용</label>
        <textarea name="content" rows="10" required><?= htmlspecialchars($post['content']) ?></textarea>

        <button type="submit">수정 완료</button>
        <a href="view.php?id=<?= $post['id'] ?>">취소</a>
    </form>
</body>
</html>