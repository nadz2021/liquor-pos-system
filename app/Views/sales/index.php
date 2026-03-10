<?php ob_start(); ?>

<div class="page-head">
  <div>
    <h1 class="page-title">Sales History</h1>
    <div class="page-subtitle">View transactions, payment method, amounts, totals, and refunds.</div>
  </div>
  <div class="page-actions">
    <a class="btn" href="/">Back to POS</a>

    <a class="btn btn-primary"
       href="/sales/export?<?= http_build_query([
         'date_from' => $filters['date_from'] ?? '',
         'date_to' => $filters['date_to'] ?? '',
         'cashier_id' => $filters['cashier_id'] ?? '',
         'sale_channel' => $filters['sale_channel'] ?? '',
         'payment_method' => $filters['payment_method'] ?? '',
         'is_refunded' => $filters['is_refunded'] ?? '',
       ]) ?>">
      Export CSV
    </a>
  </div>
</div>
<?php if (($lowStockCount ?? 0) > 0): ?>
  <div class="card" style="margin-bottom:12px;">
    <span class="badge badge-warn">Low Stock Alert</span>
    <span class="mini"><?= (int)$lowStockCount ?> product(s) are at or below reorder point.</span>
  </div>
<?php endif; ?>

<div class="summary-grid">
  <div class="summary-card sales">
    <div class="summary-icon">₱</div>
    <div class="summary-label">Total Sales</div>
    <div class="summary-value"><?= number_format((float)($summary['total_sales'] ?? 0), 2) ?></div>
    <div class="summary-note"><?= (int)($summary['sale_count'] ?? 0) ?> sale(s)</div>
  </div>

  <div class="summary-card refunds">
    <div class="summary-icon">↺</div>
    <div class="summary-label">Total Refunded</div>
    <div class="summary-value"><?= number_format((float)($summary['total_refunded'] ?? 0), 2) ?></div>
    <div class="summary-note">Refunded sales value</div>
  </div>

  <div class="summary-card cash">
    <div class="summary-icon">💵</div>
    <div class="summary-label">Cash Sales</div>
    <div class="summary-value"><?= number_format((float)($summary['total_cash'] ?? 0), 2) ?></div>
    <div class="summary-note">Cash payments</div>
  </div>

  <div class="summary-card noncash">
    <div class="summary-icon">◎</div>
    <div class="summary-label">Non-Cash Sales</div>
    <div class="summary-value"><?= number_format((float)($summary['total_non_cash'] ?? 0), 2) ?></div>
    <div class="summary-note">GCash / Card / Others</div>
  </div>
</div>

<div class="card" style="margin-bottom:12px;">
  <form method="get" action="/sales" class="form">
    <div class="form-row">
      <div class="field">
        <label>Date From</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
      </div>

      <div class="field">
        <label>Date To</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
      </div>

      <div class="field">
        <label>Cashier</label>
        <select name="cashier_id">
          <option value="">All</option>
          <?php foreach (($cashiers ?? []) as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ((string)($filters['cashier_id'] ?? '') === (string)$c['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="field">
        <label>Channel</label>
        <select name="sale_channel">
          <option value="">All</option>
          <option value="in_store" <?= (($filters['sale_channel'] ?? '') === 'in_store') ? 'selected' : '' ?>>In Store</option>
          <option value="field" <?= (($filters['sale_channel'] ?? '') === 'field') ? 'selected' : '' ?>>Field</option>
        </select>
      </div>

      <div class="field">
        <label>Payment Method</label>
        <select name="payment_method">
          <option value="">All</option>
          <option value="cash" <?= (($filters['payment_method'] ?? '') === 'cash') ? 'selected' : '' ?>>Cash</option>
          <option value="gcash_ref" <?= (($filters['payment_method'] ?? '') === 'gcash_ref') ? 'selected' : '' ?>>GCash</option>
          <option value="gift_card" <?= (($filters['payment_method'] ?? '') === 'gift_card') ? 'selected' : '' ?>>Gift Card</option>
          <option value="store_credit" <?= (($filters['payment_method'] ?? '') === 'store_credit') ? 'selected' : '' ?>>Store Credit</option>
          <option value="card_terminal" <?= (($filters['payment_method'] ?? '') === 'card_terminal') ? 'selected' : '' ?>>Card Terminal</option>
        </select>
      </div>

      <div class="field">
        <label>Refunded</label>
        <select name="is_refunded">
          <option value="">All</option>
          <option value="0" <?= (($filters['is_refunded'] ?? '') === '0') ? 'selected' : '' ?>>Not Refunded</option>
          <option value="1" <?= (($filters['is_refunded'] ?? '') === '1') ? 'selected' : '' ?>>Refunded</option>
        </select>
      </div>
    </div>

    <div class="page-actions">
      <button class="btn btn-primary" type="submit">Apply Filters</button>
      <a class="btn" href="/sales">Reset</a>
    </div>
  </form>
</div>

<table class="table">
  <thead>
    <tr>
      <th>Sale</th>
      <th>Date</th>
      <th>Cashier</th>
      <th>Channel</th>
      <th>Payment</th>
      <th class="right">Total</th>
      <th class="right">Received</th>
      <th class="right">Change</th>
      <th class="actions"></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($sales as $s): ?>
      <tr>
        <td>
          <div style="font-weight:800;"><?= htmlspecialchars($s['sale_no']) ?></div>
          <div class="mini">ID: <?= (int)$s['id'] ?></div>
        </td>
        <td><?= htmlspecialchars($s['created_at']) ?></td>
        <td><?= htmlspecialchars($s['cashier_name']) ?></td>
        <td>
          <?php if (($s['sale_channel'] ?? 'in_store') === 'field'): ?>
            <span class="badge badge-warn">Field</span>
          <?php else: ?>
            <span class="badge badge-success">In Store</span>
          <?php endif; ?>
        </td>
        <td>
          <span class="badge"><?= htmlspecialchars($s['payment_method']) ?></span>
          <?php if (!empty($s['payment_ref'])): ?>
            <div class="mini">Ref: <?= htmlspecialchars($s['payment_ref']) ?></div>
          <?php endif;
          if ((int)($s['is_refunded'] ?? 0) === 1): ?>
            <div><span class="badge badge-warn">Refunded</span></div>
          <?php endif; ?>
        </td>
        <td class="right"><?= number_format((float)$s['total'], 2) ?></td>
        <td class="right"><?= number_format((float)$s['amount_received'], 2) ?></td>
        <td class="right"><?= number_format((float)$s['change_due'], 2) ?></td>
        <td class="actions">
          <a class="btn btn-primary" href="/sales/show?id=<?= (int)$s['id'] ?>">View</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php $content = ob_get_clean(); $title='Sales'; require __DIR__ . '/../layouts/main.php'; ?>