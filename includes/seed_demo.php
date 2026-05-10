<?php
// Seed dati demo realistici per popolare dashboard/report
require_once __DIR__ . '/config.php';
$pdo = db();

// Skip se già seedato
$c = $pdo->query('SELECT COUNT(*) c FROM orders')->fetch();
if ($c['c'] > 0) { echo "Demo già popolata.\n"; exit; }

$tenant_id = 1;
$products = $pdo->query('SELECT * FROM products WHERE tenant_id=1')->fetchAll();
$tables = $pdo->query('SELECT * FROM tables WHERE tenant_id=1')->fetchAll();
$waiters = $pdo->query("SELECT * FROM users WHERE tenant_id=1 AND role='cameriere'")->fetchAll();
$cashSession = $pdo->query("SELECT id FROM cash_sessions WHERE tenant_id=1 AND status='open'")->fetch();

$now = time();
// Crea 80 ordini chiusi negli ultimi 7 giorni
for ($i = 0; $i < 80; $i++) {
    $daysAgo = rand(0, 6);
    $hour = rand(11, 23);
    $minute = rand(0, 59);
    $createdTs = strtotime("-$daysAgo days") - rand(0, 3600);
    $createdTs = mktime($hour, $minute, 0, date('m', $createdTs), date('d', $createdTs), date('Y', $createdTs));
    $created = date('Y-m-d H:i:s', $createdTs);
    $closed = date('Y-m-d H:i:s', $createdTs + rand(900, 5400));

    $table = $tables[array_rand($tables)];
    $waiter = $waiters[array_rand($waiters)];
    $code = 'O' . date('ymdHis', $createdTs) . rand(10, 99);
    $guests = rand(1, 6);

    $pdo->prepare('INSERT INTO orders (tenant_id, code, table_id, waiter_id, type, status, guests, created_at, closed_at) VALUES (?,?,?,?,?,?,?,?,?)')
        ->execute([$tenant_id, $code, $table['id'], $waiter['id'], 'dine_in', 'closed', $guests, $created, $closed]);
    $oid = (int)$pdo->lastInsertId();

    $itemCount = rand(2, 8);
    $sub = 0; $tax = 0;
    for ($j = 0; $j < $itemCount; $j++) {
        $p = $products[array_rand($products)];
        $qty = rand(1, 3);
        $sub += $qty * $p['price'];
        $tax += $qty * $p['price'] * $p['vat'] / 100 / (1 + $p['vat'] / 100);
        $pdo->prepare('INSERT INTO order_items (order_id, product_id, name, qty, price, cost, status, sent_at, served_at, destination) VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([$oid, $p['id'], $p['name'], $qty, $p['price'], $p['cost'], 'served', $created, $closed, $p['vat']==22?'bar':'kitchen']);
    }
    $total = round($sub, 2);
    $pdo->prepare('UPDATE orders SET subtotal=?, tax=?, total=?, paid=? WHERE id=?')
        ->execute([round($sub,2), round($tax,2), $total, $total, $oid]);
    // Pagamento misto
    $methods = rand(0,1) ? [['cash', $total]] : [['card', $total]];
    foreach ($methods as $m) {
        $pdo->prepare('INSERT INTO payments (tenant_id, order_id, method, amount, user_id, cash_session_id, created_at) VALUES (?,?,?,?,?,?,?)')
            ->execute([$tenant_id, $oid, $m[0], $m[1], $waiter['id'], $cashSession['id'] ?? null, $closed]);
    }
}

// Crea 4 ordini attivi (alcuni inviati, alcuni in prep, alcuni pronti)
$stages = [
    ['open', 'draft'],
    ['sent', 'sent'],
    ['preparing', 'preparing'],
    ['sent', 'ready'],
];
for ($i = 0; $i < 4; $i++) {
    $table = $tables[$i % count($tables)];
    $waiter = $waiters[array_rand($waiters)];
    $created = date('Y-m-d H:i:s', $now - rand(120, 1800));
    $code = 'O' . date('ymdHis') . $i;

    [$ordStatus, $itemStatus] = $stages[$i];
    $pdo->prepare('INSERT INTO orders (tenant_id, code, table_id, waiter_id, type, status, guests, created_at) VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$tenant_id, $code, $table['id'], $waiter['id'], 'dine_in', $ordStatus, rand(2,4), $created]);
    $oid = (int)$pdo->lastInsertId();
    $pdo->prepare('UPDATE tables SET status="occupied", occupied_since=? WHERE id=?')->execute([$created, $table['id']]);

    $sub = 0;
    for ($j = 0; $j < rand(3, 6); $j++) {
        $p = $products[array_rand($products)];
        $qty = rand(1, 2);
        $sub += $qty * $p['price'];
        $sentAt = $itemStatus === 'draft' ? null : date('Y-m-d H:i:s', strtotime($created) + 60);
        $pdo->prepare('INSERT INTO order_items (order_id, product_id, name, qty, price, cost, status, sent_at, destination, notes) VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([$oid, $p['id'], $p['name'], $qty, $p['price'], $p['cost'], $itemStatus, $sentAt, $p['vat']==22?'bar':'kitchen', rand(0,5)===0?'senza glutine':'']);
    }
    $pdo->prepare('UPDATE orders SET subtotal=?, total=? WHERE id=?')->execute([$sub, $sub, $oid]);
}

// Prenotazioni demo
$customers = ['Famiglia Rossi','Carlo Verdi','Maria Bianchi','Antonio Russo','Giulia Ferrari','Lorenzo Esposito'];
foreach ($customers as $c) {
    $d = date('Y-m-d', strtotime('+'.rand(0,7).' days'));
    $h = sprintf('%02d:%02d',rand(19,22),rand(0,1)*30);
    $pdo->prepare('INSERT INTO reservations (tenant_id, customer_name, phone, guests, date, time, status, created_at) VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$tenant_id, $c, '+39 333 '.rand(1000000,9999999), rand(2,8), $d, $h, 'confirmed', date('Y-m-d H:i:s')]);
}

// Notifiche demo
$pdo->prepare('INSERT INTO notifications (tenant_id, role, type, title, body, created_at) VALUES (?,?,?,?,?,?)')
    ->execute([$tenant_id, 'manager', 'warning', 'Magazzino', 'Stock basso su 3 prodotti', date('Y-m-d H:i:s')]);
$pdo->prepare('INSERT INTO notifications (tenant_id, role, type, title, body, created_at) VALUES (?,?,?,?,?,?)')
    ->execute([$tenant_id, null, 'info', 'Benvenuto', 'Lollab POS è pronto!', date('Y-m-d H:i:s')]);

echo "✅ Demo data popolata: 80 ordini storici, 4 ordini attivi, 6 prenotazioni\n";
