<?php
// Diagnostica produzione - cancellare dopo l'uso
if (($_GET['t'] ?? '') !== 'lp-2026-xK9pQ7zM') { http_response_code(403); exit('403'); }
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

echo "=== FILE CHECK ===\n";
$cfgFile = __DIR__ . '/includes/config.local.php';
echo "Path: $cfgFile\n";
echo "Exists: " . (file_exists($cfgFile) ? 'YES' : 'NO') . "\n";
if (file_exists($cfgFile)) {
    echo "Size: " . filesize($cfgFile) . " bytes\n";
    echo "Readable: " . (is_readable($cfgFile) ? 'YES' : 'NO') . "\n";
    echo "First 200 chars:\n" . substr(file_get_contents($cfgFile), 0, 200) . "\n";
    $r = @include $cfgFile;
    echo "Loaded: " . (is_array($r) ? 'OK ' . print_r($r, true) : 'FAIL (not array)') . "\n";
}
echo "\n";
require_once __DIR__ . '/includes/config.php';
echo "=== CONFIG ===\nDriver: " . DB_DRIVER . "\nDB: " . DB_NAME . "\nUser: " . DB_USER . "\n\n";

try {
    $pdo = db();
    echo "✓ DB connesso\n\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "=== TABELLE (" . count($tables) . ") ===\n" . implode("\n", $tables) . "\n\n";

    foreach (['tenants', 'users', 'tables', 'products', 'categories', 'orders'] as $t) {
        try {
            $c = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
            echo "$t: $c righe\n";
        } catch (Exception $e) { echo "$t: ERR " . $e->getMessage() . "\n"; }
    }
    echo "\n=== USERS ===\n";
    $u = $pdo->query("SELECT id,email,role,pin,active FROM users")->fetchAll(PDO::FETCH_ASSOC);
    print_r($u);

    echo "\n=== TEST LOGIN QUERY ===\n";
    $st = $pdo->prepare('SELECT u.*, t.name AS tenant_name FROM users u LEFT JOIN tenants t ON t.id=u.tenant_id WHERE u.email=? AND u.active=1');
    $st->execute(['admin@lollab.it']);
    $row = $st->fetch();
    echo $row ? "✓ Trovato user id=" . $row['id'] . " role=" . $row['role'] : "✗ Non trovato";
} catch (Throwable $e) {
    echo "\n✗ ERRORE: " . $e->getMessage() . "\nFile: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString();
}
