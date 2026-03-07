<?php ob_start(); ?>

<div class="page-head">
  <div>
    <h1 class="page-title">Sale Details</h1>
    <div class="page-subtitle"><?= htmlspecialchars($sale['sale_no']) ?> • <?= htmlspecialchars($sale['created_at']) ?></div>
  </div>
  <div class="page-actions">
    <a class="btn" href="/sales">Back</a>
    <a class="btn" href="/">POS</a>
  </div>
</div>

<div class="card">
  <div class="form-row">
    <div>
      <div class="muted">Cashier</div>
      <div style="font-weight:800;"><?= htmlspecialchars($sale['cashier_name']) ?></div>
    </div>
    <div>
      <div class="muted">Payment</div>
      <div style="font-weight:800;">
        <?= htmlspecialchars($sale['payment_method']) ?>
        <?php if (!empty($sale['payment_ref'])): ?>
          <span class="badge">Ref: <?= htmlspecialchars($sale['payment_ref']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="spacer"></div>

  <div class="form-row">
    <div>
      <div class="muted">Subtotal</div>
      <div style="font-weight:800;"><?= number_format((float)$sale['subtotal'], 2) ?></div>
    </div>
    <div>
      <div class="muted">Tax</div>
      <div style="font-weight:800;"><?= number_format((float)$sale['tax_total'], 2) ?></div>
    </div>
  </div>

  <div class="spacer"></div>

  <div class="form-row">
    <div>
      <div class="muted">Total</div>
      <div style="font-weight:900; font-size:18px;"><?= number_format((float)$sale['total'], 2) ?></div>
    </div>
    <div>
      <div class="muted">Received / Change</div>
      <div style="font-weight:800;">
        <?= number_format((float)$sale['amount_received'], 2) ?> /
        <?= number_format((float)$sale['change_due'], 2) ?>
      </div>
    </div>
  </div>
</div>

<div class="spacer"></div>

<h3 style="margin:0 0 10px 0;">Items</h3>

<table class="table">
  <thead>
    <tr>
      <th>Barcode</th>
      <th>Name</th>
      <th class="right">Qty</th>
      <th class="right">Unit</th>
      <th class="right">Line Total</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $it): ?>
      <tr>
        <td><?= htmlspecialchars($it['barcode']) ?></td>
        <td><?= htmlspecialchars($it['name']) ?></td>
        <td class="right"><?= (int)$it['qty'] ?></td>
        <td class="right"><?= number_format((float)$it['unit_price'], 2) ?></td>
        <td class="right"><?= number_format((float)$it['line_total'], 2) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php $content = ob_get_clean(); $title='Sale Details'; require __DIR__ . '/../layouts/main.php'; ?>