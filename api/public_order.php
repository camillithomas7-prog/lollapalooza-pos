<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

function fail($msg, $code=400){ http_response_code($code); echo json_encode(['error'=>$msg]); exit; }

$action = $_GET['action'] ?? '';
$in = ($_SERVER['CONTENT_TYPE'] ?? '') === 'application/json'
    ? (json_decode(file_get_contents('php://input'), true) ?: [])
    : array_merge($_GET, $_POST);

$token = $in['token'] ?? $in['t'] ?? '';
if (!$token) fail('token mancante');

// Verifica tavolo
$st = db()->prepare('SELECT * FROM `tables` WHERE qr_token=?');
$st->execute([$token]);
$table = $st->fetch();
if (!$table) fail('tavolo non trovato', 404);

if ($action === 'get') {
    // Ritorna items già inviati per quel tavolo (ordine aperto)
    $o = db()->prepare("SELECT id FROM orders WHERE table_id=? AND status NOT IN ('closed','cancelled') ORDER BY id DESC LIMIT 1");
    $o->execute([$table['id']]);
    $orderId = $o->fetchColumn();
    if (!$orderId) { echo json_encode(['items'=>[]]); exit; }
    $items = db()->prepare("SELECT id, name, qty, price, status FROM order_items WHERE order_id=? AND status != 'cancelled' ORDER BY id");
    $items->execute([$orderId]);
    echo json_encode(['items' => $items->fetchAll(), 'order_id' => $orderId]);
    exit;
}

if ($action === 'submit') {
    $items = $in['items'] ?? [];
    if (!is_array($items) || !$items) fail('nessun articolo');
    if (count($items) > 50) fail('troppi articoli');

    // Trova/crea ordine aperto per il tavolo
    $o = db()->prepare("SELECT id FROM orders WHERE table_id=? AND status NOT IN ('closed','cancelled') ORDER BY id DESC LIMIT 1");
    $o->execute([$table['id']]);
    $orderId = $o->fetchColumn();
    $newOrder = false;
    if (!$orderId) {
        $code = 'Q' . date('ymdHis') . rand(10,99);
        db()->prepare("INSERT INTO orders (tenant_id, code, table_id, type, status, guests, created_at) VALUES (?,?,?,?,?,?,?)")
            ->execute([$table['tenant_id'], $code, $table['id'], 'dine_in', 'sent', 2, date('Y-m-d H:i:s')]);
        $orderId = (int)db()->lastInsertId();
        db()->prepare("UPDATE `tables` SET status='occupied', occupied_since=? WHERE id=?")
            ->execute([date('Y-m-d H:i:s'), $table['id']]);
        $newOrder = true;
    }

    $now = date('Y-m-d H:i:s');
    $destsAdded = [];
    foreach ($items as $it) {
        $pid = (int)($it['product_id'] ?? 0);
        $qty = max(0.1, min(99, (float)($it['qty'] ?? 1)));
        $note = trim((string)($it['notes'] ?? ''));
        if (strlen($note) > 200) $note = substr($note, 0, 200);
        if (!$pid) continue;

        $p = db()->prepare("SELECT p.*, p.destination AS p_dest, c.destination AS c_dest FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.tenant_id=? AND p.available=1");
        $p->execute([$pid, $table['tenant_id']]);
        $prod = $p->fetch();
        if (!$prod) continue;
        // Risoluzione gerarchica: prodotto > categoria > 'kitchen' (fallback)
        $dest = $prod['p_dest'] ?: ($prod['c_dest'] ?: 'kitchen');
        $destsAdded[$dest] = true;
        db()->prepare("INSERT INTO order_items (order_id, product_id, name, qty, price, cost, notes, destination, status, sent_at) VALUES (?,?,?,?,?,?,?,?,?,?)")
            ->execute([$orderId, $pid, $prod['name'], $qty, $prod['price'], $prod['cost'], $note, $dest, 'sent', $now]);
    }

    // Recalc totali
    $tot = db()->prepare("SELECT COALESCE(SUM(qty*price),0) s FROM order_items WHERE order_id=? AND status!='cancelled'");
    $tot->execute([$orderId]);
    $sub = (float)$tot->fetchColumn();
    db()->prepare("UPDATE orders SET subtotal=?, total=? - COALESCE(discount,0) WHERE id=?")->execute([$sub, $sub, $orderId]);

    // Notifiche a destinazioni
    foreach (array_keys($destsAdded) as $dest) {
        db()->prepare("INSERT INTO notifications (tenant_id, role, type, title, body, link, created_at) VALUES (?,?,?,?,?,?,?)")
            ->execute([$table['tenant_id'], $dest, 'order', '🛎 Ordine QR Tavolo ' . $table['code'], "Nuovo ordine self-service", "/index.php?p=$dest", $now]);
    }
    // Notifica anche cameriere
    db()->prepare("INSERT INTO notifications (tenant_id, role, type, title, body, link, created_at) VALUES (?,?,?,?,?,?,?)")
        ->execute([$table['tenant_id'], 'cameriere', 'order', '🛎 Tavolo ' . $table['code'], $newOrder ? 'Nuovo ordine QR cliente' : 'Aggiunta al tavolo', '/index.php?p=order_view&id=' . $orderId, $now]);

    echo json_encode(['ok' => true, 'order_id' => $orderId]);
    exit;
}

fail('action non valida');
