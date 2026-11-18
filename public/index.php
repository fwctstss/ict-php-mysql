<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/db.php';

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function input(array $data, string $key): string
{
    return isset($data[$key]) && is_string($data[$key]) ? trim($data[$key]) : '';
}

$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$error = '';
$q = input($_GET, 'q');

try {
    $pdo = connectDb();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'], input($_POST, 'csrf'))) {
            throw new InvalidArgumentException('表格已失效，請重新整理後再試。');
        }
        $action = input($_POST, 'action');
        if (!in_array($action, ['add', 'update', 'delete'], true)) {
            throw new InvalidArgumentException('無效操作。');
        }
        $id = filter_var(input($_POST, 'id'), FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]);
        if ($action !== 'add' && $id === false) {
            throw new InvalidArgumentException('無效學生編號。');
        }
        if ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
            $stmt->execute([$id]);
        } else {
            $name = input($_POST, 'name');
            $class = strtoupper(input($_POST, 'class_name'));
            $score = filter_var(input($_POST, 'score'), FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0, 'max_range' => 100]]);
            if (!preg_match('/^.{1,50}$/us', $name)
                || !preg_match('/^[4-6][A-F]$/', $class) || $score === false) {
                throw new InvalidArgumentException('請輸入姓名（1–50字）、班別（4A–6F）及整數分數（0–100）。');
            }
            if ($action === 'add') {
                $stmt = $pdo->prepare('INSERT INTO students (name, class_name, score) VALUES (?, ?, ?)');
                $stmt->execute([$name, $class, $score]);
            } else {
                $stmt = $pdo->prepare('UPDATE students SET name = ?, class_name = ?, score = ? WHERE id = ?');
                $stmt->execute([$name, $class, $score, $id]);
            }
        }
        header('Location: ./', true, 303);
        exit;
    }
} catch (InvalidArgumentException $e) {
    $error = $e->getMessage();
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(503);
    exit('資料庫暫時未能使用，請查看 docker compose logs db web。');
}

try {
    $stmt = $pdo->prepare('SELECT id, name, class_name, score FROM students WHERE name LIKE ? ORDER BY id');
    $stmt->execute(['%' . $q . '%']);
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(503);
    exit('讀取資料失敗，請查看伺服器紀錄。');
}
?>
<!doctype html>
<html lang="zh-Hant">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ICT 學生成績管理</title>
<style>
body{font-family:system-ui,sans-serif;max-width:960px;margin:36px auto;padding:0 18px;color:#172b4d;background:#f5f8fc}
form{display:flex;gap:8px;flex-wrap:wrap;align-items:center;background:white;padding:14px;margin:12px 0;border-radius:8px}
input,button{font:inherit;padding:7px;max-width:100%;box-sizing:border-box}input[name=name]{width:150px}input[name=class_name],input[name=score]{width:80px}
button{cursor:pointer}p.error{color:#a2191f}small{color:#42526e}
</style>
<h1>學生成績管理</h1>
<p>HKDSE ICT 練習｜PHP + MySQL｜請只使用虛構資料。</p>
<?php if ($error !== ''): ?><p class="error" role="alert"><?= h($error) ?></p><?php endif; ?>
<h2>新增學生</h2>
<form method="post">
    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
    <label>姓名 <input name="name" maxlength="50" required></label>
    <label>班別 <input name="class_name" placeholder="4A" pattern="[4-6][A-F]" required></label>
    <label>分數 <input name="score" type="number" min="0" max="100" step="1" required></label>
    <button name="action" value="add">新增</button>
</form>
<h2>搜尋及編輯</h2>
<form method="get">
    <label>姓名包含 <input name="q" value="<?= h($q) ?>"></label>
    <button>搜尋</button><a href="./">顯示全部</a>
</form>
<p>共 <?= count($students) ?> 筆。修改欄位後按「儲存」；刪除後無法在此頁復原。</p>
<?php foreach ($students as $student): ?>
<form method="post">
    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
    <input type="hidden" name="id" value="<?= h($student['id']) ?>">
    <strong>#<?= h($student['id']) ?></strong>
    <label>姓名 <input name="name" value="<?= h($student['name']) ?>" maxlength="50" required></label>
    <label>班別 <input name="class_name" value="<?= h($student['class_name']) ?>" pattern="[4-6][A-F]" required></label>
    <label>分數 <input name="score" value="<?= h($student['score']) ?>" type="number" min="0" max="100" required></label>
    <button name="action" value="update">儲存</button>
    <button name="action" value="delete" formnovalidate onclick="return confirm('確定刪除此學生？')">刪除</button>
</form>
<?php endforeach; ?>
<?php if (!$students): ?><p>沒有符合條件的紀錄。</p><?php endif; ?>
<small>LIKE 搜尋中的 % 和 _ 是 SQL 萬用字元。此範例沒有網站登入功能，請保持 Codespaces 連接埠為 Private。</small>
</html>
