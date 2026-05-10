<?php
// Lollab POS - Config
session_start();
date_default_timezone_set('Europe/Rome');
mb_internal_encoding('UTF-8');

define('APP_NAME', 'Lollapalooza POS');
define('APP_VER', '1.0.0');
define('BASE_PATH', dirname(__DIR__));
define('DB_PATH', BASE_PATH . '/data/lollab.sqlite');
define('UPLOAD_PATH', BASE_PATH . '/assets/uploads');
define('BASE_URL', '');
define('JWT_SECRET', 'lollab-' . hash('sha256', __DIR__));

if (!is_dir(BASE_PATH . '/data')) mkdir(BASE_PATH . '/data', 0775, true);
if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0775, true);

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys=ON');
        $pdo->exec('PRAGMA journal_mode=WAL');
    }
    return $pdo;
}

function user(): ?array { return $_SESSION['user'] ?? null; }
function require_auth(?array $roles = null) {
    $u = user();
    if (!$u) { header('Location: /index.php?p=login'); exit; }
    if ($roles && !in_array($u['role'], $roles, true) && $u['role'] !== 'admin') {
        http_response_code(403); die('Forbidden');
    }
}
function require_role(array $roles) { require_auth($roles); }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_check(): bool {
    return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
}
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($v, $cur = '€') { return $cur . ' ' . number_format((float)$v, 2, ',', '.'); }
function flash($type, $msg) { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function flashes() { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }
function audit(string $action, ?string $entity = null, ?int $entity_id = null, ?array $data = null) {
    $u = user();
    db()->prepare('INSERT INTO audit_log (user_id, action, entity, entity_id, data, created_at) VALUES (?,?,?,?,?,?)')
        ->execute([$u['id'] ?? null, $action, $entity, $entity_id, $data ? json_encode($data) : null, date('Y-m-d H:i:s')]);
}
function json_response($data, int $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function tenant_id(): int {
    return (int)($_SESSION['tenant_id'] ?? user()['tenant_id'] ?? 1);
}
