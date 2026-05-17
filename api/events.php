<?php
require __DIR__ . '/_bootstrap.php';
$t = tenant_id();
$in = input();

switch ($action) {
    case 'list': {
        // Tutti gli eventi attivi: ricorrenti + singole date
        $st = db()->prepare('SELECT * FROM events WHERE tenant_id=? AND active=1 ORDER BY is_recurring DESC, weekday, event_date, start_time');
        $st->execute([$t]);
        json_response(['events' => $st->fetchAll()]);
    }

    case 'week': {
        // Eventi della settimana corrente (o specificata): combina ricorrenti del giorno + eventi datati
        $start = $in['start'] ?? date('Y-m-d', strtotime('monday this week'));
        $startTs = strtotime($start);
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $d = date('Y-m-d', strtotime("+$i day", $startTs));
            $wd = (int) date('w', strtotime($d)); // 0=Dom..6=Sab
            $st = db()->prepare('SELECT * FROM events WHERE tenant_id=? AND active=1 AND ((is_recurring=1 AND weekday=?) OR (is_recurring=0 AND event_date=?)) ORDER BY start_time');
            $st->execute([$t, $wd, $d]);
            $days[] = ['date' => $d, 'weekday' => $wd, 'events' => $st->fetchAll()];
        }
        json_response(['week' => $days, 'start' => $start]);
    }

    case 'save': {
        $title       = trim($in['title'] ?? '');
        if ($title === '') json_response(['error' => 'Titolo obbligatorio'], 400);
        $desc        = $in['description'] ?? '';
        $category    = $in['category'] ?? 'tema';
        $isRecurring = !empty($in['is_recurring']) ? 1 : 0;
        $weekday     = ($isRecurring && isset($in['weekday'])) ? (int) $in['weekday'] : null;
        $eventDate   = (!$isRecurring && !empty($in['event_date'])) ? $in['event_date'] : null;
        $start       = $in['start_time'] ?: '20:00:00';
        $end         = $in['end_time']   ?: '03:00:00';
        $color       = $in['color'] ?? 'amber';
        $notes       = $in['notes'] ?? '';
        $active      = isset($in['active']) ? (int)!!$in['active'] : 1;
        $now         = date('Y-m-d H:i:s');

        if (!empty($in['id'])) {
            db()->prepare('UPDATE events SET title=?, description=?, category=?, weekday=?, event_date=?, start_time=?, end_time=?, is_recurring=?, color=?, notes=?, active=?, updated_at=? WHERE id=? AND tenant_id=?')
                ->execute([$title, $desc, $category, $weekday, $eventDate, $start, $end, $isRecurring, $color, $notes, $active, $now, $in['id'], $t]);
        } else {
            db()->prepare('INSERT INTO events (tenant_id, title, description, category, weekday, event_date, start_time, end_time, is_recurring, color, notes, active, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$t, $title, $desc, $category, $weekday, $eventDate, $start, $end, $isRecurring, $color, $notes, $active, $now, $now]);
        }
        json_response(['ok' => true]);
    }

    case 'toggle': {
        db()->prepare('UPDATE events SET active = 1 - active WHERE id=? AND tenant_id=?')->execute([$in['id'], $t]);
        json_response(['ok' => true]);
    }

    case 'delete': {
        db()->prepare('DELETE FROM events WHERE id=? AND tenant_id=?')->execute([$in['id'], $t]);
        json_response(['ok' => true]);
    }

    default: json_response(['error' => 'unknown'], 400);
}
