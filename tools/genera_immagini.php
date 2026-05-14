<?php
// Genera le immagini dei prodotti del menù con gpt-image-2.
// Uso: php tools/genera_immagini.php [slug-opzionale-per-rigenerare-singolo]
// Le immagini vengono salvate in assets/uploads/products/{slug}.jpg

$API_KEY = getenv('OPENAI_API_KEY') ?: trim(shell_exec('source ~/.zshrc 2>/dev/null; echo $OPENAI_API_KEY'));
if (!$API_KEY) { fwrite(STDERR, "OPENAI_API_KEY non trovata\n"); exit(1); }

$OUT_DIR = __DIR__ . '/../assets/uploads/products';
if (!is_dir($OUT_DIR)) mkdir($OUT_DIR, 0775, true);

// Stile comune: food photography professionale, fondo elegante scuro, luce morbida
$STYLE = 'Professional restaurant food photography, 45-degree angle, dark elegant slate surface, soft natural lighting, shallow depth of field, appetizing, high detail, no text, no labels, no watermark.';
$DRINK_STYLE = 'Professional beverage photography, on a dark elegant bar counter, soft moody lighting, condensation droplets, high detail, no text, no brand labels, no watermark.';

// Prompt specifici per slug
$PROMPTS = [
  // --- Antipasti ---
  'selezione-formaggi-italiani' => 'A wooden board with an elegant selection of Italian cheeses (parmesan wedges, gorgonzola, pecorino, soft cheese), with honey, walnuts and grapes. ' . $STYLE,
  'tagliere-affettati-italiani' => 'A wooden board with Italian cured meats: prosciutto, salami, mortadella, coppa, artfully arranged with olives and grissini. ' . $STYLE,
  'tagliere-bruschette-miste' => 'A wooden board with assorted Italian bruschetta: tomato basil, mushroom, olive tapenade, on toasted rustic bread. ' . $STYLE,
  'tagliere-lolapalooza' => 'A large abundant sharing board with mixed bruschetta, grilled vegetables, fresh mozzarella balls and Italian cured meats. ' . $STYLE,
  'prosciutto-crudo-melone' => 'Thin slices of Italian prosciutto crudo draped over fresh orange cantaloupe melon wedges on a white plate. ' . $STYLE,
  // --- Primi ---
  'penne-radicchio-salsiccia' => 'A plate of penne pasta with sauteed radicchio and Italian sausage, creamy, garnished with grated cheese. ' . $STYLE,
  'rigatoni-matriciana' => 'A plate of rigatoni all\'amatriciana with rich tomato sauce, crispy guanciale and grated pecorino cheese. ' . $STYLE,
  'ravioli-burro-salvia' => 'A plate of fresh ravioli in butter and sage sauce, topped with shaved grana cheese. ' . $STYLE,
  'tagliatelle-lollapalooza' => 'A plate of tagliatelle pasta with beef ragu, mushrooms and a creamy sauce, garnished with parsley. ' . $STYLE,
  // --- Secondi ---
  'filetto-aceto-balsamico' => 'A grilled beef fillet steak medium-rare, drizzled with balsamic vinegar glaze, on a white plate with rosemary. ' . $STYLE,
  'filetto-alla-griglia' => 'A perfectly grilled beef fillet steak with grill marks, medium-rare, on a dark plate with a sprig of thyme. ' . $STYLE,
  'pollo-con-peperoni' => 'A plate of tender chicken pieces braised with colorful bell peppers in a light tomato sauce. ' . $STYLE,
  // --- Grill ---
  'tomahawk' => 'A massive grilled tomahawk steak with the long bone, perfectly seared, resting on a wooden board with grilled vegetables. ' . $STYLE,
  // --- Contorni ---
  'patate-al-forno' => 'A bowl of golden roasted potatoes with rosemary and herbs. ' . $STYLE,
  'verdure-grigliate' => 'A plate of colorful grilled vegetables: zucchini, eggplant, bell peppers, drizzled with olive oil. ' . $STYLE,
  'patate-fritte' => 'A bowl of crispy golden french fries. ' . $STYLE,
  // --- Dessert ---
  'crostate' => 'A slice of rustic Italian fruit tart (crostata) with apricot jam and a lattice crust, on a small plate. ' . $STYLE,
  'frutta-di-stagione' => 'An elegant plate of fresh sliced seasonal fruit: strawberries, melon, kiwi, orange, grapes. ' . $STYLE,
  // --- Pizze ---
  'pizza-margherita' => 'A whole Neapolitan margherita pizza with tomato sauce, melted mozzarella and fresh basil leaves, on a dark surface, top-down view. ' . $STYLE,
  'pizza-salame-piccante' => 'A whole pizza with tomato sauce, mozzarella and spicy salami slices, top-down view. ' . $STYLE,
  'pizza-mortadella' => 'A whole white pizza topped with rolled mortadella slices and pistachios, top-down view. ' . $STYLE,
  'pizza-prosciutto-crudo' => 'A whole pizza with tomato, mozzarella and prosciutto crudo slices on top with arugula, top-down view. ' . $STYLE,
  'pizza-4-formaggi' => 'A whole four-cheese pizza, golden and bubbly with different melted cheeses, top-down view. ' . $STYLE,
  'pizza-marinara' => 'A whole marinara pizza with tomato sauce, oregano and garlic slices, no cheese, top-down view. ' . $STYLE,
  'pizza-bianca' => 'A whole white pizza (pizza bianca) with olive oil, rosemary and sea salt, golden crust, top-down view. ' . $STYLE,
  // --- Soft Drinks ---
  'lemon-with-mint' => 'A tall glass of fresh lemon mint drink with ice cubes, lemon slices and fresh mint leaves. ' . $DRINK_STYLE,
  'red-bull' => 'A tall glass with a pale yellow energy drink over ice cubes. ' . $DRINK_STYLE,
  'coca-cola' => 'A tall glass of dark cola soda with ice cubes and a lemon slice. ' . $DRINK_STYLE,
  'fanta' => 'A tall glass of bright orange soda with ice cubes. ' . $DRINK_STYLE,
  'sprite' => 'A tall glass of clear lemon-lime soda with ice cubes and a mint leaf. ' . $DRINK_STYLE,
  'tonic-water' => 'A glass of clear sparkling tonic water with ice and a lime wedge. ' . $DRINK_STYLE,
  'soda-water' => 'A glass of clear sparkling soda water with ice cubes. ' . $DRINK_STYLE,
  'water' => 'A glass of still water and a clear water glass on a bar counter. ' . $DRINK_STYLE,
  'tea' => 'A glass cup of hot tea with steam rising, on a saucer. ' . $DRINK_STYLE,
  'caffe' => 'A small white cup of Italian espresso coffee with crema, on a saucer. ' . $DRINK_STYLE,
  // --- Cocktails ---
  'cocktail-alcolici-importati' => 'An elegant premium cocktail in a sophisticated glass, amber colored, with a citrus twist garnish. ' . $DRINK_STYLE,
  'cocktail-alcolici-locali' => 'A colorful cocktail in a highball glass with fruit garnish and a straw. ' . $DRINK_STYLE,
  // --- Cocktail Analcolici ---
  'mojito' => 'A classic mojito cocktail in a highball glass with fresh mint, lime wedges, crushed ice and a straw. ' . $DRINK_STYLE,
  'pina-colada' => 'A creamy pina colada cocktail in a hurricane glass with a pineapple wedge and cherry garnish. ' . $DRINK_STYLE,
  'sunshine' => 'A bright orange and yellow tropical mocktail in a tall glass with orange slice garnish. ' . $DRINK_STYLE,
  'sex-on-the-beach' => 'A layered orange and red mocktail in a tall glass with orange slice and cherry garnish. ' . $DRINK_STYLE,
  // --- Shots ---
  'shots-importati' => 'Three shot glasses filled with premium amber liquor, lined up on a dark bar. ' . $DRINK_STYLE,
  'shots-locali' => 'Three shot glasses filled with clear liquor, lined up on a dark bar. ' . $DRINK_STYLE,
  'limoncello-pocket-coffee' => 'A small glass of bright yellow limoncello next to a chocolate coffee praline, on a dark surface. ' . $DRINK_STYLE,
  'table-12-shots' => 'A wooden serving paddle with twelve colorful shot glasses lined up, party style. ' . $DRINK_STYLE,
  // --- Tappo ---
  'tappo-local-bottle' => 'An elegant unlabeled liquor bottle with shot glasses and ice bucket on a VIP table, nightclub setting. ' . $DRINK_STYLE,
  'tappo-imported-bottle' => 'A premium unlabeled spirits bottle with shot glasses, ice bucket and mixers on a VIP nightclub table. ' . $DRINK_STYLE,
  // --- Vini Bottiglia ---
  'vino-valmont-bottiglia' => 'An elegant bottle of red wine with a plain dark label, on a dark surface with a wine glass. ' . $DRINK_STYLE,
  'vino-365-bottiglia' => 'An elegant bottle of white wine with a plain label, chilled with condensation, on a dark surface. ' . $DRINK_STYLE,
  'vino-omar-khayyam-bottiglia' => 'An elegant bottle of rose wine with a plain label, on a dark surface with a glass. ' . $DRINK_STYLE,
  'vino-castello-bottiglia' => 'An elegant bottle of red wine with a plain classic label, on a dark surface with a glass. ' . $DRINK_STYLE,
  // --- Vini Calice ---
  'vino-valmont-calice' => 'A glass of red wine, deep ruby color, on a dark elegant surface. ' . $DRINK_STYLE,
  'vino-365-calice' => 'A glass of chilled white wine, pale gold, with condensation, on a dark surface. ' . $DRINK_STYLE,
  'vino-omar-khayyam-calice' => 'A glass of rose wine, pale pink, on a dark elegant surface. ' . $DRINK_STYLE,
  'vino-castello-calice' => 'A glass of red wine, dark garnet color, on a dark elegant surface. ' . $DRINK_STYLE,
  // --- Birre ---
  'heineken-draft-piccola' => 'A small glass of golden draft lager beer with a frothy white head. ' . $DRINK_STYLE,
  'heineken-draft-media' => 'A medium pint glass of golden draft lager beer with a thick frothy head. ' . $DRINK_STYLE,
  'heineken-bottiglia' => 'A green glass beer bottle with no label and a frosty glass of golden lager beside it. ' . $DRINK_STYLE,
  'birra-stella' => 'A glass of pale golden lager beer with foam, and an amber beer bottle without label. ' . $DRINK_STYLE,
  'birra-sakara' => 'A glass of golden lager beer with a white foamy head on a dark bar. ' . $DRINK_STYLE,
  'birra-meister' => 'A glass of amber lager beer with foam, on a dark bar counter. ' . $DRINK_STYLE,
  'birra-desperados' => 'A glass of golden beer with a lime wedge on the rim, tequila-flavored style. ' . $DRINK_STYLE,
  'birra-id' => 'A frosty glass of light golden lager beer with foam, on a dark bar. ' . $DRINK_STYLE,
];

$only = $argv[1] ?? null;
$done = 0; $skip = 0; $fail = 0;

foreach ($PROMPTS as $slug => $prompt) {
    if ($only && $slug !== $only) continue;
    $dest = "$OUT_DIR/$slug.jpg";
    if (!$only && file_exists($dest) && filesize($dest) > 5000) {
        echo "⏭  $slug (esiste già)\n";
        $skip++;
        continue;
    }
    echo "🎨 $slug ... ";
    flush();

    $payload = json_encode([
        'model'   => 'gpt-image-2',
        'prompt'  => $prompt,
        'size'    => '1024x1024',
        'quality' => 'medium',
        'n'       => 1,
    ]);
    $ch = curl_init('https://api.openai.com/v1/images/generations');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $API_KEY],
        CURLOPT_TIMEOUT => 120,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "❌ HTTP $code\n";
        $err = json_decode($resp, true);
        if (isset($err['error']['message'])) echo "   " . $err['error']['message'] . "\n";
        $fail++;
        sleep(2);
        continue;
    }
    $j = json_decode($resp, true);
    $b64 = $j['data'][0]['b64_json'] ?? null;
    if (!$b64) { echo "❌ nessuna immagine\n"; $fail++; continue; }

    // gpt-image-2 ritorna PNG; converto/salvo come jpg via GD
    $raw = base64_decode($b64);
    $img = @imagecreatefromstring($raw);
    if ($img) {
        imagejpeg($img, $dest, 86);
        imagedestroy($img);
    } else {
        file_put_contents($dest, $raw); // fallback: salva raw
    }
    echo "✓ (" . round(filesize($dest)/1024) . " KB)\n";
    $done++;
    usleep(300000); // piccola pausa fra le chiamate
}

echo "\n=== Generate: $done · Saltate: $skip · Errori: $fail ===\n";
