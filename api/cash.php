<?php
require __DIR__ . '/_bootstrap.php';
$t = tenant_id();
$in = input();

switch ($action) {
    case 'current':
        $st = db()->prepare('SELECT cs.*, u.name AS user_name FROM cash_sessions cs LEFT JOIN users u ON u.id=cs.user_id WHERE cs.tenant_id=? AND cs.status="open" ORDER BY cs.id DESC LIMIT 1');
        $st->execute([$t]);
        $s = $st->fetch();
        if ($s) {
            $sums = db()->prepare('SELECT method, SUM(amount) total FROM payments WHERE cash_session_id=? GROUP BY method');
            $sums->execute([$s['id']]);
            $byMethod = [];
            foreach ($sums->fetchAll() as $r) $byMethod[$r['method']] = (float)$r['total'];
            $movs = db()->prepare('SELECT type, SUM(amount) total FROM cash_movements WHERE cash_session_id=? GROUP BY type');
            $movs->execute([$s['id']]);
            $movsByType = [];
            foreach ($movs->fetchAll() as $r) $movsByType[$r['type']] = (float)$r['total'];
            $expected = ($s['open_amount'] ?? 0) + ($byMethod['cash'] ?? 0) + ($movsByType['in'] ?? 0) - ($movsByType['out'] ?? 0);
            $s['expected'] = round($expected, 2);
            $s['by_method'] = $byMethod;
            $s['movements'] = $movsByType;
        }
        json_response(['session' => $s]);

    case 'open_session':
        $exists = db()->prepare('SELECT id FROM cash_sessions WHERE tenant_id=? AND status="open"');
        $exists->execute([$t]);
        if ($exists->fetch()) json_response(['error' => 'sessione già aperta'], 400);
        db()->prepare('INSERT INTO cash_sessions (tenant_id, user_id, opened_at, open_amount, status) VALUES (?,?,?,?,?)')
            ->execute([$t, user()['id'], date('Y-m-d H:i:s'), (float)($in['open_amount'] ?? 0), 'open']);
        json_response(['ok' => true, 'id' => db()->lastInsertId()]);

    case 'close_session':
        $sid = (int)$in['id'];
        $closeAmt = (float)$in['close_amount'];
        $st = db()->prepare('SELECT * FROM cash_sessions WHERE id=? AND tenant_id=?');
        $st->execute([$sid, $t]);
        $s = $st->fetch();
        $sums = db()->prepare('SELECT SUM(amount) tot FROM payments WHERE cash_session_id=? AND method="cash"');
        $sums->execute([$sid]);
        $expected = ($s['open_amount'] ?? 0) + (float)$sums->fetch()['tot'];
        $diff = $closeAmt - $expected;
        db()->prepare('UPDATE cash_sessions SET closed_at=?, close_amount=?, expected=?, diff=?, status="closed", notes=? WHERE id=?')
            ->execute([date('Y-m-d H:i:s'), $closeAmt, $expected, $diff, $in['notes'] ?? '', $sid]);
        audit('close_cash', 'cash_sessions', $sid);
        json_response(['ok' => true, 'diff' => $diff]);

    case 'movement':
        $sid = (int)$in['session_id'];
        db()->prepare('INSERT INTO cash_movements (cash_session_id, type, amount, reason, user_id, created_at) VALUES (?,?,?,?,?,?)')
            ->execute([$sid, $in['type'], (float)$in['amount'], $in['reason'] ?? '', user()['id'], date('Y-m-d H:i:s')]);
        json_response(['ok' => true]);

    case 'history':
        $st = db()->prepare('SELECT cs.*, u.name AS user_name FROM cash_sessions cs LEFT JOIN users u ON u.id=cs.user_id WHERE cs.tenant_id=? ORDER BY cs.id DESC LIMIT 30');
        $st->execute([$t]);
        json_response(['sessions' => $st->fetchAll()]);

    default: json_response(['error' => 'unknown'], 400);
}
