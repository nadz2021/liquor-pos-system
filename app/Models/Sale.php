<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class Sale
{
  public static function listForUser(array $user, int $limit = 200): array
  {
    $pdo = DB::pdo();
    $role = $user['role'] ?? '';
    $uid  = (int)($user['id'] ?? 0);

    if ($role === 'cashier') {
      $st = $pdo->prepare("
        SELECT s.*, u.name AS cashier_name, c.name AS customer_name
        FROM sales s
        JOIN users u ON u.id = s.cashier_id
        LEFT JOIN customers c ON c.id = s.customer_id
        WHERE s.cashier_id = ?
        ORDER BY s.id DESC
        LIMIT {$limit}
      ");
      $st->execute([$uid]);
      return $st->fetchAll();
    }

    // owner/manager see all
    $st = $pdo->prepare("
      SELECT s.*, u.name AS cashier_name, c.name AS customer_name
      FROM sales s
      JOIN users u ON u.id = s.cashier_id
      LEFT JOIN customers c ON c.id = s.customer_id
      ORDER BY s.id DESC
      LIMIT {$limit}
    ");
    $st->execute();
    return $st->fetchAll();
  }

  public static function findForUser(array $user, int $id): ?array
  {
    $pdo = DB::pdo();
    $role = $user['role'] ?? '';
    $uid  = (int)($user['id'] ?? 0);

    if ($role === 'cashier') {
      $st = $pdo->prepare("
        SELECT s.*, u.name AS cashier_name, c.name AS customer_name
        FROM sales s
        JOIN users u ON u.id = s.cashier_id
        LEFT JOIN customers c ON c.id = s.customer_id
        WHERE s.id = ? AND s.cashier_id = ?
        LIMIT 1
      ");
      $st->execute([$id, $uid]);
      $row = $st->fetch();
      return $row ?: null;
    }

    $st = $pdo->prepare("
      SELECT s.*, u.name AS cashier_name, c.name AS customer_name
      FROM sales s
      JOIN users u ON u.id = s.cashier_id
      LEFT JOIN customers c ON c.id = s.customer_id
      WHERE s.id = ?
      LIMIT 1
    ");
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function items(int $saleId): array
  {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id=? ORDER BY id ASC");
    $st->execute([$saleId]);
    return $st->fetchAll();
  }

  public static function refund(int $saleId, int $userId, string $reason = ''): bool
  {
    $pdo = DB::pdo();
    $pdo->beginTransaction();

    try {

      // check sale
      $st = $pdo->prepare("SELECT * FROM sales WHERE id=? LIMIT 1");
      $st->execute([$saleId]);
      $sale = $st->fetch();

      if (!$sale) {
        throw new \RuntimeException('Sale not found.');
      }

      if ((int)$sale['is_refunded'] === 1) {
        throw new \RuntimeException('Sale already refunded.');
      }

      // get items
      $items = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id=?");
      $items->execute([$saleId]);

      $updateStock = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id=?");

      while ($item = $items->fetch()) {

        $qty = (int)$item['qty'];
        $productId = (int)$item['product_id'];

        // return stock
        $updateStock->execute([$qty, $productId]);

        // inventory log
        $pdo->prepare("
          INSERT INTO inventory_movements
          (product_id,type,ref_table,ref_id,qty_change,note,created_by)
          VALUES (?,?,?,?,?,?,?)
        ")->execute([
          $productId,
          'adjust',
          'sales',
          $saleId,
          $qty,
          'Refund for sale #' . $sale['sale_no'],
          $userId
        ]);
      }

      // mark refunded
      $pdo->prepare("
        UPDATE sales
        SET is_refunded=1,
            refunded_at=NOW(),
            refunded_by=?,
            refund_reason=?
        WHERE id=?
      ")->execute([$userId, $reason ?: null, $saleId]);

      $pdo->commit();
      return true;

    } catch (\Throwable $e) {

      $pdo->rollBack();
      throw $e;

    }
  }
  public static function filteredListForUser(array $user, array $filters = [], int $limit = 500): array
  {
    $pdo = DB::pdo();
    $role = $user['role'] ?? '';
    $uid  = (int)($user['id'] ?? 0);

    $where = [];
    $params = [];

    if ($role === 'cashier') {
      $where[] = 's.cashier_id = ?';
      $params[] = $uid;
    }

    if (!empty($filters['date_from'])) {
      $where[] = 'DATE(s.created_at) >= ?';
      $params[] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
      $where[] = 'DATE(s.created_at) <= ?';
      $params[] = $filters['date_to'];
    }

    if (!empty($filters['cashier_id'])) {
      $where[] = 's.cashier_id = ?';
      $params[] = (int)$filters['cashier_id'];
    }

    if (!empty($filters['sale_channel'])) {
      $where[] = 's.sale_channel = ?';
      $params[] = $filters['sale_channel'];
    }

    if (!empty($filters['payment_method'])) {
      $where[] = 's.payment_method = ?';
      $params[] = $filters['payment_method'];
    }

    if (isset($filters['is_refunded']) && $filters['is_refunded'] !== '') {
      $where[] = 's.is_refunded = ?';
      $params[] = (int)$filters['is_refunded'];
    }

    $sql = "
      SELECT s.*, u.name AS cashier_name, c.name AS customer_name
      FROM sales s
      JOIN users u ON u.id = s.cashier_id
      LEFT JOIN customers c ON c.id = s.customer_id
    ";

    if ($where) {
      $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= " ORDER BY s.id DESC LIMIT {$limit}";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
  }
  public static function summaryForUser(array $user, array $filters = []): array
  {
    $pdo = DB::pdo();
    $role = $user['role'] ?? '';
    $uid  = (int)($user['id'] ?? 0);

    $where = [];
    $params = [];

    if ($role === 'cashier') {
      $where[] = 's.cashier_id = ?';
      $params[] = $uid;
    }

    if (!empty($filters['date_from'])) {
      $where[] = 'DATE(s.created_at) >= ?';
      $params[] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
      $where[] = 'DATE(s.created_at) <= ?';
      $params[] = $filters['date_to'];
    }

    if (!empty($filters['cashier_id'])) {
      $where[] = 's.cashier_id = ?';
      $params[] = (int)$filters['cashier_id'];
    }

    if (!empty($filters['sale_channel'])) {
      $where[] = 's.sale_channel = ?';
      $params[] = $filters['sale_channel'];
    }

    if (!empty($filters['payment_method'])) {
      $where[] = 's.payment_method = ?';
      $params[] = $filters['payment_method'];
    }

    if (isset($filters['is_refunded']) && $filters['is_refunded'] !== '') {
      $where[] = 's.is_refunded = ?';
      $params[] = (int)$filters['is_refunded'];
    }

    $sql = "
      SELECT
        COUNT(*) AS sale_count,
        COALESCE(SUM(s.total), 0) AS total_sales,
        COALESCE(SUM(CASE WHEN s.is_refunded = 1 THEN s.total ELSE 0 END), 0) AS total_refunded,
        COALESCE(SUM(CASE WHEN s.payment_method = 'cash' THEN s.total ELSE 0 END), 0) AS total_cash,
        COALESCE(SUM(CASE WHEN s.payment_method <> 'cash' THEN s.total ELSE 0 END), 0) AS total_non_cash
      FROM sales s
    ";

    if ($where) {
      $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();

    return $row ?: [
      'sale_count' => 0,
      'total_sales' => 0,
      'total_refunded' => 0,
      'total_cash' => 0,
      'total_non_cash' => 0,
    ];
  }
  public static function cashiers(): array
  {
    $pdo = DB::pdo();
    return $pdo->query("
      SELECT id, name, username, role
      FROM users
      WHERE is_active = 1
      ORDER BY name ASC
    ")->fetchAll();
  }
  public static function lowStockCount(): int
  {
    $pdo = DB::pdo();
    $st = $pdo->query("
      SELECT COUNT(*) AS c
      FROM products
      WHERE stock <= reorder_point
        AND is_active = 1
    ");
    return (int)($st->fetch()['c'] ?? 0);
  }
  public static function exportRowsForUser(array $user, array $filters = []): array
  {
    return self::filteredListForUser($user, $filters, 5000);
  }
}