<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class Setting {
  public static function get(string $k, ?string $default=null): ?string {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT v FROM settings WHERE k=? LIMIT 1");
    $st->execute([$k]);
    $row = $st->fetch();
    return $row ? (string)$row['v'] : $default;
  }

  public static function set(string $k, string $v): void {
    $pdo = DB::pdo();
    $st = $pdo->prepare("INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)");
    $st->execute([$k,$v]);
  }

  public static function all(): array {
    $pdo = DB::pdo();
    $rows = $pdo->query("SELECT k,v FROM settings")->fetchAll();
    $out = [];
    foreach ($rows as $r) $out[$r['k']] = $r['v'];
    return $out;
  }
}
