<?php
require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/escpos.php';
$t = tenant_id();
$in = input();

/**
 * Crea print_job per ogni destinazione (kitchen/bar) con i nuovi item appena
 * inviati. Una sola comanda per destinazione, raggruppando gli item.
 */
function enqueue_kitchen_prints(int $tenant, int $oid): void {
    // Recupera intestazione ordine (tavolo, cameriere, note, code)
    $st = db()->prepare('SELECT o.id, o.code, o.guests, o.notes, t.code AS table_code, u.name AS waiter_name FROM orders o LEFT JOIN tables t ON t.id=o.table_id LEFT JOIN users u ON u.id=o.waiter_id WHERE o.id=?');
    $st->execute([$oid]);
    $order = $st->fetch();
    if (!$order) return;

    // Tenant name per intestazione
    $ten = db()->prepare('SELECT name FROM tenants WHERE id=?');
    $ten->execute([$tenant]);
    $tenantName = ($ten->fetch()['name'] ?? 'Ristorante');

    // Setting: larghezza colonne (32 = 58mm, 48 = 80mm). Default 32.
    $cset = db()->prepare('SELECT value FROM settings WHERE tenant_id=? AND ' . (DB_DRIVER === 'mysql' ? '`key`' : 'key') . '=?');
    $cset->execute([$tenant, 'printer_cols']);
    $cols = (int)($cset->fetch()['value'] ?? 32);
    if ($cols !== 32 && $cols !== 48) $cols = 32;

    // Per ogni destinazione, raggruppa gli item appena inviati (sent_at recente)
    $itSt = db()->prepare('SELECT id, name, qty, notes, variants, extras, destination FROM order_items WHERE order_id=? AND status="sent" AND destination!="none"');
    $itSt->execute([$oid]);
    $byDest = [];
    foreach ($itSt->fetchAll() as $it) {
        $dest = $it['destination'] ?: 'kitchen';
        $byDest[$dest][] = $it;
    }
    foreach ($byDest as $dest => $items) {
        $label = $dest === 'bar' ? 'BAR' : 'CUCINA';
        $bytes = LollabEscPos\buildKitchenTicket(
            (array)$order,
            $items,
            $label,
            ['tenant_name' => $tenantName, 'cols' => $cols, 'beep' => true]
        );
        $payload = base64_encode($bytes);
        db()->prepare('INSERT INTO print_jobs (tenant_id, order_id, destination, payload, status, attempts, created_at) VALUES (?,?,?,?,?,0,?)')
            ->execute([$tenant, $oid, $dest, $payload, 'pending', date('Y-m-d H:i:s')]);
    }
}

function recalc_order(int $oid) {
    $st = db()->prepare('SELECT SUM(qty*price) sub FROM order_items WHERE order_id=? AND status!="cancelled"');
    $st->execute([$oid]);
    $sub = (float)$st->fetch()['sub'];
    $taxSt = db()->prepare('SELECT SUM(oi.qty*oi.price*COALESCE(p.vat,22)/100.0/(1.0+COALESCE(p.vat,22)/100.0)) tax FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=? AND oi.status!="cancelled"');
    $taxSt->execute([$oid]);
    $tax = (float)($taxSt->fetch()['tax'] ?? 0);
    db()->prepare('UPDATE orders SET subtotal=?, tax=?, total=? - COALESCE(discount,0) WHERE id=?')->execute([$sub, $tax, $sub, $oid]);
}

switch ($action) {
    case 'get':
        $oid = (int)$in['id'];
        $st = db()->prepare('SELECT o.*, t.code AS table_code, u.name AS waiter_name, c.name AS customer_name FROM orders o LEFT JOIN tables t ON t.id=o.table_id LEFT JOIN users u ON u.id=o.waiter_id LEFT JOIN customers c ON c.id=o.customer_id WHERE o.id=? AND o.tenant_id=?');
        $st->execute([$oid, $t]);
        $order = $st->fetch();
        if (!$order) json_response(['error' => 'not found'], 404);
        $items = db()->prepare('SELECT * FROM order_items WHERE order_id=? ORDER BY id');
        $items->execute([$oid]);
        $pays = db()->prepare('SELECT * FROM payments WHERE order_id=? ORDER BY id');
        $pays->execute([$oid]);
        json_response(['order' => $order, 'items' => $items->fetchAll(), 'payments' => $pays->fetchAll()]);

    case 'list':
        $where = 'o.tenant_id=?';
        $params = [$t];
        if (!empty($in['status'])) { $where .= ' AND o.status=?'; $params[] = $in['status']; }
        if (!empty($in['active'])) { $where .= ' AND o.status NOT IN ("closed","cancelled")'; }
        if (!empty($in['date'])) { $where .= ' AND date(o.created_at)=?'; $params[] = $in['date']; }
        $sql = "SELECT o.*, t.code AS table_code, u.name AS waiter_name FROM orders o LEFT JOIN tables t ON t.id=o.table_id LEFT JOIN users u ON u.id=o.waiter_id WHERE $where ORDER BY o.created_at DESC LIMIT 200";
        $st = db()->prepare($sql);
        $st->execute($params);
        json_response(['orders' => $st->fetchAll()]);

    case 'add_item':
        $oid = (int)$in['order_id'];
        $pid = (int)$in['product_id'];
        $qty = (float)($in['qty'] ?? 1);
        $notes = $in['notes'] ?? '';
        $p = db()->prepare('SELECT p.*, c.destination FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.tenant_id=?');
        $p->execute([$pid, $t]);
        $product = $p->fetch();
        if (!$product) json_response(['error' => 'prodotto non trovato'], 404);
        db()->prepare('INSERT INTO order_items (order_id, product_id, name, qty, price, cost, notes, destination, status) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([$oid, $pid, $product['name'], $qty, $product['price'], $product['cost'], $notes, $product['destination'] ?? 'kitchen', 'draft']);
        recalc_order($oid);
        json_response(['ok' => true]);

    case 'update_item':
        $iid = (int)$in['id'];
        $st = db()->prepare('SELECT order_id, status FROM order_items WHERE id=?');
        $st->execute([$iid]);
        $item = $st->fetch();
        if (!$item) json_response(['error' => 'not found'], 404);
        if (in_array($item['status'], ['preparing','ready','served'])) json_response(['error' => 'già in preparazione'], 400);
        if (isset($in['qty'])) {
            if ((float)$in['qty'] <= 0) {
                db()->prepare('DELETE FROM order_items WHERE id=?')->execute([$iid]);
            } else {
                db()->prepare('UPDATE order_items SET qty=?, notes=? WHERE id=?')
                    ->execute([(float)$in['qty'], $in['notes'] ?? '', $iid]);
            }
        }
        recalc_order((int)$item['order_id']);
        json_response(['ok' => true]);

    case 'send':
        // Invia ordine in cucina/bar
        $oid = (int)$in['order_id'];
        db()->prepare('UPDATE order_items SET status="sent", sent_at=? WHERE order_id=? AND status="draft"')
            ->execute([date('Y-m-d H:i:s'), $oid]);
        db()->prepare('UPDATE orders SET status="sent" WHERE id=? AND status="open"')->execute([$oid]);
        // Notify
        $st = db()->prepare('SELECT DISTINCT destination FROM order_items WHERE order_id=? AND status="sent"');
        $st->execute([$oid]);
        foreach ($st->fetchAll() as $d) {
            notify($d['destination'], 'Nuovo ordine', "Ordine #$oid", "/index.php?p={$d['destination']}");
        }
        // Accoda comande di stampa (cucina + bar) — il KDS le invierà alla stampante BT
        try { enqueue_kitchen_prints($t, $oid); } catch (Throwable $e) { /* non bloccare l'ordine */ }
        audit('send_order', 'orders', $oid);
        json_response(['ok' => true]);

    case 'item_status':
        // Cambia stato singolo item (cucina/bar)
        $iid = (int)$in['id'];
        $newStatus = $in['status'];
        $now = date('Y-m-d H:i:s');
        $cols = match($newStatus) {
            'preparing' => 'status=?, ready_at=NULL',
            'ready' => 'status=?, ready_at="'.$now.'"',
            'served' => 'status=?, served_at="'.$now.'"',
            default => 'status=?',
        };
        db()->prepare("UPDATE order_items SET $cols WHERE id=?")->execute([$newStatus, $iid]);
        if ($newStatus === 'ready') {
            $r = db()->prepare('SELECT order_id, name FROM order_items WHERE id=?');
            $r->execute([$iid]);
            $i = $r->fetch();
            notify('cameriere', 'Ordine pronto', "{$i['name']} pronto", "/index.php?p=waiter");
        }
        json_response(['ok' => true]);

    case 'discount':
        $oid = (int)$in['order_id'];
        $disc = (float)$in['discount'];
        db()->prepare('UPDATE orders SET discount=? WHERE id=? AND tenant_id=?')->execute([$disc, $oid, $t]);
        recalc_order($oid);
        json_response(['ok' => true]);

    case 'pay':
        $oid = (int)$in['order_id'];
        $payments = $in['payments'] ?? [];
        $cashSession = db()->prepare('SELECT id FROM cash_sessions WHERE tenant_id=? AND status="open" ORDER BY id DESC LIMIT 1');
        $cashSession->execute([$t]);
        $cs = $cashSession->fetch();
        $totalPaid = 0;
        foreach ($payments as $p) {
            $amt = (float)$p['amount'];
            if ($amt <= 0) continue;
            $totalPaid += $amt;
            db()->prepare('INSERT INTO payments (tenant_id, order_id, method, amount, user_id, cash_session_id, created_at) VALUES (?,?,?,?,?,?,?)')
                ->execute([$t, $oid, $p['method'], $amt, user()['id'], $cs['id'] ?? null, date('Y-m-d H:i:s')]);
        }
        // Aggiorna ordine
        $ord = db()->prepare('SELECT total, table_id FROM orders WHERE id=?');
        $ord->execute([$oid]);
        $ordRow = $ord->fetch();
        $sumPaid = db()->prepare('SELECT SUM(amount) p FROM payments WHERE order_id=?');
        $sumPaid->execute([$oid]);
        $paid = (float)$sumPaid->fetch()['p'];
        db()->prepare('UPDATE orders SET paid=? WHERE id=?')->execute([$paid, $oid]);
        if ($paid >= $ordRow['total'] - 0.01) {
            db()->prepare('UPDATE orders SET status="closed", closed_at=? WHERE id=?')
                ->execute([date('Y-m-d H:i:s'), $oid]);
            if ($ordRow['table_id']) {
                db()->prepare('UPDATE tables SET status="free", occupied_since=NULL WHERE id=?')
                    ->execute([$ordRow['table_id']]);
            }
            // Scarico magazzino
            $items = db()->prepare('SELECT * FROM order_items WHERE order_id=? AND status!="cancelled"');
            $items->execute([$oid]);
            foreach ($items->fetchAll() as $it) {
                if ($it['product_id']) {
                    $pp = db()->prepare('SELECT track_stock FROM products WHERE id=?');
                    $pp->execute([$it['product_id']]);
                    $prod = $pp->fetch();
                    if ($prod && $prod['track_stock']) {
                        db()->prepare('UPDATE products SET stock = stock - ? WHERE id=?')->execute([$it['qty'], $it['product_id']]);
                        db()->prepare('INSERT INTO stock_movements (tenant_id, product_id, type, qty, user_id, notes, created_at) VALUES (?,?,?,?,?,?,?)')
                            ->execute([$t, $it['product_id'], 'sale', -$it['qty'], user()['id'], "Ordine #$oid", date('Y-m-d H:i:s')]);
                    }
                }
            }
            audit('close_order', 'orders', $oid);
        }
        json_response(['ok' => true, 'paid' => $paid]);

    case 'cancel':
        $oid = (int)$in['order_id'];
        $ord = db()->prepare('SELECT table_id FROM orders WHERE id=?');
        $ord->execute([$oid]);
        $r = $ord->fetch();
        db()->prepare('UPDATE orders SET status="cancelled", closed_at=? WHERE id=?')
            ->execute([date('Y-m-d H:i:s'), $oid]);
        if ($r && $r['table_id']) {
            db()->prepare('UPDATE tables SET status="free", occupied_since=NULL WHERE id=?')->execute([$r['table_id']]);
        }
        audit('cancel_order', 'orders', $oid);
        json_response(['ok' => true]);

    case 'kitchen_queue':
        $dest = $in['dest'] ?? 'kitchen';
        $st = db()->prepare('SELECT oi.*, o.code AS order_code, o.guests, o.notes AS order_notes, t.code AS table_code, u.name AS waiter_name, o.id AS order_id
            FROM order_items oi
            JOIN orders o ON o.id=oi.order_id
            LEFT JOIN tables t ON t.id=o.table_id
            LEFT JOIN users u ON u.id=o.waiter_id
            WHERE o.tenant_id=? AND oi.destination=? AND oi.status IN ("sent","preparing")
            ORDER BY oi.sent_at ASC');
        $st->execute([$t, $dest]);
        json_response(['items' => $st->fetchAll()]);

    case 'ready_pickup':
        $st = db()->prepare('SELECT oi.*, t.code AS table_code FROM order_items oi JOIN orders o ON o.id=oi.order_id LEFT JOIN tables t ON t.id=o.table_id WHERE o.tenant_id=? AND oi.status="ready" ORDER BY oi.ready_at');
        $st->execute([$t]);
        json_response(['items' => $st->fetchAll()]);

    default: json_response(['error' => 'unknown'], 400);
}
