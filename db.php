<?php
// MySQL 서버 접속 정보
$host = "localhost";
$user = "board_user";
$pass = "password";
$dbname = "cruddb";

// MySQL 데이터베이스에 연결
$conn = new mysqli($host, $user, $pass, $dbname);

// 연결 실패 시 에러 메시지 출력 후 종료
if ($conn->connect_error) {
    die("DB 연결 실패: " . $conn->connect_error);
}

// 한글 및 다양한 문자 처리를 위해 문자셋 설정
$conn->set_charset("utf8mb4");
?>