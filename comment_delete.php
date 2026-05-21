<?php
// DB 연결과 인증 함수 불러오기
include 'db.php';
include 'auth.php';

// 로그인한 사용자만 접근 가능
require_login();

// 댓글 번호와 게시글 번호 확인
if (!isset($_GET['id']) || !isset($_GET['post_id'])) {
    die('잘못된 접근입니다.');
}

$id = (int)$_GET['id'];
$post_id = (int)$_GET['post_id'];
$user_id = $_SESSION['user_id'];

// 본인 댓글만 삭제
$stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND author_id = ?");
if (!$stmt) {
    die("댓글 삭제 prepare 실패: " . $conn->error);
}

$stmt->bind_param("ii", $id, $user_id);

if (!$stmt->execute()) {
    die("댓글 삭제 execute 실패: " . $stmt->error);
}

$stmt->close();

// 삭제 후 게시글 상세 페이지로 이동
header("Location: view.php?id=" . $post_id);
exit;
?>