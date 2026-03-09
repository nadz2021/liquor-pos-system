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
    if (time() - $last > ($timeoutMin * 60)) {
      self::logout();
      header('Location: /login');
      exit;
    }
    $_SESSION['last_activity'] = time();
  }

  public static function attempt(string $username, string $secret): bool {
    $u = User::findByUsername($username);
    if (!$u || (int)$u['is_active'] !== 1) return false;

    $role = (string)($u['role'] ?? '');
    $isAdmin = in_array($role, ['admin', 'super_admin'], true);

    $hash = (string)($u['pin_hash'] ?? '');
    if ($hash === '') return false;

    if ($isAdmin) {
      // admin / super_admin can use a normal password in the same field
      if (!password_verify($secret, $hash)) return false;
    } else {
      // non-admin users must enter numeric PIN only
      if (!ctype_digit($secret)) return false;
      if (!password_verify($secret, $hash)) return false;
    }

    $_SESSION['user'] = [
      'id' => (int)$u['id'],
      'name' => $u['name'],
      'username' => $u['username'],
      'role' => $u['role']
    ];
    $_SESSION['last_activity'] = time();
    return true;
  }

  public static function logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
      );
    }
    session_destroy();
  }

  public static function can(string $cap): bool {
    $u = self::user();
    if (!$u) return false;

    $map = [
      'super_admin' => ['*'],
      'admin' => ['*'],
      'owner' => [
        'pos.use',
        'sales.view',
        'products.manage',
        'categories.manage',
        'settings.manage',
      ],
      'manager' => [
        'pos.use',
        'sales.view',
        'products.manage',
        'inventory.manage',
        'po.manage',
        'reports.view',
        'loyalty.manage',
        'categories.manage',
        'settings.manage',
      ],
      'cashier' => [
        'pos.use',
        'sales.view',
      ],
    ];

    $allowed = $map[$u['role']] ?? [];

    return in_array('*', $allowed, true) || in_array($cap, $allowed, true);
  }
}