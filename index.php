<?php
include 'db.php';
$result = $conn->query("SELECT id, title, writer, created_at FROM posts ORDER BY id DESC");
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>게시판 목록</title>
    <style>
        body { font-family: Arial, sans-serif; width: 900px; margin: 40px auto; }
        h1 { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: center; }
        th { background: #f3f3f3; }
        a { text-decoration: none; color: #222; }
        .top { display: flex; justify-content: space-between; align-items: center; }
        .btn { display: inline-block; padding: 8px 14px; border: 1px solid #333; background: #fafafa; }
        .title-cell { text-align: left; }
    </style>
</head>
<body>
    <div class="top">
        <h1>CRUD 게시판</h1>
        <a class="btn" href="create.php">글쓰기</a>
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
            <td><?= htmlspecialchars($row['writer']) ?></td>
            <td><?= $row['created_at'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>