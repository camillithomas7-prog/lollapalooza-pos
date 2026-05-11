<?php
/**
 * Lollapalooza POS — Genera 22 prodotti (2×11 categorie) + immagini gpt-image-2.
 * Uso CLI: php scripts/generate_menu.php
 */
require_once __DIR__ . '/../includes/config.php';

$apiKey = getenv('OPENAI_API_KEY') ?: '';
if (!$apiKey) { fwrite(STDERR, "❌ OPENAI_API_KEY non impostata\n"); exit(1); }

$imgDir = BASE_PATH . '/assets/uploads/products';
if (!is_dir($imgDir)) mkdir($imgDir, 0775, true);

$cats = [];
foreach (db()->query("SELECT id, name FROM categories WHERE tenant_id=1") as $r) {
    $cats[$r['name']] = (int)$r['id'];
}
if (!$cats) { fwrite(STDERR, "❌ Nessuna categoria nel DB locale\n"); exit(1); }

// 22 prodotti (2 per categoria, i più iconici)
$menu = [
    ['Antipasti', 'Tagliere Misto Italiano', 'Salumi misti DOP, formaggi locali, mostarda, grissini', 14.00, 5.50, 10,
        'overhead photo of an Italian wooden charcuterie board with prosciutto, salami, parmesan, mozzarella, olives, grissini breadsticks, fig jam, restaurant food photography, natural soft light, dark wood background'],
    ['Antipasti', 'Bruschette al Pomodoro', 'Pane casereccio tostato, pomodoro fresco, basilico (4 pezzi)', 6.50, 1.20, 10,
        'overhead photo of four Italian bruschetta with fresh diced tomato, basil leaves, olive oil drizzle on toasted rustic bread, white ceramic plate, restaurant food photography, soft natural light'],

    ['Primi Piatti', 'Spaghetti Carbonara', 'Guanciale croccante, pecorino romano, uovo, pepe nero', 11.00, 2.80, 10,
        'overhead photo of Italian spaghetti carbonara with crispy guanciale, pecorino cheese, egg yolk, black pepper, white ceramic plate, restaurant food photography, natural light'],
    ['Primi Piatti', 'Risotto ai Funghi Porcini', 'Riso Carnaroli mantecato, porcini freschi, prezzemolo', 13.50, 4.00, 10,
        'creamy Italian mushroom risotto with porcini, parsley, parmesan flakes, white plate, restaurant food photography, overhead, soft natural light'],

    ['Secondi', 'Tagliata di Manzo', '300g manzo Angus, rucola, grana e aceto balsamico', 22.00, 9.00, 10,
        'Italian beef tagliata, sliced grilled steak medium rare on bed of arugula, parmesan shavings, balsamic glaze, white plate, restaurant food photography, soft light'],
    ['Secondi', 'Branzino al Sale', 'Pesce intero al forno in crosta di sale', 24.00, 11.00, 10,
        'whole sea bass baked in salt crust, partially broken to show flesh, with lemon, herbs, on white serving plate, restaurant food photography, elegant'],

    ['Pizza', 'Margherita', 'Pomodoro San Marzano, mozzarella fior di latte, basilico', 8.00, 1.80, 10,
        'classic Italian Margherita pizza wood-fired with fresh mozzarella, tomato sauce, basil leaves, on wooden board, top down view, restaurant food photography, authentic napoletana'],
    ['Pizza', 'Diavola', 'Pomodoro, mozzarella, salame piccante calabrese', 10.00, 2.50, 10,
        'Italian Diavola pizza with spicy salami pepperoni, mozzarella, tomato sauce, wood-fired, on wooden board, top down view, restaurant food photography'],

    ['Panini', 'Hamburger Lollapalooza', '200g manzo, cheddar, bacon croccante, salsa speciale', 13.50, 4.00, 10,
        'gourmet burger with juicy beef patty, melted cheddar, crispy bacon, lettuce, tomato, brioche bun, side of fries, restaurant food photography, dramatic lighting'],
    ['Panini', 'Club Sandwich', 'Pollo grigliato, bacon, uovo, lattuga, pomodoro', 10.00, 3.00, 10,
        'classic triple-decker club sandwich with grilled chicken, bacon, egg, lettuce, tomato, mayo, with French fries, restaurant food photography, overhead'],

    ['Dolci', 'Tiramisù della Casa', 'Mascarpone, savoiardi, caffè espresso, cacao', 6.00, 1.20, 10,
        'classic Italian tiramisu in elegant glass cup, dusted with cocoa powder, espresso bean garnish, restaurant dessert photography, dark moody background'],
    ['Dolci', 'Cheesecake ai Frutti di Bosco', 'New York style, frutti rossi, base biscotto', 6.50, 1.50, 10,
        'New York cheesecake slice with mixed berries on top blueberries raspberries strawberries, white plate, restaurant dessert photography'],

    ['Birre', 'Moretti alla Spina 0.4L', 'Bionda italiana spillata', 5.50, 1.80, 22,
        'tall pint glass of Italian draft lager beer, foamy head, golden color, condensation drops, restaurant photography, dark wood bar background'],
    ['Birre', 'IPA Artigianale', 'Birra artigianale italiana luppolata', 7.00, 2.50, 22,
        'craft IPA beer in tulip glass, hazy amber golden color, thick foamy head, hops garnish, restaurant photography, dark moody background'],

    ['Cocktail', 'Spritz Aperol', 'Aperol, prosecco, soda, fetta arancia', 7.00, 1.80, 22,
        'Italian Aperol Spritz cocktail in large wine glass with orange slice, ice cubes, vibrant orange color, restaurant photography, warm sunset light, terrace setting'],
    ['Cocktail', 'Negroni', 'Gin, vermouth rosso, Campari, scorza arancia', 9.00, 2.20, 22,
        'classic Italian Negroni cocktail in lowball rocks glass with large ice cube and orange peel garnish, deep red color, restaurant photography, dark moody bar'],

    ['Vini', 'Calice Rosso Chianti', 'Chianti Classico DOCG', 6.00, 1.50, 22,
        'glass of red wine Italian Chianti, on dark wood table with wine bottle behind, restaurant photography, warm candlelight'],
    ['Vini', 'Calice Bianco Falanghina', 'Falanghina del Sannio DOC', 6.00, 1.50, 22,
        'glass of white wine Italian Falanghina, on light table, restaurant photography, fresh bright lighting'],

    ['Analcolici', 'Coca Cola', 'Lattina 33cl', 3.50, 0.60, 22,
        'glass of cola with ice cubes and lime slice, condensation, restaurant photography, bright lighting, dark table'],
    ['Analcolici', 'Spremuta d\'Arancia Fresca', 'Spremuta del momento', 5.00, 1.20, 10,
        'glass of fresh orange juice with orange slice on rim, fresh oranges around, restaurant photography, bright morning light'],

    ['Caffetteria', 'Espresso', 'Caffè italiano in tazzina', 1.50, 0.20, 22,
        'Italian espresso shot in small white porcelain cup with saucer, golden crema on top, restaurant photography, dark wood table, top down'],
    ['Caffetteria', 'Cappuccino', 'Espresso e latte montato con latte art', 2.00, 0.40, 22,
        'Italian cappuccino with beautiful latte art rosetta heart, white ceramic cup, restaurant photography, top down view, soft natural light'],
];

echo "🍽 Lollapalooza menu generation — " . count($menu) . " prodotti\n\n";

db()->exec("UPDATE products SET available=0 WHERE tenant_id=1");
echo "🧹 Prodotti esistenti disabilitati\n\n";

function genImage(string $apiKey, string $prompt, int $maxRetry = 3): ?string {
    for ($try = 1; $try <= $maxRetry; $try++) {
        $payload = json_encode([
            'model' => 'gpt-image-2', 'prompt' => $prompt,
            'size' => '1024x1024', 'quality' => 'medium', 'n' => 1,
        ]);
        $ch = curl_init('https://api.openai.com/v1/images/generations');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
            CURLOPT_TIMEOUT => 180, CURLOPT_CONNECTTIMEOUT => 20,
        ]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        if ($err) { echo " (retry $try: $err)"; sleep(2); continue; }
        $j = json_decode($resp, true);
        if (isset($j['data'][0]['b64_json'])) return base64_decode($j['data'][0]['b64_json']);
        if (isset($j['error']['message'])) { echo " (api err: " . $j['error']['message'] . ")"; return null; }
        sleep(2);
    }
    return null;
}

$results = [];
foreach ($menu as $idx => $row) {
    [$cat, $name, $desc, $price, $cost, $vat, $prompt] = $row;
    $catId = $cats[$cat] ?? null;
    if (!$catId) { echo "⚠ Cat '$cat' mancante\n"; continue; }

    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
    $slug = trim($slug, '-');
    $imgFile = "$slug.png";
    $imgPath = "$imgDir/$imgFile";
    $imgUrl = "/assets/uploads/products/$imgFile";

    if (!file_exists($imgPath) || filesize($imgPath) < 1000) {
        echo "[" . ($idx + 1) . "/" . count($menu) . "] 🎨 $name … ";
        $bin = genImage($apiKey, $prompt);
        if (!$bin) { echo " ❌\n"; continue; }
        file_put_contents($imgPath, $bin);
        echo "✓ " . round(strlen($bin) / 1024) . " KB\n";
    } else {
        echo "[" . ($idx + 1) . "/" . count($menu) . "] ⏭ $name (già presente)\n";
    }

    $st = db()->prepare("SELECT id FROM products WHERE tenant_id=1 AND name=?");
    $st->execute([$name]);
    $eid = $st->fetchColumn();
    if ($eid) {
        db()->prepare("UPDATE products SET category_id=?, description=?, price=?, cost=?, vat=?, image=?, available=1 WHERE id=?")
            ->execute([$catId, $desc, $price, $cost, $vat, $imgUrl, $eid]);
    } else {
        db()->prepare("INSERT INTO products (tenant_id, category_id, name, description, price, cost, vat, image, available, track_stock, stock, stock_min, created_at) VALUES (1,?,?,?,?,?,?,?,1,0,0,0,?)")
            ->execute([$catId, $name, $desc, $price, $cost, $vat, $imgUrl, date('Y-m-d H:i:s')]);
    }
    $results[] = $name;
}

echo "\n✅ Completato: " . count($results) . "/" . count($menu) . " prodotti.\n";
echo "Immagini in $imgDir\n";
