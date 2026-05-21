<?php
// 세션이 시작되지 않았다면 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 현재 로그인 상태인지 확인하는 함수
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// 로그인하지 않은 사용자는 로그인 페이지로 이동시키는 함수
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}
?>