<?php
/**
 * One-shot endpoint: applica menu finale (22 prodotti) al DB MySQL Hostinger.
 * Le immagini sono già state pushate via git in /assets/uploads/products/.
 * Uso: /sync-menu.php?token=lp-2026-xK9pQ7zM
 * Cancellare dopo l'uso.
 */
if (($_GET['token'] ?? '') !== 'lp-2026-xK9pQ7zM') { http_response_code(403); exit('403'); }
require_once __DIR__ . '/includes/config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== SYNC MENU MySQL ===\n";
echo "Driver: " . DB_DRIVER . "\n\n";

$pdo = db();

// Mappa categorie
$cats = [];
foreach ($pdo->query("SELECT id, name FROM categories WHERE tenant_id=1") as $r) {
    $cats[$r['name']] = (int)$r['id'];
}
echo "Categorie trovate: " . count($cats) . "\n\n";

// Lo stesso menu locale
$menu = [
    ['Antipasti', 'Tagliere Misto Italiano', 'Salumi misti DOP, formaggi locali, mostarda, grissini', 14.00, 5.50, 10, 'tagliere-misto-italiano.jpg'],
    ['Antipasti', 'Bruschette al Pomodoro', 'Pane casereccio tostato, pomodoro fresco, basilico (4 pezzi)', 6.50, 1.20, 10, 'bruschette-al-pomodoro.jpg'],
    ['Primi Piatti', 'Spaghetti Carbonara', 'Guanciale croccante, pecorino romano, uovo, pepe nero', 11.00, 2.80, 10, 'spaghetti-carbonara.jpg'],
    ['Primi Piatti', 'Risotto ai Funghi Porcini', 'Riso Carnaroli mantecato, porcini freschi, prezzemolo', 13.50, 4.00, 10, 'risotto-ai-funghi-porcini.jpg'],
    ['Secondi', 'Tagliata di Manzo', '300g manzo Angus, rucola, grana e aceto balsamico', 22.00, 9.00, 10, 'tagliata-di-manzo.jpg'],
    ['Secondi', 'Branzino al Sale', 'Pesce intero al forno in crosta di sale', 24.00, 11.00, 10, 'branzino-al-sale.jpg'],
    ['Pizza', 'Margherita', 'Pomodoro San Marzano, mozzarella fior di latte, basilico', 8.00, 1.80, 10, 'margherita.jpg'],
    ['Pizza', 'Diavola', 'Pomodoro, mozzarella, salame piccante calabrese', 10.00, 2.50, 10, 'diavola.jpg'],
    ['Panini', 'Hamburger Lollapalooza', '200g manzo, cheddar, bacon croccante, salsa speciale', 13.50, 4.00, 10, 'hamburger-lollapalooza.jpg'],
    ['Panini', 'Club Sandwich', 'Pollo grigliato, bacon, uovo, lattuga, pomodoro', 10.00, 3.00, 10, 'club-sandwich.jpg'],
    ['Dolci', 'Tiramisù della Casa', 'Mascarpone, savoiardi, caffè espresso, cacao', 6.00, 1.20, 10, 'tiramis-della-casa.jpg'],
    ['Dolci', 'Cheesecake ai Frutti di Bosco', 'New York style, frutti rossi, base biscotto', 6.50, 1.50, 10, 'cheesecake-ai-frutti-di-bosco.jpg'],
    ['Birre', 'Moretti alla Spina 0.4L', 'Bionda italiana spillata', 5.50, 1.80, 22, 'moretti-alla-spina-0-4l.jpg'],
    ['Birre', 'IPA Artigianale', 'Birra artigianale italiana luppolata', 7.00, 2.50, 22, 'ipa-artigianale.jpg'],
    ['Cocktail', 'Spritz Aperol', 'Aperol, prosecco, soda, fetta arancia', 7.00, 1.80, 22, 'spritz-aperol.jpg'],
    ['Cocktail', 'Negroni', 'Gin, vermouth rosso, Campari, scorza arancia', 9.00, 2.20, 22, 'negroni.jpg'],
    ['Vini', 'Calice Rosso Chianti', 'Chianti Classico DOCG', 6.00, 1.50, 22, 'calice-rosso-chianti.jpg'],
    ['Vini', 'Calice Bianco Falanghina', 'Falanghina del Sannio DOC', 6.00, 1.50, 22, 'calice-bianco-falanghina.jpg'],
    ['Analcolici', 'Coca Cola', 'Lattina 33cl', 3.50, 0.60, 22, 'coca-cola.jpg'],
    ['Analcolici', "Spremuta d'Arancia Fresca", 'Spremuta del momento', 5.00, 1.20, 10, 'spremuta-d-arancia-fresca.jpg'],
    ['Caffetteria', 'Espresso', 'Caffè italiano in tazzina', 1.50, 0.20, 22, 'espresso.jpg'],
    ['Caffetteria', 'Cappuccino', 'Espresso e latte montato con latte art', 2.00, 0.40, 22, 'cappuccino.jpg'],
];

$pdo->exec("UPDATE products SET available=0 WHERE tenant_id=1");
echo "🧹 Disabilitati prodotti esistenti\n\n";

$ok = 0;
foreach ($menu as $r) {
    [$cat, $name, $desc, $price, $cost, $vat, $imgFile] = $r;
    $catId = $cats[$cat] ?? null;
    if (!$catId) { echo "⚠ Cat '$cat' mancante per $name\n"; continue; }
    $imgUrl = "/assets/uploads/products/$imgFile";
    $imgFs  = __DIR__ . $imgUrl;
    $hasImg = file_exists($imgFs);

    $st = $pdo->prepare("SELECT id FROM products WHERE tenant_id=1 AND name=?");
    $st->execute([$name]);
    $eid = $st->fetchColumn();
    if ($eid) {
        $pdo->prepare("UPDATE products SET category_id=?, description=?, price=?, cost=?, vat=?, image=?, available=1 WHERE id=?")
            ->execute([$catId, $desc, $price, $cost, $vat, $imgUrl, $eid]);
        echo "✓ UPD $name " . ($hasImg ? "📷" : "🚫") . "\n";
    } else {
        $pdo->prepare("INSERT INTO products (tenant_id, category_id, name, description, price, cost, vat, image, available, track_stock, stock, stock_min, created_at) VALUES (1,?,?,?,?,?,?,?,1,0,0,0,?)")
            ->execute([$catId, $name, $desc, $price, $cost, $vat, $imgUrl, date('Y-m-d H:i:s')]);
        echo "+ INS $name " . ($hasImg ? "📷" : "🚫") . "\n";
    }
    $ok++;
}

echo "\n✅ Menu sincronizzato: $ok/" . count($menu) . " prodotti\n";
echo "⚠ Cancella sync-menu.php dopo l'uso\n";
