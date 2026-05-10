<?php
require __DIR__ . '/_bootstrap.php';
$t = tenant_id();
$in = input();
$role = user()['role'];

switch ($action) {
    case 'unread':
        $st = db()->prepare('SELECT * FROM notifications WHERE tenant_id=? AND read_at IS NULL AND (role IS NULL OR role=? OR role="admin") ORDER BY id DESC LIMIT 20');
        $st->execute([$t, $role]);
        $items = $st->fetchAll();
        json_response(['count' => count($items), 'items' => $items]);

    case 'mark_read':
        db()->prepare('UPDATE notifications SET read_at=? WHERE id=? AND tenant_id=?')
            ->execute([date('Y-m-d H:i:s'), $in['id'], $t]);
        json_response(['ok' => true]);

    case 'mark_all_read':
        db()->prepare('UPDATE notifications SET read_at=? WHERE tenant_id=? AND read_at IS NULL AND (role IS NULL OR role=?)')
            ->execute([date('Y-m-d H:i:s'), $t, $role]);
        json_response(['ok' => true]);

    default: json_response(['error' => 'unknown'], 400);
}
