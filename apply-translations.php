<?php
/**
 * One-shot: applica traduzioni EN/ES/FR/DE a tutte le categorie e prodotti del menu.
 * Uso: /apply-translations.php?token=lp-2026-xK9pQ7zM
 * Cancellare dopo l'uso.
 */
if (($_GET['token'] ?? '') !== 'lp-2026-xK9pQ7zM') { http_response_code(403); exit('403'); }
require_once __DIR__ . '/includes/config.php';
header('Content-Type: text/plain; charset=utf-8');

$pdo = db();

// === CATEGORIE ===
$catTranslations = [
    'Antipasti'      => ['en'=>['name'=>'Starters'],          'es'=>['name'=>'Entrantes'],         'fr'=>['name'=>'Entrées'],            'de'=>['name'=>'Vorspeisen']],
    'Primi Piatti'   => ['en'=>['name'=>'Pasta & Risotto'],   'es'=>['name'=>'Pastas y Risottos'], 'fr'=>['name'=>'Pâtes & Risotto'],    'de'=>['name'=>'Pasta & Risotto']],
    'Secondi'        => ['en'=>['name'=>'Main Courses'],      'es'=>['name'=>'Segundos Platos'],   'fr'=>['name'=>'Plats Principaux'],   'de'=>['name'=>'Hauptgänge']],
    'Pizza'          => ['en'=>['name'=>'Pizza'],             'es'=>['name'=>'Pizza'],             'fr'=>['name'=>'Pizza'],              'de'=>['name'=>'Pizza']],
    'Panini'         => ['en'=>['name'=>'Sandwiches & Burgers'], 'es'=>['name'=>'Sándwiches'],      'fr'=>['name'=>'Sandwichs & Burgers'],'de'=>['name'=>'Sandwiches & Burger']],
    'Dolci'          => ['en'=>['name'=>'Desserts'],          'es'=>['name'=>'Postres'],           'fr'=>['name'=>'Desserts'],           'de'=>['name'=>'Desserts']],
    'Birre'          => ['en'=>['name'=>'Beers'],             'es'=>['name'=>'Cervezas'],          'fr'=>['name'=>'Bières'],             'de'=>['name'=>'Biere']],
    'Cocktail'       => ['en'=>['name'=>'Cocktails'],         'es'=>['name'=>'Cócteles'],          'fr'=>['name'=>'Cocktails'],          'de'=>['name'=>'Cocktails']],
    'Vini'           => ['en'=>['name'=>'Wines'],             'es'=>['name'=>'Vinos'],             'fr'=>['name'=>'Vins'],               'de'=>['name'=>'Weine']],
    'Analcolici'     => ['en'=>['name'=>'Soft Drinks'],       'es'=>['name'=>'Refrescos'],         'fr'=>['name'=>'Boissons sans alcool'],'de'=>['name'=>'Alkoholfreie Getränke']],
    'Caffetteria'    => ['en'=>['name'=>'Coffee'],            'es'=>['name'=>'Cafetería'],         'fr'=>['name'=>'Café'],               'de'=>['name'=>'Kaffee']],
];

echo "=== CATEGORIE ===\n";
$cOk = 0;
foreach ($catTranslations as $name => $tr) {
    $st = $pdo->prepare("UPDATE categories SET translations=? WHERE tenant_id=1 AND name=?");
    $st->execute([json_encode($tr, JSON_UNESCAPED_UNICODE), $name]);
    if ($st->rowCount() > 0) { echo "✓ $name\n"; $cOk++; } else { echo "⏭  $name (non trovata)\n"; }
}

// === PRODOTTI === [italiano nome => translations]
$prodTranslations = [
    'Tagliere Misto Italiano' => [
        'en'=>['name'=>'Italian Mixed Platter','description'=>'Cured meats DOP, local cheeses, mostarda, breadsticks','allergens'=>''],
        'es'=>['name'=>'Tabla Mixta Italiana','description'=>'Embutidos DOP, quesos locales, mostaza, colines','allergens'=>''],
        'fr'=>['name'=>'Planche Mixte Italienne','description'=>'Charcuterie DOP, fromages locaux, moutarde, gressins','allergens'=>''],
        'de'=>['name'=>'Italienische Wurst- & Käseplatte','description'=>'DOP-Wurstwaren, regionale Käse, Mostarda, Grissini','allergens'=>''],
    ],
    'Bruschette al Pomodoro' => [
        'en'=>['name'=>'Tomato Bruschetta','description'=>'Toasted homemade bread, fresh tomato, basil (4 pieces)','allergens'=>''],
        'es'=>['name'=>'Bruschette de Tomate','description'=>'Pan casero tostado, tomate fresco, albahaca (4 piezas)','allergens'=>''],
        'fr'=>['name'=>'Bruschette à la Tomate','description'=>'Pain maison toasté, tomate fraîche, basilic (4 pièces)','allergens'=>''],
        'de'=>['name'=>'Tomaten-Bruschetta','description'=>'Geröstetes Landbrot, frische Tomate, Basilikum (4 Stück)','allergens'=>''],
    ],
    'Spaghetti Carbonara' => [
        'en'=>['name'=>'Spaghetti Carbonara','description'=>'Crispy guanciale, pecorino romano, egg, black pepper','allergens'=>''],
        'es'=>['name'=>'Espaguetis a la Carbonara','description'=>'Guanciale crujiente, pecorino romano, huevo, pimienta negra','allergens'=>''],
        'fr'=>['name'=>'Spaghetti Carbonara','description'=>'Guanciale croustillant, pecorino romano, œuf, poivre noir','allergens'=>''],
        'de'=>['name'=>'Spaghetti Carbonara','description'=>'Knuspriger Guanciale, Pecorino Romano, Ei, schwarzer Pfeffer','allergens'=>''],
    ],
    'Risotto ai Funghi Porcini' => [
        'en'=>['name'=>'Porcini Mushroom Risotto','description'=>'Creamy Carnaroli rice, fresh porcini, parsley','allergens'=>''],
        'es'=>['name'=>'Risotto de Setas Porcini','description'=>'Arroz Carnaroli cremoso, porcini frescos, perejil','allergens'=>''],
        'fr'=>['name'=>'Risotto aux Cèpes','description'=>'Riz Carnaroli crémeux, cèpes frais, persil','allergens'=>''],
        'de'=>['name'=>'Steinpilz-Risotto','description'=>'Cremiger Carnaroli-Reis, frische Steinpilze, Petersilie','allergens'=>''],
    ],
    'Tagliata di Manzo' => [
        'en'=>['name'=>'Sliced Beef Tagliata','description'=>'300g Angus beef, arugula, parmesan, balsamic glaze','allergens'=>''],
        'es'=>['name'=>'Tagliata de Ternera','description'=>'300g ternera Angus, rúcula, parmesano, glaseado balsámico','allergens'=>''],
        'fr'=>['name'=>'Tagliata de Bœuf','description'=>'300g bœuf Angus, roquette, parmesan, glaçage balsamique','allergens'=>''],
        'de'=>['name'=>'Rinder-Tagliata','description'=>'300g Angus-Rind, Rucola, Grana, Balsamico-Glasur','allergens'=>''],
    ],
    'Branzino al Sale' => [
        'en'=>['name'=>'Sea Bass in Salt Crust','description'=>'Whole oven-baked fish in salt crust','allergens'=>''],
        'es'=>['name'=>'Lubina a la Sal','description'=>'Pescado entero al horno en costra de sal','allergens'=>''],
        'fr'=>['name'=>'Bar en Croûte de Sel','description'=>'Poisson entier cuit au four en croûte de sel','allergens'=>''],
        'de'=>['name'=>'Wolfsbarsch in Salzkruste','description'=>'Ganzer ofengebackener Fisch in Salzkruste','allergens'=>''],
    ],
    'Margherita' => [
        'en'=>['name'=>'Margherita','description'=>'San Marzano tomato, fior di latte mozzarella, basil','allergens'=>''],
        'es'=>['name'=>'Margarita','description'=>'Tomate San Marzano, mozzarella fior di latte, albahaca','allergens'=>''],
        'fr'=>['name'=>'Margherita','description'=>'Tomate San Marzano, mozzarella fior di latte, basilic','allergens'=>''],
        'de'=>['name'=>'Margherita','description'=>'San-Marzano-Tomate, Fior di Latte Mozzarella, Basilikum','allergens'=>''],
    ],
    'Diavola' => [
        'en'=>['name'=>'Diavola','description'=>'Tomato, mozzarella, spicy Calabrian salami','allergens'=>''],
        'es'=>['name'=>'Diavola','description'=>'Tomate, mozzarella, salami picante calabrés','allergens'=>''],
        'fr'=>['name'=>'Diavola','description'=>'Tomate, mozzarella, salami piquant calabrais','allergens'=>''],
        'de'=>['name'=>'Diavola','description'=>'Tomate, Mozzarella, scharfe kalabrische Salami','allergens'=>''],
    ],
    'Hamburger Lollapalooza' => [
        'en'=>['name'=>'Lollapalooza Burger','description'=>'200g beef, cheddar, crispy bacon, special sauce','allergens'=>''],
        'es'=>['name'=>'Hamburguesa Lollapalooza','description'=>'200g ternera, cheddar, bacon crujiente, salsa especial','allergens'=>''],
        'fr'=>['name'=>'Burger Lollapalooza','description'=>'200g bœuf, cheddar, bacon croustillant, sauce spéciale','allergens'=>''],
        'de'=>['name'=>'Lollapalooza Burger','description'=>'200g Rindfleisch, Cheddar, knuspriger Bacon, Spezialsauce','allergens'=>''],
    ],
    'Club Sandwich' => [
        'en'=>['name'=>'Club Sandwich','description'=>'Grilled chicken, bacon, egg, lettuce, tomato','allergens'=>''],
        'es'=>['name'=>'Club Sandwich','description'=>'Pollo a la plancha, bacon, huevo, lechuga, tomate','allergens'=>''],
        'fr'=>['name'=>'Club Sandwich','description'=>'Poulet grillé, bacon, œuf, laitue, tomate','allergens'=>''],
        'de'=>['name'=>'Club Sandwich','description'=>'Gegrilltes Hähnchen, Bacon, Ei, Salat, Tomate','allergens'=>''],
    ],
    'Tiramisù della Casa' => [
        'en'=>['name'=>'Homemade Tiramisu','description'=>'Mascarpone, ladyfingers, espresso, cocoa','allergens'=>''],
        'es'=>['name'=>'Tiramisú de la Casa','description'=>'Mascarpone, bizcochos de soletilla, espresso, cacao','allergens'=>''],
        'fr'=>['name'=>'Tiramisu Maison','description'=>'Mascarpone, biscuits boudoir, espresso, cacao','allergens'=>''],
        'de'=>['name'=>'Hausgemachtes Tiramisu','description'=>'Mascarpone, Löffelbiskuit, Espresso, Kakao','allergens'=>''],
    ],
    'Cheesecake ai Frutti di Bosco' => [
        'en'=>['name'=>'Berry Cheesecake','description'=>'New York style, red berries, biscuit base','allergens'=>''],
        'es'=>['name'=>'Cheesecake de Frutos del Bosque','description'=>'Estilo New York, frutos rojos, base de galleta','allergens'=>''],
        'fr'=>['name'=>'Cheesecake aux Fruits Rouges','description'=>'Style New York, fruits rouges, base biscuit','allergens'=>''],
        'de'=>['name'=>'Beeren-Cheesecake','description'=>'New York Style, rote Beeren, Keksboden','allergens'=>''],
    ],
    'Moretti alla Spina 0.4L' => [
        'en'=>['name'=>'Moretti Draft 0.4L','description'=>'Italian lager on tap','allergens'=>''],
        'es'=>['name'=>'Moretti de Barril 0.4L','description'=>'Rubia italiana de barril','allergens'=>''],
        'fr'=>['name'=>'Moretti Pression 0.4L','description'=>'Blonde italienne pression','allergens'=>''],
        'de'=>['name'=>'Moretti vom Fass 0.4L','description'=>'Italienisches Lager vom Fass','allergens'=>''],
    ],
    'IPA Artigianale' => [
        'en'=>['name'=>'Craft IPA','description'=>'Italian hoppy craft beer','allergens'=>''],
        'es'=>['name'=>'IPA Artesanal','description'=>'Cerveza artesanal italiana lupulada','allergens'=>''],
        'fr'=>['name'=>'IPA Artisanale','description'=>'Bière artisanale italienne houblonnée','allergens'=>''],
        'de'=>['name'=>'Craft IPA','description'=>'Italienisches Craft-Bier mit Hopfen','allergens'=>''],
    ],
    'Spritz Aperol' => [
        'en'=>['name'=>'Aperol Spritz','description'=>'Aperol, prosecco, soda, orange slice','allergens'=>''],
        'es'=>['name'=>'Aperol Spritz','description'=>'Aperol, prosecco, soda, rodaja de naranja','allergens'=>''],
        'fr'=>['name'=>'Spritz Aperol','description'=>'Aperol, prosecco, soda, tranche d\'orange','allergens'=>''],
        'de'=>['name'=>'Aperol Spritz','description'=>'Aperol, Prosecco, Soda, Orangenscheibe','allergens'=>''],
    ],
    'Negroni' => [
        'en'=>['name'=>'Negroni','description'=>'Gin, red vermouth, Campari, orange peel','allergens'=>''],
        'es'=>['name'=>'Negroni','description'=>'Ginebra, vermut rojo, Campari, piel de naranja','allergens'=>''],
        'fr'=>['name'=>'Negroni','description'=>'Gin, vermouth rouge, Campari, zeste d\'orange','allergens'=>''],
        'de'=>['name'=>'Negroni','description'=>'Gin, roter Wermut, Campari, Orangenschale','allergens'=>''],
    ],
    'Calice Rosso Chianti' => [
        'en'=>['name'=>'Glass of Red Chianti','description'=>'Chianti Classico DOCG','allergens'=>''],
        'es'=>['name'=>'Copa de Tinto Chianti','description'=>'Chianti Classico DOCG','allergens'=>''],
        'fr'=>['name'=>'Verre de Chianti Rouge','description'=>'Chianti Classico DOCG','allergens'=>''],
        'de'=>['name'=>'Glas Rotwein Chianti','description'=>'Chianti Classico DOCG','allergens'=>''],
    ],
    'Calice Bianco Falanghina' => [
        'en'=>['name'=>'Glass of White Falanghina','description'=>'Falanghina del Sannio DOC','allergens'=>''],
        'es'=>['name'=>'Copa de Blanco Falanghina','description'=>'Falanghina del Sannio DOC','allergens'=>''],
        'fr'=>['name'=>'Verre de Falanghina Blanc','description'=>'Falanghina del Sannio DOC','allergens'=>''],
        'de'=>['name'=>'Glas Weißwein Falanghina','description'=>'Falanghina del Sannio DOC','allergens'=>''],
    ],
    'Coca Cola' => [
        'en'=>['name'=>'Coca Cola','description'=>'33cl can','allergens'=>''],
        'es'=>['name'=>'Coca Cola','description'=>'Lata 33cl','allergens'=>''],
        'fr'=>['name'=>'Coca Cola','description'=>'Canette 33cl','allergens'=>''],
        'de'=>['name'=>'Coca Cola','description'=>'33cl Dose','allergens'=>''],
    ],
    "Spremuta d'Arancia Fresca" => [
        'en'=>['name'=>'Fresh Orange Juice','description'=>'Freshly squeezed','allergens'=>''],
        'es'=>['name'=>'Zumo de Naranja Fresco','description'=>'Recién exprimido','allergens'=>''],
        'fr'=>['name'=>'Jus d\'Orange Frais','description'=>'Pressé minute','allergens'=>''],
        'de'=>['name'=>'Frischer Orangensaft','description'=>'Frisch gepresst','allergens'=>''],
    ],
    'Espresso' => [
        'en'=>['name'=>'Espresso','description'=>'Italian espresso shot','allergens'=>''],
        'es'=>['name'=>'Café Espresso','description'=>'Café espresso italiano','allergens'=>''],
        'fr'=>['name'=>'Espresso','description'=>'Café espresso italien','allergens'=>''],
        'de'=>['name'=>'Espresso','description'=>'Italienischer Espresso','allergens'=>''],
    ],
    'Cappuccino' => [
        'en'=>['name'=>'Cappuccino','description'=>'Espresso with steamed milk and latte art','allergens'=>''],
        'es'=>['name'=>'Cappuccino','description'=>'Espresso y leche espumada con latte art','allergens'=>''],
        'fr'=>['name'=>'Cappuccino','description'=>'Espresso et lait moussé avec latte art','allergens'=>''],
        'de'=>['name'=>'Cappuccino','description'=>'Espresso mit aufgeschäumter Milch und Latte Art','allergens'=>''],
    ],
];

echo "\n=== PRODOTTI ===\n";
$pOk = 0;
foreach ($prodTranslations as $name => $tr) {
    $st = $pdo->prepare("UPDATE products SET translations=? WHERE tenant_id=1 AND name=?");
    $st->execute([json_encode($tr, JSON_UNESCAPED_UNICODE), $name]);
    if ($st->rowCount() > 0) { echo "✓ $name\n"; $pOk++; } else { echo "⏭  $name (non trovato)\n"; }
}

echo "\n✅ Traduzioni applicate: $cOk categorie + $pOk prodotti\n";
echo "⚠ Cancella apply-translations.php dopo l'uso\n";
