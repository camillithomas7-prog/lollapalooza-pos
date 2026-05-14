<?php
// Menù completo Lollapalooza — nomi, prezzi, descrizioni e traduzioni
// (it base + en/ar/es/fr/de). Tradotto a mano, nessuna API esterna.
// Usato da /install-menu.php per ricostruire categorie + prodotti.
//
// Struttura: lista di categorie, ognuna con:
//   name, icon, color, destination (kitchen|bar), tr (traduzioni nome),
//   products[] -> { name, price, slug (per immagine), desc, tr }
// tr per prodotto: { lang: { name, description } }

return [

// ====================== CUCINA ======================
[
  'name' => 'Antipasti', 'icon' => '🧀', 'color' => '#10b981', 'destination' => 'kitchen',
  'tr' => ['en'=>'Starters','ar'=>'المقبلات','es'=>'Entrantes','fr'=>'Entrées','de'=>'Vorspeisen'],
  'products' => [
    ['name'=>'Selezione di Formaggi Italiani','price'=>800,'slug'=>'selezione-formaggi-italiani','desc'=>'',
      'tr'=>['en'=>['name'=>'Italian Cheese Selection'],'ar'=>['name'=>'تشكيلة أجبان إيطالية'],'es'=>['name'=>'Selección de Quesos Italianos'],'fr'=>['name'=>'Sélection de Fromages Italiens'],'de'=>['name'=>'Italienische Käseauswahl']]],
    ['name'=>'Tagliere di Affettati Italiani','price'=>800,'slug'=>'tagliere-affettati-italiani','desc'=>'',
      'tr'=>['en'=>['name'=>'Italian Cured Meats Board'],'ar'=>['name'=>'طبق لحوم مقددة إيطالية'],'es'=>['name'=>'Tabla de Embutidos Italianos'],'fr'=>['name'=>'Planche de Charcuterie Italienne'],'de'=>['name'=>'Italienische Wurstplatte']]],
    ['name'=>'Tagliere Bruschette Miste','price'=>350,'slug'=>'tagliere-bruschette-miste','desc'=>'',
      'tr'=>['en'=>['name'=>'Mixed Bruschetta Board'],'ar'=>['name'=>'طبق بروسكيتا متنوعة'],'es'=>['name'=>'Tabla de Bruschettas Variadas'],'fr'=>['name'=>'Planche de Bruschettas Variées'],'de'=>['name'=>'Gemischte Bruschetta-Platte']]],
    ['name'=>'Tagliere LolaPalooza','price'=>650,'slug'=>'tagliere-lolapalooza','desc'=>'Mix bruschette, verdure, mozzarelle, affettati',
      'tr'=>['en'=>['name'=>'LolaPalooza Board','description'=>'Mixed bruschetta, vegetables, mozzarella, cured meats'],'ar'=>['name'=>'طبق لولابالوزا','description'=>'بروسكيتا، خضار، موزاريلا، لحوم مقددة'],'es'=>['name'=>'Tabla LolaPalooza','description'=>'Bruschettas, verduras, mozzarella, embutidos'],'fr'=>['name'=>'Planche LolaPalooza','description'=>'Bruschettas, légumes, mozzarella, charcuterie'],'de'=>['name'=>'LolaPalooza-Platte','description'=>'Bruschetta, Gemüse, Mozzarella, Aufschnitt']]],
    ['name'=>'Prosciutto Crudo e Melone','price'=>400,'slug'=>'prosciutto-crudo-melone','desc'=>'',
      'tr'=>['en'=>['name'=>'Cured Ham and Melon'],'ar'=>['name'=>'بروشوتو والشمام'],'es'=>['name'=>'Jamón Crudo y Melón'],'fr'=>['name'=>'Jambon Cru et Melon'],'de'=>['name'=>'Rohschinken mit Melone']]],
  ],
],

[
  'name' => 'Primi', 'icon' => '🍝', 'color' => '#f59e0b', 'destination' => 'kitchen',
  'tr' => ['en'=>'First Courses','ar'=>'الأطباق الأولى','es'=>'Primeros','fr'=>'Premiers Plats','de'=>'Erste Gänge'],
  'products' => [
    ['name'=>'Penne Radicchio & Salsiccia','price'=>550,'slug'=>'penne-radicchio-salsiccia','desc'=>'',
      'tr'=>['en'=>['name'=>'Penne with Radicchio & Sausage'],'ar'=>['name'=>'بيني بالراديكيو والسجق'],'es'=>['name'=>'Penne con Radicchio y Salchicha'],'fr'=>['name'=>'Penne Radicchio & Saucisse'],'de'=>['name'=>'Penne mit Radicchio & Wurst']]],
    ['name'=>'Rigatoni Matriciana','price'=>550,'slug'=>'rigatoni-matriciana','desc'=>'Pomodoro, guanciale e pecorino',
      'tr'=>['en'=>['name'=>'Rigatoni Amatriciana','description'=>'Tomato, guanciale and pecorino'],'ar'=>['name'=>'ريغاتوني أماتريتشانا','description'=>'طماطم، لحم خد الخنزير، جبنة بيكورينو'],'es'=>['name'=>'Rigatoni Amatriciana','description'=>'Tomate, guanciale y pecorino'],'fr'=>['name'=>'Rigatoni Amatriciana','description'=>'Tomate, guanciale et pecorino'],'de'=>['name'=>'Rigatoni Amatriciana','description'=>'Tomate, Guanciale und Pecorino']]],
    ['name'=>'Ravioli Burro & Salvia','price'=>400,'slug'=>'ravioli-burro-salvia','desc'=>'Con grana',
      'tr'=>['en'=>['name'=>'Ravioli Butter & Sage','description'=>'With grana cheese'],'ar'=>['name'=>'رافيولي بالزبدة والمريمية','description'=>'مع جبنة جرانا'],'es'=>['name'=>'Ravioli Mantequilla y Salvia','description'=>'Con queso grana'],'fr'=>['name'=>'Raviolis Beurre & Sauge','description'=>'Au grana'],'de'=>['name'=>'Ravioli Butter & Salbei','description'=>'Mit Grana-Käse']]],
    ['name'=>'Tagliatelle LollaPalooza','price'=>450,'slug'=>'tagliatelle-lollapalooza','desc'=>'Ragù di manzo, funghi e panna',
      'tr'=>['en'=>['name'=>'Tagliatelle LollaPalooza','description'=>'Beef ragù, mushrooms and cream'],'ar'=>['name'=>'تالياتيلي لولابالوزا','description'=>'صلصة لحم البقر، فطر، كريمة'],'es'=>['name'=>'Tagliatelle LollaPalooza','description'=>'Ragú de ternera, setas y nata'],'fr'=>['name'=>'Tagliatelles LollaPalooza','description'=>'Ragoût de bœuf, champignons et crème'],'de'=>['name'=>'Tagliatelle LollaPalooza','description'=>'Rinderragout, Pilze und Sahne']]],
  ],
],

[
  'name' => 'Secondi', 'icon' => '🥩', 'color' => '#ef4444', 'destination' => 'kitchen',
  'tr' => ['en'=>'Main Courses','ar'=>'الأطباق الرئيسية','es'=>'Segundos','fr'=>'Plats Principaux','de'=>'Hauptgänge'],
  'products' => [
    ['name'=>'Filetto Aceto Balsamico','price'=>700,'slug'=>'filetto-aceto-balsamico','desc'=>'',
      'tr'=>['en'=>['name'=>'Beef Fillet with Balsamic Vinegar'],'ar'=>['name'=>'فيليه بخل البلسميك'],'es'=>['name'=>'Solomillo al Vinagre Balsámico'],'fr'=>['name'=>'Filet au Vinaigre Balsamique'],'de'=>['name'=>'Rinderfilet mit Balsamico']]],
    ['name'=>'Filetto alla Griglia','price'=>700,'slug'=>'filetto-alla-griglia','desc'=>'',
      'tr'=>['en'=>['name'=>'Grilled Beef Fillet'],'ar'=>['name'=>'فيليه مشوي'],'es'=>['name'=>'Solomillo a la Parrilla'],'fr'=>['name'=>'Filet Grillé'],'de'=>['name'=>'Gegrilltes Rinderfilet']]],
    ['name'=>'Pollo con Peperoni','price'=>400,'slug'=>'pollo-con-peperoni','desc'=>'',
      'tr'=>['en'=>['name'=>'Chicken with Peppers'],'ar'=>['name'=>'دجاج بالفلفل'],'es'=>['name'=>'Pollo con Pimientos'],'fr'=>['name'=>'Poulet aux Poivrons'],'de'=>['name'=>'Hähnchen mit Paprika']]],
  ],
],

[
  'name' => 'Grill', 'icon' => '🔥', 'color' => '#dc2626', 'destination' => 'kitchen',
  'tr' => ['en'=>'Grill','ar'=>'مشويات','es'=>'Parrilla','fr'=>'Grill','de'=>'Grill'],
  'products' => [
    ['name'=>'Tomahawk','price'=>200,'slug'=>'tomahawk','desc'=>'200 LE ogni 100g · minimo 2 persone · con verdure grigliate o patate al forno',
      'tr'=>['en'=>['name'=>'Tomahawk','description'=>'200 LE per 100g · minimum 2 people · with grilled vegetables or roast potatoes'],'ar'=>['name'=>'توماهوك','description'=>'200 جنيه لكل 100غ · لشخصين على الأقل · مع خضار مشوية أو بطاطس بالفرن'],'es'=>['name'=>'Tomahawk','description'=>'200 LE cada 100g · mínimo 2 personas · con verduras a la parrilla o patatas al horno'],'fr'=>['name'=>'Tomahawk','description'=>'200 LE les 100g · minimum 2 personnes · avec légumes grillés ou pommes de terre au four'],'de'=>['name'=>'Tomahawk','description'=>'200 LE pro 100g · mindestens 2 Personen · mit Grillgemüse oder Ofenkartoffeln']]],
  ],
],

[
  'name' => 'Contorni', 'icon' => '🥔', 'color' => '#84cc16', 'destination' => 'kitchen',
  'tr' => ['en'=>'Sides','ar'=>'الأطباق الجانبية','es'=>'Guarniciones','fr'=>'Accompagnements','de'=>'Beilagen'],
  'products' => [
    ['name'=>'Patate al Forno','price'=>200,'slug'=>'patate-al-forno','desc'=>'',
      'tr'=>['en'=>['name'=>'Roast Potatoes'],'ar'=>['name'=>'بطاطس بالفرن'],'es'=>['name'=>'Patatas al Horno'],'fr'=>['name'=>'Pommes de Terre au Four'],'de'=>['name'=>'Ofenkartoffeln']]],
    ['name'=>'Verdure Grigliate','price'=>200,'slug'=>'verdure-grigliate','desc'=>'',
      'tr'=>['en'=>['name'=>'Grilled Vegetables'],'ar'=>['name'=>'خضار مشوية'],'es'=>['name'=>'Verduras a la Parrilla'],'fr'=>['name'=>'Légumes Grillés'],'de'=>['name'=>'Grillgemüse']]],
    ['name'=>'Patate Fritte','price'=>200,'slug'=>'patate-fritte','desc'=>'',
      'tr'=>['en'=>['name'=>'French Fries'],'ar'=>['name'=>'بطاطس مقلية'],'es'=>['name'=>'Patatas Fritas'],'fr'=>['name'=>'Frites'],'de'=>['name'=>'Pommes Frites']]],
  ],
],

[
  'name' => 'Dessert', 'icon' => '🍰', 'color' => '#ec4899', 'destination' => 'kitchen',
  'tr' => ['en'=>'Desserts','ar'=>'الحلويات','es'=>'Postres','fr'=>'Desserts','de'=>'Desserts'],
  'products' => [
    ['name'=>'Crostate','price'=>250,'slug'=>'crostate','desc'=>'',
      'tr'=>['en'=>['name'=>'Fruit Tarts'],'ar'=>['name'=>'تارت'],'es'=>['name'=>'Tartas'],'fr'=>['name'=>'Tartes'],'de'=>['name'=>'Obstkuchen']]],
    ['name'=>'Frutta di Stagione','price'=>250,'slug'=>'frutta-di-stagione','desc'=>'',
      'tr'=>['en'=>['name'=>'Seasonal Fruit'],'ar'=>['name'=>'فواكه موسمية'],'es'=>['name'=>'Fruta de Temporada'],'fr'=>['name'=>'Fruits de Saison'],'de'=>['name'=>'Saisonales Obst']]],
  ],
],

[
  'name' => 'Pizze', 'icon' => '🍕', 'color' => '#f97316', 'destination' => 'kitchen',
  'tr' => ['en'=>'Pizzas','ar'=>'البيتزا','es'=>'Pizzas','fr'=>'Pizzas','de'=>'Pizzen'],
  'products' => [
    ['name'=>'Margherita','price'=>250,'slug'=>'pizza-margherita','desc'=>'Pomodoro e formaggio',
      'tr'=>['en'=>['name'=>'Margherita','description'=>'Tomato and cheese'],'ar'=>['name'=>'مارغريتا','description'=>'طماطم وجبن'],'es'=>['name'=>'Margarita','description'=>'Tomate y queso'],'fr'=>['name'=>'Margherita','description'=>'Tomate et fromage'],'de'=>['name'=>'Margherita','description'=>'Tomate und Käse']]],
    ['name'=>'Salame Piccante','price'=>450,'slug'=>'pizza-salame-piccante','desc'=>'Pomodoro, formaggio e salame piccante',
      'tr'=>['en'=>['name'=>'Spicy Salami','description'=>'Tomato, cheese and spicy salami'],'ar'=>['name'=>'سلامي حار','description'=>'طماطم، جبن، سلامي حار'],'es'=>['name'=>'Salami Picante','description'=>'Tomate, queso y salami picante'],'fr'=>['name'=>'Salami Piquant','description'=>'Tomate, fromage et salami piquant'],'de'=>['name'=>'Scharfe Salami','description'=>'Tomate, Käse und scharfe Salami']]],
    ['name'=>'Mortadella','price'=>480,'slug'=>'pizza-mortadella','desc'=>'Pizza bianca e mortadella',
      'tr'=>['en'=>['name'=>'Mortadella','description'=>'White pizza with mortadella'],'ar'=>['name'=>'مورتاديلا','description'=>'بيتزا بيضاء مع مورتاديلا'],'es'=>['name'=>'Mortadela','description'=>'Pizza blanca con mortadela'],'fr'=>['name'=>'Mortadelle','description'=>'Pizza blanche et mortadelle'],'de'=>['name'=>'Mortadella','description'=>'Weiße Pizza mit Mortadella']]],
    ['name'=>'Prosciutto Crudo','price'=>480,'slug'=>'pizza-prosciutto-crudo','desc'=>'Pomodoro, formaggio e prosciutto crudo',
      'tr'=>['en'=>['name'=>'Cured Ham Pizza','description'=>'Tomato, cheese and cured ham'],'ar'=>['name'=>'بيتزا بروشوتو','description'=>'طماطم، جبن، بروشوتو'],'es'=>['name'=>'Jamón Crudo','description'=>'Tomate, queso y jamón crudo'],'fr'=>['name'=>'Jambon Cru','description'=>'Tomate, fromage et jambon cru'],'de'=>['name'=>'Rohschinken','description'=>'Tomate, Käse und Rohschinken']]],
    ['name'=>'4 Formaggi','price'=>400,'slug'=>'pizza-4-formaggi','desc'=>'',
      'tr'=>['en'=>['name'=>'Four Cheese'],'ar'=>['name'=>'أربعة أجبان'],'es'=>['name'=>'Cuatro Quesos'],'fr'=>['name'=>'Quatre Fromages'],'de'=>['name'=>'Vier Käse']]],
    ['name'=>'Marinara','price'=>250,'slug'=>'pizza-marinara','desc'=>'Pomodoro, origano e aglio',
      'tr'=>['en'=>['name'=>'Marinara','description'=>'Tomato, oregano and garlic'],'ar'=>['name'=>'مارينارا','description'=>'طماطم، أوريغانو، ثوم'],'es'=>['name'=>'Marinara','description'=>'Tomate, orégano y ajo'],'fr'=>['name'=>'Marinara','description'=>'Tomate, origan et ail'],'de'=>['name'=>'Marinara','description'=>'Tomate, Oregano und Knoblauch']]],
    ['name'=>'Pizza Bianca','price'=>180,'slug'=>'pizza-bianca','desc'=>'',
      'tr'=>['en'=>['name'=>'White Pizza'],'ar'=>['name'=>'بيتزا بيضاء'],'es'=>['name'=>'Pizza Blanca'],'fr'=>['name'=>'Pizza Blanche'],'de'=>['name'=>'Weiße Pizza']]],
  ],
],

// ====================== BAR ======================
[
  'name' => 'Soft Drinks', 'icon' => '🥤', 'color' => '#06b6d4', 'destination' => 'bar',
  'tr' => ['en'=>'Soft Drinks','ar'=>'مشروبات غازية','es'=>'Refrescos','fr'=>'Boissons','de'=>'Erfrischungsgetränke'],
  'products' => [
    ['name'=>'Lemon with Mint','price'=>150,'slug'=>'lemon-with-mint','desc'=>'',
      'tr'=>['en'=>['name'=>'Lemon with Mint'],'ar'=>['name'=>'ليمون بالنعناع'],'es'=>['name'=>'Limón con Menta'],'fr'=>['name'=>'Citron à la Menthe'],'de'=>['name'=>'Zitrone mit Minze']]],
    ['name'=>'Red Bull','price'=>150,'slug'=>'red-bull','desc'=>'',
      'tr'=>['en'=>['name'=>'Red Bull'],'ar'=>['name'=>'ريد بُل'],'es'=>['name'=>'Red Bull'],'fr'=>['name'=>'Red Bull'],'de'=>['name'=>'Red Bull']]],
    ['name'=>'Coca Cola','price'=>80,'slug'=>'coca-cola','desc'=>'',
      'tr'=>['en'=>['name'=>'Coca Cola'],'ar'=>['name'=>'كوكا كولا'],'es'=>['name'=>'Coca Cola'],'fr'=>['name'=>'Coca Cola'],'de'=>['name'=>'Coca Cola']]],
    ['name'=>'Fanta','price'=>80,'slug'=>'fanta','desc'=>'',
      'tr'=>['en'=>['name'=>'Fanta'],'ar'=>['name'=>'فانتا'],'es'=>['name'=>'Fanta'],'fr'=>['name'=>'Fanta'],'de'=>['name'=>'Fanta']]],
    ['name'=>'Sprite','price'=>80,'slug'=>'sprite','desc'=>'',
      'tr'=>['en'=>['name'=>'Sprite'],'ar'=>['name'=>'سبرايت'],'es'=>['name'=>'Sprite'],'fr'=>['name'=>'Sprite'],'de'=>['name'=>'Sprite']]],
    ['name'=>'Tonic Water','price'=>80,'slug'=>'tonic-water','desc'=>'',
      'tr'=>['en'=>['name'=>'Tonic Water'],'ar'=>['name'=>'ماء تونيك'],'es'=>['name'=>'Agua Tónica'],'fr'=>['name'=>'Tonic'],'de'=>['name'=>'Tonic Water']]],
    ['name'=>'Soda Water','price'=>80,'slug'=>'soda-water','desc'=>'',
      'tr'=>['en'=>['name'=>'Soda Water'],'ar'=>['name'=>'ماء صودا'],'es'=>['name'=>'Soda'],'fr'=>['name'=>'Eau Gazeuse'],'de'=>['name'=>'Sodawasser']]],
    ['name'=>'Water','price'=>60,'slug'=>'water','desc'=>'',
      'tr'=>['en'=>['name'=>'Water'],'ar'=>['name'=>'ماء'],'es'=>['name'=>'Agua'],'fr'=>['name'=>'Eau'],'de'=>['name'=>'Wasser']]],
    ['name'=>'Tea','price'=>80,'slug'=>'tea','desc'=>'',
      'tr'=>['en'=>['name'=>'Tea'],'ar'=>['name'=>'شاي'],'es'=>['name'=>'Té'],'fr'=>['name'=>'Thé'],'de'=>['name'=>'Tee']]],
    ['name'=>'Caffè','price'=>100,'slug'=>'caffe','desc'=>'',
      'tr'=>['en'=>['name'=>'Coffee'],'ar'=>['name'=>'قهوة'],'es'=>['name'=>'Café'],'fr'=>['name'=>'Café'],'de'=>['name'=>'Kaffee']]],
  ],
],

[
  'name' => 'Cocktails', 'icon' => '🍸', 'color' => '#8b5cf6', 'destination' => 'bar',
  'tr' => ['en'=>'Cocktails','ar'=>'كوكتيلات','es'=>'Cócteles','fr'=>'Cocktails','de'=>'Cocktails'],
  'products' => [
    ['name'=>'Cocktail con Alcolici Importati','price'=>400,'slug'=>'cocktail-alcolici-importati','desc'=>'',
      'tr'=>['en'=>['name'=>'Cocktail with Imported Spirits'],'ar'=>['name'=>'كوكتيل بكحول مستورد'],'es'=>['name'=>'Cóctel con Licores Importados'],'fr'=>['name'=>'Cocktail Spiritueux Importés'],'de'=>['name'=>'Cocktail mit importierten Spirituosen']]],
    ['name'=>'Cocktail con Alcolici Locali','price'=>250,'slug'=>'cocktail-alcolici-locali','desc'=>'',
      'tr'=>['en'=>['name'=>'Cocktail with Local Spirits'],'ar'=>['name'=>'كوكتيل بكحول محلي'],'es'=>['name'=>'Cóctel con Licores Locales'],'fr'=>['name'=>'Cocktail Spiritueux Locaux'],'de'=>['name'=>'Cocktail mit lokalen Spirituosen']]],
  ],
],

[
  'name' => 'Cocktail Analcolici', 'icon' => '🍹', 'color' => '#ec4899', 'destination' => 'bar',
  'tr' => ['en'=>'Mocktails','ar'=>'كوكتيلات بدون كحول','es'=>'Cócteles sin Alcohol','fr'=>'Cocktails sans Alcool','de'=>'Alkoholfreie Cocktails'],
  'products' => [
    ['name'=>'Mojito','price'=>150,'slug'=>'mojito','desc'=>'',
      'tr'=>['en'=>['name'=>'Mojito'],'ar'=>['name'=>'موهيتو'],'es'=>['name'=>'Mojito'],'fr'=>['name'=>'Mojito'],'de'=>['name'=>'Mojito']]],
    ['name'=>'Pina Colada','price'=>150,'slug'=>'pina-colada','desc'=>'',
      'tr'=>['en'=>['name'=>'Pina Colada'],'ar'=>['name'=>'بينا كولادا'],'es'=>['name'=>'Piña Colada'],'fr'=>['name'=>'Piña Colada'],'de'=>['name'=>'Pina Colada']]],
    ['name'=>'Sunshine','price'=>150,'slug'=>'sunshine','desc'=>'',
      'tr'=>['en'=>['name'=>'Sunshine'],'ar'=>['name'=>'صن شاين'],'es'=>['name'=>'Sunshine'],'fr'=>['name'=>'Sunshine'],'de'=>['name'=>'Sunshine']]],
    ['name'=>'Sex on the Beach','price'=>150,'slug'=>'sex-on-the-beach','desc'=>'',
      'tr'=>['en'=>['name'=>'Sex on the Beach'],'ar'=>['name'=>'سيكس أون ذا بيتش'],'es'=>['name'=>'Sex on the Beach'],'fr'=>['name'=>'Sex on the Beach'],'de'=>['name'=>'Sex on the Beach']]],
  ],
],

[
  'name' => 'Shots', 'icon' => '🥃', 'color' => '#f59e0b', 'destination' => 'bar',
  'tr' => ['en'=>'Shots','ar'=>'شوتات','es'=>'Chupitos','fr'=>'Shots','de'=>'Shots'],
  'products' => [
    ['name'=>'Shots Importati','price'=>300,'slug'=>'shots-importati','desc'=>'',
      'tr'=>['en'=>['name'=>'Imported Shots'],'ar'=>['name'=>'شوتات مستوردة'],'es'=>['name'=>'Chupitos Importados'],'fr'=>['name'=>'Shots Importés'],'de'=>['name'=>'Importierte Shots']]],
    ['name'=>'Shots Locali','price'=>200,'slug'=>'shots-locali','desc'=>'',
      'tr'=>['en'=>['name'=>'Local Shots'],'ar'=>['name'=>'شوتات محلية'],'es'=>['name'=>'Chupitos Locales'],'fr'=>['name'=>'Shots Locaux'],'de'=>['name'=>'Lokale Shots']]],
    ['name'=>'Limoncello and Pocket Coffee','price'=>200,'slug'=>'limoncello-pocket-coffee','desc'=>'',
      'tr'=>['en'=>['name'=>'Limoncello and Pocket Coffee'],'ar'=>['name'=>'ليمونتشيلو وبوكيت كوفي'],'es'=>['name'=>'Limoncello y Pocket Coffee'],'fr'=>['name'=>'Limoncello et Pocket Coffee'],'de'=>['name'=>'Limoncello und Pocket Coffee']]],
    ['name'=>'Table 12 Shots','price'=>1000,'slug'=>'table-12-shots','desc'=>'12 shots per il tavolo',
      'tr'=>['en'=>['name'=>'Table 12 Shots','description'=>'12 shots for the table'],'ar'=>['name'=>'12 شوت للطاولة','description'=>'12 شوت للطاولة'],'es'=>['name'=>'Mesa 12 Chupitos','description'=>'12 chupitos para la mesa'],'fr'=>['name'=>'Table 12 Shots','description'=>'12 shots pour la table'],'de'=>['name'=>'Tisch 12 Shots','description'=>'12 Shots für den Tisch']]],
  ],
],

[
  'name' => 'Tappo', 'icon' => '🍾', 'color' => '#a855f7', 'destination' => 'bar',
  'tr' => ['en'=>'Bottle Service','ar'=>'خدمة الزجاجة','es'=>'Servicio de Botella','fr'=>'Service Bouteille','de'=>'Flaschenservice'],
  'products' => [
    ['name'=>'Tappo Local Bottle','price'=>500,'slug'=>'tappo-local-bottle','desc'=>'',
      'tr'=>['en'=>['name'=>'Local Bottle'],'ar'=>['name'=>'زجاجة محلية'],'es'=>['name'=>'Botella Local'],'fr'=>['name'=>'Bouteille Locale'],'de'=>['name'=>'Lokale Flasche']]],
    ['name'=>'Tappo Imported Bottle','price'=>800,'slug'=>'tappo-imported-bottle','desc'=>'',
      'tr'=>['en'=>['name'=>'Imported Bottle'],'ar'=>['name'=>'زجاجة مستوردة'],'es'=>['name'=>'Botella Importada'],'fr'=>['name'=>'Bouteille Importée'],'de'=>['name'=>'Importierte Flasche']]],
  ],
],

[
  'name' => 'Vini — Bottiglia', 'icon' => '🍷', 'color' => '#9f1239', 'destination' => 'bar',
  'tr' => ['en'=>'Wines — Bottle','ar'=>'نبيذ — زجاجة','es'=>'Vinos — Botella','fr'=>'Vins — Bouteille','de'=>'Weine — Flasche'],
  'products' => [
    ['name'=>'Valmont — Bottiglia','price'=>1200,'slug'=>'vino-valmont-bottiglia','desc'=>'',
      'tr'=>['en'=>['name'=>'Valmont — Bottle'],'ar'=>['name'=>'فالمونت — زجاجة'],'es'=>['name'=>'Valmont — Botella'],'fr'=>['name'=>'Valmont — Bouteille'],'de'=>['name'=>'Valmont — Flasche']]],
    ['name'=>'Wine 365 — Bottiglia','price'=>900,'slug'=>'vino-365-bottiglia','desc'=>'',
      'tr'=>['en'=>['name'=>'Wine 365 — Bottle'],'ar'=>['name'=>'واين 365 — زجاجة'],'es'=>['name'=>'Wine 365 — Botella'],'fr'=>['name'=>'Wine 365 — Bouteille'],'de'=>['name'=>'Wine 365 — Flasche']]],
    ['name'=>'Omar Khayyam — Bottiglia','price'=>900,'slug'=>'vino-omar-khayyam-bottiglia','desc'=>'',
      'tr'=>['en'=>['name'=>'Omar Khayyam — Bottle'],'ar'=>['name'=>'عمر الخيام — زجاجة'],'es'=>['name'=>'Omar Khayyam — Botella'],'fr'=>['name'=>'Omar Khayyam — Bouteille'],'de'=>['name'=>'Omar Khayyam — Flasche']]],
    ['name'=>'Castello — Bottiglia','price'=>1000,'slug'=>'vino-castello-bottiglia','desc'=>'',
      'tr'=>['en'=>['name'=>'Castello — Bottle'],'ar'=>['name'=>'كاستيلو — زجاجة'],'es'=>['name'=>'Castello — Botella'],'fr'=>['name'=>'Castello — Bouteille'],'de'=>['name'=>'Castello — Flasche']]],
  ],
],

[
  'name' => 'Vini — Calice', 'icon' => '🥂', 'color' => '#be123c', 'destination' => 'bar',
  'tr' => ['en'=>'Wines — Glass','ar'=>'نبيذ — كأس','es'=>'Vinos — Copa','fr'=>'Vins — Verre','de'=>'Weine — Glas'],
  'products' => [
    ['name'=>'Valmont — Calice','price'=>250,'slug'=>'vino-valmont-calice','desc'=>'',
      'tr'=>['en'=>['name'=>'Valmont — Glass'],'ar'=>['name'=>'فالمونت — كأس'],'es'=>['name'=>'Valmont — Copa'],'fr'=>['name'=>'Valmont — Verre'],'de'=>['name'=>'Valmont — Glas']]],
    ['name'=>'Wine 365 — Calice','price'=>150,'slug'=>'vino-365-calice','desc'=>'',
      'tr'=>['en'=>['name'=>'Wine 365 — Glass'],'ar'=>['name'=>'واين 365 — كأس'],'es'=>['name'=>'Wine 365 — Copa'],'fr'=>['name'=>'Wine 365 — Verre'],'de'=>['name'=>'Wine 365 — Glas']]],
    ['name'=>'Omar Khayyam — Calice','price'=>150,'slug'=>'vino-omar-khayyam-calice','desc'=>'',
      'tr'=>['en'=>['name'=>'Omar Khayyam — Glass'],'ar'=>['name'=>'عمر الخيام — كأس'],'es'=>['name'=>'Omar Khayyam — Copa'],'fr'=>['name'=>'Omar Khayyam — Verre'],'de'=>['name'=>'Omar Khayyam — Glas']]],
    ['name'=>'Castello — Calice','price'=>200,'slug'=>'vino-castello-calice','desc'=>'',
      'tr'=>['en'=>['name'=>'Castello — Glass'],'ar'=>['name'=>'كاستيلو — كأس'],'es'=>['name'=>'Castello — Copa'],'fr'=>['name'=>'Castello — Verre'],'de'=>['name'=>'Castello — Glas']]],
  ],
],

[
  'name' => 'Birre', 'icon' => '🍺', 'color' => '#d97706', 'destination' => 'bar',
  'tr' => ['en'=>'Beers','ar'=>'البيرة','es'=>'Cervezas','fr'=>'Bières','de'=>'Biere'],
  'products' => [
    ['name'=>'Heineken Draft Piccola','price'=>150,'slug'=>'heineken-draft-piccola','desc'=>'Alla spina, piccola',
      'tr'=>['en'=>['name'=>'Heineken Draft Small','description'=>'Draft, small'],'ar'=>['name'=>'هاينكن دraft صغيرة','description'=>'من الصنبور، صغيرة'],'es'=>['name'=>'Heineken Draft Pequeña','description'=>'De barril, pequeña'],'fr'=>['name'=>'Heineken Pression Petite','description'=>'Pression, petite'],'de'=>['name'=>'Heineken Fass Klein','description'=>'Vom Fass, klein']]],
    ['name'=>'Heineken Draft Media','price'=>200,'slug'=>'heineken-draft-media','desc'=>'Alla spina, media',
      'tr'=>['en'=>['name'=>'Heineken Draft Medium','description'=>'Draft, medium'],'ar'=>['name'=>'هاينكن دraft وسط','description'=>'من الصنبور، وسط'],'es'=>['name'=>'Heineken Draft Mediana','description'=>'De barril, mediana'],'fr'=>['name'=>'Heineken Pression Moyenne','description'=>'Pression, moyenne'],'de'=>['name'=>'Heineken Fass Mittel','description'=>'Vom Fass, mittel']]],
    ['name'=>'Heineken','price'=>150,'slug'=>'heineken-bottiglia','desc'=>'In bottiglia',
      'tr'=>['en'=>['name'=>'Heineken','description'=>'Bottle'],'ar'=>['name'=>'هاينكن','description'=>'زجاجة'],'es'=>['name'=>'Heineken','description'=>'Botella'],'fr'=>['name'=>'Heineken','description'=>'Bouteille'],'de'=>['name'=>'Heineken','description'=>'Flasche']]],
    ['name'=>'Stella','price'=>150,'slug'=>'birra-stella','desc'=>'',
      'tr'=>['en'=>['name'=>'Stella'],'ar'=>['name'=>'ستيلا'],'es'=>['name'=>'Stella'],'fr'=>['name'=>'Stella'],'de'=>['name'=>'Stella']]],
    ['name'=>'Sakara','price'=>150,'slug'=>'birra-sakara','desc'=>'',
      'tr'=>['en'=>['name'=>'Sakara'],'ar'=>['name'=>'سقارة'],'es'=>['name'=>'Sakara'],'fr'=>['name'=>'Sakara'],'de'=>['name'=>'Sakara']]],
    ['name'=>'Meister','price'=>150,'slug'=>'birra-meister','desc'=>'',
      'tr'=>['en'=>['name'=>'Meister'],'ar'=>['name'=>'مايستر'],'es'=>['name'=>'Meister'],'fr'=>['name'=>'Meister'],'de'=>['name'=>'Meister']]],
    ['name'=>'Desperados','price'=>150,'slug'=>'birra-desperados','desc'=>'',
      'tr'=>['en'=>['name'=>'Desperados'],'ar'=>['name'=>'ديسبيرادوس'],'es'=>['name'=>'Desperados'],'fr'=>['name'=>'Desperados'],'de'=>['name'=>'Desperados']]],
    ['name'=>'ID','price'=>150,'slug'=>'birra-id','desc'=>'',
      'tr'=>['en'=>['name'=>'ID'],'ar'=>['name'=>'آي دي'],'es'=>['name'=>'ID'],'fr'=>['name'=>'ID'],'de'=>['name'=>'ID']]],
  ],
],

];
