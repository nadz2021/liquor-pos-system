<?php
declare(strict_types=1);

require __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Env;
use App\Core\DB;

Env::load(__DIR__ . '/../.env');

$pdo = DB::pdo();

function addColumnIfMissing(PDO $pdo, string $table, string $column, string $ddl): void {
  $stmt = $pdo->prepare("
    SELECT COUNT(*) AS c
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = ?
      AND COLUMN_NAME = ?
  ");
  $stmt->execute([$table, $column]);
  $row = $stmt->fetch();
  $exists = (int)($row['c'] ?? 0) > 0;

  if (!$exists) {
    $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$ddl}");
    echo "Added column {$table}.{$column}\n";
  }
}

/*
|--------------------------------------------------------------------------
| CREATE TABLES IN CORRECT ORDER
|--------------------------------------------------------------------------
*/

$pdo->exec("
CREATE TABLE IF NOT EXISTS categories (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL UNIQUE,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS subcategories (
  id INT PRIMARY KEY AUTO_INCREMENT,
  category_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_category_subcategory (category_id, name),
  CONSTRAINT fk_subcategories_category
    FOREIGN KEY (category_id) REFERENCES categories(id)
    ON DELETE CASCADE
)
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS suppliers (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  phone VARCHAR(50) NULL,
  email VARCHAR(120) NULL,
  address VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  username VARCHAR(60) NOT NULL UNIQUE,
  pin_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin','admin','cashier','owner','manager') NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS settings (
  id INT PRIMARY KEY AUTO_INCREMENT,
  k VARCHAR(120) NOT NULL UNIQUE,
  v TEXT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS products (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  barcode VARCHAR(64) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  category VARCHAR(120) NULL,
  category_id INT NULL,
  subcategory_id INT NULL,
  cost DECIMAL(10,2) NOT NULL DEFAULT 0,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  reorder_point INT NOT NULL DEFAULT 0,
  low_stock_threshold INT NOT NULL DEFAULT 0,
  image_path VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS purchase_orders (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  supplier_id BIGINT UNSIGNED NOT NULL,
  status ENUM('draft','ordered','received','cancelled') NOT NULL DEFAULT 'draft',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
)
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS purchase_order_items (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  purchase_order_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  qty INT NOT NULL,
  cost DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
)
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS sales (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  sale_no VARCHAR(30) NOT NULL UNIQUE,
  cashier_id BIGINT UNSIGNED NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  tax_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  amount_received DECIMAL(10,2) NOT NULL DEFAULT 0,
  change_due DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_method ENUM('cash','gcash_ref','gift_card','store_credit','card_terminal') NOT NULL,
  payment_ref VARCHAR(120) NULL,
  loyalty_customer_id BIGINT UNSIGNED NULL,
  loyalty_points_earned INT NOT NULL DEFAULT 0,
  loyalty_points_redeemed INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cashier_id) REFERENCES users(id)
)
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS sale_items (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  sale_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  barcode VARCHAR(64) NOT NULL,
  name VARCHAR(255) NOT NULL,
  qty INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  line_total DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (sale_id) REFERENCES sales(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
)
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS inventory_movements (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  product_id BIGINT UNSIGNED NOT NULL,
  type ENUM('sale','receive','adjust','void') NOT NULL,
  ref_table VARCHAR(60) NULL,
  ref_id BIGINT UNSIGNED NULL,
  qty_change INT NOT NULL,
  note VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id)
)
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS loyalty_customers (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  phone VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(120) NULL,
  points INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  meta JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
)
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS sync_queue (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  entity VARCHAR(60) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  payload JSON NOT NULL,
  status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  try_count INT NOT NULL DEFAULT 0,
  last_error TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
");

echo "Schema applied.\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS gift_cards (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(50) NOT NULL UNIQUE,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  status ENUM('created','assigned','redeemed') NOT NULL DEFAULT 'created',
  assigned_at TIMESTAMP NULL,
  redeemed_at TIMESTAMP NULL,
  assigned_sale_id BIGINT UNSIGNED NULL,
  redeemed_sale_id BIGINT UNSIGNED NULL,
  customer_id INT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
");

/*
|--------------------------------------------------------------------------
| ENSURE EXTRA COLUMNS
|--------------------------------------------------------------------------
*/

addColumnIfMissing($pdo, 'products', 'description', 'description TEXT NULL AFTER name');
addColumnIfMissing($pdo, 'products', 'image_path', 'image_path VARCHAR(255) NULL');
addColumnIfMissing($pdo, 'products', 'category_id', 'category_id INT NULL');
addColumnIfMissing($pdo, 'products', 'subcategory_id', 'subcategory_id INT NULL');

addColumnIfMissing($pdo, 'suppliers', 'description', 'description TEXT NULL AFTER name');
addColumnIfMissing($pdo, 'categories', 'description', 'description TEXT NULL AFTER name');
addColumnIfMissing($pdo, 'subcategories', 'description', 'description TEXT NULL AFTER name');

addColumnIfMissing($pdo, 'sales', 'is_refunded', 'is_refunded TINYINT(1) NOT NULL DEFAULT 0 AFTER change_due');
addColumnIfMissing($pdo, 'sales', 'refunded_at', 'refunded_at TIMESTAMP NULL AFTER is_refunded');
addColumnIfMissing($pdo, 'sales', 'refunded_by', 'refunded_by BIGINT UNSIGNED NULL AFTER refunded_at');
addColumnIfMissing($pdo, 'sales', 'refund_reason', 'refund_reason TEXT NULL AFTER refunded_by');

addColumnIfMissing($pdo, 'gift_cards', 'assigned_at', 'assigned_at TIMESTAMP NULL AFTER status');
addColumnIfMissing($pdo, 'gift_cards', 'redeemed_at', 'redeemed_at TIMESTAMP NULL AFTER assigned_at');
addColumnIfMissing($pdo, 'gift_cards', 'assigned_sale_id', 'assigned_sale_id BIGINT UNSIGNED NULL AFTER redeemed_at');
addColumnIfMissing($pdo, 'gift_cards', 'redeemed_sale_id', 'redeemed_sale_id BIGINT UNSIGNED NULL AFTER assigned_sale_id');
addColumnIfMissing($pdo, 'gift_cards', 'customer_id', 'customer_id INT UNSIGNED NULL AFTER redeemed_sale_id');
addColumnIfMissing($pdo, 'gift_cards', 'created_by', 'created_by BIGINT UNSIGNED NULL AFTER customer_id');

echo "Columns ensured.\n";

/*
|--------------------------------------------------------------------------
| DEFAULT SETTINGS
|--------------------------------------------------------------------------
*/

$defaults = [
  'store_name' => 'YOUR LIQUOR STORE',
  'store_address' => 'TOWN, PHILIPPINES',
  'vat_enabled' => Env::get('VAT_ENABLED','0') ?? '0',
  'vat_rate' => Env::get('VAT_RATE','12') ?? '12',
  'cash_drawer_enabled' => Env::get('CASH_DRAWER_ENABLED','0') ?? '0',
  'cash_drawer_kick_on' => Env::get('CASH_DRAWER_KICK_ON','cash') ?? 'cash',
];

$stmt = $pdo->prepare("INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=v");
foreach ($defaults as $k=>$v) {
  $stmt->execute([$k,(string)$v]);
}

echo "Settings ensured.\n";

/*
|--------------------------------------------------------------------------
| USERS
|--------------------------------------------------------------------------
*/

function upsertUser(PDO $pdo, string $name, string $username, string $pin, string $role): void
{
  $hash = password_hash($pin, PASSWORD_BCRYPT);

  $check = $pdo->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
  $check->execute([$username]);
  $row = $check->fetch();

  if ($row) {
    $upd = $pdo->prepare("UPDATE users SET name=?, pin_hash=?, role=?, is_active=1 WHERE username=?");
    $upd->execute([$name, $hash, $role, $username]);
    echo "Updated {$role}: {$username}\n";
    return;
  }

  $ins = $pdo->prepare("INSERT INTO users (name, username, pin_hash, role, is_active) VALUES (?, ?, ?, ?, 1)");
  $ins->execute([$name, $username, $hash, $role]);
  echo "Inserted {$role}: {$username}\n";
}

upsertUser($pdo, 'Owner', 'owner', '1234', 'owner');
upsertUser($pdo, 'Manager', 'manager', '2222', 'manager');
upsertUser($pdo, 'Cashier', 'cashier', '1111', 'cashier');

echo "Users ensured.\n";
echo "Done.\n";

/*
|--------------------------------------------------------------------------
| ADMIN USER
|--------------------------------------------------------------------------
*/
// Seed default admin user
$check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$check->execute(['admin']);
$exists = $check->fetch();

if (!$exists) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);

    $st = $pdo->prepare("
        INSERT INTO users (name, username, pin_hash, role, is_active)
        VALUES (?, ?, ?, ?, ?)
    ");

    $st->execute([
        'System Admin',
        'admin',
        $hash,
        'admin',
        1
    ]);

    echo "Admin user created (username: admin, password: admin123)\n";
} else {
    echo "Admin user already exists\n";
}

// If your table already exists, also add a safe alter after table creation:
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN selling_mode ENUM('in_store','field') NOT NULL DEFAULT 'in_store' AFTER role");
} catch (\Throwable $e) {
    // column may already exist
}

try {
    $pdo->exec("ALTER TABLE sales ADD COLUMN customer_id INT UNSIGNED NULL AFTER cashier_id");
} catch (\Throwable $e) {
    // may already exist
}

try {
    $pdo->exec("ALTER TABLE sales ADD COLUMN sale_channel ENUM('in_store','field') NOT NULL DEFAULT 'in_store' AFTER customer_id");
} catch (\Throwable $e) {
    // may already exist
}

// If sales already exists, add safe alters:
try {
    $pdo->exec("
        ALTER TABLE sales
        ADD CONSTRAINT fk_sales_refunded_by
        FOREIGN KEY (refunded_by) REFERENCES users(id)
        ON DELETE SET NULL
    ");
} catch (\Throwable $e) {
    // may already exist
}

try {
    $pdo->exec("ALTER TABLE gift_cards CHANGE COLUMN sold_at assigned_at TIMESTAMP NULL");
} catch (\Throwable $e) {
    // may not exist
}

try {
    $pdo->exec("ALTER TABLE gift_cards CHANGE COLUMN sold_sale_id assigned_sale_id BIGINT UNSIGNED NULL");
} catch (\Throwable $e) {
    // may not exist
}

try {
    $pdo->exec("ALTER TABLE gift_cards MODIFY COLUMN status ENUM('created','assigned','redeemed') NOT NULL DEFAULT 'created'");
} catch (\Throwable $e) {
    // may already be correct
}

try {
    $pdo->exec("UPDATE gift_cards SET status='created' WHERE status='new'");
} catch (\Throwable $e) {
    // old status may not exist
}

try {
    $pdo->exec("ALTER TABLE gift_cards ADD COLUMN customer_id INT UNSIGNED NULL");
} catch (\Throwable $e) {
    // ignore
}

try {
    $pdo->exec("
        ALTER TABLE gift_cards
        ADD CONSTRAINT fk_gift_cards_assigned_sale
        FOREIGN KEY (assigned_sale_id) REFERENCES sales(id)
        ON DELETE SET NULL
    ");
} catch (\Throwable $e) {
    // may already exist
}

try {
    $pdo->exec("
        ALTER TABLE gift_cards
        ADD CONSTRAINT fk_gift_cards_redeemed_sale
        FOREIGN KEY (redeemed_sale_id) REFERENCES sales(id)
        ON DELETE SET NULL
    ");
} catch (\Throwable $e) {
    // may already exist
}

try {
    $pdo->exec("
        ALTER TABLE gift_cards
        ADD CONSTRAINT fk_gift_cards_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON DELETE SET NULL
    ");
} catch (\Throwable $e) {
    // may already exist
}

try {
    $pdo->exec("
        ALTER TABLE gift_cards
        ADD CONSTRAINT fk_gift_cards_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
    ");
} catch (\Throwable $e) {
    // may already exist
}

// WARNING WARNING DONT REMOVE THE COMMENT....
// SET FOREIGN_KEY_CHECKS = 0;

// DROP TABLE IF EXISTS purchase_order_items;
// DROP TABLE IF EXISTS purchase_orders;
// DROP TABLE IF EXISTS sale_items;
// DROP TABLE IF EXISTS sales;
// DROP TABLE IF EXISTS inventory_movements;
// DROP TABLE IF EXISTS products;
// DROP TABLE IF EXISTS subcategories;
// DROP TABLE IF EXISTS categories;
// DROP TABLE IF EXISTS suppliers;
// DROP TABLE IF EXISTS audit_logs;
// DROP TABLE IF EXISTS loyalty_customers;
// DROP TABLE IF EXISTS settings;
// DROP TABLE IF EXISTS sync_queue;
// DROP TABLE IF EXISTS users;

// SET FOREIGN_KEY_CHECKS = 1;