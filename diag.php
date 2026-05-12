<?php
// Script di diagnostica: mostra gli ultimi ordini e dove sono finiti gli item.
// Read-only, sicuro. Cancellare dopo il debug se vuoi.
require_once __DIR__ . '/includes/config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNOSTICA ORDINI ===\n\n";

$tid = (int)(db()->query('SELECT id FROM tenants ORDER BY id LIMIT 1')->fetchColumn() ?: 1);
echo "Tenant attivo: $tid\n\n";

// Ultimi 5 ordini
$o = db()->prepare("SELECT id, code, table_id, status, type, created_at FROM orders WHERE tenant_id=? ORDER BY id DESC LIMIT 5");
$o->execute([$tid]);
$orders = $o->fetchAll();
echo "ULTIMI 5 ORDINI:\n";
foreach ($orders as $ord) {
    echo "  #{$ord['id']} | code={$ord['code']} | tavolo={$ord['table_id']} | status={$ord['status']} | type={$ord['type']} | creato={$ord['created_at']}\n";
}
echo "\n";

if (!$orders) {
    echo "Nessun ordine trovato.\n";
    exit;
}

// Items dell'ordine più recente
$lastOid = $orders[0]['id'];
$it = db()->prepare("SELECT id, name, qty, destination, status, sent_at, ready_at FROM order_items WHERE order_id=? ORDER BY id");
$it->execute([$lastOid]);
echo "ITEMS DELL'ORDINE #$lastOid:\n";
foreach ($it->fetchAll() as $i) {
    $emoji = $i['destination'] === 'kitchen' ? '👨‍🍳' : ($i['destination'] === 'bar' ? '🍸' : '❓');
    echo "  $emoji {$i['name']} | qty={$i['qty']} | dest={$i['destination']} | status={$i['status']} | sent_at={$i['sent_at']}\n";
}
echo "\n";

// Conteggio items per destinazione e stato (ultimi 60 min)
echo "ITEMS NEGLI ULTIMI 60 MINUTI (per dest/stato):\n";
$range = DB_DRIVER === 'mysql' ? "DATE_SUB(NOW(), INTERVAL 60 MINUTE)" : "datetime('now','-60 minutes')";
$c = db()->prepare("SELECT oi.destination, oi.status, COUNT(*) c FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.tenant_id=? AND oi.sent_at >= $range GROUP BY oi.destination, oi.status");
$c->execute([$tid]);
foreach ($c->fetchAll() as $r) {
    echo "  destinazione={$r['destination']} | stato={$r['status']} | quantità={$r['c']}\n";
}
echo "\n";

// Esattamente cosa torna kitchen_queue per la cucina e per il bar
foreach (['kitchen','bar'] as $dest) {
    echo "QUERY kitchen_queue per dest=$dest:\n";
    $q = db()->prepare('SELECT oi.id, oi.name, oi.destination, oi.status, oi.sent_at FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.tenant_id=? AND oi.destination=? AND (oi.status IN ("sent","preparing") OR (oi.status="ready" AND oi.ready_at >= ' . $range . '))');
    $q->execute([$tid, $dest]);
    $rows = $q->fetchAll();
    if (!$rows) {
        echo "  (vuoto)\n";
    } else {
        foreach ($rows as $r) {
            echo "  #{$r['id']} {$r['name']} | status={$r['status']} | sent={$r['sent_at']}\n";
        }
    }
    echo "\n";
}

echo "=== FINE DIAGNOSTICA ===\n";
