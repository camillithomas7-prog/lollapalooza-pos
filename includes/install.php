<?php
require_once __DIR__ . '/config.php';

$pdo = db();
$schemaFile = DB_DRIVER === 'mysql' ? 'schema_mysql.sql' : 'schema.sql';
$schema = file_get_contents(__DIR__ . '/' . $schemaFile);
$schema = preg_replace('/^\s*--.*$/m', '', $schema);

// Esegui statement uno alla volta (compatibile MySQL e SQLite)
$statements = array_filter(array_map('trim', preg_split('/;\s*$/m', $schema)));
foreach ($statements as $stmt) {
    if ($stmt) {
        try { $pdo->exec($stmt); } catch (PDOException $e) {
            echo "⚠ Errore SQL: " . $e->getMessage() . "\nQuery: " . substr($stmt, 0, 100) . "...\n";
        }
    }
}
echo "Schema $schemaFile applicato (driver: " . DB_DRIVER . ")\n";

// Demo tenant
$st = $pdo->prepare('SELECT COUNT(*) c FROM tenants');
$st->execute();
if ($st->fetch()['c'] == 0) {
    $pdo->prepare("INSERT INTO tenants (name, slug, address, phone, vat, currency, locale, color_primary, created_at) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute(['Lollapalooza', 'lollapalooza', 'Via Roma 1, Milano', '+39 02 1234567', 'IT12345678901', 'EUR', 'it', '#0f172a', date('Y-m-d H:i:s')]);

    // Users (password: lollab2026 for all)
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
        $pdo->prepare('INSERT INTO users (tenant_id, name, email, password, role, pin, created_at) VALUES (1,?,?,?,?,?,?)')
            ->execute([$u[0], $u[1], $hash, $u[2], $u[3], date('Y-m-d H:i:s')]);
    }

    // Sale
    $pdo->prepare("INSERT INTO rooms (tenant_id, name, width, height, sort) VALUES (1,'Sala Principale',900,600,0)")->execute();
    $pdo->prepare("INSERT INTO rooms (tenant_id, name, width, height, sort) VALUES (1,'Dehors',900,500,1)")->execute();

    // Tavoli sala 1
    for ($i=1;$i<=8;$i++) {
        $x = 80 + (($i-1) % 4) * 180;
        $y = 80 + intdiv($i-1, 4) * 200;
        $pdo->prepare("INSERT INTO tables (tenant_id, room_id, code, seats, pos_x, pos_y, qr_token) VALUES (1,1,?,?,?,?,?)")
            ->execute(["T$i", 4, $x, $y, bin2hex(random_bytes(8))]);
    }
    // Dehors
    for ($i=1;$i<=6;$i++) {
        $x = 80 + (($i-1) % 3) * 220;
        $y = 100 + intdiv($i-1, 3) * 200;
        $pdo->prepare("INSERT INTO tables (tenant_id, room_id, code, seats, shape, pos_x, pos_y, qr_token) VALUES (1,2,?,?,?,?,?,?)")
            ->execute(["D$i", 2, 'round', $x, $y, bin2hex(random_bytes(8))]);
    }

    // Categorie
    $cats = [
        ['Antipasti','🥗','#10b981','kitchen'],
        ['Primi Piatti','🍝','#f59e0b','kitchen'],
        ['Secondi','🥩','#ef4444','kitchen'],
        ['Pizza','🍕','#dc2626','kitchen'],
        ['Panini','🥪','#f97316','kitchen'],
        ['Dolci','🍰','#ec4899','kitchen'],
        ['Birre','🍺','#eab308','bar'],
        ['Cocktail','🍸','#8b5cf6','bar'],
        ['Vini','🍷','#9f1239','bar'],
        ['Analcolici','🥤','#06b6d4','bar'],
        ['Caffetteria','☕','#78350f','bar'],
    ];
    foreach ($cats as $i => $c) {
        $pdo->prepare('INSERT INTO categories (tenant_id, name, icon, color, destination, sort) VALUES (1,?,?,?,?,?)')
            ->execute([$c[0], $c[1], $c[2], $c[3], $i]);
    }

    // Prodotti
    $prods = [
        // Antipasti (1)
        [1,'Tagliere Misto','Salumi e formaggi locali',14.00,5.50,10],
        [1,'Bruschette','Pomodoro e basilico (4pz)',6.50,1.20,10],
        [1,'Tartare di Manzo','Manzo crudo, capperi',12.00,4.50,10],
        // Primi (2)
        [2,'Spaghetti Carbonara','Guanciale, pecorino',11.00,2.80,10],
        [2,'Tagliatelle al Ragù','Ragù di carne 8h',12.00,3.20,10],
        [2,'Risotto ai Funghi','Porcini freschi',13.50,4.00,10],
        // Secondi (3)
        [3,'Tagliata di Manzo','300g, rucola e parmigiano',22.00,9.00,10],
        [3,'Branzino al Sale','Pesce fresco',24.00,11.00,10],
        // Pizza (4)
        [4,'Margherita','Pomodoro, mozzarella, basilico',8.00,1.80,10],
        [4,'Diavola','Salame piccante',10.00,2.50,10],
        [4,'Quattro Formaggi','Mozzarella, gorgonzola, fontina, parmigiano',11.00,3.10,10],
        // Panini (5)
        [5,'Hamburger Lollapalooza','200g manzo, cheddar, bacon',13.50,4.00,10],
        [5,'Club Sandwich','Pollo, bacon, uovo',10.00,3.00,10],
        // Dolci (6)
        [6,'Tiramisù','Della casa',6.00,1.20,10],
        [6,'Panna Cotta','Frutti di bosco',5.50,1.00,10],
        // Birre (7)
        [7,'Peroni 33cl','Bionda',4.00,1.20,22],
        [7,'Heineken 33cl','Bionda olandese',4.50,1.40,22],
        [7,'Moretti 0.4L','Spina',5.50,1.80,22],
        [7,'IPA Artigianale','Birra artigianale',7.00,2.50,22],
        // Cocktail (8)
        [8,'Spritz Aperol','Classico',7.00,1.80,22],
        [8,'Negroni','Gin, vermouth, campari',9.00,2.20,22],
        [8,'Gin Tonic','Gin premium',10.00,2.50,22],
        [8,'Mojito','Rum, lime, menta',9.00,2.00,22],
        [8,'Margarita','Tequila, lime',10.00,2.40,22],
        // Vini (9)
        [9,'Calice Rosso','Chianti DOCG',6.00,1.50,22],
        [9,'Calice Bianco','Falanghina',6.00,1.50,22],
        [9,'Bottiglia Rosso','Brunello di Montalcino',45.00,18.00,22],
        // Analcolici (10)
        [10,'Coca Cola','33cl',3.50,0.60,22],
        [10,'Acqua Naturale','75cl',2.50,0.30,22],
        [10,'Acqua Frizzante','75cl',2.50,0.30,22],
        [10,'Spremuta Arancia','Fresca',5.00,1.20,10],
        // Caffetteria (11)
        [11,'Espresso','',1.50,0.20,22],
        [11,'Cappuccino','',2.00,0.40,22],
        [11,'Caffè Americano','',2.50,0.40,22],
    ];
    foreach ($prods as $p) {
        $pdo->prepare('INSERT INTO products (tenant_id, category_id, name, description, price, cost, vat, available, track_stock, stock, stock_min, created_at) VALUES (1,?,?,?,?,?,?,1,1,?,?,?)')
            ->execute([$p[0],$p[1],$p[2],$p[3],$p[4],$p[5], rand(20,80), 5, date('Y-m-d H:i:s')]);
    }

    // Clienti demo
    $custs = [
        ['Giovanni Rossi','+39 333 1234567','giovanni@example.com','1980-05-12'],
        ['Maria Bianchi','+39 333 7654321','maria@example.com','1985-08-23'],
        ['Carlo Verdi','+39 333 9876543','carlo@example.com','1990-03-15'],
    ];
    foreach ($custs as $c) {
        $pdo->prepare('INSERT INTO customers (tenant_id, name, phone, email, birthday, points, total_spent, visits, created_at) VALUES (1,?,?,?,?,?,?,?,?)')
            ->execute([$c[0],$c[1],$c[2],$c[3], rand(50,500), rand(100,2000), rand(3,30), date('Y-m-d H:i:s')]);
    }

    // Fornitori demo
    $sups = [
        ['Ortofrutta Milano','Carlo','+39 02 0001111','orto@example.com'],
        ['Carni Pregiate Srl','Luigi','+39 02 0002222','carni@example.com'],
        ['Cantina Toscana','Anna','+39 055 0003333','vini@example.com'],
        ['Birrificio Nord','Paolo','+39 02 0004444','birra@example.com'],
    ];
    foreach ($sups as $s) {
        $pdo->prepare('INSERT INTO suppliers (tenant_id, name, contact, phone, email) VALUES (1,?,?,?,?)')->execute($s);
    }

    // Spese demo (ultimi 30 giorni)
    $expCats = [['Affitto',2500],['Bollette',450],['Stipendi',3500],['Marketing',300],['Manutenzione',150],['Tasse',800]];
    foreach ($expCats as $ec) {
        $pdo->prepare('INSERT INTO expenses (tenant_id, category, amount, description, date, created_at) VALUES (1,?,?,?,?,?)')
            ->execute([$ec[0], $ec[1], $ec[0].' mensile', date('Y-m-d', strtotime('-'.rand(1,28).' days')), date('Y-m-d H:i:s')]);
    }

    // Cash session aperta
    $pdo->prepare('INSERT INTO cash_sessions (tenant_id, user_id, opened_at, open_amount, status) VALUES (1,3,?,?,?)')
        ->execute([date('Y-m-d H:i:s'), 100.00, 'open']);

    echo "✅ Database installato con successo\n";
    echo "Login admin: admin@lollab.it / lollab2026\n";
} else {
    echo "Database già installato.\n";
}
