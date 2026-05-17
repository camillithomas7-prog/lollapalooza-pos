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

// Migrazione: se esiste un tenant ancora impostato a EUR (default vecchio), passa a EGP.
// Idempotente: una volta che è EGP non fa più niente.
try {
    $upd = $pdo->prepare("UPDATE tenants SET currency='EGP' WHERE currency='EUR' OR currency='' OR currency IS NULL");
    $upd->execute();
    if ($upd->rowCount() > 0) echo "✓ Tenant aggiornati a valuta EGP (Lira Egiziana): " . $upd->rowCount() . "\n";
} catch (Throwable $e) {}

// Migrazione: aggiunge gift_card_id e discount_percent a orders
try {
    if (DB_DRIVER === 'mysql') {
        $has = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'gift_card_id'")->fetchColumn();
        if (!$has) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN gift_card_id INT NULL");
            $pdo->exec("ALTER TABLE orders ADD COLUMN discount_percent DECIMAL(5,2) DEFAULT 0");
            $pdo->exec("ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0");
            echo "✓ Aggiunte colonne gift_card_id, discount_percent, discount_amount a orders\n";
        }
    } else {
        $cols = $pdo->query("PRAGMA table_info(orders)")->fetchAll(PDO::FETCH_ASSOC);
        $names = array_column($cols, 'name');
        if (!in_array('gift_card_id', $names))    $pdo->exec("ALTER TABLE orders ADD COLUMN gift_card_id INTEGER");
        if (!in_array('discount_percent', $names)) $pdo->exec("ALTER TABLE orders ADD COLUMN discount_percent REAL DEFAULT 0");
        if (!in_array('discount_amount', $names))  $pdo->exec("ALTER TABLE orders ADD COLUMN discount_amount REAL DEFAULT 0");
    }
} catch (Throwable $e) { /* gestito */ }

// Migrazione: aggiunge orari validità alla tabella gift_cards
try {
    if (DB_DRIVER === 'mysql') {
        $has = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'gift_cards' AND column_name = 'valid_from_hour'")->fetchColumn();
        if (!$has) {
            $pdo->exec("ALTER TABLE gift_cards ADD COLUMN valid_from_hour TIME DEFAULT '20:00:00'");
            $pdo->exec("ALTER TABLE gift_cards ADD COLUMN valid_to_hour TIME DEFAULT '03:00:00'");
            echo "✓ Aggiunte colonne valid_from_hour, valid_to_hour a gift_cards\n";
        }
    } else {
        $cols = $pdo->query("PRAGMA table_info(gift_cards)")->fetchAll(PDO::FETCH_ASSOC);
        $names = array_column($cols, 'name');
        if (!in_array('valid_from_hour', $names)) $pdo->exec("ALTER TABLE gift_cards ADD COLUMN valid_from_hour TEXT DEFAULT '20:00:00'");
        if (!in_array('valid_to_hour', $names))   $pdo->exec("ALTER TABLE gift_cards ADD COLUMN valid_to_hour TEXT DEFAULT '03:00:00'");
    }
} catch (Throwable $e) { /* tabella non ancora esistente — gestito dal blocco successivo */ }

// Migrazione: crea tabella gift_cards se mancante
try {
    if (DB_DRIVER === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS gift_cards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT,
            code VARCHAR(40) NOT NULL UNIQUE,
            customer_name VARCHAR(255) NOT NULL,
            phone VARCHAR(50),
            valid_date DATE NOT NULL,
            percent DECIMAL(5,2) DEFAULT 10.00,
            note VARCHAR(255),
            status VARCHAR(20) DEFAULT 'issued',
            used_at DATETIME NULL,
            used_by_user INT NULL,
            issued_by_user INT NULL,
            created_at DATETIME,
            INDEX idx_gc_code (code),
            INDEX idx_gc_date (tenant_id, valid_date, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS gift_cards (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER,
            code TEXT NOT NULL UNIQUE,
            customer_name TEXT NOT NULL,
            phone TEXT,
            valid_date TEXT NOT NULL,
            percent REAL DEFAULT 10.0,
            note TEXT,
            status TEXT DEFAULT 'issued',
            used_at TEXT,
            used_by_user INTEGER,
            issued_by_user INTEGER,
            created_at TEXT
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_gc_code ON gift_cards(code)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_gc_date ON gift_cards(tenant_id, valid_date, status)");
    }
} catch (Throwable $e) { echo "⚠ Errore migrazione gift_cards: " . $e->getMessage() . "\n"; }

// Migrazione: aggiunge return_when e return_status a transfers
try {
    if (DB_DRIVER === 'mysql') {
        $has = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'transfers' AND column_name = 'return_when'")->fetchColumn();
        if (!$has) {
            $pdo->exec("ALTER TABLE transfers ADD COLUMN return_when DATETIME NULL");
            $pdo->exec("ALTER TABLE transfers ADD COLUMN return_status VARCHAR(20) DEFAULT 'scheduled'");
            echo "✓ Aggiunte colonne return_when, return_status a transfers\n";
        }
    } else {
        $cols = $pdo->query("PRAGMA table_info(transfers)")->fetchAll(PDO::FETCH_ASSOC);
        $names = array_column($cols, 'name');
        if (!in_array('return_when', $names)) $pdo->exec("ALTER TABLE transfers ADD COLUMN return_when TEXT");
        if (!in_array('return_status', $names)) $pdo->exec("ALTER TABLE transfers ADD COLUMN return_status TEXT DEFAULT 'scheduled'");
    }
} catch (Throwable $e) { /* tabella ancora non esiste — verrà gestita dal blocco successivo */ }

// Migrazione: crea tabella transfers se mancante
try {
    if (DB_DRIVER === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS transfers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT,
            customer_name VARCHAR(255) NOT NULL,
            phone VARCHAR(50),
            language VARCHAR(8) DEFAULT 'it',
            direction VARCHAR(20) DEFAULT 'arrival',
            pickup_when DATETIME NOT NULL,
            return_when DATETIME NULL,
            pickup_location VARCHAR(255),
            pickup_address VARCHAR(500),
            dropoff_location VARCHAR(255),
            dropoff_address VARCHAR(500),
            passengers INT DEFAULT 1,
            luggage INT DEFAULT 0,
            flight_no VARCHAR(50),
            vehicle VARCHAR(100),
            driver_name VARCHAR(100),
            price_egp DECIMAL(10,2) DEFAULT 0,
            reservation_id INT,
            notes TEXT,
            status VARCHAR(20) DEFAULT 'scheduled',
            return_status VARCHAR(20) DEFAULT 'scheduled',
            created_at DATETIME,
            updated_at DATETIME,
            INDEX idx_transfers_when (tenant_id, pickup_when),
            INDEX idx_transfers_status (tenant_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS transfers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER,
            customer_name TEXT NOT NULL,
            phone TEXT,
            language TEXT DEFAULT 'it',
            direction TEXT DEFAULT 'arrival',
            pickup_when TEXT NOT NULL,
            return_when TEXT,
            pickup_location TEXT,
            pickup_address TEXT,
            dropoff_location TEXT,
            dropoff_address TEXT,
            passengers INTEGER DEFAULT 1,
            luggage INTEGER DEFAULT 0,
            flight_no TEXT,
            vehicle TEXT,
            driver_name TEXT,
            price_egp REAL DEFAULT 0,
            reservation_id INTEGER,
            notes TEXT,
            status TEXT DEFAULT 'scheduled',
            return_status TEXT DEFAULT 'scheduled',
            created_at TEXT,
            updated_at TEXT
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_transfers_when ON transfers(tenant_id, pickup_when)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_transfers_status ON transfers(tenant_id, status)");
    }
} catch (Throwable $e) { echo "⚠ Errore migrazione transfers: " . $e->getMessage() . "\n"; }

// Migrazione: aggiunge products.destination se mancante (override categoria)
try {
    if (DB_DRIVER === 'mysql') {
        $check = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'destination'")->fetchColumn();
    } else {
        $cols = $pdo->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_ASSOC);
        $check = 0;
        foreach ($cols as $c) if ($c['name'] === 'destination') { $check = 1; break; }
    }
    if (!$check) {
        $pdo->exec("ALTER TABLE products ADD COLUMN destination " . (DB_DRIVER === 'mysql' ? "VARCHAR(20)" : "TEXT") . " DEFAULT NULL");
        echo "✓ Aggiunta colonna products.destination (override per prodotto)\n";
    }
} catch (Throwable $e) { echo "⚠ Errore migrazione destination: " . $e->getMessage() . "\n"; }

// Demo tenant
$st = $pdo->prepare('SELECT COUNT(*) c FROM tenants');
$st->execute();
if ($st->fetch()['c'] == 0) {
    $pdo->prepare("INSERT INTO tenants (name, slug, address, phone, vat, currency, locale, color_primary, created_at) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute(['Lollapalooza', 'lollapalooza', 'Sharm El Sheikh, Egitto', '+20 100 000 0000', '', 'EGP', 'it', '#0f172a', date('Y-m-d H:i:s')]);

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

// === Migrazione traduzioni menu (idempotente) ============================
// Applica traduzioni hard-coded (ar/en/es/fr/de) ai piatti e categorie
// con nomi standard, SENZA chiamare alcuna API esterna. Lavora per match
// case-insensitive sul nome italiano. Aggiunge solo le lingue mancanti,
// non sovrascrive traduzioni già inserite dall'utente.
$seed = require __DIR__ . '/menu_translations_seed.php';
$applyTranslations = function(string $table, array $dict) use ($pdo) {
    $applied = 0;
    $st = $pdo->prepare("SELECT id, name, translations FROM $table");
    $st->execute();
    foreach ($st->fetchAll() as $row) {
        $key = mb_strtolower(trim((string)$row['name']));
        if (!isset($dict[$key])) continue;
        $cur = $row['translations'] ? (json_decode($row['translations'], true) ?: []) : [];
        $changed = false;
        foreach ($dict[$key] as $lang => $fields) {
            foreach ($fields as $field => $value) {
                if (empty($cur[$lang][$field]) && $value !== '') {
                    $cur[$lang][$field] = $value;
                    $changed = true;
                }
            }
        }
        if ($changed) {
            $pdo->prepare("UPDATE $table SET translations = ? WHERE id = ?")
                ->execute([json_encode($cur, JSON_UNESCAPED_UNICODE), $row['id']]);
            $applied++;
        }
    }
    return $applied;
};

try {
    $a = $applyTranslations('products', $seed['products']);
    if ($a > 0) echo "✓ Traduzioni applicate a $a piatti (ar/en/es/fr/de)\n";
    $b = $applyTranslations('categories', $seed['categories']);
    if ($b > 0) echo "✓ Traduzioni applicate a $b categorie (ar/en/es/fr/de)\n";
} catch (Throwable $e) {
    echo "⚠ Errore traduzioni: " . $e->getMessage() . "\n";
}

// === Auto-registrazione funnel dalla cartella /funnels (idempotente) =======
// Ogni sottocartella di /funnels con un index.html è un funnel pubblico.
// I file viaggiano via git; questa scansione crea la riga DB mancante
// (necessaria perché SQLite locale e MySQL Hostinger non si sincronizzano).
try {
    $funnelsDir = BASE_PATH . '/funnels';
    if (is_dir($funnelsDir)) {
        foreach (glob($funnelsDir . '/*', GLOB_ONLYDIR) as $fdir) {
            $slug = basename($fdir);
            if (!is_file("$fdir/index.html")) continue;
            $ex = $pdo->prepare('SELECT id FROM funnels WHERE slug = ?');
            $ex->execute([$slug]);
            if ($ex->fetchColumn()) continue;
            $meta = is_file("$fdir/funnel.json")
                ? (json_decode((string)file_get_contents("$fdir/funnel.json"), true) ?: [])
                : [];
            $now = date('Y-m-d H:i:s');
            $pdo->prepare('INSERT INTO funnels (tenant_id, slug, title, description, active, views, created_at, updated_at) VALUES (1,?,?,?,1,0,?,?)')
                ->execute([$slug, $meta['title'] ?? $slug, $meta['description'] ?? '', $meta['created_at'] ?? $now, $now]);
            echo "✓ Funnel registrato: $slug\n";
        }
    }
} catch (Throwable $e) {
    echo "⚠ Errore registrazione funnel: " . $e->getMessage() . "\n";
}

// Migrazione: crea tabella events (calendario serate/eventi a tema)
try {
    if (DB_DRIVER === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(50) DEFAULT 'tema',
            event_date DATE NULL,
            weekday TINYINT NULL,
            start_time TIME DEFAULT '20:00:00',
            end_time TIME DEFAULT '03:00:00',
            is_recurring TINYINT(1) DEFAULT 0,
            active TINYINT(1) DEFAULT 1,
            color VARCHAR(20) DEFAULT 'amber',
            notes TEXT,
            created_at DATETIME,
            updated_at DATETIME,
            INDEX idx_events_date (tenant_id, event_date),
            INDEX idx_events_weekday (tenant_id, weekday, is_recurring)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER,
            title TEXT NOT NULL,
            description TEXT,
            category TEXT DEFAULT 'tema',
            event_date TEXT,
            weekday INTEGER,
            start_time TEXT DEFAULT '20:00:00',
            end_time TEXT DEFAULT '03:00:00',
            is_recurring INTEGER DEFAULT 0,
            active INTEGER DEFAULT 1,
            color TEXT DEFAULT 'amber',
            notes TEXT,
            created_at TEXT,
            updated_at TEXT
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_events_date ON events(tenant_id, event_date)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_events_weekday ON events(tenant_id, weekday, is_recurring)");
    }
} catch (Throwable $e) { echo "⚠ Errore migrazione events: " . $e->getMessage() . "\n"; }

// Seed eventi settimanali ricorrenti (solo se tabella vuota)
try {
    $cnt = (int) $pdo->query("SELECT COUNT(*) FROM events WHERE tenant_id=1")->fetchColumn();
    if ($cnt === 0) {
        $now = date('Y-m-d H:i:s');
        // weekday: 0=Dom, 1=Lun, 2=Mar, 3=Mer, 4=Gio, 5=Ven, 6=Sab (compat con JS getDay)
        $seeds = [
            ['Serata Orientale con ballerina', 'Spettacolo di danza del ventre',           'spettacolo',  2, '21:00:00', '03:00:00', 'rose'],
            ['Anni 60/70 — Serata Italiana',   'Musica italiana intramontabile',          'tema',        3, '21:00:00', '03:00:00', 'amber'],
            ['Serata Latino',                  'Salsa, bachata, reggaeton',                'tema',        5, '22:00:00', '03:00:00', 'emerald'],
            ['Disco + Sassofonista a cena',    'Sassofonista durante la cena, poi disco', 'musica_live', 6, '20:00:00', '03:00:00', 'sky'],
        ];
        $stmt = $pdo->prepare("INSERT INTO events (tenant_id, title, description, category, weekday, start_time, end_time, is_recurring, active, color, created_at, updated_at) VALUES (1,?,?,?,?,?,?,1,1,?,?,?)");
        foreach ($seeds as $s) {
            $stmt->execute([$s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $now, $now]);
        }
        echo "✓ Seed eventi ricorrenti (" . count($seeds) . ")\n";
    }
} catch (Throwable $e) { echo "⚠ Errore seed events: " . $e->getMessage() . "\n"; }
