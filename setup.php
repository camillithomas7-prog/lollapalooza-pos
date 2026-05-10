<?php
// Lollapalooza POS - Setup endpoint (uso una tantum)
// CANCELLARE questo file dopo il primo install per sicurezza.
// Accesso: https://tuo-dominio/setup.php?token=lp-2026-xK9pQ7zM

$SETUP_TOKEN = 'lp-2026-xK9pQ7zM';

if (($_GET['token'] ?? '') !== $SETUP_TOKEN) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("403 Forbidden — token mancante o errato.\n");
}

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Setup Lollapalooza POS</title>";
echo "<style>body{font-family:monospace;background:#0a0a13;color:#e2e8f0;padding:30px;line-height:1.6}h1{color:#8b5cf6}.ok{color:#10b981}.err{color:#ef4444}.warn{color:#f59e0b}pre{background:rgba(255,255,255,0.05);padding:10px;border-radius:6px;overflow:auto}.btn{display:inline-block;background:linear-gradient(135deg,#7c3aed,#8b5cf6);color:white;padding:12px 24px;border-radius:8px;text-decoration:none;margin-top:10px}</style></head><body>";
echo "<h1>🍽 Lollapalooza POS — Setup</h1>";

require_once __DIR__ . '/includes/config.php';

echo "<h3>1️⃣ Configurazione</h3><pre>";
echo "Driver:   " . DB_DRIVER . "\n";
echo "Host:     " . DB_HOST . "\n";
echo "Database: " . DB_NAME . "\n";
echo "User:     " . DB_USER . "\n";
echo "</pre>";

if (DB_DRIVER === 'sqlite') {
    echo "<p class='warn'>⚠ Stai usando SQLite. Per produzione su Hostinger dovresti configurare MySQL in <code>includes/config.local.php</code>.</p>";
}

echo "<h3>2️⃣ Connessione DB</h3><pre>";
try {
    $pdo = db();
    echo "<span class='ok'>✓ Connesso al database</span>\n";
} catch (Throwable $e) {
    echo "<span class='err'>✗ Errore: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "</pre><p class='err'>Verifica le credenziali in <code>includes/config.local.php</code>.</p></body></html>";
    exit;
}
echo "</pre>";

echo "<h3>3️⃣ Schema tabelle</h3><pre>";
$schemaFile = DB_DRIVER === 'mysql' ? 'schema_mysql.sql' : 'schema.sql';
$schema = file_get_contents(__DIR__ . '/includes/' . $schemaFile);
$schema = preg_replace('/^\s*--.*$/m', '', $schema); // strip SQL comments
$statements = array_filter(array_map('trim', preg_split('/;\s*$/m', $schema)));
$created = 0;
foreach ($statements as $stmt) {
    if ($stmt) {
        try { $pdo->exec($stmt); $created++; }
        catch (PDOException $e) { echo "<span class='err'>✗ " . htmlspecialchars($e->getMessage()) . "</span>\n"; }
    }
}
echo "<span class='ok'>✓ $created statement eseguiti (schema $schemaFile)</span>\n";
echo "</pre>";

echo "<h3>4️⃣ Dati demo</h3><pre>";
$check = $pdo->query('SELECT COUNT(*) c FROM tenants')->fetch();
if ($check['c'] > 0) {
    echo "<span class='warn'>⚠ Database già popolato — skip seed</span>\n";
} else {
    // Seed completo
    $pdo->prepare("INSERT INTO tenants (name, slug, address, phone, vat, currency, locale, color_primary, created_at) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute(['Lollapalooza', 'lollapalooza', 'Via Roma 1, Milano', '+39 02 1234567', 'IT12345678901', 'EUR', 'it', '#0f172a', date('Y-m-d H:i:s')]);
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
    $pdo->prepare("INSERT INTO rooms (tenant_id, name, width, height, sort) VALUES (1,'Sala Principale',900,600,0)")->execute();
    $pdo->prepare("INSERT INTO rooms (tenant_id, name, width, height, sort) VALUES (1,'Dehors',900,500,1)")->execute();
    for ($i=1;$i<=8;$i++) {
        $x = 80 + (($i-1) % 4) * 180;
        $y = 80 + intdiv($i-1, 4) * 200;
        $pdo->prepare("INSERT INTO tables (tenant_id, room_id, code, seats, pos_x, pos_y, qr_token) VALUES (1,1,?,?,?,?,?)")
            ->execute(["T$i", 4, $x, $y, bin2hex(random_bytes(8))]);
    }
    for ($i=1;$i<=6;$i++) {
        $x = 80 + (($i-1) % 3) * 220;
        $y = 100 + intdiv($i-1, 3) * 200;
        $pdo->prepare("INSERT INTO tables (tenant_id, room_id, code, seats, shape, pos_x, pos_y, qr_token) VALUES (1,2,?,?,?,?,?,?)")
            ->execute(["D$i", 2, 'round', $x, $y, bin2hex(random_bytes(8))]);
    }
    $cats = [
        ['Antipasti','🥗','#10b981','kitchen'],['Primi Piatti','🍝','#f59e0b','kitchen'],
        ['Secondi','🥩','#ef4444','kitchen'],['Pizza','🍕','#dc2626','kitchen'],
        ['Panini','🥪','#f97316','kitchen'],['Dolci','🍰','#ec4899','kitchen'],
        ['Birre','🍺','#eab308','bar'],['Cocktail','🍸','#8b5cf6','bar'],
        ['Vini','🍷','#9f1239','bar'],['Analcolici','🥤','#06b6d4','bar'],
        ['Caffetteria','☕','#78350f','bar'],
    ];
    foreach ($cats as $i => $c) {
        $pdo->prepare('INSERT INTO categories (tenant_id, name, icon, color, destination, sort) VALUES (1,?,?,?,?,?)')
            ->execute([$c[0], $c[1], $c[2], $c[3], $i]);
    }
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
    $pdo->prepare('INSERT INTO cash_sessions (tenant_id, user_id, opened_at, open_amount, status) VALUES (1,3,?,?,?)')
        ->execute([date('Y-m-d H:i:s'), 100.00, 'open']);
    echo "<span class='ok'>✓ Seed completato: 1 tenant, 8 utenti, 14 tavoli, 11 categorie, 34 prodotti</span>\n";
}
echo "</pre>";

echo "<h3>✅ Setup completato!</h3>";
echo "<p><strong class='warn'>⚠ Per sicurezza, cancella subito questo file <code>setup.php</code> dal File Manager.</strong></p>";
echo "<p>Login admin: <code>admin@lollab.it</code> / <code>lollab2026</code> (PIN <code>0000</code>)</p>";
echo "<a class='btn' href='/index.php?p=login'>→ Vai al login</a>";
echo "</body></html>";
