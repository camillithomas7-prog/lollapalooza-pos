<?php
require __DIR__ . '/_bootstrap.php';
$t = tenant_id();
$in = input();

switch ($action) {
    case 'list':
        $rooms = db()->prepare('SELECT * FROM rooms WHERE tenant_id=? ORDER BY sort');
        $rooms->execute([$t]);
        $rooms = $rooms->fetchAll();
        $tables = db()->prepare('
            SELECT t.*,
                (SELECT id FROM orders WHERE table_id=t.id AND status NOT IN ("closed","cancelled") LIMIT 1) AS order_id,
                (SELECT total FROM orders WHERE table_id=t.id AND status NOT IN ("closed","cancelled") LIMIT 1) AS order_total,
                (SELECT guests FROM orders WHERE table_id=t.id AND status NOT IN ("closed","cancelled") LIMIT 1) AS order_guests
            FROM tables t WHERE tenant_id=? ORDER BY code
        ');
        $tables->execute([$t]);
        json_response(['rooms' => $rooms, 'tables' => $tables->fetchAll()]);

    case 'save_layout':
        $tables = $in['tables'] ?? [];
        $st = db()->prepare('UPDATE tables SET pos_x=?, pos_y=?, room_id=?, shape=?, seats=?, code=? WHERE id=? AND tenant_id=?');
        foreach ($tables as $tbl) {
            $st->execute([$tbl['pos_x'], $tbl['pos_y'], $tbl['room_id'], $tbl['shape'] ?? 'square', $tbl['seats'], $tbl['code'], $tbl['id'], $t]);
        }
        audit('save_table_layout');
        json_response(['ok' => true]);

    case 'create':
        db()->prepare('INSERT INTO tables (tenant_id, room_id, code, seats, shape, pos_x, pos_y, qr_token) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$t, $in['room_id'], $in['code'], $in['seats'] ?? 4, $in['shape'] ?? 'square', $in['pos_x'] ?? 50, $in['pos_y'] ?? 50, bin2hex(random_bytes(8))]);
        json_response(['ok' => true, 'id' => db()->lastInsertId()]);

    case 'delete':
        db()->prepare('DELETE FROM tables WHERE id=? AND tenant_id=?')->execute([$in['id'], $t]);
        json_response(['ok' => true]);

    case 'set_status':
        db()->prepare('UPDATE tables SET status=? WHERE id=? AND tenant_id=?')
            ->execute([$in['status'], $in['id'], $t]);
        json_response(['ok' => true]);

    case 'open':
        // Apre nuovo ordine sul tavolo
        $tableId = (int)$in['table_id'];
        $guests = (int)($in['guests'] ?? 1);
        $existing = db()->prepare('SELECT id FROM orders WHERE table_id=? AND status NOT IN ("closed","cancelled") LIMIT 1');
        $existing->execute([$tableId]);
        if ($existing->fetch()) json_response(['error' => 'tavolo già aperto'], 400);

        $code = 'O' . date('ymdHis') . rand(10,99);
        db()->prepare('INSERT INTO orders (tenant_id, code, table_id, waiter_id, type, status, guests, created_at) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$t, $code, $tableId, user()['id'], 'dine_in', 'open', $guests, date('Y-m-d H:i:s')]);
        $oid = (int)db()->lastInsertId();
        db()->prepare('UPDATE tables SET status="occupied", occupied_since=? WHERE id=?')
            ->execute([date('Y-m-d H:i:s'), $tableId]);
        audit('open_table', 'orders', $oid);
        json_response(['ok' => true, 'order_id' => $oid]);

    case 'merge':
        db()->prepare('UPDATE tables SET merged_with=? WHERE id=? AND tenant_id=?')
            ->execute([$in['parent_id'], $in['child_id'], $t]);
        json_response(['ok' => true]);

    case 'unmerge':
        db()->prepare('UPDATE tables SET merged_with=NULL WHERE id=? AND tenant_id=?')
            ->execute([$in['id'], $t]);
        json_response(['ok' => true]);

    default: json_response(['error' => 'unknown action'], 400);
}
