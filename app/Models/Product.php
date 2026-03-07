<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class Product {

  public static function list(int $limit=500): array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("
      SELECT
        p.*,
        c.name AS main_category_name,
        s.name AS subcategory_name
      FROM products p
      LEFT JOIN categories c ON c.id = p.category_id
      LEFT JOIN subcategories s ON s.id = p.subcategory_id
      ORDER BY p.id DESC
      LIMIT {$limit}
    ");
    $st->execute();
    return $st->fetchAll();
  }

  public static function find(int $id): ?array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function findByBarcode(string $barcode): ?array {
    $pdo = DB::pdo();
    $st = $pdo->prepare("
      SELECT id, barcode, name, price, stock, image_path
      FROM products
      WHERE barcode=? AND is_active=1
      LIMIT 1
    ");
    $st->execute([$barcode]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function create(array $d): int {
    $pdo = DB::pdo();

    $st = $pdo->prepare("
      INSERT INTO products
      (barcode,name,category,cost,price,stock,reorder_point,low_stock_threshold,is_active,image_path,category_id,subcategory_id)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $st->execute([
      $d['barcode'],
      $d['name'],
      $d['category'] ?: null,
      (float)$d['cost'],
      (float)$d['price'],
      (int)$d['stock'],
      (int)$d['reorder_point'],
      (int)$d['low_stock_threshold'],
      (int)$d['is_active'],
      $d['image_path'] ?? null,
      !empty($d['category_id']) ? (int)$d['category_id'] : null,
      !empty($d['subcategory_id']) ? (int)$d['subcategory_id'] : null,
    ]);

    return (int)$pdo->lastInsertId();
  }

  public static function update(int $id, array $d): void {
    $pdo = DB::pdo();

    $fields = [
      'barcode' => $d['barcode'],
      'name' => $d['name'],
      'category' => $d['category'] ?: null,
      'cost' => (float)$d['cost'],
      'price' => (float)$d['price'],
      'stock' => (int)$d['stock'],
      'reorder_point' => (int)$d['reorder_point'],
      'low_stock_threshold' => (int)$d['low_stock_threshold'],
      'is_active' => (int)$d['is_active'],
      'category_id' => !empty($d['category_id']) ? (int)$d['category_id'] : null,
      'subcategory_id' => !empty($d['subcategory_id']) ? (int)$d['subcategory_id'] : null,
    ];

    if (isset($d['image_path'])) {
      $fields['image_path'] = $d['image_path'];
    }

    $set = [];
    $vals = [];
    foreach ($fields as $k => $v) {
      $set[] = "{$k}=?";
      $vals[] = $v;
    }

    $vals[] = $id;

    $sql = "UPDATE products SET " . implode(',', $set) . " WHERE id=?";
    $st = $pdo->prepare($sql);
    $st->execute($vals);
  }

  public static function toggle(int $id): void {
    $pdo = DB::pdo();
    $pdo->prepare("UPDATE products SET is_active=IF(is_active=1,0,1) WHERE id=?")->execute([$id]);
  }
}