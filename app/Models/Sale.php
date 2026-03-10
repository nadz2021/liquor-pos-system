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
}