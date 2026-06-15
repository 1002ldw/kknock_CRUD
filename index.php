<?php
// 게시판별 게시글을 작성자 검색 조건과 정렬 조건에 따라 조회합니다.
include 'db.php';
include 'auth.php';
include 'board.php';

require_login();

$board = board_type($_GET['board'] ?? 'general');
$search = trim($_GET['user'] ?? '');
if (text_length($search) > 50) {
    http_error(400, '검색어는 50자 이하로 입력하세요.');
}

$sort = $_GET['sort'] ?? 'newest';
// 정렬 SQL은 허용 목록에서만 선택해 사용자 입력이 SQL에 직접 들어가지 않게 합니다.
$sortOptions = [
    'newest' => ['최신순', 'posts.id DESC'],
    'oldest' => ['오래된순', 'posts.id ASC'],
    'title' => ['제목순', 'posts.title ASC, posts.id DESC'],
    'author' => ['작성자순', 'users.username ASC, posts.id DESC'],
];
if (!isset($sortOptions[$sort])) {
    $sort = 'newest';
}

$sql = "SELECT posts.id, posts.title, posts.created_at, users.username,
               COUNT(attachments.id) AS attachment_count
        FROM posts
        JOIN users ON posts.author_id = users.id
        LEFT JOIN attachments ON attachments.post_id = posts.id
        WHERE posts.board_type = ?";

// 검색어가 있을 때만 LIKE 조건과 바인딩 변수를 추가합니다.
if ($search !== '') {
    $sql .= ' AND users.username LIKE ?';
}
$sql .= " GROUP BY posts.id, posts.title, posts.created_at, users.username
          ORDER BY " . $sortOptions[$sort][1];

$stmt = $conn->prepare($sql);
// SQL 조건과 동일하게 검색어가 있을 때만 두 번째 바인딩 값을 전달합니다.
if ($search !== '') {
    $searchPattern = '%' . $search . '%';
    $stmt->bind_param('ss', $board, $searchPattern);
} else {
    $stmt->bind_param('s', $board);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(board_label($board)) ?></title>
    <style>
        body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:0 16px}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{border:1px solid #ddd;padding:12px;text-align:center}th{background:#f3f3f3}a{text-decoration:none;color:#222}.top,.toolbar,.nav{display:flex;justify-content:space-between;align-items:center;gap:10px}.nav{justify-content:flex-start;margin:18px 0}.btn,button{display:inline-block;padding:8px 14px;border:1px solid #333;background:#fafafa;color:#222}.active{background:#333;color:#fff}.title-cell{text-align:left}input,select{padding:8px}.empty{padding:30px;color:#666}.inline{display:inline}.toolbar>div{display:flex;align-items:center;gap:6px}@media(max-width:700px){.top,.toolbar{align-items:stretch;flex-direction:column}table{font-size:14px}th,td{padding:8px}}
    </style>
</head>
<body>
    <div class="top">
        <h1><?= htmlspecialchars(board_label($board)) ?></h1>
        <div>
            <span><?= htmlspecialchars($_SESSION['username']) ?>님</span>
            <a class="btn" href="create.php?board=<?= urlencode($board) ?>">글쓰기</a>
            <form class="inline" method="post" action="logout.php">
                <?= csrf_input() ?>
                <button type="submit">로그아웃</button>
            </form>
        </div>
    </div>

    <nav class="nav" aria-label="게시판 선택">
        <?php foreach (BOARD_TYPES as $type => $label): ?>
            <a class="btn <?= $board === $type ? 'active' : '' ?>" href="<?= htmlspecialchars(board_url($type)) ?>"><?= htmlspecialchars($label) ?></a>
        <?php endforeach; ?>
    </nav>

    <form class="toolbar" method="get">
        <input type="hidden" name="board" value="<?= htmlspecialchars($board) ?>">
        <div>
            <label for="user">작성자 검색</label>
            <input id="user" type="search" name="user" value="<?= htmlspecialchars($search) ?>" maxlength="50" placeholder="사용자명">
            <button type="submit">검색</button>
            <?php if ($search !== ''): ?><a class="btn" href="<?= htmlspecialchars(board_url($board, ['sort' => $sort])) ?>">초기화</a><?php endif; ?>
        </div>
        <div>
            <label for="sort">정렬</label>
            <select id="sort" name="sort" onchange="this.form.submit()">
                <?php foreach ($sortOptions as $value => $option): ?>
                    <option value="<?= $value ?>" <?= $sort === $value ? 'selected' : '' ?>><?= htmlspecialchars($option[0]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <table>
        <thead><tr><th>번호</th><th>제목</th><th>작성자</th><th>작성일</th></tr></thead>
        <tbody>
        <?php if ($result->num_rows === 0): ?>
            <tr><td class="empty" colspan="4">게시물이 없습니다.</td></tr>
        <?php endif; ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= (int)$row['id'] ?></td>
                <td class="title-cell">
                    <a href="view.php?id=<?= (int)$row['id'] ?>"><?= htmlspecialchars($row['title']) ?></a>
                    <?php if ((int)$row['attachment_count'] > 0): ?> [파일 <?= (int)$row['attachment_count'] ?>]<?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
<?php $stmt->close(); ?>
