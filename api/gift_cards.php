<?php
require __DIR__ . '/_bootstrap.php';
$t = tenant_id();
$in = input();
$u = user();

function gc_generate_code(): string {
    // formato: LOL-ddmmYY-XXXX (X alfanumerico maiuscolo)
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // niente 0/O/1/I/l per leggibilità
    $rand = '';
    for ($i = 0; $i < 4; $i++) $rand .= $alphabet[random_int(0, strlen($alphabet)-1)];
    return 'LOL-' . date('dmy') . '-' . $rand;
}

/**
 * Calcola finestra di validità di una gift card (in ora egiziana, Africa/Cairo).
 * Restituisce [start_ts, end_ts] (timestamp UNIX nel timezone corrente del server).
 * Se to_hour < from_hour la finestra si estende al giorno successivo (locale di sera).
 */
function gc_validity_window(array $g): array {
    $date  = $g['valid_date'];
    $from  = $g['valid_from_hour'] ?? '20:00:00';
    $to    = $g['valid_to_hour']   ?? '03:00:00';
    $start = strtotime("$date $from");
    $end   = strtotime("$date $to");
    if ($end <= $start) {
        // l'orario di fine è prima dell'inizio → giorno successivo
        $end = strtotime("$date $to +1 day");
    }
    return [$start, $end];
}

function gc_is_valid_now(array $g): bool {
    if ($g['status'] !== 'issued') return false;
    [$s, $e] = gc_validity_window($g);
    $now = time();
    return ($now >= $s && $now <= $e);
}

function gc_window_label(array $g): string {
    $from = substr($g['valid_from_hour'] ?? '20:00:00', 0, 5);
    $to   = substr($g['valid_to_hour']   ?? '03:00:00', 0, 5);
    $next_day = ((int)substr($to,0,2)) < ((int)substr($from,0,2));
    $date_lbl = date('d/m/Y', strtotime($g['valid_date']));
    if ($next_day) {
        $date_next = date('d/m/Y', strtotime($g['valid_date'].' +1 day'));
        return "Dalle $from del $date_lbl alle $to del $date_next";
    }
    return "Il $date_lbl dalle $from alle $to";
}

switch ($action) {
    case 'list':
        $from = $in['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $to   = $in['to']   ?? date('Y-m-d', strtotime('+60 days'));
        $st = db()->prepare("SELECT g.*, u.name AS used_by_name
                             FROM gift_cards g
                             LEFT JOIN users u ON u.id = g.used_by_user
                             WHERE g.tenant_id=? AND g.valid_date BETWEEN ? AND ?
                             ORDER BY g.valid_date DESC, g.id DESC");
        $st->execute([$t, $from, $to]);
        $cards = $st->fetchAll();
        // marca come 'expired' quelle scadute non ancora aggiornate
        $today = date('Y-m-d');
        foreach ($cards as &$c) {
            if ($c['status'] === 'issued' && $c['valid_date'] < $today) {
                $c['status'] = 'expired';
            }
        }
        // stats
        $stats = db()->prepare("SELECT
            (SELECT COUNT(*) FROM gift_cards WHERE tenant_id=? AND DATE(created_at)=?) AS today_issued,
            (SELECT COUNT(*) FROM gift_cards WHERE tenant_id=? AND status='used' AND DATE(used_at)=?) AS today_used,
            (SELECT COUNT(*) FROM gift_cards WHERE tenant_id=? AND status='issued' AND valid_date>=?) AS active_total");
        $stats->execute([$t, $today, $t, $today, $t, $today]);
        json_response(['gift_cards' => $cards, 'stats' => $stats->fetch()]);

    case 'create':
        $name = trim($in['customer_name'] ?? '');
        $date = $in['valid_date'] ?? '';
        if ($name === '') json_response(['error' => 'Nome cliente obbligatorio'], 400);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) json_response(['error' => 'Data non valida'], 400);

        // orari (default 20:00 → 03:00)
        $from_h = $in['valid_from_hour'] ?? '20:00';
        $to_h   = $in['valid_to_hour']   ?? '03:00';
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $from_h)) json_response(['error' => 'Ora inizio non valida'], 400);
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $to_h))   json_response(['error' => 'Ora fine non valida'], 400);
        if (strlen($from_h) === 5) $from_h .= ':00';
        if (strlen($to_h) === 5)   $to_h   .= ':00';

        // genera codice univoco (max 5 tentativi)
        $code = null;
        for ($i = 0; $i < 5; $i++) {
            $c = gc_generate_code();
            $chk = db()->prepare('SELECT 1 FROM gift_cards WHERE code=?');
            $chk->execute([$c]);
            if (!$chk->fetchColumn()) { $code = $c; break; }
        }
        if (!$code) json_response(['error' => 'Impossibile generare codice'], 500);

        db()->prepare('INSERT INTO gift_cards (tenant_id, code, customer_name, phone, valid_date, valid_from_hour, valid_to_hour, percent, note, status, issued_by_user, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([
                $t, $code, $name,
                $in['phone'] ?? '',
                $date, $from_h, $to_h,
                (float)($in['percent'] ?? 10),
                $in['note'] ?? '',
                'issued',
                $u['id'] ?? null,
                date('Y-m-d H:i:s')
            ]);
        $id = (int)db()->lastInsertId();
        audit('create_gift_card', 'gift_cards', $id, ['code' => $code, 'name' => $name, 'date' => $date]);

        $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        json_response([
            'ok' => true,
            'id' => $id,
            'code' => $code,
            'image_url' => $base . '/gift_card.php?code=' . urlencode($code),
            'download_url' => $base . '/gift_card.php?code=' . urlencode($code) . '&dl=1'
        ]);

    case 'lookup':
        // ricerca codice (per cassa)
        $code = trim(strtoupper($in['code'] ?? ''));
        if ($code === '') json_response(['error' => 'Codice mancante'], 400);
        $st = db()->prepare('SELECT * FROM gift_cards WHERE code=? AND tenant_id=?');
        $st->execute([$code, $t]);
        $g = $st->fetch();
        if (!$g) json_response(['error' => 'Codice non trovato', 'found' => false], 404);

        $valid_now = gc_is_valid_now($g);
        [$ws, $we] = gc_validity_window($g);
        $now = time();
        $reason = '';
        if ($g['status'] === 'used')      $reason = 'Codice già usato il ' . date('d/m/Y H:i', strtotime($g['used_at']));
        elseif ($g['status'] === 'cancelled') $reason = 'Codice annullato';
        elseif ($now < $ws)               $reason = 'Sarà valida dal ' . date('d/m/Y H:i', $ws);
        elseif ($now > $we)               $reason = 'Scaduta il ' . date('d/m/Y H:i', $we);

        json_response([
            'found' => true,
            'gift_card' => $g,
            'valid_now' => $valid_now,
            'valid_today' => $valid_now, // retrocompatibilità
            'window_label' => gc_window_label($g),
            'window_start' => date('c', $ws),
            'window_end'   => date('c', $we),
            'reason' => $reason
        ]);

    case 'redeem':
        // segna come usata (cassa)
        $code = trim(strtoupper($in['code'] ?? ''));
        $st = db()->prepare('SELECT * FROM gift_cards WHERE code=? AND tenant_id=?');
        $st->execute([$code, $t]);
        $g = $st->fetch();
        if (!$g) json_response(['error' => 'Codice non trovato'], 404);
        if ($g['status'] !== 'issued') json_response(['error' => 'Gift card non utilizzabile (stato: ' . $g['status'] . ')'], 400);
        if (!gc_is_valid_now($g)) {
            [$ws, $we] = gc_validity_window($g);
            $now = time();
            $msg = $now < $ws ? ('Sarà valida dal ' . date('d/m/Y H:i', $ws)) : ('Scaduta il ' . date('d/m/Y H:i', $we));
            json_response(['error' => 'Gift card non valida in questo momento — ' . $msg], 400);
        }

        db()->prepare("UPDATE gift_cards SET status='used', used_at=?, used_by_user=? WHERE id=?")
            ->execute([date('Y-m-d H:i:s'), $u['id'] ?? null, $g['id']]);
        audit('redeem_gift_card', 'gift_cards', $g['id'], ['code' => $code]);
        json_response(['ok' => true, 'gift_card' => $g]);

    case 'search':
        // autocomplete: cerca per codice o nome (per dropdown ordini)
        $q = trim($in['q'] ?? '');
        $only_valid_now = !empty($in['only_valid_now']);
        $sql = "SELECT * FROM gift_cards WHERE tenant_id=? AND status='issued'";
        $args = [$t];
        if ($q !== '') {
            $sql .= " AND (code LIKE ? OR customer_name LIKE ?)";
            $args[] = '%' . $q . '%';
            $args[] = '%' . $q . '%';
        }
        $sql .= " ORDER BY valid_date DESC LIMIT 50";
        $st = db()->prepare($sql);
        $st->execute($args);
        $rows = $st->fetchAll();
        if ($only_valid_now) $rows = array_values(array_filter($rows, 'gc_is_valid_now'));
        // arricchisco con valid_now per il front
        foreach ($rows as &$r) {
            $r['valid_now'] = gc_is_valid_now($r);
            $r['window_label'] = gc_window_label($r);
        }
        json_response(['gift_cards' => $rows]);

    case 'apply_to_order':
        // applica gift card a un ordine: valida fascia oraria, calcola sconto, marca come usata
        $code  = trim(strtoupper($in['code'] ?? ''));
        $order_id = (int)($in['order_id'] ?? 0);
        if (!$code || !$order_id) json_response(['error' => 'Codice e order_id obbligatori'], 400);

        $st = db()->prepare('SELECT * FROM gift_cards WHERE code=? AND tenant_id=?');
        $st->execute([$code, $t]);
        $g = $st->fetch();
        if (!$g) json_response(['error' => 'Codice non trovato'], 404);
        if ($g['status'] !== 'issued') json_response(['error' => 'Gift card non utilizzabile (stato: ' . $g['status'] . ')'], 400);
        if (!gc_is_valid_now($g)) {
            [$ws, $we] = gc_validity_window($g);
            $now = time();
            $msg = $now < $ws ? ('Sarà valida dal ' . date('d/m/Y H:i', $ws)) : ('Scaduta il ' . date('d/m/Y H:i', $we));
            json_response(['error' => 'Gift card non valida in questo momento — ' . $msg], 400);
        }

        // verifica ordine
        $ost = db()->prepare('SELECT id, total, gift_card_id, status FROM orders WHERE id=? AND tenant_id=?');
        $ost->execute([$order_id, $t]);
        $o = $ost->fetch();
        if (!$o) json_response(['error' => 'Ordine non trovato'], 404);
        if ($o['gift_card_id']) json_response(['error' => 'L\'ordine ha già una gift card applicata'], 400);
        if (in_array($o['status'], ['closed','cancelled'])) json_response(['error' => 'Ordine già chiuso'], 400);

        $pct = (float)$g['percent'];
        $discount = round((float)$o['total'] * $pct / 100, 2);
        $newTotal = round((float)$o['total'] - $discount, 2);

        // transazione
        db()->beginTransaction();
        try {
            db()->prepare("UPDATE gift_cards SET status='used', used_at=?, used_by_user=? WHERE id=?")
                ->execute([date('Y-m-d H:i:s'), $u['id'] ?? null, $g['id']]);
            db()->prepare('UPDATE orders SET gift_card_id=?, discount_percent=?, discount_amount=?, total=? WHERE id=?')
                ->execute([$g['id'], $pct, $discount, $newTotal, $order_id]);
            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
            json_response(['error' => 'Errore applicazione: ' . $e->getMessage()], 500);
        }
        audit('apply_gift_card', 'orders', $order_id, ['code' => $code, 'discount' => $discount]);
        json_response(['ok' => true, 'discount_percent' => $pct, 'discount_amount' => $discount, 'new_total' => $newTotal]);

    case 'valid_now':
        // lista compatta delle gift card valide adesso (per cassa)
        $st = db()->prepare("SELECT * FROM gift_cards WHERE tenant_id=? AND status='issued' ORDER BY valid_date DESC LIMIT 200");
        $st->execute([$t]);
        $rows = array_values(array_filter($st->fetchAll(), 'gc_is_valid_now'));
        foreach ($rows as &$r) $r['window_label'] = gc_window_label($r);
        json_response(['gift_cards' => $rows]);

    case 'cancel':
        db()->prepare("UPDATE gift_cards SET status='cancelled' WHERE id=? AND tenant_id=? AND status='issued'")
            ->execute([(int)$in['id'], $t]);
        audit('cancel_gift_card', 'gift_cards', (int)$in['id']);
        json_response(['ok' => true]);

    case 'delete':
        db()->prepare('DELETE FROM gift_cards WHERE id=? AND tenant_id=?')
            ->execute([(int)$in['id'], $t]);
        audit('delete_gift_card', 'gift_cards', (int)$in['id']);
        json_response(['ok' => true]);

    default: json_response(['error' => 'unknown action'], 400);
}
