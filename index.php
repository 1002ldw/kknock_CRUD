<?php
// DB 연결과 인증 함수 불러오기
include 'db.php';
include 'auth.php';

// 로그인한 사용자만 접근 가능
require_login();

// 게시글 목록 조회
$sql = "SELECT posts.id, posts.title, posts.created_at, users.username
        FROM posts
        JOIN users ON posts.author_id = users.id
        ORDER BY posts.id DESC";

$result = $conn->query($sql);

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
        body { font-family: Arial, sans-serif; width: 900px; margin: 40px auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: center; }
        th { background: #f3f3f3; }
        a { text-decoration: none; color: #222; }
        .top { display: flex; justify-content: space-between; align-items: center; }
        .btn { display: inline-block; padding: 8px 14px; border: 1px solid #333; background: #fafafa; margin-left: 6px; }
        .title-cell { text-align: left; }
    </style>
</head>
<body>
    <div class="top">
        <h1>CRUD 게시판</h1>
        <div>
            <span><?= htmlspecialchars($_SESSION['username']) ?>님</span>
            <a class="btn" href="create.php">글쓰기</a>
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
            <td><?= $row['id'] ?></td>
            <td class="title-cell">
                <a href="view.php?id=<?= $row['id'] ?>"><?= htmlspecialchars($row['title']) ?></a>
            </td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>