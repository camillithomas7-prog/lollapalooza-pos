<?php
require __DIR__ . '/_bootstrap.php';
$t = tenant_id();
$in = input();

switch ($action) {
    case 'list':
        $from = $in['from'] ?? date('Y-m-d');
        $to = $in['to'] ?? date('Y-m-d', strtotime('+30 days'));
        $st = db()->prepare('SELECT r.*, t.code AS table_code FROM reservations r LEFT JOIN tables t ON t.id=r.table_id WHERE r.tenant_id=? AND r.date BETWEEN ? AND ? ORDER BY r.date, r.time');
        $st->execute([$t, $from, $to]);
        json_response(['reservations' => $st->fetchAll()]);

    case 'save':
        if (!empty($in['id'])) {
            db()->prepare('UPDATE reservations SET customer_name=?, phone=?, email=?, guests=?, date=?, time=?, table_id=?, notes=?, status=? WHERE id=? AND tenant_id=?')
                ->execute([$in['customer_name'], $in['phone'] ?? '', $in['email'] ?? '', (int)$in['guests'], $in['date'], $in['time'], $in['table_id'] ?: null, $in['notes'] ?? '', $in['status'] ?? 'confirmed', $in['id'], $t]);
        } else {
            db()->prepare('INSERT INTO reservations (tenant_id, customer_name, phone, email, guests, date, time, table_id, notes, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$t, $in['customer_name'], $in['phone'] ?? '', $in['email'] ?? '', (int)$in['guests'], $in['date'], $in['time'], $in['table_id'] ?: null, $in['notes'] ?? '', 'confirmed', date('Y-m-d H:i:s')]);
        }
        json_response(['ok' => true]);

    case 'set_status':
        db()->prepare('UPDATE reservations SET status=? WHERE id=? AND tenant_id=?')->execute([$in['status'], $in['id'], $t]);
        json_response(['ok' => true]);

    case 'delete':
        db()->prepare('DELETE FROM reservations WHERE id=? AND tenant_id=?')->execute([$in['id'], $t]);
        json_response(['ok' => true]);

    default: json_response(['error' => 'unknown'], 400);
}
