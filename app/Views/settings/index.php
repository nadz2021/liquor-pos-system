<?php
$store_name = $settings['store_name'] ?? 'YOUR LIQUOR STORE';
$store_address = $settings['store_address'] ?? 'TOWN, PHILIPPINES';
$vat_enabled = ($settings['vat_enabled'] ?? '0') === '1';
$vat_rate = $settings['vat_rate'] ?? '12';
$drawer_enabled = ($settings['cash_drawer_enabled'] ?? '0') === '1';
$kick_on = $settings['cash_drawer_kick_on'] ?? 'cash';
?>
<?php ob_start(); ?>

<div class="page-head">
  <div>
    <h1 class="page-title">Settings</h1>
    <div class="page-subtitle">Receipt header, VAT settings, and optional cash drawer kick.</div>
  </div>
</div>

<form method="post" action="/settings/save">
  <div class="card">

    <div class="section-title">Receipt Header</div>

    <div class="form-row">
      <div class="field">
        <label>Store name</label>
        <input name="store_name" value="<?= htmlspecialchars($store_name) ?>">
      </div>
      <div class="field">
        <label>Store address</label>
        <input name="store_address" value="<?= htmlspecialchars($store_address) ?>">
      </div>
    </div>

    <div class="spacer"></div>

    <div class="section-title">Tax (VAT)</div>

    <div class="form-row">
      <div class="field">
        <label>
          <input type="checkbox" name="vat_enabled" <?= $vat_enabled?'checked':'' ?>>
          Enable VAT
        </label>
        <div class="mini">If enabled, VAT is added to subtotal during checkout.</div>
      </div>

      <div class="field">
        <label>VAT rate (%)</label>
        <input type="number" step="0.01" name="vat_rate" value="<?= htmlspecialchars((string)$vat_rate) ?>">
      </div>
    </div>

    <div class="spacer"></div>

    <div class="section-title">Cash Drawer (Optional)</div>

    <div class="form-row">
      <div class="field">
        <label>
          <input type="checkbox" name="cash_drawer_enabled" <?= $drawer_enabled?'checked':'' ?>>
          Enable cash drawer kick
        </label>
        <div class="mini">Works when printer/bridge supports drawer kick.</div>
      </div>

      <div class="field">
        <label>Kick drawer on</label>
        <select name="cash_drawer_kick_on">
          <option value="cash" <?= $kick_on==='cash'?'selected':'' ?>>Cash only</option>
          <option value="always" <?= $kick_on==='always'?'selected':'' ?>>Always</option>
          <option value="gcash_ref" <?= $kick_on==='gcash_ref'?'selected':'' ?>>GCash ref</option>
          <option value="gift_card" <?= $kick_on==='gift_card'?'selected':'' ?>>Gift card</option>
          <option value="store_credit" <?= $kick_on==='store_credit'?'selected':'' ?>>Store credit</option>
          <option value="card_terminal" <?= $kick_on==='card_terminal'?'selected':'' ?>>Card terminal</option>
        </select>
      </div>
    </div>
<div class="spacer"></div>
    <div class="page-actions">
      <button class="btn btn-primary" type="submit">Save Settings</button>
    </div>

  </div>
</form>

<?php $content = ob_get_clean(); $title='Settings'; require __DIR__ . '/../layouts/main.php'; ?>