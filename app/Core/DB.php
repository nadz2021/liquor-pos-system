<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class DB {
  private static ?PDO $pdo = null;

  public static function pdo(): PDO {
    if (self::$pdo) return self::$pdo;

    $host = Env::get('DB_HOST','db');
    $port = Env::get('DB_PORT','3306');
    $name = Env::get('DB_NAME','pos_db');
    $user = Env::get('DB_USER','pos_user');
    $pass = Env::get('DB_PASS','pos_pass');

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    self::$pdo = new PDO($dsn, $user, $pass, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return self::$pdo;
  }
}
