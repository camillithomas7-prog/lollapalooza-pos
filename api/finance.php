<?php
require __DIR__ . '/_bootstrap.php';
$t = tenant_id();
$in = input();

switch ($action) {
    case 'expenses':
        $from = $in['from'] ?? date('Y-m-01');
        $to = $in['to'] ?? date('Y-m-d');
        $st = db()->prepare('SELECT * FROM expenses WHERE tenant_id=? AND date BETWEEN ? AND ? ORDER BY date DESC, id DESC');
        $st->execute([$t, $from, $to]);
        json_response(['expenses' => $st->fetchAll()]);

    case 'save_expense':
        if (!empty($in['id'])) {
            db()->prepare('UPDATE expenses SET category=?, amount=?, description=?, date=? WHERE id=? AND tenant_id=?')
                ->execute([$in['category'], (float)$in['amount'], $in['description'] ?? '', $in['date'], $in['id'], $t]);
        } else {
            db()->prepare('INSERT INTO expenses (tenant_id, category, amount, description, date, user_id, created_at) VALUES (?,?,?,?,?,?,?)')
                ->execute([$t, $in['category'], (float)$in['amount'], $in['description'] ?? '', $in['date'], user()['id'], date('Y-m-d H:i:s')]);
        }
        json_response(['ok' => true]);

    case 'delete_expense':
        db()->prepare('DELETE FROM expenses WHERE id=? AND tenant_id=?')->execute([$in['id'], $t]);
        json_response(['ok' => true]);

    default: json_response(['error' => 'unknown'], 400);
}
