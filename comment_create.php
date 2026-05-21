<?php
// DB 연결과 인증 함수 불러오기
include 'db.php';
include 'auth.php';

// 로그인한 사용자만 접근 가능
require_login();

// POST 방식만 허용
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die('잘못된 접근입니다.');
}

$post_id = (int)($_POST['post_id'] ?? 0);
$user_id = $_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');

// 입력값 검증
if ($post_id <= 0 || $content === '') {
    die('댓글 내용을 입력하세요.');
}

// 댓글 저장
$stmt = $conn->prepare("INSERT INTO comments (post_id, author_id, content) VALUES (?, ?, ?)");
if (!$stmt) {
    die("댓글 작성 prepare 실패: " . $conn->error);
}

$stmt->bind_param("iis", $post_id, $user_id, $content);

if (!$stmt->execute()) {
    die("댓글 작성 execute 실패: " . $stmt->error);
}

$stmt->close();

// 작성 후 게시글 상세 페이지로 이동
header("Location: view.php?id=" . $post_id);
exit;
?>