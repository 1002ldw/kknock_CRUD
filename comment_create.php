<?php
// DB 연결 파일을 불러옵니다.
include 'db.php';

// 로그인 관련 함수 파일을 불러옵니다.
include 'auth.php';

// 로그인한 사용자만 댓글을 작성할 수 있습니다.
require_login();

// POST 요청이 아니면 잘못된 접근으로 처리합니다.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die('잘못된 접근입니다.');
}

// 댓글이 달릴 게시글 번호를 정수형으로 변환합니다.
$post_id = (int)($_POST['post_id'] ?? 0);

// 현재 로그인한 사용자의 id를 가져옵니다.
$user_id = $_SESSION['user_id'];

// 댓글 내용을 가져오고 앞뒤 공백을 제거합니다.
$content = trim($_POST['content'] ?? '');

// 게시글 번호가 없거나 댓글 내용이 비어 있으면 처리하지 않습니다.
if ($post_id <= 0 || $content === '') {
    die('댓글 내용을 입력하세요.');
}

// 댓글을 comments 테이블에 저장하는 SQL문을 준비합니다.
$stmt = $conn->prepare("INSERT INTO comments (post_id, author_id, content) VALUES (?, ?, ?)");

// SQL 준비 실패 시 에러를 출력하고 종료합니다.
if (!$stmt) {
    die("댓글 작성 prepare 실패: " . $conn->error);
}

// SQL문의 ? 자리에 게시글 번호, 사용자 번호, 댓글 내용을 바인딩합니다.
$stmt->bind_param("iis", $post_id, $user_id, $content);

// SQL 실행 실패 시 에러를 출력하고 종료합니다.
if (!$stmt->execute()) {
    die("댓글 작성 execute 실패: " . $stmt->error);
}

// statement를 닫습니다.
$stmt->close();

// 댓글 작성 후 다시 해당 게시글 상세보기 페이지로 이동합니다.
header("Location: view.php?id=" . $post_id);
exit;
?>