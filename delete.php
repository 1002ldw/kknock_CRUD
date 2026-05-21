<?php
// DB 연결과 인증 함수 불러오기
include 'db.php';
include 'auth.php';

// 로그인한 사용자만 접근 가능
require_login();

// 게시글 번호 확인
if (!isset($_GET['id'])) {
    die('잘못된 접근입니다.');
}

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// 게시글에 달린 댓글 먼저 삭제
$stmt = $conn->prepare("DELETE FROM comments WHERE post_id = ?");
if (!$stmt) {
    die("댓글 삭제 prepare 실패: " . $conn->error);
}

$stmt->bind_param("i", $id);

if (!$stmt->execute()) {
    die("댓글 삭제 execute 실패: " . $stmt->error);
}

$stmt->close();

// 본인 글만 삭제
$stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND author_id = ?");
if (!$stmt) {
    die("게시글 삭제 prepare 실패: " . $conn->error);
}

$stmt->bind_param("ii", $id, $user_id);

if (!$stmt->execute()) {
    die("게시글 삭제 execute 실패: " . $stmt->error);
}

$stmt->close();

// 삭제 후 목록 페이지로 이동
header("Location: index.php");
exit;
?>