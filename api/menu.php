<?php
require __DIR__ . '/_bootstrap.php';
$t = tenant_id();
$in = input();

switch ($action) {
    case 'list':
        $cats = db()->prepare('SELECT * FROM categories WHERE tenant_id=? AND active=1 ORDER BY sort');
        $cats->execute([$t]);
        $cats = $cats->fetchAll();
        $prods = db()->prepare('SELECT p.*, c.name AS category_name, c.destination FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.tenant_id=? AND p.available=1 ORDER BY p.sort, p.name');
        $prods->execute([$t]);
        json_response(['categories' => $cats, 'products' => $prods->fetchAll()]);

    case 'save_category':
        $trJson = isset($in['translations']) ? (is_string($in['translations']) ? $in['translations'] : json_encode($in['translations'])) : null;
        if (!empty($in['id'])) {
            db()->prepare('UPDATE categories SET name=?, icon=?, color=?, destination=?, active=?, translations=? WHERE id=? AND tenant_id=?')
                ->execute([$in['name'], $in['icon'], $in['color'], $in['destination'], (int)($in['active']??1), $trJson, $in['id'], $t]);
        } else {
            db()->prepare('INSERT INTO categories (tenant_id, name, icon, color, destination, active, translations) VALUES (?,?,?,?,?,1,?)')
                ->execute([$t, $in['name'], $in['icon'], $in['color'] ?? '#0ea5e9', $in['destination'] ?? 'kitchen', $trJson]);
        }
        json_response(['ok' => true]);

    case 'delete_category':
        db()->prepare('UPDATE categories SET active=0 WHERE id=? AND tenant_id=?')->execute([$in['id'], $t]);
        json_response(['ok' => true]);

    case 'save_product':
        $fields = [
            'category_id' => $in['category_id'] ?? null,
            'name' => $in['name'],
            'description' => $in['description'] ?? '',
            'image' => $in['image'] ?? null,
            'price' => (float)$in['price'],
            'cost' => (float)($in['cost'] ?? 0),
            'vat' => (float)($in['vat'] ?? 22),
            'allergens' => $in['allergens'] ?? '',
            'ingredients' => $in['ingredients'] ?? '',
            'available' => (int)($in['available'] ?? 1),
            'track_stock' => (int)($in['track_stock'] ?? 0),
            'stock' => (float)($in['stock'] ?? 0),
            'stock_min' => (float)($in['stock_min'] ?? 0),
            'unit' => $in['unit'] ?? 'pz',
            'translations' => isset($in['translations']) ? (is_string($in['translations']) ? $in['translations'] : json_encode($in['translations'])) : null,
            // destination: '' o assente = eredita dalla categoria; valore esplicito = override
            'destination' => (isset($in['destination']) && in_array($in['destination'], ['kitchen','bar','none'], true)) ? $in['destination'] : null,
        ];
        if (!empty($in['id'])) {
            $sql = 'UPDATE products SET ' . implode(',', array_map(fn($k)=>"$k=?", array_keys($fields))) . ' WHERE id=? AND tenant_id=?';
            db()->prepare($sql)->execute([...array_values($fields), $in['id'], $t]);
            json_response(['ok' => true, 'id' => $in['id']]);
        } else {
            $fields['tenant_id'] = $t;
            $fields['created_at'] = date('Y-m-d H:i:s');
            $sql = 'INSERT INTO products (' . implode(',', array_keys($fields)) . ') VALUES (' . implode(',', array_fill(0, count($fields), '?')) . ')';
            db()->prepare($sql)->execute(array_values($fields));
            json_response(['ok' => true, 'id' => db()->lastInsertId()]);
        }

    case 'delete_product':
        db()->prepare('UPDATE products SET available=0 WHERE id=? AND tenant_id=?')->execute([$in['id'], $t]);
        json_response(['ok' => true]);

    case 'toggle_available':
        db()->prepare('UPDATE products SET available = 1 - available WHERE id=? AND tenant_id=?')->execute([$in['id'], $t]);
        json_response(['ok' => true]);

    default: json_response(['error' => 'unknown'], 400);
}
