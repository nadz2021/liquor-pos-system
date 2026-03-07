<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth {
  public static function user(): ?array { return $_SESSION['user'] ?? null; }
  public static function check(): bool { return isset($_SESSION['user']); }

  public static function requireLogin(): void {
    if (!self::check()) { header('Location: /login'); exit; }
    $timeoutMin = Env::int('SESSION_TIMEOUT_MIN', 15);
    $last = $_SESSION['last_activity'] ?? time();
    if (time() - $last > ($timeoutMin * 60)) { self::logout(); header('Location: /login'); exit; }
    $_SESSION['last_activity'] = time();
  }

  public static function attempt(string $username, string $pin): bool {
    $u = User::findByUsername($username);
    if (!$u || (int)$u['is_active'] !== 1) return false;
    if (!password_verify($pin, $u['pin_hash'])) return false;

    $_SESSION['user'] = ['id'=>(int)$u['id'], 'name'=>$u['name'], 'username'=>$u['username'], 'role'=>$u['role']];
    $_SESSION['last_activity'] = time();
    return true;
  }

  public static function logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time()-42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
  }

  public static function can(string $cap): bool {
    $u = self::user(); if (!$u) return false;
    if ($u['role'] === 'owner') return true;
    $map = [
      'manager' => ['pos.use','products.manage','inventory.manage','po.manage','reports.view','loyalty.manage'],
      'cashier' => ['pos.use'],
    ];
    return in_array($cap, $map[$u['role']] ?? [], true);
  }
}
