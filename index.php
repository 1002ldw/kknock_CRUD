<?php
// DB 연결 파일을 불러옵니다.
// 이 파일 안에는 $conn 이라는 MySQL 연결 객체가 들어 있습니다.
include 'db.php';

// 로그인 관련 함수(require_login)를 불러옵니다.
include 'auth.php';

// 로그인하지 않은 사용자는 접근하지 못하게 합니다.
// 로그인하지 않았다면 보통 login.php로 이동시킵니다.
require_login();

// 게시글 목록을 가져오는 SQL문입니다.
// posts 테이블의 author_id와 users 테이블의 id를 JOIN하여
// 글쓴이의 username도 함께 조회합니다.
$sql = "SELECT posts.id, posts.title, posts.created_at, users.username
        FROM posts
        JOIN users ON posts.author_id = users.id
        ORDER BY posts.id DESC";

// SQL문을 실행합니다.
$result = $conn->query($sql);

// 쿼리 실행에 실패하면 에러 메시지를 출력하고 종료합니다.
if (!$result) {
    die("게시글 조회 실패: " . $conn->error);
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>게시판 목록</title>
    <style>
        /* 전체 페이지 기본 스타일 */
        body {
            font-family: Arial, sans-serif;
            width: 900px;
            margin: 40px auto;
        }

        /* 표 전체 스타일 */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        /* 표 칸 스타일 */
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }

        /* 표 제목 행 배경색 */
        th {
            background: #f3f3f3;
        }

        /* 링크 기본 스타일 */
        a {
            text-decoration: none;
            color: #222;
        }

        /* 상단 제목과 버튼 정렬 */
        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* 버튼처럼 보이게 하는 링크 스타일 */
        .btn {
            display: inline-block;
            padding: 8px 14px;
            border: 1px solid #333;
            background: #fafafa;
            margin-left: 6px;
        }

        /* 제목 칸은 왼쪽 정렬 */
        .title-cell {
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="top">
        <h1>CRUD 게시판</h1>
        <div>
            <!-- require_login() 때문에 여기서는 로그인 상태가 항상 유지됩니다. -->
            <!-- 현재 로그인한 사용자 이름을 출력합니다. -->
            <span><?= htmlspecialchars($_SESSION['username']) ?>님</span>

            <!-- 글쓰기 페이지로 이동 -->
            <a class="btn" href="create.php">글쓰기</a>

            <!-- 로그아웃 처리 페이지로 이동 -->
            <a class="btn" href="logout.php">로그아웃</a>
        </div>
    </div>

    <table>
        <tr>
            <th>번호</th>
            <th>제목</th>
            <th>작성자</th>
            <th>작성일</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <!-- 게시글 번호 출력 -->
            <td><?= $row['id'] ?></td>

            <!-- 게시글 제목 출력, 클릭하면 상세보기 페이지로 이동 -->
            <td class="title-cell">
                <a href="view.php?id=<?= $row['id'] ?>">
                    <?= htmlspecialchars($row['title']) ?>
                </a>
            </td>

            <!-- 작성자 이름 출력 -->
            <td><?= htmlspecialchars($row['username']) ?></td>

            <!-- 작성일 출력 -->
            <td><?= htmlspecialchars($row['created_at']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>