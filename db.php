<?php
$host = "localhost";
$user = "board_user";
$pass = "password";
$dbname = "cruddb";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("DB 연결 실패: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>