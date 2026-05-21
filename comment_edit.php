<?php
// DB 연결 파일을 불러옵니다.
include 'db.php';

// 로그인 관련 함수 파일을 불러옵니다.
include 'auth.php';

// 로그인한 사용자만 댓글을 수정할 수 있습니다.
require_login();

// URL에 댓글 번호(id)가 없으면 잘못된 접근으로 처리합니다.
if (!isset($_GET['id'])) {
    die('잘못된 접근입니다.');
}

// 수정할 댓글 번호를 정수형으로 변환합니다.
$id = (int)$_GET['id'];

// 현재 로그인한 사용자의 id를 가져옵니다.
$user_id = $_SESSION['user_id'];

// 에러 메시지를 저장할 변수입니다.
$error = '';

// 현재 로그인한 사용자가 작성한 댓글인지 확인하고,
// 수정 폼에 보여줄 기존 댓글 내용을 가져옵니다.
$stmt = $conn->prepare("SELECT * FROM comments WHERE id = ? AND author_id = ?");

// SQL 준비 실패 시 에러를 출력하고 종료합니다.
if (!$stmt) {
    die("댓글 조회 prepare 실패: " . $conn->error);
}

// SQL문의 ? 자리에 댓글 번호와 사용자 번호를 바인딩합니다.
$stmt->bind_param("ii", $id, $user_id);

// SQL문을 실행합니다.
$stmt->execute();

// 실행 결과를 가져옵니다.
$result = $stmt->get_result();

// 댓글 정보를 가져옵니다.
$comment = $result->fetch_assoc();

// statement를 닫습니다.
$stmt->close();

// 댓글이 없으면 본인 댓글이 아니거나 존재하지 않는 것입니다.
if (!$comment) {
    die('본인 댓글만 수정할 수 있습니다.');
}

// POST 요청이면 댓글 수정 처리를 진행합니다.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 사용자가 입력한 댓글 내용을 가져오고 공백을 제거합니다.
    $content = trim($_POST['content'] ?? '');

    // 댓글 내용이 비어 있으면 에러 메시지를 저장합니다.
    if ($content === '') {
        $error = '댓글 내용을 입력하세요.';
    } else {
        // 댓글 내용을 수정하는 SQL문을 준비합니다.
        $stmt = $conn->prepare("UPDATE comments SET content = ? WHERE id = ? AND author_id = ?");

        // SQL 준비 실패 시 에러를 출력하고 종료합니다.
        if (!$stmt) {
            die("댓글 수정 prepare 실패: " . $conn->error);
        }

        // SQL문의 ? 자리에 댓글 내용, 댓글 번호, 사용자 번호를 바인딩합니다.
        $stmt->bind_param("sii", $content, $id, $user_id);

        // SQL 실행 실패 시 에러를 출력하고 종료합니다.
        if (!$stmt->execute()) {
            die("댓글 수정 execute 실패: " . $stmt->error);
        }

        // statement를 닫습니다.
        $stmt->close();

        // 수정 후 원래 게시글 상세 페이지로 이동합니다.
        header("Location: view.php?id=" . $comment['post_id']);
        exit;
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>댓글 수정</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            width: 900px;
            margin: 40px auto;
        }
        textarea {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <h1>댓글 수정</h1>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <textarea name="content" rows="5" required><?= htmlspecialchars($comment['content']) ?></textarea>
        <button type="submit">수정 완료</button>
        <a href="view.php?id=<?= $comment['post_id'] ?>">취소</a>
    </form>
</body>
</html>