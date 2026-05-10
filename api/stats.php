<?php
require __DIR__ . '/_bootstrap.php';
$t = tenant_id();
$in = input();
$today = date('Y-m-d');

switch ($action) {
    case 'dashboard':
        $today = $in['date'] ?? date('Y-m-d');
        // Incassi oggi
        $rev = db()->prepare('SELECT COALESCE(SUM(p.amount),0) tot FROM payments p JOIN orders o ON o.id=p.order_id WHERE p.tenant_id=? AND date(p.created_at)=?');
        $rev->execute([$t, $today]);
        $revToday = (float)$rev->fetch()['tot'];

        // Ordini oggi
        $ord = db()->prepare('SELECT COUNT(*) c FROM orders WHERE tenant_id=? AND date(created_at)=?');
        $ord->execute([$t, $today]);
        $ordersToday = (int)$ord->fetch()['c'];

        // Ordini attivi
        $act = db()->prepare('SELECT COUNT(*) c FROM orders WHERE tenant_id=? AND status NOT IN ("closed","cancelled")');
        $act->execute([$t]);
        $activeOrders = (int)$act->fetch()['c'];

        // Tavoli
        $tot = db()->prepare('SELECT COUNT(*) c FROM tables WHERE tenant_id=?');
        $tot->execute([$t]);
        $totalTables = (int)$tot->fetch()['c'];
        $occ = db()->prepare('SELECT COUNT(*) c FROM tables WHERE tenant_id=? AND status="occupied"');
        $occ->execute([$t]);
        $occupiedTables = (int)$occ->fetch()['c'];

        // Costi oggi
        $exp = db()->prepare('SELECT COALESCE(SUM(amount),0) tot FROM expenses WHERE tenant_id=? AND date=?');
        $exp->execute([$t, $today]);
        $expensesToday = (float)$exp->fetch()['tot'];

        // Costo merce oggi (cost x qty)
        $cogs = db()->prepare('SELECT COALESCE(SUM(oi.qty*oi.cost),0) tot FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.tenant_id=? AND date(o.closed_at)=? AND o.status="closed"');
        $cogs->execute([$t, $today]);
        $cogsToday = (float)$cogs->fetch()['tot'];

        // Top prodotti oggi
        $top = db()->prepare('SELECT oi.name, SUM(oi.qty) qty, SUM(oi.qty*oi.price) revenue FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.tenant_id=? AND date(o.created_at)=? AND oi.status!="cancelled" GROUP BY oi.name ORDER BY qty DESC LIMIT 8');
        $top->execute([$t, $today]);

        // Revenue ultimi 7 giorni
        $hist = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $r = db()->prepare('SELECT COALESCE(SUM(p.amount),0) tot FROM payments p WHERE p.tenant_id=? AND date(p.created_at)=?');
            $r->execute([$t, $d]);
            $hist[] = ['date' => $d, 'label' => date('d/m', strtotime($d)), 'revenue' => (float)$r->fetch()['tot']];
        }

        // Recent orders
        $recent = db()->prepare('SELECT o.*, t.code AS table_code FROM orders o LEFT JOIN tables t ON t.id=o.table_id WHERE o.tenant_id=? ORDER BY o.id DESC LIMIT 10');
        $recent->execute([$t]);

        json_response([
            'revenue_today' => $revToday,
            'orders_today' => $ordersToday,
            'active_orders' => $activeOrders,
            'tables' => ['total' => $totalTables, 'occupied' => $occupiedTables],
            'expenses_today' => $expensesToday,
            'cogs_today' => $cogsToday,
            'profit_today' => $revToday - $cogsToday - $expensesToday,
            'top_products' => $top->fetchAll(),
            'history_7d' => $hist,
            'recent_orders' => $recent->fetchAll(),
        ]);

    case 'finance':
        $from = $in['from'] ?? date('Y-m-01');
        $to = $in['to'] ?? date('Y-m-d');
        // Entrate per giorno
        $rev = db()->prepare('SELECT date(p.created_at) d, SUM(p.amount) tot FROM payments p WHERE p.tenant_id=? AND date(p.created_at) BETWEEN ? AND ? GROUP BY date(p.created_at)');
        $rev->execute([$t, $from, $to]);
        // Uscite per giorno
        $exp = db()->prepare('SELECT date d, SUM(amount) tot FROM expenses WHERE tenant_id=? AND date BETWEEN ? AND ? GROUP BY date');
        $exp->execute([$t, $from, $to]);
        // Per categoria spese
        $expCat = db()->prepare('SELECT category, SUM(amount) tot FROM expenses WHERE tenant_id=? AND date BETWEEN ? AND ? GROUP BY category ORDER BY tot DESC');
        $expCat->execute([$t, $from, $to]);
        // Pagamenti per metodo
        $byMethod = db()->prepare('SELECT method, SUM(amount) tot FROM payments WHERE tenant_id=? AND date(created_at) BETWEEN ? AND ? GROUP BY method');
        $byMethod->execute([$t, $from, $to]);
        // Totals
        $tRev = db()->prepare('SELECT SUM(amount) tot FROM payments WHERE tenant_id=? AND date(created_at) BETWEEN ? AND ?');
        $tRev->execute([$t, $from, $to]);
        $tExp = db()->prepare('SELECT SUM(amount) tot FROM expenses WHERE tenant_id=? AND date BETWEEN ? AND ?');
        $tExp->execute([$t, $from, $to]);
        $tCogs = db()->prepare('SELECT SUM(oi.qty*oi.cost) tot FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.tenant_id=? AND o.status="closed" AND date(o.closed_at) BETWEEN ? AND ?');
        $tCogs->execute([$t, $from, $to]);
        $totRev = (float)$tRev->fetch()['tot'];
        $totExp = (float)$tExp->fetch()['tot'];
        $totCogs = (float)$tCogs->fetch()['tot'];
        json_response([
            'revenue' => $rev->fetchAll(),
            'expenses' => $exp->fetchAll(),
            'expenses_by_cat' => $expCat->fetchAll(),
            'payments_by_method' => $byMethod->fetchAll(),
            'totals' => [
                'revenue' => $totRev,
                'cogs' => $totCogs,
                'expenses' => $totExp,
                'profit' => $totRev - $totCogs - $totExp,
            ]
        ]);

    case 'reports':
        $from = $in['from'] ?? date('Y-m-01');
        $to = $in['to'] ?? date('Y-m-d');
        // Top prodotti
        $top = db()->prepare('SELECT oi.name, SUM(oi.qty) qty, SUM(oi.qty*oi.price) revenue, SUM(oi.qty*oi.cost) cost, SUM(oi.qty*(oi.price-oi.cost)) margin FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.tenant_id=? AND o.status="closed" AND date(o.closed_at) BETWEEN ? AND ? AND oi.status!="cancelled" GROUP BY oi.name ORDER BY revenue DESC LIMIT 30');
        $top->execute([$t, $from, $to]);
        // Performance camerieri
        $waiters = db()->prepare('SELECT u.name, COUNT(o.id) orders_count, SUM(o.total) revenue FROM users u LEFT JOIN orders o ON o.waiter_id=u.id AND o.status="closed" AND date(o.closed_at) BETWEEN ? AND ? WHERE u.role="cameriere" AND u.tenant_id=? GROUP BY u.id ORDER BY revenue DESC');
        $waiters->execute([$from, $to, $t]);
        // Vendite per ora
        $byHour = db()->prepare('SELECT strftime("%H", o.closed_at) h, SUM(o.total) tot, COUNT(*) c FROM orders o WHERE o.tenant_id=? AND o.status="closed" AND date(o.closed_at) BETWEEN ? AND ? GROUP BY h ORDER BY h');
        $byHour->execute([$t, $from, $to]);
        // Tavoli redditizi
        $tables = db()->prepare('SELECT tb.code, COUNT(o.id) orders_count, SUM(o.total) revenue FROM tables tb LEFT JOIN orders o ON o.table_id=tb.id AND o.status="closed" AND date(o.closed_at) BETWEEN ? AND ? WHERE tb.tenant_id=? GROUP BY tb.id ORDER BY revenue DESC LIMIT 20');
        $tables->execute([$from, $to, $t]);
        json_response([
            'top_products' => $top->fetchAll(),
            'waiters' => $waiters->fetchAll(),
            'by_hour' => $byHour->fetchAll(),
            'tables' => $tables->fetchAll(),
        ]);

    default: json_response(['error' => 'unknown'], 400);
}
