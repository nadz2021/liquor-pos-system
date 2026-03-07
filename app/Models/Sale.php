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
        SELECT s.*, u.name AS cashier_name
        FROM sales s
        JOIN users u ON u.id = s.cashier_id
        WHERE s.cashier_id = ?
        ORDER BY s.id DESC
        LIMIT {$limit}
      ");
      $st->execute([$uid]);
      return $st->fetchAll();
    }

    // owner/manager see all
    $st = $pdo->prepare("
      SELECT s.*, u.name AS cashier_name
      FROM sales s
      JOIN users u ON u.id = s.cashier_id
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
        SELECT s.*, u.name AS cashier_name
        FROM sales s
        JOIN users u ON u.id = s.cashier_id
        WHERE s.id = ? AND s.cashier_id = ?
        LIMIT 1
      ");
      $st->execute([$id, $uid]);
      $row = $st->fetch();
      return $row ?: null;
    }

    $st = $pdo->prepare("
      SELECT s.*, u.name AS cashier_name
      FROM sales s
      JOIN users u ON u.id = s.cashier_id
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
}