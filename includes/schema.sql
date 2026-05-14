-- Lollab POS - Schema completo

CREATE TABLE IF NOT EXISTS tenants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE,
    address TEXT,
    phone TEXT,
    vat TEXT,
    currency TEXT DEFAULT 'EUR',
    locale TEXT DEFAULT 'it',
    logo TEXT,
    color_primary TEXT DEFAULT '#0f172a',
    settings TEXT,
    active INTEGER DEFAULT 1,
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER REFERENCES tenants(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('admin','manager','cassiere','cameriere','cucina','bar','magazziniere')),
    pin TEXT,
    phone TEXT,
    salary REAL DEFAULT 0,
    hourly_rate REAL DEFAULT 0,
    active INTEGER DEFAULT 1,
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER REFERENCES tenants(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    width INTEGER DEFAULT 800,
    height INTEGER DEFAULT 600,
    sort INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER REFERENCES tenants(id) ON DELETE CASCADE,
    room_id INTEGER REFERENCES rooms(id) ON DELETE SET NULL,
    code TEXT NOT NULL,
    seats INTEGER DEFAULT 4,
    shape TEXT DEFAULT 'square',
    pos_x INTEGER DEFAULT 50,
    pos_y INTEGER DEFAULT 50,
    width INTEGER DEFAULT 80,
    height INTEGER DEFAULT 80,
    status TEXT DEFAULT 'free' CHECK(status IN ('free','occupied','reserved','dirty')),
    merged_with INTEGER,
    occupied_since TEXT,
    qr_token TEXT
);

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER REFERENCES tenants(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    icon TEXT,
    color TEXT DEFAULT '#0ea5e9',
    destination TEXT DEFAULT 'kitchen' CHECK(destination IN ('kitchen','bar','none')),
    sort INTEGER DEFAULT 0,
    active INTEGER DEFAULT 1,
    translations TEXT
);

CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER REFERENCES tenants(id) ON DELETE CASCADE,
    category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    name TEXT NOT NULL,
    description TEXT,
    image TEXT,
    price REAL NOT NULL DEFAULT 0,
    cost REAL DEFAULT 0,
    vat REAL DEFAULT 22,
    sku TEXT,
    barcode TEXT,
    allergens TEXT,
    ingredients TEXT,
    available INTEGER DEFAULT 1,
    track_stock INTEGER DEFAULT 0,
    stock REAL DEFAULT 0,
    stock_min REAL DEFAULT 0,
    unit TEXT DEFAULT 'pz',
    sort INTEGER DEFAULT 0,
    translations TEXT,
    -- destination override: se NULL eredita dalla categoria, altrimenti
    -- forza l'invio a 'kitchen' / 'bar' / 'none' indipendentemente.
    destination TEXT DEFAULT NULL,
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS product_variants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    price_delta REAL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS product_extras (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    price REAL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER REFERENCES tenants(id) ON DELETE CASCADE,
    code TEXT,
    table_id INTEGER REFERENCES tables(id) ON DELETE SET NULL,
    waiter_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    customer_id INTEGER REFERENCES customers(id) ON DELETE SET NULL,
    type TEXT DEFAULT 'dine_in' CHECK(type IN ('dine_in','takeaway','delivery','bar')),
    status TEXT DEFAULT 'open' CHECK(status IN ('open','sent','preparing','ready','served','closed','cancelled')),
    guests INTEGER DEFAULT 1,
    subtotal REAL DEFAULT 0,
    discount REAL DEFAULT 0,
    tax REAL DEFAULT 0,
    total REAL DEFAULT 0,
    paid REAL DEFAULT 0,
    notes TEXT,
    created_at TEXT,
    closed_at TEXT
);

CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER REFERENCES orders(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(id) ON DELETE SET NULL,
    name TEXT NOT NULL,
    qty REAL NOT NULL DEFAULT 1,
    price REAL NOT NULL DEFAULT 0,
    cost REAL DEFAULT 0,
    notes TEXT,
    variants TEXT,
    extras TEXT,
    destination TEXT DEFAULT 'kitchen',
    status TEXT DEFAULT 'sent' CHECK(status IN ('draft','sent','preparing','ready','served','cancelled')),
    sent_at TEXT,
    ready_at TEXT,
    served_at TEXT
);

CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    order_id INTEGER REFERENCES orders(id) ON DELETE CASCADE,
    method TEXT NOT NULL CHECK(method IN ('cash','card','pos','voucher','transfer','split')),
    amount REAL NOT NULL,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    cash_session_id INTEGER,
    notes TEXT,
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS cash_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    opened_at TEXT,
    closed_at TEXT,
    open_amount REAL DEFAULT 0,
    close_amount REAL,
    expected REAL DEFAULT 0,
    diff REAL DEFAULT 0,
    notes TEXT,
    status TEXT DEFAULT 'open' CHECK(status IN ('open','closed'))
);

CREATE TABLE IF NOT EXISTS cash_movements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cash_session_id INTEGER REFERENCES cash_sessions(id) ON DELETE CASCADE,
    type TEXT CHECK(type IN ('in','out')),
    amount REAL,
    reason TEXT,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    category TEXT,
    amount REAL,
    description TEXT,
    date TEXT,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    name TEXT,
    contact TEXT,
    phone TEXT,
    email TEXT,
    notes TEXT
);

CREATE TABLE IF NOT EXISTS stock_movements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    type TEXT CHECK(type IN ('in','out','adjust','sale')),
    qty REAL,
    cost REAL DEFAULT 0,
    supplier_id INTEGER,
    notes TEXT,
    user_id INTEGER,
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS reservations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    customer_name TEXT,
    phone TEXT,
    email TEXT,
    guests INTEGER DEFAULT 2,
    date TEXT,
    time TEXT,
    table_id INTEGER REFERENCES tables(id) ON DELETE SET NULL,
    notes TEXT,
    status TEXT DEFAULT 'confirmed' CHECK(status IN ('pending','confirmed','seated','no_show','cancelled')),
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    name TEXT,
    phone TEXT,
    email TEXT,
    birthday TEXT,
    notes TEXT,
    points INTEGER DEFAULT 0,
    fidelity_card TEXT,
    total_spent REAL DEFAULT 0,
    visits INTEGER DEFAULT 0,
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS shifts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    date TEXT,
    start_time TEXT,
    end_time TEXT,
    notes TEXT
);

CREATE TABLE IF NOT EXISTS attendance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    clock_in TEXT,
    clock_out TEXT,
    hours REAL
);

CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    user_id INTEGER,
    role TEXT,
    type TEXT,
    title TEXT,
    body TEXT,
    link TEXT,
    read_at TEXT,
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT,
    entity TEXT,
    entity_id INTEGER,
    data TEXT,
    created_at TEXT
);

CREATE TABLE IF NOT EXISTS settings (
    tenant_id INTEGER,
    key TEXT,
    value TEXT,
    PRIMARY KEY (tenant_id, key)
);

CREATE TABLE IF NOT EXISTS print_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    order_id INTEGER REFERENCES orders(id) ON DELETE CASCADE,
    destination TEXT DEFAULT 'kitchen',
    payload TEXT,
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending','printed','failed')),
    attempts INTEGER DEFAULT 0,
    error TEXT,
    created_at TEXT,
    printed_at TEXT
);
CREATE INDEX IF NOT EXISTS idx_print_jobs_status ON print_jobs(tenant_id, destination, status, created_at);

CREATE INDEX IF NOT EXISTS idx_orders_tenant_status ON orders(tenant_id, status);
CREATE INDEX IF NOT EXISTS idx_orders_table ON orders(table_id, status);
CREATE INDEX IF NOT EXISTS idx_items_order ON order_items(order_id);
CREATE INDEX IF NOT EXISTS idx_items_status ON order_items(status, destination);
CREATE INDEX IF NOT EXISTS idx_products_cat ON products(category_id, available);
CREATE INDEX IF NOT EXISTS idx_payments_order ON payments(order_id);
CREATE INDEX IF NOT EXISTS idx_stock_product ON stock_movements(product_id);

CREATE TABLE IF NOT EXISTS funnels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER DEFAULT 1,
    slug TEXT NOT NULL,
    title TEXT NOT NULL,
    description TEXT,
    active INTEGER DEFAULT 1,
    views INTEGER DEFAULT 0,
    created_at TEXT,
    updated_at TEXT
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_funnels_slug ON funnels(slug);
