<!doctype html>
<html>
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'POS') ?></title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php
  $role = $user['role'] ?? '';
  $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
  function isActive(string $path, string $route): string {
    if ($route === '/' && $path === '/') return 'active';
    if ($route !== '/' && str_starts_with($path, $route)) return 'active';
    return '';
  }
?>
<div class="topbar">
  <div class="app-shell">
    <div class="nav">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M10 3h4v2c0 .6.4 1 1 1h1v3c0 .6-.4 1-1 1v10a3 3 0 0 1-3 3h-2a3 3 0 0 1-3-3V10c-.6 0-1-.4-1-1V6h1c.6 0 1-.4 1-1V3z"
                  stroke="currentColor" stroke-width="1.6" />
            <path d="M8.5 13h7" stroke="currentColor" stroke-width="1.6" />
          </svg>
        </div>
        <div class="brand-title">
          <strong>Liquor Store POS</strong>
          <span><?= htmlspecialchars($user['name'] ?? '') ?></span>
        </div>

        <div class="role-badge role-<?= htmlspecialchars($role) ?>">
          <span class="role-dot"></span>
          <?= htmlspecialchars(ucfirst($role)) ?>
        </div>
      </div>

      <div class="nav-links">
        <a class="<?= isActive($path, '/') ?>" href="/">POS</a>

        <?php if (in_array($role, ['owner','manager','cashier'], true)): ?>
          <a class="<?= isActive($path, '/sales') ?>" href="/sales">Sales</a>
        <?php endif; ?>

        <?php if (in_array($role, ['owner','manager'], true)): ?>
          <a class="<?= isActive($path, '/products') ?>" href="/products">Products</a>
          <a class="<?= isActive($path, '/categories') ?>" href="/categories">Categories</a>
          <a class="<?= isActive($path, '/subcategories') ?>" href="/subcategories">Sub Categories</a>
          <a class="<?= isActive($path, '/settings') ?>" href="/settings">Settings</a>
        <?php endif; ?>
      </div>

      <div class="nav-right">
        <form action="/logout" method="post" style="margin:0">
          <button class="btn btn-danger" type="submit">Logout</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="app-shell">
  <?= $content ?? '' ?>
</div>
</body>
</html>