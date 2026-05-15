<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

function input(): array {
    if (($_SERVER['CONTENT_TYPE'] ?? '') === 'application/json') {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
    return array_merge($_GET, $_POST);
}

$in = input();
$token = $in['t'] ?? $in['token'] ?? '';
if (!$token) json_response(['error' => 'missing token'], 401);

// Risolvi token → tenant_id
$st = db()->prepare("SELECT tenant_id FROM settings WHERE `key`='transfer_driver_token' AND value=?");
$st->execute([$token]);
$tid = $st->fetchColumn();
if (!$tid) json_response(['error' => 'invalid token'], 403);
$tid = (int)$tid;

$action = $_GET['action'] ?? $in['action'] ?? 'list';

switch ($action) {
    case 'list':
        $from = $in['from'] ?? date('Y-m-d');
        // 7 giorni avanti by default — l'autista pianifica
        $to = $in['to'] ?? date('Y-m-d', strtotime($from . ' +7 days'));
        $st = db()->prepare("SELECT id, customer_name, phone, language, direction, pickup_when, pickup_location, pickup_address, dropoff_location, dropoff_address, passengers, luggage, flight_no, vehicle, driver_name, notes, status FROM transfers WHERE tenant_id=? AND DATE(pickup_when) BETWEEN ? AND ? AND status NOT IN ('cancelled') ORDER BY pickup_when");
        $st->execute([$tid, $from, $to]);
        json_response(['transfers' => $st->fetchAll()]);

    case 'set_status':
        $allowed = ['scheduled','on_way','picked_up','completed','no_show'];
        $s = $in['status'] ?? '';
        if (!in_array($s, $allowed, true)) json_response(['error' => 'invalid status'], 400);
        db()->prepare('UPDATE transfers SET status=?, updated_at=? WHERE id=? AND tenant_id=?')
            ->execute([$s, date('Y-m-d H:i:s'), (int)$in['id'], $tid]);
        json_response(['ok' => true]);

    default: json_response(['error' => 'unknown'], 400);
}
