<?php
// 로그인/세션 관련 설정 파일을 불러옵니다.
// 보통 auth.php 안에서 session_start()가 실행됩니다.
include 'auth.php';

// 현재 세션에 저장된 모든 데이터를 비웁니다.
session_unset();

// 세션 쿠키를 사용하는 환경이라면,
// 브라우저에 저장된 세션 쿠키도 만료시켜 완전히 로그아웃되도록 합니다.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),   // 현재 세션 쿠키 이름
        '',               // 빈 값으로 설정
        time() - 42000,   // 과거 시간으로 설정하여 만료 처리
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 서버에 저장된 세션 자체를 삭제합니다.
session_destroy();

// 로그아웃 후 로그인 페이지로 이동합니다.
header("Location: login.php");
exit;
?>