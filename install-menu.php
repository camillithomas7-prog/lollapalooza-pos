<?php
// Applica il menù completo Lollapalooza: cancella categorie+prodotti demo
// e inserisce il menù definitivo con prezzi, descrizioni, traduzioni e immagini.
// IDEMPOTENTE: si può rilanciare, ricostruisce sempre da zero il menù.
// Apri https://tuo-sito/install-menu.php per eseguirlo.

require_once __DIR__ . '/includes/config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== Lollapalooza POS — Applicazione Menù ===\n\n";

$pdo  = db();
$menu = require __DIR__ . '/includes/seed_menu_lollab.php';
$tid  = 1;

// I prodotti vecchi referenziati da order_items NON si possono cancellare
// (foreign key). Strategia: i prodotti demo che NON sono piu' nel menù
// vengono solo nascosti (available=0). Le categorie demo vengono rimosse
// se non hanno prodotti collegati ad ordini.

try {
    $pdo->beginTransaction();

    // 1. Nascondi tutti i prodotti esistenti (li ripristineremo o resteranno nascosti)
    $pdo->prepare('UPDATE products SET available = 0 WHERE tenant_id = ?')->execute([$tid]);

    // 2. Cancella le categorie demo (i prodotti restano, category_id va a NULL)
    $pdo->prepare('DELETE FROM categories WHERE tenant_id = ?')->execute([$tid]);

    $catCount = 0; $prodCount = 0; $prodUpdated = 0;

    foreach ($menu as $catIndex => $cat) {
        // Traduzioni categoria
        $catTr = [];
        foreach (($cat['tr'] ?? []) as $lang => $nm) {
            $catTr[$lang] = ['name' => $nm];
        }
        $pdo->prepare('INSERT INTO categories (tenant_id, name, icon, color, destination, sort, active, translations) VALUES (?,?,?,?,?,?,1,?)')
            ->execute([$tid, $cat['name'], $cat['icon'], $cat['color'], $cat['destination'], $catIndex,
                       json_encode($catTr, JSON_UNESCAPED_UNICODE)]);
        $catId = (int)$pdo->lastInsertId();
        $catCount++;

        foreach ($cat['products'] as $prodIndex => $p) {
            $img = '/assets/uploads/products/' . $p['slug'] . '.jpg';
            // Se il file immagine non esiste sul server, lascia vuoto (placeholder)
            if (!file_exists(__DIR__ . $img)) $img = '';

            $trJson = json_encode($p['tr'] ?? [], JSON_UNESCAPED_UNICODE);

            // Esiste già un prodotto con lo stesso nome? (per non duplicare se rilancio)
            $existing = $pdo->prepare('SELECT id FROM products WHERE tenant_id=? AND name=? LIMIT 1');
            $existing->execute([$tid, $p['name']]);
            $pid = $existing->fetchColumn();

            if ($pid) {
                $pdo->prepare('UPDATE products SET category_id=?, description=?, price=?, image=?, available=1, sort=?, translations=?, destination=NULL WHERE id=?')
                    ->execute([$catId, $p['desc'] ?? '', $p['price'], $img, $prodIndex, $trJson, $pid]);
                $prodUpdated++;
            } else {
                $pdo->prepare('INSERT INTO products (tenant_id, category_id, name, description, image, price, cost, vat, available, sort, translations, destination, created_at) VALUES (?,?,?,?,?,?,?,?,1,?,?,NULL,?)')
                    ->execute([$tid, $catId, $p['name'], $p['desc'] ?? '', $img, $p['price'], 0, 0, $prodIndex, $trJson, date('Y-m-d H:i:s')]);
                $prodCount++;
            }
        }
    }

    $pdo->commit();

    echo "✓ Categorie create: $catCount\n";
    echo "✓ Prodotti nuovi inseriti: $prodCount\n";
    echo "✓ Prodotti aggiornati: $prodUpdated\n";

    // Conta immagini effettivamente presenti
    $imgDir = __DIR__ . '/assets/uploads/products';
    $imgCount = 0;
    foreach ($menu as $cat) {
        foreach ($cat['products'] as $p) {
            if (file_exists("$imgDir/{$p['slug']}.jpg")) $imgCount++;
        }
    }
    $totalProd = $prodCount + $prodUpdated;
    echo "✓ Immagini prodotto trovate: $imgCount / $totalProd\n";
    if ($imgCount < $totalProd) {
        echo "  (i prodotti senza immagine mostrano l'icona della categoria)\n";
    }

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "\n!!! ERRORE: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Menù applicato. Vai su Admin → Menu per verificare. ===\n";
