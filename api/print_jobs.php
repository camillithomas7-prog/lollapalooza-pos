<?php
require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/escpos.php';
require_once __DIR__ . '/../includes/i18n.php';
$t = tenant_id();
$in = input();

// Carica preferenze stampante (codepage + lingua + cols) per il tenant
function _print_settings(int $tenant): array {
    $keyCol = DB_DRIVER === 'mysql' ? '`key`' : 'key';
    $st = db()->prepare("SELECT $keyCol AS k, value FROM settings WHERE tenant_id=? AND $keyCol IN ('printer_cols','printer_codepage','printer_lang')");
    $st->execute([$tenant]);
    $cfg = ['cols'=>32, 'codepage'=>'cp858', 'lang'=>'it'];
    foreach ($st->fetchAll() as $r) {
        if ($r['k'] === 'printer_cols')      $cfg['cols'] = (int)$r['value'];
        if ($r['k'] === 'printer_codepage')  $cfg['codepage'] = $r['value'];
        if ($r['k'] === 'printer_lang')      $cfg['lang'] = $r['value'];
    }
    if ($cfg['cols'] !== 32 && $cfg['cols'] !== 48) $cfg['cols'] = 32;
    if (!in_array($cfg['lang'], SUPPORTED_LANGS, true)) $cfg['lang'] = 'it';
    return $cfg;
}

switch ($action) {
    case 'pending': {
        // Restituisce le comande da stampare per una destinazione.
        // Solo gli ultimi 5 minuti (così se la stampante è disconnessa per
        // mezzora non ne accumuliamo 200 al ricollegamento — l'operatore
        // farà la ristampa manuale dalla card).
        $dest = ($in['dest'] ?? 'kitchen');
        if (!in_array($dest, ['kitchen', 'bar'], true)) $dest = 'kitchen';
        $sinceMin = (int)($in['since_minutes'] ?? 10);
        $cutoff = date('Y-m-d H:i:s', time() - $sinceMin * 60);
        $st = db()->prepare("SELECT id, order_id, destination, payload, attempts, created_at
                              FROM print_jobs
                              WHERE tenant_id=? AND destination=? AND status='pending' AND created_at >= ?
                              ORDER BY created_at ASC LIMIT 20");
        $st->execute([$t, $dest, $cutoff]);
        json_response(['jobs' => $st->fetchAll()]);
    }

    case 'ack': {
        // Conferma stampa completata.
        $id = (int)($in['id'] ?? 0);
        if (!$id) json_response(['error' => 'missing id'], 400);
        db()->prepare("UPDATE print_jobs SET status='printed', printed_at=?, error=NULL WHERE id=? AND tenant_id=?")
            ->execute([date('Y-m-d H:i:s'), $id, $t]);
        json_response(['ok' => true]);
    }

    case 'fail': {
        // Errore di stampa (carta finita, BT scollegato, ecc.) — incrementa
        // contatore e ferma a 3 tentativi.
        $id  = (int)($in['id'] ?? 0);
        $err = mb_substr((string)($in['error'] ?? 'sconosciuto'), 0, 300);
        if (!$id) json_response(['error' => 'missing id'], 400);
        $st = db()->prepare("SELECT attempts FROM print_jobs WHERE id=? AND tenant_id=?");
        $st->execute([$id, $t]);
        $row = $st->fetch();
        if (!$row) json_response(['error' => 'not found'], 404);
        $attempts = (int)$row['attempts'] + 1;
        $newStatus = $attempts >= 3 ? 'failed' : 'pending';
        db()->prepare("UPDATE print_jobs SET attempts=?, error=?, status=? WHERE id=? AND tenant_id=?")
            ->execute([$attempts, $err, $newStatus, $id, $t]);
        json_response(['ok' => true, 'attempts' => $attempts, 'status' => $newStatus]);
    }

    case 'reprint': {
        // Ri-accoda una stampa per un order_item (es. tasto Ristampa sul KDS).
        $orderId = (int)($in['order_id'] ?? 0);
        $dest    = ($in['dest'] ?? 'kitchen');
        if (!$orderId || !in_array($dest, ['kitchen', 'bar'], true)) {
            json_response(['error' => 'missing order_id or dest'], 400);
        }
        $oSt = db()->prepare('SELECT o.id, o.code, o.guests, o.notes, t.code AS table_code, u.name AS waiter_name FROM orders o LEFT JOIN tables t ON t.id=o.table_id LEFT JOIN users u ON u.id=o.waiter_id WHERE o.id=? AND o.tenant_id=?');
        $oSt->execute([$orderId, $t]);
        $order = $oSt->fetch();
        if (!$order) json_response(['error' => 'order not found'], 404);

        $cfg = _print_settings($t);
        $iSt = db()->prepare('SELECT oi.id, oi.name, oi.qty, oi.notes, oi.variants, oi.extras, p.translations AS p_translations FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=? AND oi.destination=? AND oi.status!="cancelled" ORDER BY oi.id');
        $iSt->execute([$orderId, $dest]);
        $items = $iSt->fetchAll();
        if (!$items) json_response(['error' => 'no items for ' . $dest], 400);
        foreach ($items as &$it) {
            $tr = tr_field($it['p_translations'] ?? null, 'name', $it['name'], $cfg['lang']);
            if ($tr) $it['name'] = $tr;
            unset($it['p_translations']);
        }

        $tn = db()->prepare('SELECT name FROM tenants WHERE id=?'); $tn->execute([$t]);
        $tenantName = ($tn->fetch()['name'] ?? 'Ristorante');

        $label = match (true) {
            $cfg['lang'] === 'ar' && $dest === 'bar'     => 'البار (إعادة)',
            $cfg['lang'] === 'ar' && $dest === 'kitchen' => 'المطبخ (إعادة)',
            $dest === 'bar' => 'BAR (RISTAMPA)',
            default         => 'CUCINA (RISTAMPA)',
        };
        $bytes = LollabEscPos\buildKitchenTicket($order, $items, $label, ['tenant_name' => $tenantName, 'cols' => $cfg['cols'], 'beep' => true, 'codepage' => $cfg['codepage']]);
        db()->prepare('INSERT INTO print_jobs (tenant_id, order_id, destination, payload, status, attempts, created_at) VALUES (?,?,?,?,?,0,?)')
            ->execute([$t, $orderId, $dest, base64_encode($bytes), 'pending', date('Y-m-d H:i:s')]);
        audit('reprint_ticket', 'orders', $orderId, ['dest' => $dest]);
        json_response(['ok' => true]);
    }

    case 'test': {
        // Inserisce una mini comanda di prova per testare la connessione BT.
        $dest = ($in['dest'] ?? 'kitchen');
        if (!in_array($dest, ['kitchen', 'bar'], true)) $dest = 'kitchen';
        $cfg = _print_settings($t);
        $tn = db()->prepare('SELECT name FROM tenants WHERE id=?'); $tn->execute([$t]);
        $tenantName = ($tn->fetch()['name'] ?? 'Ristorante');
        $fakeOrder = ['code' => 'TEST', 'table_code' => 'TEST', 'waiter_name' => 'Test', 'guests' => 0, 'notes' => ''];
        $fakeItems = $cfg['lang'] === 'ar'
            ? [['qty' => 1, 'name' => 'اختبار الطابعة', 'notes' => 'إذا رأيت هذا الإيصال، الطباعة تعمل!']]
            : [['qty' => 1, 'name' => 'Test stampante', 'notes' => 'Se vedi questa ricevuta, la stampa funziona!']];
        $label = $cfg['lang'] === 'ar' ? 'اختبار' : strtoupper($dest) . ' TEST';
        $bytes = LollabEscPos\buildKitchenTicket($fakeOrder, $fakeItems, $label, ['tenant_name' => $tenantName, 'cols' => $cfg['cols'], 'codepage' => $cfg['codepage']]);
        db()->prepare('INSERT INTO print_jobs (tenant_id, order_id, destination, payload, status, attempts, created_at) VALUES (?,?,?,?,?,0,?)')
            ->execute([$t, 0, $dest, base64_encode($bytes), 'pending', date('Y-m-d H:i:s')]);
        json_response(['ok' => true]);
    }

    case 'test_codepages': {
        // Stampa uno scontrino diagnostico con la stessa parola araba ripetuta
        // in TUTTI i codepage supportati. L'operatore vede quale viene fuori
        // leggibile e imposta quello in Impostazioni.
        $dest = ($in['dest'] ?? 'kitchen');
        if (!in_array($dest, ['kitchen', 'bar'], true)) $dest = 'kitchen';
        $sample = 'مرحبا'; // "Marhaba" (ciao)
        $out = LollabEscPos\init();
        $out .= LollabEscPos\alignCenter() . LollabEscPos\boldOn() . "=== TEST CODEPAGE ===" . LollabEscPos\LF . LollabEscPos\boldOff();
        $out .= "Italiano: Ciao mondo!" . LollabEscPos\LF;
        $out .= LollabEscPos\LF;
        foreach (['cp864' => 22, 'cp1256' => 29] as $name => $esc) {
            $out .= LollabEscPos\separator(32, '-');
            $out .= "Codepage $name (ESC t $esc):" . LollabEscPos\LF;
            $out .= LollabEscPos\setCodepage($esc);
            $out .= LollabEscPos\encodeForPrinter($sample, $name) . LollabEscPos\LF;
            $out .= LollabEscPos\setCodepage(19); // torna a latino per la riga descrittiva
        }
        $out .= LollabEscPos\separator(32, '=');
        $out .= "Quale riga e leggibile?" . LollabEscPos\LF;
        $out .= "Impostala come codepage." . LollabEscPos\LF;
        $out .= LollabEscPos\feed(4) . LollabEscPos\cut();
        db()->prepare('INSERT INTO print_jobs (tenant_id, order_id, destination, payload, status, attempts, created_at) VALUES (?,?,?,?,?,0,?)')
            ->execute([$t, 0, $dest, base64_encode($out), 'pending', date('Y-m-d H:i:s')]);
        json_response(['ok' => true]);
    }

    case 'set_cols': {
        // Salva la larghezza colonne (32 o 48) per il tenant.
        $cols = (int)($in['cols'] ?? 32);
        if ($cols !== 32 && $cols !== 48) json_response(['error' => 'cols must be 32 or 48'], 400);
        $keyCol = DB_DRIVER === 'mysql' ? '`key`' : 'key';
        $exists = db()->prepare("SELECT 1 FROM settings WHERE tenant_id=? AND $keyCol=?");
        $exists->execute([$t, 'printer_cols']);
        if ($exists->fetch()) {
            db()->prepare("UPDATE settings SET value=? WHERE tenant_id=? AND $keyCol=?")->execute([(string)$cols, $t, 'printer_cols']);
        } else {
            db()->prepare("INSERT INTO settings (tenant_id, $keyCol, value) VALUES (?,?,?)")->execute([$t, 'printer_cols', (string)$cols]);
        }
        json_response(['ok' => true, 'cols' => $cols]);
    }

    default:
        json_response(['error' => 'unknown action: ' . $action], 400);
}
