<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class Audit {
  public static function log(?int $userId, string $action, array $meta=[]): void {
    $pdo = DB::pdo();
    $st = $pdo->prepare("INSERT INTO audit_logs (user_id, action, meta) VALUES (?, ?, ?)");
    $st->execute([$userId, $action, json_encode($meta)]);
  }
}
