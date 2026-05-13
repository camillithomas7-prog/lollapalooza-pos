-- Lollapalooza POS - Schema MySQL

CREATE TABLE IF NOT EXISTS tenants (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(190) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','manager','cassiere','cameriere','cucina','bar','magazziniere') NOT NULL,
    pin VARCHAR(10),
    phone VARCHAR(50),
    salary DECIMAL(10,2) DEFAULT 0,
    hourly_rate DECIMAL(10,2) DEFAULT 0,
    active TINYINT DEFAULT 1,
    created_at DATETIME,
    INDEX idx_users_tenant (tenant_id),
    INDEX idx_users_pin (pin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    name VARCHAR(255) NOT NULL,
    width INT DEFAULT 800,
    height INT DEFAULT 600,
    sort INT DEFAULT 0,
    INDEX idx_rooms_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    room_id INT,
    code VARCHAR(50) NOT NULL,
    seats INT DEFAULT 4,
    shape VARCHAR(20) DEFAULT 'square',
    pos_x INT DEFAULT 50,
    pos_y INT DEFAULT 50,
    width INT DEFAULT 80,
    height INT DEFAULT 80,
    status ENUM('free','occupied','reserved','dirty') DEFAULT 'free',
    merged_with INT,
    occupied_since DATETIME,
    qr_token VARCHAR(50),
    INDEX idx_tables_tenant (tenant_id),
    INDEX idx_tables_room (room_id),
    INDEX idx_tables_qr (qr_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    name VARCHAR(255) NOT NULL,
    icon VARCHAR(20),
    color VARCHAR(20) DEFAULT '#0ea5e9',
    destination ENUM('kitchen','bar','none') DEFAULT 'kitchen',
    sort INT DEFAULT 0,
    active TINYINT DEFAULT 1,
    translations TEXT,
    INDEX idx_cat_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(500),
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    cost DECIMAL(10,2) DEFAULT 0,
    vat DECIMAL(5,2) DEFAULT 22,
    sku VARCHAR(100),
    barcode VARCHAR(100),
    allergens VARCHAR(500),
    ingredients TEXT,
    available TINYINT DEFAULT 1,
    track_stock TINYINT DEFAULT 0,
    stock DECIMAL(12,3) DEFAULT 0,
    stock_min DECIMAL(12,3) DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'pz',
    sort INT DEFAULT 0,
    translations TEXT,
    destination VARCHAR(20) DEFAULT NULL,
    created_at DATETIME,
    INDEX idx_prod_cat (category_id, available),
    INDEX idx_prod_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    name VARCHAR(255) NOT NULL,
    price_delta DECIMAL(10,2) DEFAULT 0,
    INDEX idx_var_prod (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_extras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) DEFAULT 0,
    INDEX idx_ext_prod (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    name VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(190),
    birthday DATE,
    notes TEXT,
    points INT DEFAULT 0,
    fidelity_card VARCHAR(50),
    total_spent DECIMAL(12,2) DEFAULT 0,
    visits INT DEFAULT 0,
    created_at DATETIME,
    INDEX idx_cust_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    code VARCHAR(50),
    table_id INT,
    waiter_id INT,
    customer_id INT,
    type ENUM('dine_in','takeaway','delivery','bar') DEFAULT 'dine_in',
    status ENUM('open','sent','preparing','ready','served','closed','cancelled') DEFAULT 'open',
    guests INT DEFAULT 1,
    subtotal DECIMAL(12,2) DEFAULT 0,
    discount DECIMAL(12,2) DEFAULT 0,
    tax DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) DEFAULT 0,
    paid DECIMAL(12,2) DEFAULT 0,
    notes TEXT,
    created_at DATETIME,
    closed_at DATETIME,
    INDEX idx_orders_tenant_status (tenant_id, status),
    INDEX idx_orders_table (table_id, status),
    INDEX idx_orders_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    name VARCHAR(255) NOT NULL,
    qty DECIMAL(10,3) NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    cost DECIMAL(10,2) DEFAULT 0,
    notes VARCHAR(500),
    variants TEXT,
    extras TEXT,
    destination VARCHAR(20) DEFAULT 'kitchen',
    status ENUM('draft','sent','preparing','ready','served','cancelled') DEFAULT 'sent',
    sent_at DATETIME,
    ready_at DATETIME,
    served_at DATETIME,
    INDEX idx_items_order (order_id),
    INDEX idx_items_status (status, destination)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    order_id INT,
    method ENUM('cash','card','pos','voucher','transfer','split') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    user_id INT,
    cash_session_id INT,
    notes VARCHAR(500),
    created_at DATETIME,
    INDEX idx_pay_order (order_id),
    INDEX idx_pay_session (cash_session_id),
    INDEX idx_pay_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cash_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    user_id INT,
    opened_at DATETIME,
    closed_at DATETIME,
    open_amount DECIMAL(12,2) DEFAULT 0,
    close_amount DECIMAL(12,2),
    expected DECIMAL(12,2) DEFAULT 0,
    diff DECIMAL(12,2) DEFAULT 0,
    notes VARCHAR(500),
    status ENUM('open','closed') DEFAULT 'open',
    INDEX idx_cs_tenant_status (tenant_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cash_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cash_session_id INT,
    type ENUM('in','out'),
    amount DECIMAL(12,2),
    reason VARCHAR(255),
    user_id INT,
    created_at DATETIME,
    INDEX idx_cm_session (cash_session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    category VARCHAR(100),
    amount DECIMAL(12,2),
    description VARCHAR(500),
    date DATE,
    user_id INT,
    created_at DATETIME,
    INDEX idx_exp_tenant_date (tenant_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    name VARCHAR(255),
    contact VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(190),
    notes TEXT,
    INDEX idx_sup_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    product_id INT,
    type ENUM('in','out','adjust','sale'),
    qty DECIMAL(12,3),
    cost DECIMAL(10,2) DEFAULT 0,
    supplier_id INT,
    notes VARCHAR(500),
    user_id INT,
    created_at DATETIME,
    INDEX idx_sm_product (product_id),
    INDEX idx_sm_tenant (tenant_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    customer_name VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(190),
    guests INT DEFAULT 2,
    date DATE,
    time TIME,
    table_id INT,
    notes TEXT,
    status ENUM('pending','confirmed','seated','no_show','cancelled') DEFAULT 'confirmed',
    created_at DATETIME,
    INDEX idx_res_tenant_date (tenant_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    user_id INT,
    date DATE,
    start_time TIME,
    end_time TIME,
    notes VARCHAR(500),
    INDEX idx_shifts_user_date (user_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    user_id INT,
    clock_in DATETIME,
    clock_out DATETIME,
    hours DECIMAL(6,2),
    INDEX idx_att_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    user_id INT,
    role VARCHAR(50),
    type VARCHAR(50),
    title VARCHAR(255),
    body TEXT,
    link VARCHAR(500),
    read_at DATETIME,
    created_at DATETIME,
    INDEX idx_notif_tenant_read (tenant_id, read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100),
    entity VARCHAR(100),
    entity_id INT,
    data TEXT,
    created_at DATETIME,
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
    tenant_id INT,
    `key` VARCHAR(100),
    value TEXT,
    PRIMARY KEY (tenant_id, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS print_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    order_id INT,
    destination VARCHAR(30) DEFAULT 'kitchen',
    payload LONGTEXT,
    status VARCHAR(20) DEFAULT 'pending',
    attempts INT DEFAULT 0,
    error TEXT,
    created_at DATETIME,
    printed_at DATETIME,
    INDEX idx_print_jobs_status (tenant_id, destination, status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
