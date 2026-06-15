<?php
// POST 요청과 CSRF 토큰을 확인한 뒤 현재 로그인 세션을 완전히 종료합니다.
include 'auth.php';

require_post();
verify_csrf();

// 서버 세션 데이터와 브라우저 세션 쿠키를 모두 제거합니다.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

header('Location: login.php');
exit;
