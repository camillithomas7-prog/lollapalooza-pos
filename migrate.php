<?php
// Migrazione idempotente — aggiunge campi translations a products e categories
if (($_GET['token'] ?? '') !== 'lp-2026-xK9pQ7zM') { http_response_code(403); exit('403'); }
require_once __DIR__ . '/includes/config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== MIGRATE: aggiungo campo translations ===\n";
echo "Driver: " . DB_DRIVER . "\n\n";

$pdo = db();

function columnExists(PDO $pdo, string $table, string $col): bool {
    if (DB_DRIVER === 'mysql') {
        $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?");
        $st->execute([$table, $col]);
        return $st->fetchColumn() > 0;
    } else {
        $st = $pdo->query("PRAGMA table_info($table)");
        foreach ($st->fetchAll() as $r) if ($r['name'] === $col) return true;
        return false;
    }
}

foreach (['products', 'categories'] as $table) {
    if (columnExists($pdo, $table, 'translations')) {
        echo "⏭  $table.translations già esiste\n";
    } else {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN translations TEXT NULL");
        echo "✓ $table.translations aggiunto\n";
    }
}

echo "\n✅ Migrazione completata\n";
echo "⚠ Cancella migrate.php dopo l'uso\n";
