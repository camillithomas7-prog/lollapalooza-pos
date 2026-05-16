<?php
require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/escpos.php';
require_once __DIR__ . '/../includes/i18n.php';
$t = tenant_id();
$in = input();

/**
 * Crea print_job per ogni destinazione (kitchen/bar) con i nuovi item appena
 * inviati. Una sola comanda per destinazione, raggruppando gli item.
 */
/**
 * Legge un setting tenant (key→value) con default.
 */
function _tenant_setting(int $tenant, string $key, $default = '') {
    $keyCol = DB_DRIVER === 'mysql' ? '`key`' : 'key';
    $st = db()->prepare("SELECT value FROM settings WHERE tenant_id=? AND $keyCol=?");
    $st->execute([$tenant, $key]);
    $r = $st->fetch();
    return $r ? $r['value'] : $default;
}

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

    // Larghezza colonne (32 = 58mm, 48 = 80mm). Default 32.
    $cols = (int)_tenant_setting($tenant, 'printer_cols', 32);
    if ($cols !== 32 && $cols !== 48) $cols = 32;

    // Codepage stampante + lingua di stampa (configurabile da admin)
    $codepage = _tenant_setting($tenant, 'printer_codepage', 'cp858');
    $printLang = _tenant_setting($tenant, 'printer_lang', 'it');
    if (!in_array($printLang, SUPPORTED_LANGS, true)) $printLang = 'it';

    // Item con traduzione del nome dal menu prodotti
    $itSt = db()->prepare('SELECT oi.id, oi.name, oi.qty, oi.notes, oi.variants, oi.extras, oi.destination, p.translations AS p_translations
                           FROM order_items oi
                           LEFT JOIN products p ON p.id=oi.product_id
                           WHERE oi.order_id=? AND oi.status="sent" AND oi.destination!="none"');
    $itSt->execute([$oid]);
    $byDest = [];
    foreach ($itSt->fetchAll() as $it) {
        // Sostituisci nome con traduzione se disponibile per la lingua di stampa
        $translated = tr_field($it['p_translations'] ?? null, 'name', $it['name'], $printLang);
        if ($translated) $it['name'] = $translated;
        unset($it['p_translations']);
        $dest = $it['destination'] ?: 'kitchen';
        $byDest[$dest][] = $it;
    }
    foreach ($byDest as $dest => $items) {
        // Etichetta destinazione: in arabo se stampa in arabo
        $label = match (true) {
            $printLang === 'ar' && $dest === 'bar'     => 'البار',
            $printLang === 'ar' && $dest === 'kitchen' => 'المطبخ',
            $dest === 'bar' => 'BAR',
            default         => 'CUCINA',
        };
        $bytes = LollabEscPos\buildKitchenTicket(
            (array)$order,
            $items,
            $label,
            ['tenant_name' => $tenantName, 'cols' => $cols, 'beep' => true, 'codepage' => $codepage]
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

    case 'quick_create':
        // Apre un nuovo ordine al banco/take-away (per cassa).
        // Parametri: table_id (opzionale), type ('bar'|'dine_in'|'takeaway'), guests (default 1)
        $tableId = !empty($in['table_id']) ? (int)$in['table_id'] : null;
        $type    = $in['type'] ?? ($tableId ? 'dine_in' : 'bar');
        $guests  = (int)($in['guests'] ?? 1);
        $code = 'O' . date('ymdHis') . rand(10,99);
        db()->prepare('INSERT INTO orders (tenant_id, code, table_id, waiter_id, type, status, guests, created_at) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$t, $code, $tableId, user()['id'], $type, 'open', $guests, date('Y-m-d H:i:s')]);
        $oid = (int)db()->lastInsertId();
        if ($tableId) {
            db()->prepare('UPDATE tables SET status="occupied", occupied_since=? WHERE id=?')
                ->execute([date('Y-m-d H:i:s'), $tableId]);
        }
        audit('quick_create_order', 'orders', $oid, ['type' => $type]);
        json_response(['ok' => true, 'order_id' => $oid, 'code' => $code]);

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
        // Recupera prodotto + destination categoria. Risoluzione gerarchica:
        // 1. prodotto.destination (se valorizzata = override admin)
        // 2. categoria.destination (default ereditato)
        // 3. 'kitchen' come ultimo fallback
        $p = db()->prepare('SELECT p.*, p.destination AS p_dest, c.destination AS c_dest FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.tenant_id=?');
        $p->execute([$pid, $t]);
        $product = $p->fetch();
        if (!$product) json_response(['error' => 'prodotto non trovato'], 404);
        $dest = $product['p_dest'] ?: ($product['c_dest'] ?: 'kitchen');
        db()->prepare('INSERT INTO order_items (order_id, product_id, name, qty, price, cost, notes, destination, status) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([$oid, $pid, $product['name'], $qty, $product['price'], $product['cost'], $notes, $dest, 'draft']);
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
            // Notifica al cameriere SOLO se TUTTI gli item dell'ordine (per
            // la stessa destinazione: cucina o bar) sono pronti/serviti.
            $r = db()->prepare('SELECT order_id, destination FROM order_items WHERE id=?');
            $r->execute([$iid]);
            $i = $r->fetch();
            if ($i) {
                $check = db()->prepare("SELECT COUNT(*) c, SUM(CASE WHEN status IN ('ready','served') THEN 1 ELSE 0 END) ready_n
                                        FROM order_items
                                        WHERE order_id=? AND destination=? AND status != 'cancelled'");
                $check->execute([$i['order_id'], $i['destination']]);
                $st = $check->fetch();
                if ($st && (int)$st['c'] > 0 && (int)$st['c'] === (int)$st['ready_n']) {
                    // Tutti pronti per questa destinazione → notifica
                    $tbl = db()->prepare("SELECT t.code AS tcode FROM orders o LEFT JOIN tables t ON t.id=o.table_id WHERE o.id=?");
                    $tbl->execute([$i['order_id']]);
                    $tc = $tbl->fetch();
                    $label = $i['destination'] === 'bar' ? 'Bar' : 'Cucina';
                    $tlabel = $tc && $tc['tcode'] ? $tc['tcode'] : '#' . $i['order_id'];
                    notify('cameriere', "$label · Tavolo $tlabel pronto", "Tutti i piatti del tavolo $tlabel sono pronti", "/index.php?p=waiter");
                }
            }
        }
        json_response(['ok' => true]);

    case 'order_all_ready': {
        // Marca TUTTI gli item di un ordine per una destinazione come "ready".
        // Usato dal bottone "Tutto pronto" sul KDS.
        $oid  = (int)($in['order_id'] ?? 0);
        $dest = $in['dest'] ?? 'kitchen';
        if (!$oid) json_response(['error' => 'missing order_id'], 400);
        $now = date('Y-m-d H:i:s');
        db()->prepare("UPDATE order_items SET status='ready', ready_at=? WHERE order_id=? AND destination=? AND status IN ('sent','preparing')")
            ->execute([$now, $oid, $dest]);
        // Notifica cameriere (sempre, perché l'azione è esplicita)
        $tbl = db()->prepare("SELECT t.code AS tcode FROM orders o LEFT JOIN tables t ON t.id=o.table_id WHERE o.id=?");
        $tbl->execute([$oid]);
        $tc = $tbl->fetch();
        $label = $dest === 'bar' ? 'Bar' : 'Cucina';
        $tlabel = $tc && $tc['tcode'] ? $tc['tcode'] : '#' . $oid;
        notify('cameriere', "$label · Tavolo $tlabel pronto", "Tutti i piatti del tavolo $tlabel sono pronti", "/index.php?p=waiter");
        json_response(['ok' => true]);
    }

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
        // Restituisce gli ordini RAGGRUPPATI PER TAVOLO/ORDINE, non come
        // lista piatta di item. Cucina/bar vedono una card per tavolo
        // con dentro tutti i piatti, e possono spuntarli uno per uno o
        // marcare tutto pronto in un colpo.
        $dest = $in['dest'] ?? 'kitchen';
        $lang = $in['lang'] ?? null;
        if (!$lang || !in_array($lang, SUPPORTED_LANGS, true)) $lang = current_lang();

        // Mostra SOLO gli item ancora da preparare (sent/preparing).
        // Quando il cuoco marca "tutto pronto" la card sparisce subito alla
        // prossima sync (entro 2 sec): cucina pulita, no accumulo di card
        // barrate. Per il contatore dei piatti gia' pronti ma non ancora
        // serviti vedi sotto la query separata "ready_count".
        $st = db()->prepare('SELECT oi.*, p.translations AS p_translations
            FROM order_items oi
            JOIN orders o ON o.id=oi.order_id
            LEFT JOIN products p ON p.id=oi.product_id
            WHERE o.tenant_id=? AND oi.destination=? AND oi.status IN ("sent","preparing")
            ORDER BY oi.sent_at ASC');
        $st->execute([$t, $dest]);
        $items = $st->fetchAll();

        // Conteggio piatti gia' pronti ma non ancora serviti (per la pillola in cucina)
        $readyCntSt = db()->prepare('SELECT COUNT(*) c FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.tenant_id=? AND oi.destination=? AND oi.status="ready"');
        $readyCntSt->execute([$t, $dest]);
        $readyCount = (int)$readyCntSt->fetch()['c'];

        // Recupera info ordini in una sola query.
        // IMPORTANTE: array_values per riindicizzare l'array (array_unique
        // mantiene gli indici sparse, PDO si confonde con i placeholder).
        $orderIds = array_values(array_unique(array_column($items, 'order_id')));
        $ordersById = [];
        if ($orderIds) {
            $place = implode(',', array_fill(0, count($orderIds), '?'));
            $os = db()->prepare("SELECT o.id, o.code AS order_code, o.guests, o.notes AS order_notes, t.code AS table_code, u.name AS waiter_name
                FROM orders o
                LEFT JOIN tables t ON t.id=o.table_id
                LEFT JOIN users u ON u.id=o.waiter_id
                WHERE o.id IN ($place)");
            $os->execute($orderIds);
            foreach ($os->fetchAll() as $o) $ordersById[$o['id']] = $o;
        }

        // Raggruppa per order_id
        $orders = [];
        foreach ($items as $it) {
            $oid = $it['order_id'];
            // Traduci il nome piatto
            $localized = tr_field($it['p_translations'] ?? null, 'name', $it['name'], $lang);
            if ($localized) $it['name'] = $localized;
            unset($it['p_translations']);

            if (!isset($orders[$oid])) {
                $base = $ordersById[$oid] ?? [];
                $orders[$oid] = [
                    'order_id'    => $oid,
                    'order_code'  => $base['order_code'] ?? null,
                    'table_code'  => $base['table_code'] ?? null,
                    'waiter_name' => $base['waiter_name'] ?? null,
                    'order_notes' => $base['order_notes'] ?? null,
                    'guests'      => $base['guests'] ?? null,
                    'items'       => [],
                    'sent_at'     => $it['sent_at'],   // verrà sovrascritto sotto col più vecchio
                    'done_count'  => 0,
                    'total_count' => 0,
                ];
            }
            $orders[$oid]['items'][] = $it;
            $orders[$oid]['total_count']++;
            if (in_array($it['status'], ['ready','served'], true)) $orders[$oid]['done_count']++;
            // sent_at = il più vecchio degli item dell'ordine (così il timer parte dal primo)
            if ($it['sent_at'] && (!$orders[$oid]['sent_at'] || $it['sent_at'] < $orders[$oid]['sent_at'])) {
                $orders[$oid]['sent_at'] = $it['sent_at'];
            }
        }

        // Ordina dal più vecchio al più recente
        $list = array_values($orders);
        usort($list, fn($a, $b) => strcmp($a['sent_at'] ?? '', $b['sent_at'] ?? ''));

        json_response(['orders' => $list, 'ready_count' => $readyCount]);

    case 'kitchen_archive': {
        // Archivio degli ordini completati (tutti gli item ready/served)
        // nelle ultime N ore (default 2). Raggruppati per ordine, dal piu
        // recente. Serve al cuoco per "rivedere cosa ha fatto" o riaprire
        // un ordine se ha sbagliato a marcare pronto.
        $dest  = $in['dest'] ?? 'kitchen';
        $hours = max(1, min(12, (int)($in['hours'] ?? 2)));
        $lang  = $in['lang'] ?? null;
        if (!$lang || !in_array($lang, SUPPORTED_LANGS, true)) $lang = current_lang();
        $cutoff = DB_DRIVER === 'mysql'
            ? "DATE_SUB(NOW(), INTERVAL $hours HOUR)"
            : "datetime('now','-$hours hours')";
        // Tutti gli item per la destinazione, in stato ready/served, da $hours fa
        $st = db()->prepare("SELECT oi.*, p.translations AS p_translations
            FROM order_items oi
            JOIN orders o ON o.id=oi.order_id
            LEFT JOIN products p ON p.id=oi.product_id
            WHERE o.tenant_id=? AND oi.destination=? AND oi.status IN ('ready','served')
              AND COALESCE(oi.ready_at, oi.sent_at) >= $cutoff
            ORDER BY COALESCE(oi.ready_at, oi.sent_at) DESC");
        $st->execute([$t, $dest]);
        $items = $st->fetchAll();

        // Recupera info ordini in batch
        $orderIds = array_values(array_unique(array_column($items, 'order_id')));
        $ordersById = [];
        if ($orderIds) {
            $place = implode(',', array_fill(0, count($orderIds), '?'));
            $os = db()->prepare("SELECT o.id, o.code AS order_code, t.code AS table_code, u.name AS waiter_name
                FROM orders o LEFT JOIN tables t ON t.id=o.table_id LEFT JOIN users u ON u.id=o.waiter_id
                WHERE o.id IN ($place)");
            $os->execute($orderIds);
            foreach ($os->fetchAll() as $o) $ordersById[$o['id']] = $o;
        }

        // Mostra SOLO ordini in cui TUTTI gli item della destinazione sono pronti/serviti
        // (cioè la cucina ha finito tutta la sua parte). Esclude ordini ancora in lavorazione.
        $byOid = [];
        foreach ($items as $it) {
            $oid = $it['order_id'];
            $localized = tr_field($it['p_translations'] ?? null, 'name', $it['name'], $lang);
            if ($localized) $it['name'] = $localized;
            unset($it['p_translations']);
            if (!isset($byOid[$oid])) {
                $base = $ordersById[$oid] ?? [];
                $byOid[$oid] = [
                    'order_id'    => $oid,
                    'order_code'  => $base['order_code'] ?? null,
                    'table_code'  => $base['table_code'] ?? null,
                    'waiter_name' => $base['waiter_name'] ?? null,
                    'items'       => [],
                    'completed_at'=> $it['ready_at'] ?? $it['sent_at'],
                ];
            }
            $byOid[$oid]['items'][] = $it;
            // tieni il timestamp piu recente come "completed_at"
            $ts = $it['ready_at'] ?? $it['sent_at'];
            if ($ts && $ts > $byOid[$oid]['completed_at']) $byOid[$oid]['completed_at'] = $ts;
        }

        // Filtra fuori gli ordini che hanno ANCHE item ancora pending (non davvero completati)
        $pendingSt = db()->prepare("SELECT order_id FROM order_items WHERE destination=? AND status IN ('sent','preparing') AND order_id IN (" . (count($orderIds) ? implode(',', array_fill(0, count($orderIds), '?')) : 'NULL') . ")");
        if ($orderIds) {
            $pendingSt->execute(array_merge([$dest], $orderIds));
            foreach ($pendingSt->fetchAll() as $p) unset($byOid[$p['order_id']]);
        }

        $list = array_values($byOid);
        usort($list, fn($a, $b) => strcmp($b['completed_at'] ?? '', $a['completed_at'] ?? '')); // piu recenti prima
        json_response(['orders' => $list]);
    }

    case 'order_reopen': {
        // Riapre un ordine archiviato: cambia status item da ready -> preparing.
        // Cosi torna a comparire nella coda di cucina/bar.
        $oid  = (int)($in['order_id'] ?? 0);
        $dest = $in['dest'] ?? 'kitchen';
        if (!$oid) json_response(['error' => 'missing order_id'], 400);
        db()->prepare("UPDATE order_items SET status='preparing', ready_at=NULL
                       WHERE order_id=? AND destination=? AND status='ready'")
            ->execute([$oid, $dest]);
        audit('reopen_order', 'orders', $oid, ['dest' => $dest]);
        json_response(['ok' => true]);
    }

    case 'ready_pickup':
        $st = db()->prepare('SELECT oi.*, t.code AS table_code FROM order_items oi JOIN orders o ON o.id=oi.order_id LEFT JOIN tables t ON t.id=o.table_id WHERE o.tenant_id=? AND oi.status="ready" ORDER BY oi.ready_at');
        $st->execute([$t]);
        json_response(['items' => $st->fetchAll()]);

    default: json_response(['error' => 'unknown'], 400);
}
