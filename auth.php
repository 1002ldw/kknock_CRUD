<?php
// 세션이 아직 시작되지 않았다면 세션을 시작합니다.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 현재 사용자가 로그인 상태인지 확인하는 함수입니다.
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// 로그인하지 않은 사용자는 로그인 페이지로 보내는 함수입니다.
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}
?>