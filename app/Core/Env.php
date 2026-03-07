<?php
declare(strict_types=1);

namespace App\Core;

final class Env {
  public static function load(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '' || str_starts_with($line, '#')) continue;
      [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
      $k = trim($k); $v = trim($v);
      if ($k !== '') $_ENV[$k] = $v;
    }
  }
  public static function get(string $key, ?string $default=null): ?string { return $_ENV[$key] ?? $default; }
  public static function int(string $key, int $default=0): int { $v=self::get($key); return ($v===null||$v==='')?$default:(int)$v; }
}
