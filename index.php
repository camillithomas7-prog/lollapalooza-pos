<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/layout.php';

$page = $_GET['p'] ?? 'dashboard';

// Logout
if ($page === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: /index.php?p=login');
    exit;
}

// Login
if ($page === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        $st = db()->prepare('SELECT u.*, t.name AS tenant_name FROM users u LEFT JOIN tenants t ON t.id=u.tenant_id WHERE u.email=? AND u.active=1');
        $st->execute([$email]);
        $user = $st->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            $_SESSION['tenant_id'] = $user['tenant_id'];
            audit('login', 'users', $user['id']);
            $home = match ($user['role']) {
                'cucina' => 'kitchen',
                'bar' => 'bar',
                'cameriere' => 'waiter',
                'cassiere' => 'cash',
                'magazziniere' => 'inventory',
                default => 'dashboard',
            };
            header('Location: /index.php?p=' . $home);
            exit;
        }
        flash('error', 'Credenziali non valide');
    }
    require __DIR__ . '/pages/login.php';
    exit;
}

// PIN quick login (per camerieri/cassieri)
if ($page === 'pin') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pin = $_POST['pin'] ?? '';
        $st = db()->prepare('SELECT u.* FROM users u WHERE pin=? AND active=1');
        $st->execute([$pin]);
        $user = $st->fetch();
        if ($user) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            $_SESSION['tenant_id'] = $user['tenant_id'];
            $home = match ($user['role']) {
                'cucina' => 'kitchen', 'bar' => 'bar', 'cameriere' => 'waiter',
                'cassiere' => 'cash', 'magazziniere' => 'inventory',
                default => 'dashboard',
            };
            header('Location: /index.php?p=' . $home);
            exit;
        }
        flash('error', 'PIN non valido');
        header('Location: /index.php?p=pin'); exit;
    }
    require __DIR__ . '/pages/pin.php';
    exit;
}

// Public QR menu (pubblico, no auth) — usa ?p=carta per evitare conflitto con admin/menu
if ($page === 'qr' || $page === 'carta') {
    require __DIR__ . '/pages/public/qr.php';
    exit;
}

// Public booking form (pubblico, no auth)
if ($page === 'prenota' || $page === 'book') {
    require __DIR__ . '/pages/public/booking.php';
    exit;
}

// Auth required
require_auth();

// Pages
$pages = [
    'dashboard' => ['admin/dashboard.php', ['admin','manager']],
    'tables' => ['admin/tables.php', ['admin','manager','cassiere']],
    'tables_editor' => ['admin/tables_editor.php', ['admin','manager']],
    'orders' => ['admin/orders.php', ['admin','manager','cassiere','cameriere']],
    'order_view' => ['admin/order_view.php', ['admin','manager','cassiere','cameriere']],
    'menu' => ['admin/menu.php', ['admin','manager']],
    'product_edit' => ['admin/product_edit.php', ['admin','manager']],
    'cash' => ['admin/cash.php', ['admin','manager','cassiere']],
    'inventory' => ['admin/inventory.php', ['admin','manager','magazziniere']],
    'suppliers' => ['admin/suppliers.php', ['admin','manager','magazziniere']],
    'finance' => ['admin/finance.php', ['admin','manager']],
    'staff' => ['admin/staff.php', ['admin','manager']],
    'reservations' => ['admin/reservations.php', ['admin','manager']],
    'customers' => ['admin/customers.php', ['admin','manager']],
    'reports' => ['admin/reports.php', ['admin','manager']],
    'settings' => ['admin/settings.php', ['admin']],
    'qrcodes' => ['admin/qrcodes.php', ['admin','manager']],
    'waiter' => ['waiter/index.php', ['admin','manager','cameriere']],
    'waiter_table' => ['waiter/table.php', ['admin','manager','cameriere']],
    'kitchen' => ['kitchen/index.php', ['admin','manager','cucina']],
    'bar' => ['bar/index.php', ['admin','manager','bar']],
];

if (!isset($pages[$page])) {
    http_response_code(404);
    echo '<h1>404</h1>'; exit;
}

[$file, $roles] = $pages[$page];
require_role($roles);
require __DIR__ . '/pages/' . $file;
