<?php ob_start(); ?>

<div class="login-wrapper">
  <div class="login-grid">

    <!-- LEFT: Login form -->
    <div class="login-card">
      <div class="header">
        <div class="brand">
          <div class="logo" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M10 3h4v2c0 .6.4 1 1 1h1v3c0 .6-.4 1-1 1v10a3 3 0 0 1-3 3h-2a3 3 0 0 1-3-3V10c-.6 0-1-.4-1-1V6h1c.6 0 1-.4 1-1V3z"
                    stroke="currentColor" stroke-width="1.6" />
              <path d="M8.5 13h7" stroke="currentColor" stroke-width="1.6" />
            </svg>
          </div>
          <div>
            <h1 class="title">Liquor Store POS</h1>
            <div class="subtitle">Secure staff login</div>
          </div>
        </div>

        <div class="top-actions">
          <button class="icon-btn" id="themeToggle" type="button" aria-label="Switch theme">🌙</button>
        </div>
      </div>

      <hr class="sep">

      <div class="role-row">
        <div class="chip admin" data-rolechip="1" data-username="admin" title="Admin">
          <span class="dot"></span> Admin
        </div>
        <div class="chip owner" data-rolechip="1" data-username="owner" title="owner">
          <span class="dot"></span> Owner
        </div>
        <div class="chip manager" data-rolechip="1" data-username="manager" title="Manager">
          <span class="dot"></span> Manager
        </div>
        <div class="chip cashier" data-rolechip="1" data-username="cashier" title="Cashier">
          <span class="dot"></span> Cashier
        </div>
      </div>

      <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form id="loginForm" method="post" action="/login">
        <div class="form-group">
          <label>Username</label>
          <input name="username" autocomplete="username" required>
        </div>

        <div class="form-group">
          <label>PIN / Password</label>
          <input
            name="pin"
            type="password"
            autocomplete="current-password"
            required
            placeholder="Enter PIN or password"
          >
        </div>

        <button class="primary" type="submit">Login</button>

        <div class="default-login">
          Default: Admin <strong>admin / admin123</strong> · Manager <strong>manager / 2222</strong> · Cashier <strong>cashier / 1111</strong>
        </div>
      </form>
    </div>

    <!-- RIGHT: PIN pad -->
    <div class="login-card">
      <div class="pad-title">Touch PIN Pad</div>
      <div class="pad-hint">Tap numbers for PIN users. Admin can also type password from keyboard.</div>

      <div id="pinpad" class="pinpad">
        <button type="button" class="pad-btn" data-pad="1">1</button>
        <button type="button" class="pad-btn" data-pad="2">2</button>
        <button type="button" class="pad-btn" data-pad="3">3</button>

        <button type="button" class="pad-btn" data-pad="4">4</button>
        <button type="button" class="pad-btn" data-pad="5">5</button>
        <button type="button" class="pad-btn" data-pad="6">6</button>

        <button type="button" class="pad-btn" data-pad="7">7</button>
        <button type="button" class="pad-btn" data-pad="8">8</button>
        <button type="button" class="pad-btn" data-pad="9">9</button>

        <button type="button" class="pad-btn secondary" data-pad="clr">Clear</button>
        <button type="button" class="pad-btn" data-pad="0">0</button>
        <button type="button" class="pad-btn secondary" data-pad="bs">⌫</button>

        <button type="button" class="pad-btn enter" data-pad="enter" style="grid-column:1/-1;">Enter</button>
      </div>
    </div>

  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/auth.php';