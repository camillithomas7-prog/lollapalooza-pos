<?php
require __DIR__ . '/_bootstrap.php';
$t = tenant_id();
$in = input();

function transfer_token(int $tid): string {
    $st = db()->prepare("SELECT value FROM settings WHERE tenant_id=? AND `key`='transfer_driver_token'");
    $st->execute([$tid]);
    $tok = $st->fetchColumn();
    if (!$tok) {
        $tok = bin2hex(random_bytes(16));
        $st = db()->prepare("INSERT INTO settings (tenant_id, `key`, value) VALUES (?,?,?)");
        try { $st->execute([$tid, 'transfer_driver_token', $tok]); }
        catch (PDOException $e) {
            db()->prepare("UPDATE settings SET value=? WHERE tenant_id=? AND `key`='transfer_driver_token'")
                ->execute([$tok, $tid]);
        }
    }
    return $tok;
}

switch ($action) {
    case 'list':
        $from = $in['from'] ?? date('Y-m-d');
        $to = $in['to'] ?? date('Y-m-d', strtotime($from . ' +14 days'));
        // include sia transfer con pickup nel range, sia transfer con ritorno nel range
        $st = db()->prepare('SELECT * FROM transfers WHERE tenant_id=? AND (DATE(pickup_when) BETWEEN ? AND ? OR DATE(return_when) BETWEEN ? AND ?) ORDER BY pickup_when');
        $st->execute([$t, $from, $to, $from, $to]);
        $rows = $st->fetchAll();

        // Stats giornaliere (oggi) — somma andata + ritorno
        $today = date('Y-m-d');
        $stats = db()->prepare("SELECT
            (SELECT COUNT(*) FROM transfers WHERE tenant_id=? AND (DATE(pickup_when)=? OR DATE(return_when)=?)) AS total,
            (SELECT COUNT(*) FROM transfers WHERE tenant_id=? AND DATE(pickup_when)=? AND status IN ('scheduled','on_way'))
              + (SELECT COUNT(*) FROM transfers WHERE tenant_id=? AND DATE(return_when)=? AND return_status IN ('scheduled','on_way')) AS pending,
            (SELECT COUNT(*) FROM transfers WHERE tenant_id=? AND DATE(pickup_when)=? AND status='picked_up')
              + (SELECT COUNT(*) FROM transfers WHERE tenant_id=? AND DATE(return_when)=? AND return_status='picked_up') AS in_progress,
            (SELECT COUNT(*) FROM transfers WHERE tenant_id=? AND DATE(pickup_when)=? AND status='completed')
              + (SELECT COUNT(*) FROM transfers WHERE tenant_id=? AND DATE(return_when)=? AND return_status='completed') AS completed");
        $stats->execute([$t,$today,$today, $t,$today, $t,$today, $t,$today, $t,$today, $t,$today, $t,$today]);
        json_response(['transfers' => $rows, 'stats' => $stats->fetch(), 'token' => transfer_token($t)]);

    case 'get_link':
        $tok = transfer_token($t);
        $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        json_response(['token' => $tok, 'url' => $base . '/index.php?p=driver&t=' . $tok]);

    case 'regenerate_token':
        $new = bin2hex(random_bytes(16));
        $st = db()->prepare("UPDATE settings SET value=? WHERE tenant_id=? AND `key`='transfer_driver_token'");
        $st->execute([$new, $t]);
        if ($st->rowCount() === 0) {
            db()->prepare("INSERT INTO settings (tenant_id, `key`, value) VALUES (?,?,?)")
                ->execute([$t, 'transfer_driver_token', $new]);
        }
        audit('regenerate_transfer_token');
        $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        json_response(['token' => $new, 'url' => $base . '/index.php?p=driver&t=' . $new]);

    case 'save':
        $now = date('Y-m-d H:i:s');
        $fields = [
            $in['customer_name'] ?? '',
            $in['phone'] ?? '',
            $in['language'] ?? 'it',
            $in['direction'] ?? 'to_venue',
            $in['pickup_when'] ?? $now,
            !empty($in['return_when']) ? $in['return_when'] : null,
            $in['pickup_location'] ?? '',
            $in['pickup_address'] ?? '',
            $in['dropoff_location'] ?? '',
            $in['dropoff_address'] ?? '',
            (int)($in['passengers'] ?? 1),
            (int)($in['luggage'] ?? 0),
            $in['flight_no'] ?? '',
            $in['vehicle'] ?? '',
            $in['driver_name'] ?? '',
            (float)($in['price_egp'] ?? 0),
            !empty($in['reservation_id']) ? (int)$in['reservation_id'] : null,
            $in['notes'] ?? '',
            $in['status'] ?? 'scheduled',
            $in['return_status'] ?? 'scheduled',
        ];
        if (!empty($in['id'])) {
            $sql = 'UPDATE transfers SET customer_name=?, phone=?, language=?, direction=?, pickup_when=?, return_when=?, pickup_location=?, pickup_address=?, dropoff_location=?, dropoff_address=?, passengers=?, luggage=?, flight_no=?, vehicle=?, driver_name=?, price_egp=?, reservation_id=?, notes=?, status=?, return_status=?, updated_at=? WHERE id=? AND tenant_id=?';
            $fields[] = $now;
            $fields[] = (int)$in['id'];
            $fields[] = $t;
            db()->prepare($sql)->execute($fields);
            audit('update_transfer', 'transfers', (int)$in['id']);
        } else {
            $sql = 'INSERT INTO transfers (customer_name, phone, language, direction, pickup_when, return_when, pickup_location, pickup_address, dropoff_location, dropoff_address, passengers, luggage, flight_no, vehicle, driver_name, price_egp, reservation_id, notes, status, return_status, created_at, updated_at, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $fields[] = $now;
            $fields[] = $now;
            $fields[] = $t;
            db()->prepare($sql)->execute($fields);
            $id = (int)db()->lastInsertId();
            audit('create_transfer', 'transfers', $id);
        }
        json_response(['ok' => true]);

    case 'set_status':
        // leg: 'out' (andata) | 'ret' (ritorno). Default 'out' per retrocompatibilità
        $leg = $in['leg'] ?? 'out';
        $col = ($leg === 'ret') ? 'return_status' : 'status';
        db()->prepare("UPDATE transfers SET $col=?, updated_at=? WHERE id=? AND tenant_id=?")
            ->execute([$in['status'], date('Y-m-d H:i:s'), (int)$in['id'], $t]);
        audit('set_transfer_status', 'transfers', (int)$in['id'], ['leg' => $leg, 'status' => $in['status']]);
        json_response(['ok' => true]);

    case 'delete':
        db()->prepare('DELETE FROM transfers WHERE id=? AND tenant_id=?')->execute([(int)$in['id'], $t]);
        audit('delete_transfer', 'transfers', (int)$in['id']);
        json_response(['ok' => true]);

    case 'from_reservation':
        // Pre-compila un transfer dai dati di una prenotazione esistente
        $rid = (int)($in['reservation_id'] ?? 0);
        $st = db()->prepare('SELECT * FROM reservations WHERE id=? AND tenant_id=?');
        $st->execute([$rid, $t]);
        $r = $st->fetch();
        if (!$r) json_response(['error' => 'reservation not found'], 404);
        json_response(['draft' => [
            'customer_name' => $r['customer_name'],
            'phone' => $r['phone'],
            'pickup_when' => $r['date'] . ' ' . (($r['time'] ?? '20:00') ?: '20:00'),
            'passengers' => (int)$r['guests'],
            'direction' => 'internal',
            'dropoff_location' => 'Lollapalooza',
            'reservation_id' => $r['id'],
            'notes' => $r['notes'] ?? '',
        ]]);

    default: json_response(['error' => 'unknown'], 400);
}
