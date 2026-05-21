<?php
// DB 연결 파일을 불러옵니다.
include 'db.php';

// 로그인 관련 함수 파일을 불러옵니다.
include 'auth.php';

// 로그인한 사용자만 댓글을 삭제할 수 있습니다.
require_login();

// URL에 댓글 번호(id)와 게시글 번호(post_id)가 없으면 잘못된 접근으로 처리합니다.
if (!isset($_GET['id']) || !isset($_GET['post_id'])) {
    die('잘못된 접근입니다.');
}

// 삭제할 댓글 번호를 정수형으로 변환합니다.
$id = (int)$_GET['id'];

// 삭제 후 돌아갈 게시글 번호를 정수형으로 변환합니다.
$post_id = (int)$_GET['post_id'];

// 현재 로그인한 사용자의 id를 가져옵니다.
$user_id = $_SESSION['user_id'];

// 본인 댓글만 삭제할 수 있도록
// 댓글 번호와 작성자 번호가 모두 일치하는 경우에만 삭제합니다.
$stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND author_id = ?");

// SQL 준비 실패 시 에러를 출력하고 종료합니다.
if (!$stmt) {
    die("댓글 삭제 prepare 실패: " . $conn->error);
}

// SQL문의 ? 자리에 댓글 번호와 사용자 번호를 바인딩합니다.
$stmt->bind_param("ii", $id, $user_id);

// SQL 실행 실패 시 에러를 출력하고 종료합니다.
if (!$stmt->execute()) {
    die("댓글 삭제 execute 실패: " . $stmt->error);
}

// statement를 닫습니다.
$stmt->close();

// 삭제 후 원래 게시글 상세보기 페이지로 이동합니다.
header("Location: view.php?id=" . $post_id);
exit;
?>