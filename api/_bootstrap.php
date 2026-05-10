<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!user()) json_response(['error' => 'unauthorized'], 401);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

function input(): array {
    if (($_SERVER['CONTENT_TYPE'] ?? '') === 'application/json') {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
    return array_merge($_GET, $_POST);
}
function notify(?string $role, string $title, string $body = '', ?string $link = null) {
    db()->prepare('INSERT INTO notifications (tenant_id, role, type, title, body, link, created_at) VALUES (?,?,?,?,?,?,?)')
        ->execute([tenant_id(), $role, 'info', $title, $body, $link, date('Y-m-d H:i:s')]);
}
