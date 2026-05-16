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

        // genera codice univoco (max 5 tentativi)
        $code = null;
        for ($i = 0; $i < 5; $i++) {
            $c = gc_generate_code();
            $chk = db()->prepare('SELECT 1 FROM gift_cards WHERE code=?');
            $chk->execute([$c]);
            if (!$chk->fetchColumn()) { $code = $c; break; }
        }
        if (!$code) json_response(['error' => 'Impossibile generare codice'], 500);

        db()->prepare('INSERT INTO gift_cards (tenant_id, code, customer_name, phone, valid_date, percent, note, status, issued_by_user, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([
                $t, $code, $name,
                $in['phone'] ?? '',
                $date,
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

        $today = date('Y-m-d');
        $valid_now = ($g['status'] === 'issued' && $g['valid_date'] === $today);
        $reason = '';
        if ($g['status'] === 'used') $reason = 'Codice già usato il ' . date('d/m/Y', strtotime($g['used_at']));
        elseif ($g['status'] === 'cancelled') $reason = 'Codice annullato';
        elseif ($g['valid_date'] < $today) $reason = 'Codice scaduto · era valido per il ' . date('d/m/Y', strtotime($g['valid_date']));
        elseif ($g['valid_date'] > $today) $reason = 'Valida solo dal ' . date('d/m/Y', strtotime($g['valid_date']));

        json_response(['found' => true, 'gift_card' => $g, 'valid_today' => $valid_now, 'reason' => $reason]);

    case 'redeem':
        // segna come usata (cassa)
        $code = trim(strtoupper($in['code'] ?? ''));
        $st = db()->prepare('SELECT * FROM gift_cards WHERE code=? AND tenant_id=?');
        $st->execute([$code, $t]);
        $g = $st->fetch();
        if (!$g) json_response(['error' => 'Codice non trovato'], 404);
        if ($g['status'] !== 'issued') json_response(['error' => 'Gift card non utilizzabile (stato: ' . $g['status'] . ')'], 400);
        $today = date('Y-m-d');
        if ($g['valid_date'] !== $today) json_response(['error' => 'Gift card non valida per oggi'], 400);

        db()->prepare("UPDATE gift_cards SET status='used', used_at=?, used_by_user=? WHERE id=?")
            ->execute([date('Y-m-d H:i:s'), $u['id'] ?? null, $g['id']]);
        audit('redeem_gift_card', 'gift_cards', $g['id'], ['code' => $code]);
        json_response(['ok' => true, 'gift_card' => $g]);

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
