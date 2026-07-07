<?php

// 인증 상태, 세션 보안, CSRF 검증 등 모든 화면에서 사용하는 보안 기능을 제공합니다.

function parse_ip_list($value) {
    if (!is_string($value) || trim($value) === '') {
        return [];
    }

    return array_values(array_filter(
        preg_split('/[\s,]+/', trim($value)),
        fn($entry) => $entry !== ''
    ));
}

function ip_matches_cidr($ip, $cidr) {
    $parts = explode('/', $cidr, 2);
    if (count($parts) !== 2 || !ctype_digit($parts[1])) {
        return false;
    }

    [$network, $prefix] = $parts;
    $ipBytes = inet_pton($ip);
    $networkBytes = inet_pton($network);
    if ($ipBytes === false || $networkBytes === false || strlen($ipBytes) !== strlen($networkBytes)) {
        return false;
    }

    $prefix = (int) $prefix;
    $bits = strlen($ipBytes) * 8;
    if ($prefix < 0 || $prefix > $bits) {
        return false;
    }

    $fullBytes = intdiv($prefix, 8);
    $remainingBits = $prefix % 8;

    if ($fullBytes > 0 && substr($ipBytes, 0, $fullBytes) !== substr($networkBytes, 0, $fullBytes)) {
        return false;
    }

    if ($remainingBits === 0) {
        return true;
    }

    $mask = (0xff << (8 - $remainingBits)) & 0xff;
    return (ord($ipBytes[$fullBytes]) & $mask) === (ord($networkBytes[$fullBytes]) & $mask);
}

function ip_is_blocked($ip, $blockedIps) {
    if (!is_string($ip) || inet_pton($ip) === false) {
        return false;
    }

    foreach ($blockedIps as $blockedIp) {
        if (strpos($blockedIp, '/') !== false) {
            if (ip_matches_cidr($ip, $blockedIp)) {
                return true;
            }
            continue;
        }

        if (inet_pton($blockedIp) !== false && $ip === $blockedIp) {
            return true;
        }
    }

    return false;
}

function enforce_ip_blacklist() {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $blockedIps = parse_ip_list(getenv('BLOCKED_IPS') ?: '');
    if (ip_is_blocked($clientIp, $blockedIps)) {
        http_error(403, '접근이 차단된 IP입니다.');
    }
}

// 브라우저의 MIME 추측, iframe 삽입, 외부 리퍼러 전달을 제한합니다.
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
}

// 세션 고정 공격을 줄이고 JavaScript에서 세션 쿠키를 읽지 못하게 설정합니다.
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

enforce_ip_blacklist();

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_post() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_error(405, '허용되지 않은 요청 방식입니다.');
    }
}

// 세션마다 예측할 수 없는 CSRF 토큰 하나를 생성해 상태 변경 요청에 사용합니다.
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input() {
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

// 폼에서 전달된 토큰을 시간 차 공격에 안전한 방식으로 비교합니다.
function verify_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_error(403, '요청을 확인할 수 없습니다. 페이지를 새로고침한 후 다시 시도하세요.');
    }
}

function http_error($status, $message) {
    http_response_code($status);
    exit($message);
}

function text_length($value) {
    $count = preg_match_all('/./us', $value, $matches);
    return $count === false ? strlen($value) : $count;
}

function format_kst_datetime($value) {
    if (!is_string($value) || $value === '') {
        return '';
    }

    $timezone = new DateTimeZone('Asia/Seoul');
    $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value, $timezone);
    if (!$date) {
        try {
            $date = new DateTimeImmutable($value, $timezone);
        } catch (Exception $exception) {
            return $value;
        }
    }

    return $date->setTimezone($timezone)->format('Y-m-d H:i:s') . ' KST';
}
