<?php ob_start(); ?>

<div class="page-head">
  <div>
    <h1 class="page-title">Reporting</h1>
    <div class="page-subtitle">Sales analytics and performance reports.</div>
  </div>
</div>

<div class="card">
  <form method="GET" class="form-row">
    <div>
      <label>From</label>
      <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
    </div>

    <div>
      <label>To</label>
      <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
    </div>

    <div style="align-self:flex-end;">
      <button class="btn btn-primary">Filter</button>
    </div>
  </form>
</div>

<div class="spacer"></div>

<div class="summary-grid">
  <div class="summary-card sales">
    <div class="summary-icon">₱</div>
    <div class="summary-label">Total Sales</div>
    <div class="summary-value"><?= number_format((float)($summary['total_sales'] ?? 0), 2) ?></div>
    <div class="summary-note"><?= (int)($summary['total_transactions'] ?? 0) ?> transaction(s)</div>
  </div>

  <div class="summary-card refunds">
    <div class="summary-icon">↺</div>
    <div class="summary-label">Total Refunded</div>
    <div class="summary-value"><?= number_format((float)($summary['total_refunded'] ?? 0), 2) ?></div>
    <div class="summary-note">Refunded sales value</div>
  </div>

  <div class="summary-card cash">
    <div class="summary-icon">◎</div>
    <div class="summary-label">Avg Transaction</div>
    <div class="summary-value"><?= number_format((float)($summary['avg_transaction'] ?? 0), 2) ?></div>
    <div class="summary-note">Average sale amount</div>
  </div>

  <div class="summary-card noncash">
    <div class="summary-icon">★</div>
    <div class="summary-label">Best Product</div>
    <div class="summary-value" style="font-size:20px;">
      <?= htmlspecialchars($summary['best_product'] ?? '-') ?>
    </div>
    <div class="summary-note">Top seller in selected range</div>
  </div>
</div>

<div class="spacer"></div>

<div class="card">
  <h3 style="margin-top:0;">Top 10 Products</h3>

  <table class="table">
    <thead>
      <tr>
        <th>Product</th>
        <th>Barcode</th>
        <th class="right">Qty Sold</th>
        <th class="right">Sales</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($topProducts)): ?>
        <tr>
          <td colspan="4" class="muted" style="text-align:center;">No data found.</td>
        </tr>
      <?php endif; ?>

      <?php foreach ($topProducts as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><?= htmlspecialchars($r['barcode']) ?></td>
          <td class="right"><?= (int)$r['total_qty'] ?></td>
          <td class="right"><?= number_format((float)$r['total_sales'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="spacer"></div>

<div class="card">
  <h3 style="margin-top:0;">Refund Report</h3>

  <table class="table">
    <thead>
      <tr>
        <th>Sale No</th>
        <th>Cashier</th>
        <th>Refunded At</th>
        <th>Reason</th>
        <th class="right">Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($refundReport)): ?>
        <tr>
          <td colspan="5" class="muted" style="text-align:center;">No refunded sales found.</td>
        </tr>
      <?php endif; ?>

      <?php foreach ($refundReport as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['sale_no']) ?></td>
          <td><?= htmlspecialchars($r['cashier_name'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['refunded_at'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['refund_reason'] ?? '') ?></td>
          <td class="right"><?= number_format((float)$r['total'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="spacer"></div>

<div class="card">
  <h3 style="margin-top:0;">Detailed Reports</h3>

  <div class="page-actions">
    <a class="btn btn-primary" href="/reporting/products?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Sales by Product</a>
    <a class="btn btn-primary" href="/reporting/categories?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Sales by Category</a>
    <a class="btn btn-primary" href="/reporting/hours?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Sales by Hour</a>
    <a class="btn btn-primary" href="/reporting/customers?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Sales by Customer</a>
    <a class="btn btn-primary" href="/reporting/cashiers?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Sales by Cashier</a>
    <a class="btn btn-primary" href="/reporting/refunds?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Refund Report</a>
  </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Reporting';
require __DIR__ . '/../layouts/main.php';
?>