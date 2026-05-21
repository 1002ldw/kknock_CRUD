<?php
// MySQL 서버 주소를 저장합니다.
// localhost는 현재 이 PHP 파일이 실행되는 서버 안의 MySQL을 의미합니다.
$host = "localhost";

// MySQL에 접속할 사용자 계정명입니다.
$user = "board_user";

// 위 사용자 계정의 비밀번호입니다.
$pass = "password";

// 접속할 데이터베이스 이름입니다.
$dbname = "cruddb";

// MySQL 서버에 실제로 접속합니다.
// new mysqli(호스트, 사용자명, 비밀번호, DB이름) 형식으로 연결 객체를 생성합니다.
$conn = new mysqli($host, $user, $pass, $dbname);

// DB 연결에 실패했는지 확인합니다.
// connect_error에 값이 있으면 연결이 실패한 것이므로 메시지를 출력하고 프로그램을 종료합니다.
if ($conn->connect_error) {
    die("DB 연결 실패: " . $conn->connect_error);
}

// 문자 인코딩을 utf8mb4로 설정합니다.
// 한글, 이모지 등 다양한 문자가 깨지지 않도록 하기 위해 보통 utf8mb4를 사용합니다.
$conn->set_charset("utf8mb4");
?>