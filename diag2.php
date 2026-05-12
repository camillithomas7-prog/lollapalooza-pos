<?php
// Diag 2: simula ESATTAMENTE quello che fa kitchen_queue dell'API
// e mostra il JSON che ritorna.
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/i18n.php';
header('Content-Type: text/plain; charset=utf-8');

$tid = 1;
$dest = $_GET['dest'] ?? 'kitchen';
$lang = $_GET['lang'] ?? 'ar';

echo "=== SIMULAZIONE kitchen_queue ===\n";
echo "tenant=$tid · dest=$dest · lang=$lang\n\n";

try {
    $sqlTime = DB_DRIVER === 'mysql' ? "DATE_SUB(NOW(), INTERVAL 30 SECOND)" : "datetime('now','-30 seconds')";
    $sql = 'SELECT oi.*, p.translations AS p_translations
        FROM order_items oi
        JOIN orders o ON o.id=oi.order_id
        LEFT JOIN products p ON p.id=oi.product_id
        WHERE o.tenant_id=? AND oi.destination=? AND (
            oi.status IN ("sent","preparing")
            OR (oi.status = "ready" AND oi.ready_at >= ' . $sqlTime . ')
        )
        ORDER BY oi.sent_at ASC';
    echo "SQL:\n$sql\n\n";
    $st = db()->prepare($sql);
    $st->execute([$tid, $dest]);
    $items = $st->fetchAll();
    echo "ITEM TROVATI: " . count($items) . "\n\n";
    foreach ($items as $i) {
        echo "  #{$i['id']} name='{$i['name']}' order_id={$i['order_id']} dest={$i['destination']} status={$i['status']}\n";
    }
    echo "\n";

    // Order ids
    $orderIds = array_values(array_unique(array_column($items, 'order_id')));
    echo "ORDER IDs unici: " . implode(',', $orderIds) . "\n\n";

    // Recupera info ordini
    $ordersById = [];
    if ($orderIds) {
        $place = implode(',', array_fill(0, count($orderIds), '?'));
        $os = db()->prepare("SELECT o.id, o.code AS order_code, o.guests, o.notes AS order_notes, t.code AS table_code, u.name AS waiter_name FROM orders o LEFT JOIN tables t ON t.id=o.table_id LEFT JOIN users u ON u.id=o.waiter_id WHERE o.id IN ($place)");
        $os->execute($orderIds);
        foreach ($os->fetchAll() as $o) $ordersById[$o['id']] = $o;
    }
    echo "ORDERS BY ID: " . count($ordersById) . " ordini\n";
    foreach ($ordersById as $o) {
        echo "  #{$o['id']} code={$o['order_code']} tavolo={$o['table_code']} cameriere=" . ($o['waiter_name'] ?: 'null') . "\n";
    }
    echo "\n";

    // Raggruppa
    $orders = [];
    foreach ($items as $it) {
        $oid = $it['order_id'];
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
                'sent_at'     => $it['sent_at'],
                'done_count'  => 0,
                'total_count' => 0,
            ];
        }
        $orders[$oid]['items'][] = $it;
        $orders[$oid]['total_count']++;
        if (in_array($it['status'], ['ready','served'], true)) $orders[$oid]['done_count']++;
        if ($it['sent_at'] && (!$orders[$oid]['sent_at'] || $it['sent_at'] < $orders[$oid]['sent_at'])) {
            $orders[$oid]['sent_at'] = $it['sent_at'];
        }
    }

    $list = array_values($orders);
    usort($list, fn($a, $b) => strcmp($a['sent_at'] ?? '', $b['sent_at'] ?? ''));

    echo "JSON FINALE (quello che torna all'API):\n";
    echo json_encode(['orders' => $list], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    echo "\n!!! ERRORE: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
