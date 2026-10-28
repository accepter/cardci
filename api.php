<?php
declare(strict_types=1);

/**
 * API 端點 — Google Keep 風格卡片 CRUD
 * POST 新增卡片｜GET 取得卡片列表
 */

// ── 允許的前端來源 ───────────────────────────────────────────────────────────
$allowedOrigins = [
    'https://card.ci',
    'https://accepter.github.io',
];

// ── CORS 標頭 ────────────────────────────────────────────────────────────────
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=UTF-8');

// ── OPTIONS 預檢請求 ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── 回應函數 ─────────────────────────────────────────────────────────────────
function respond(int $statusCode, bool $success, string $message, array $data = []): void
{
    http_response_code($statusCode);
    echo json_encode(
        array_merge(['success' => $success, 'message' => $message], $data),
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
    exit;
}

// ── 資料庫連線 ───────────────────────────────────────────────────────────────
$dbHost     = getenv('DB_HOST')    ?: 'localhost';
$dbName     = getenv('DB_NAME')    ?: 'your_database';
$dbUser     = getenv('DB_USER')    ?: 'your_username';
$dbPass     = getenv('DB_PASS')    ?: 'your_password';
$dbCharset  = 'utf8mb4';

try {
    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$dbCharset}",
    ]);
} catch (PDOException $e) {
    error_log('DB Connection Error: ' . $e->getMessage());
    respond(500, false, '資料庫連線失敗');
}

// ── POST：新增卡片 ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if (!is_array($input)) {
        respond(400, false, '請提供有效的 JSON 資料');
    }

    // content 為必填
    if (!isset($input['content']) || !is_string($input['content']) || trim($input['content']) === '') {
        respond(400, false, '缺少或無效的欄位：content');
    }

    $title   = isset($input['title'])   ? trim($input['title'])   : null;
    $content = trim($input['content']);
    $color   = isset($input['color'])
        && in_array($input['color'], ['default', 'gray', 'yellow', 'green', 'pink', 'blue', 'purple'], true)
        ? $input['color']
        : 'default';
    $isPinned = isset($input['is_pinned']) ? (bool)$input['is_pinned'] : false;

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO card (title, content, color, is_pinned) VALUES (:title, :content, :color, :is_pinned)'
        );
        $stmt->execute([
            ':title'     => $title,
            ':content'   => $content,
            ':color'     => $color,
            ':is_pinned' => $isPinned ? 1 : 0,
        ]);

        $cardId = (int)$pdo->lastInsertId();

        respond(201, true, '新增成功', [
            'id'         => $cardId,
            'title'      => $title,
            'content'    => $content,
            'color'      => $color,
            'is_pinned'  => $isPinned,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    } catch (PDOException $e) {
        error_log('Insert Error: ' . $e->getMessage());
        respond(500, false, '寫入資料庫失敗');
    }
}

// ── GET：取得卡片列表 ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query(
            'SELECT id, title, content, color, is_pinned, created_at, updated_at
             FROM card
             ORDER BY is_pinned DESC, created_at DESC'
        );
        $cards = $stmt->fetchAll();

        respond(200, true, '取得成功', ['cards' => $cards]);
    } catch (PDOException $e) {
        error_log('Select Error: ' . $e->getMessage());
        respond(500, false, '讀取卡片列表失敗');
    }
}
