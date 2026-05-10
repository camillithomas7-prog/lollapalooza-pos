<?php
require __DIR__ . '/_bootstrap.php';
$t = tenant_id();
$in = input();

switch ($action) {
    case 'list':
        $q = '%' . ($in['q'] ?? '') . '%';
        $st = db()->prepare('SELECT * FROM customers WHERE tenant_id=? AND (name LIKE ? OR phone LIKE ? OR email LIKE ?) ORDER BY total_spent DESC LIMIT 200');
        $st->execute([$t, $q, $q, $q]);
        json_response(['customers' => $st->fetchAll()]);

    case 'save':
        if (!empty($in['id'])) {
            db()->prepare('UPDATE customers SET name=?, phone=?, email=?, birthday=?, notes=?, fidelity_card=? WHERE id=? AND tenant_id=?')
                ->execute([$in['name'], $in['phone'] ?? '', $in['email'] ?? '', $in['birthday'] ?? null, $in['notes'] ?? '', $in['fidelity_card'] ?? '', $in['id'], $t]);
        } else {
            db()->prepare('INSERT INTO customers (tenant_id, name, phone, email, birthday, notes, fidelity_card, created_at) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$t, $in['name'], $in['phone'] ?? '', $in['email'] ?? '', $in['birthday'] ?? null, $in['notes'] ?? '', $in['fidelity_card'] ?? '', date('Y-m-d H:i:s')]);
        }
        json_response(['ok' => true]);

    case 'delete':
        db()->prepare('DELETE FROM customers WHERE id=? AND tenant_id=?')->execute([$in['id'], $t]);
        json_response(['ok' => true]);

    default: json_response(['error' => 'unknown'], 400);
}
