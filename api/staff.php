<?php
require __DIR__ . '/_bootstrap.php';
$t = tenant_id();
$in = input();

switch ($action) {
    case 'list':
        $st = db()->prepare('SELECT id,name,email,role,phone,salary,hourly_rate,active,pin,created_at FROM users WHERE tenant_id=? ORDER BY active DESC, name');
        $st->execute([$t]);
        json_response(['users' => $st->fetchAll()]);

    case 'save':
        $hashed = !empty($in['password']) ? password_hash($in['password'], PASSWORD_BCRYPT) : null;
        if (!empty($in['id'])) {
            $sql = 'UPDATE users SET name=?, email=?, role=?, phone=?, salary=?, hourly_rate=?, pin=?, active=?';
            $params = [$in['name'], $in['email'], $in['role'], $in['phone'] ?? '', (float)($in['salary']??0), (float)($in['hourly_rate']??0), $in['pin'] ?? null, (int)($in['active']??1)];
            if ($hashed) { $sql .= ', password=?'; $params[] = $hashed; }
            $sql .= ' WHERE id=? AND tenant_id=?';
            $params[] = $in['id']; $params[] = $t;
            db()->prepare($sql)->execute($params);
        } else {
            db()->prepare('INSERT INTO users (tenant_id, name, email, password, role, phone, salary, hourly_rate, pin, active, created_at) VALUES (?,?,?,?,?,?,?,?,?,1,?)')
                ->execute([$t, $in['name'], $in['email'], $hashed ?: password_hash('lollab2026', PASSWORD_BCRYPT), $in['role'], $in['phone'] ?? '', (float)($in['salary']??0), (float)($in['hourly_rate']??0), $in['pin'] ?? null, date('Y-m-d H:i:s')]);
        }
        json_response(['ok' => true]);

    case 'shifts':
        $from = $in['from'] ?? date('Y-m-d', strtotime('monday this week'));
        $to = $in['to'] ?? date('Y-m-d', strtotime('sunday this week'));
        $st = db()->prepare('SELECT s.*, u.name FROM shifts s JOIN users u ON u.id=s.user_id WHERE s.tenant_id=? AND s.date BETWEEN ? AND ? ORDER BY s.date, s.start_time');
        $st->execute([$t, $from, $to]);
        json_response(['shifts' => $st->fetchAll()]);

    case 'save_shift':
        db()->prepare('INSERT INTO shifts (tenant_id, user_id, date, start_time, end_time, notes) VALUES (?,?,?,?,?,?)')
            ->execute([$t, $in['user_id'], $in['date'], $in['start_time'], $in['end_time'], $in['notes'] ?? '']);
        json_response(['ok' => true]);

    case 'delete_shift':
        db()->prepare('DELETE FROM shifts WHERE id=? AND tenant_id=?')->execute([$in['id'], $t]);
        json_response(['ok' => true]);

    case 'clock_in':
        db()->prepare('INSERT INTO attendance (tenant_id, user_id, clock_in) VALUES (?,?,?)')
            ->execute([$t, user()['id'], date('Y-m-d H:i:s')]);
        json_response(['ok' => true]);

    case 'clock_out':
        $st = db()->prepare('SELECT * FROM attendance WHERE user_id=? AND clock_out IS NULL ORDER BY id DESC LIMIT 1');
        $st->execute([user()['id']]);
        if ($a = $st->fetch()) {
            $now = date('Y-m-d H:i:s');
            $hours = round((strtotime($now) - strtotime($a['clock_in'])) / 3600, 2);
            db()->prepare('UPDATE attendance SET clock_out=?, hours=? WHERE id=?')->execute([$now, $hours, $a['id']]);
        }
        json_response(['ok' => true]);

    default: json_response(['error' => 'unknown'], 400);
}
