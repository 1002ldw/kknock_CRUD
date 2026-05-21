<?php
// 인증 파일 불러오기
include 'auth.php';

// 세션 데이터 비우기
session_unset();

// 세션 삭제
session_destroy();

// 로그인 페이지로 이동
header("Location: login.php");
exit;
?>