<?php
require __DIR__ . '/_bootstrap.php';
$t = tenant_id();
$in = input();

switch ($action) {
    case 'stock':
        $st = db()->prepare('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.tenant_id=? AND p.track_stock=1 ORDER BY p.stock ASC, p.name');
        $st->execute([$t]);
        json_response(['products' => $st->fetchAll()]);

    case 'movement':
        $pid = (int)$in['product_id'];
        $type = $in['type'];
        $qty = (float)$in['qty'];
        $cost = (float)($in['cost'] ?? 0);
        $delta = ($type === 'out' ? -$qty : $qty);
        if ($type === 'adjust') $delta = $qty;
        db()->prepare('INSERT INTO stock_movements (tenant_id, product_id, type, qty, cost, supplier_id, notes, user_id, created_at) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([$t, $pid, $type, $qty, $cost, $in['supplier_id'] ?? null, $in['notes'] ?? '', user()['id'], date('Y-m-d H:i:s')]);
        if ($type === 'adjust') {
            db()->prepare('UPDATE products SET stock=? WHERE id=? AND tenant_id=?')->execute([$qty, $pid, $t]);
        } else {
            db()->prepare('UPDATE products SET stock = stock + ? WHERE id=? AND tenant_id=?')->execute([$delta, $pid, $t]);
        }
        if ($type === 'in' && $cost > 0) {
            db()->prepare('UPDATE products SET cost=? WHERE id=? AND tenant_id=?')->execute([$cost, $pid, $t]);
        }
        json_response(['ok' => true]);

    case 'history':
        $pid = (int)($in['product_id'] ?? 0);
        $where = 'tenant_id=?'; $params = [$t];
        if ($pid) { $where .= ' AND product_id=?'; $params[] = $pid; }
        $st = db()->prepare("SELECT sm.*, p.name AS product_name, u.name AS user_name, s.name AS supplier_name FROM stock_movements sm LEFT JOIN products p ON p.id=sm.product_id LEFT JOIN users u ON u.id=sm.user_id LEFT JOIN suppliers s ON s.id=sm.supplier_id WHERE $where ORDER BY sm.id DESC LIMIT 100");
        $st->execute($params);
        json_response(['movements' => $st->fetchAll()]);

    case 'low_stock':
        $st = db()->prepare('SELECT * FROM products WHERE tenant_id=? AND track_stock=1 AND stock <= stock_min ORDER BY stock ASC');
        $st->execute([$t]);
        json_response(['products' => $st->fetchAll()]);

    case 'suppliers':
        $st = db()->prepare('SELECT * FROM suppliers WHERE tenant_id=? ORDER BY name');
        $st->execute([$t]);
        json_response(['suppliers' => $st->fetchAll()]);

    case 'save_supplier':
        if (!empty($in['id'])) {
            db()->prepare('UPDATE suppliers SET name=?, contact=?, phone=?, email=?, notes=? WHERE id=? AND tenant_id=?')
                ->execute([$in['name'], $in['contact'], $in['phone'], $in['email'], $in['notes'] ?? '', $in['id'], $t]);
        } else {
            db()->prepare('INSERT INTO suppliers (tenant_id, name, contact, phone, email, notes) VALUES (?,?,?,?,?,?)')
                ->execute([$t, $in['name'], $in['contact'] ?? '', $in['phone'] ?? '', $in['email'] ?? '', $in['notes'] ?? '']);
        }
        json_response(['ok' => true]);

    default: json_response(['error' => 'unknown'], 400);
}
