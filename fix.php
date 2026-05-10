<?php
// One-shot fix endpoint
if (($_GET['t'] ?? '') !== 'lp-2026-xK9pQ7zM') { http_response_code(403); exit('403'); }
ini_set('display_errors', '1'); error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/includes/config.php';
$pdo = db();

echo "=== FIX TABELLA TENANTS + SEED ===\n\n";

// Crea tabella tenants mancante
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS tenants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(100) UNIQUE,
        address VARCHAR(500),
        phone VARCHAR(50),
        vat VARCHAR(50),
        currency VARCHAR(10) DEFAULT 'EUR',
        locale VARCHAR(10) DEFAULT 'it',
        logo VARCHAR(500),
        color_primary VARCHAR(20) DEFAULT '#0f172a',
        settings TEXT,
        active TINYINT DEFAULT 1,
        created_at DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✓ Tabella 'tenants' creata\n";
} catch (Exception $e) { echo "✗ tenants: " . $e->getMessage() . "\n"; exit; }

// Seed completo
$check = $pdo->query('SELECT COUNT(*) c FROM tenants')->fetch();
if ($check['c'] > 0) { echo "⚠ Seed già fatto, esco\n"; exit; }

$pdo->prepare("INSERT INTO tenants (name, slug, address, phone, vat, currency, locale, color_primary, created_at) VALUES (?,?,?,?,?,?,?,?,?)")
    ->execute(['Lollapalooza', 'lollapalooza', 'Via Roma 1, Milano', '+39 02 1234567', 'IT12345678901', 'EUR', 'it', '#0f172a', date('Y-m-d H:i:s')]);
echo "✓ Tenant creato\n";

$hash = password_hash('lollab2026', PASSWORD_BCRYPT);
$users = [
    ['Admin', 'admin@lollab.it', 'admin', '0000'],
    ['Marco Manager', 'manager@lollab.it', 'manager', '1111'],
    ['Sara Cassa', 'cassa@lollab.it', 'cassiere', '2222'],
    ['Luca Cameriere', 'luca@lollab.it', 'cameriere', '3333'],
    ['Anna Cameriere', 'anna@lollab.it', 'cameriere', '3334'],
    ['Chef Mario', 'cucina@lollab.it', 'cucina', '4444'],
    ['Barman Giulio', 'bar@lollab.it', 'bar', '5555'],
    ['Magazzino', 'magazzino@lollab.it', 'magazziniere', '6666'],
];
foreach ($users as $u) {
    $pdo->prepare('INSERT INTO users (tenant_id, name, email, password, role, pin, active, created_at) VALUES (1,?,?,?,?,?,1,?)')
        ->execute([$u[0], $u[1], $hash, $u[2], $u[3], date('Y-m-d H:i:s')]);
}
echo "✓ 8 utenti creati\n";

$pdo->prepare("INSERT INTO rooms (tenant_id, name, width, height, sort) VALUES (1,'Sala Principale',900,600,0)")->execute();
$pdo->prepare("INSERT INTO rooms (tenant_id, name, width, height, sort) VALUES (1,'Dehors',900,500,1)")->execute();
echo "✓ 2 sale\n";

for ($i=1;$i<=8;$i++) {
    $x = 80 + (($i-1) % 4) * 180;
    $y = 80 + intdiv($i-1, 4) * 200;
    $pdo->prepare("INSERT INTO `tables` (tenant_id, room_id, code, seats, pos_x, pos_y, qr_token) VALUES (1,1,?,?,?,?,?)")
        ->execute(["T$i", 4, $x, $y, bin2hex(random_bytes(8))]);
}
for ($i=1;$i<=6;$i++) {
    $x = 80 + (($i-1) % 3) * 220;
    $y = 100 + intdiv($i-1, 3) * 200;
    $pdo->prepare("INSERT INTO `tables` (tenant_id, room_id, code, seats, shape, pos_x, pos_y, qr_token) VALUES (1,2,?,?,?,?,?,?)")
        ->execute(["D$i", 2, 'round', $x, $y, bin2hex(random_bytes(8))]);
}
echo "✓ 14 tavoli\n";

$cats = [
    ['Antipasti','🥗','#10b981','kitchen'],['Primi Piatti','🍝','#f59e0b','kitchen'],
    ['Secondi','🥩','#ef4444','kitchen'],['Pizza','🍕','#dc2626','kitchen'],
    ['Panini','🥪','#f97316','kitchen'],['Dolci','🍰','#ec4899','kitchen'],
    ['Birre','🍺','#eab308','bar'],['Cocktail','🍸','#8b5cf6','bar'],
    ['Vini','🍷','#9f1239','bar'],['Analcolici','🥤','#06b6d4','bar'],
    ['Caffetteria','☕','#78350f','bar'],
];
foreach ($cats as $i => $c) {
    $pdo->prepare('INSERT INTO categories (tenant_id, name, icon, color, destination, sort, active) VALUES (1,?,?,?,?,?,1)')
        ->execute([$c[0], $c[1], $c[2], $c[3], $i]);
}
echo "✓ 11 categorie\n";

$prods = [
    [1,'Tagliere Misto','Salumi e formaggi locali',14.00,5.50,10],
    [1,'Bruschette','Pomodoro e basilico (4pz)',6.50,1.20,10],
    [1,'Tartare di Manzo','Manzo crudo, capperi',12.00,4.50,10],
    [2,'Spaghetti Carbonara','Guanciale, pecorino',11.00,2.80,10],
    [2,'Tagliatelle al Ragù','Ragù di carne 8h',12.00,3.20,10],
    [2,'Risotto ai Funghi','Porcini freschi',13.50,4.00,10],
    [3,'Tagliata di Manzo','300g, rucola e parmigiano',22.00,9.00,10],
    [3,'Branzino al Sale','Pesce fresco',24.00,11.00,10],
    [4,'Margherita','Pomodoro, mozzarella, basilico',8.00,1.80,10],
    [4,'Diavola','Salame piccante',10.00,2.50,10],
    [4,'Quattro Formaggi','Mozzarella, gorgonzola, fontina, parmigiano',11.00,3.10,10],
    [5,'Hamburger Lollapalooza','200g manzo, cheddar, bacon',13.50,4.00,10],
    [5,'Club Sandwich','Pollo, bacon, uovo',10.00,3.00,10],
    [6,'Tiramisù','Della casa',6.00,1.20,10],
    [6,'Panna Cotta','Frutti di bosco',5.50,1.00,10],
    [7,'Peroni 33cl','Bionda',4.00,1.20,22],
    [7,'Heineken 33cl','Bionda olandese',4.50,1.40,22],
    [7,'Moretti 0.4L','Spina',5.50,1.80,22],
    [7,'IPA Artigianale','Birra artigianale',7.00,2.50,22],
    [8,'Spritz Aperol','Classico',7.00,1.80,22],
    [8,'Negroni','Gin, vermouth, campari',9.00,2.20,22],
    [8,'Gin Tonic','Gin premium',10.00,2.50,22],
    [8,'Mojito','Rum, lime, menta',9.00,2.00,22],
    [8,'Margarita','Tequila, lime',10.00,2.40,22],
    [9,'Calice Rosso','Chianti DOCG',6.00,1.50,22],
    [9,'Calice Bianco','Falanghina',6.00,1.50,22],
    [9,'Bottiglia Rosso','Brunello di Montalcino',45.00,18.00,22],
    [10,'Coca Cola','33cl',3.50,0.60,22],
    [10,'Acqua Naturale','75cl',2.50,0.30,22],
    [10,'Acqua Frizzante','75cl',2.50,0.30,22],
    [10,'Spremuta Arancia','Fresca',5.00,1.20,10],
    [11,'Espresso','',1.50,0.20,22],
    [11,'Cappuccino','',2.00,0.40,22],
    [11,'Caffè Americano','',2.50,0.40,22],
];
foreach ($prods as $p) {
    $pdo->prepare('INSERT INTO products (tenant_id, category_id, name, description, price, cost, vat, available, track_stock, stock, stock_min, created_at) VALUES (1,?,?,?,?,?,?,1,1,?,?,?)')
        ->execute([$p[0],$p[1],$p[2],$p[3],$p[4],$p[5], rand(20,80), 5, date('Y-m-d H:i:s')]);
}
echo "✓ 34 prodotti\n";

$pdo->prepare('INSERT INTO cash_sessions (tenant_id, user_id, opened_at, open_amount, status) VALUES (1,3,?,?,?)')
    ->execute([date('Y-m-d H:i:s'), 100.00, 'open']);
echo "✓ Cash session aperta\n";

echo "\n🎉 SETUP COMPLETATO! Vai su /index.php?p=login e accedi con admin@lollab.it / lollab2026\n";
echo "\n⚠ Cancella questo file (fix.php), diag.php, setup.php dal File Manager per sicurezza.\n";
