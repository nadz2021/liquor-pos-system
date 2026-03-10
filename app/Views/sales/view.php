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
    <?php if (!empty($sale['customer_name'])): ?>
    <div>
      <div class="muted">Customer</div>
      <div style="font-weight:800;"><?= htmlspecialchars($sale['customer_name']) ?></div>
    </div>
    <?php endif; ?>
    <div>
      <div class="muted">Payment</div>
      <div style="font-weight:800;">
        <?= htmlspecialchars($sale['payment_method']) ?>
        <?php if (!empty($sale['payment_ref'])): ?>
          <span class="badge">Ref: <?= htmlspecialchars($sale['payment_ref']) ?></span>
        <?php endif;
        if ((int)($sale['is_refunded'] ?? 0) === 1): ?>
          <span class="badge badge-warn">Refunded</span>
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
  <?php if ((int)($sale['is_refunded'] ?? 0) === 1): ?>
    <div class="spacer"></div>

    <div class="form-row">
      <div>
        <div class="muted">Refunded At</div>
        <div style="font-weight:800;"><?= htmlspecialchars((string)($sale['refunded_at'] ?? '')) ?></div>
      </div>
      <div>
        <div class="muted">Refund Reason</div>
        <div style="font-weight:800;"><?= htmlspecialchars((string)($sale['refund_reason'] ?? '')) ?></div>
      </div>
    </div>
  <?php endif; ?>
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
<?php if (in_array(($user['role'] ?? ''), ['super_admin', 'admin', 'owner', 'manager'], true)): ?>
  <div class="spacer"></div>

  <div class="card">
    <h3 style="margin-top:0;">Refund</h3>

    <?php if ((int)($sale['is_refunded'] ?? 0) === 1): ?>
      <div class="badge badge-warn">This sale is already refunded.</div>
    <?php else: ?>
      <form method="post" action="/sales/refund" class="form">
        <input type="hidden" name="sale_id" value="<?= (int)$sale['id'] ?>">

        <div class="field">
          <label>Refund Reason</label>
          <textarea name="refund_reason" placeholder="Optional reason for refund"></textarea>
        </div>

        <div class="page-actions">
          <button
            class="btn btn-danger"
            type="submit"
            onclick="return confirm('Are you sure you want to refund this sale? Stock will be returned.')"
          >
            Refund Sale
          </button>
        </div>
      </form>
    <?php endif; ?>
  </div>
<?php endif; ?>
<?php $content = ob_get_clean(); $title='Sale Details'; require __DIR__ . '/../layouts/main.php'; ?>