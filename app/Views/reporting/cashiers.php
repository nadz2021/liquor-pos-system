
<?php ob_start(); ?>

<div class="page-head">
  <div>
    <h1 class="page-title">Sales by Cashier</h1>
    <div class="page-subtitle">Cashier sales within the selected date range.</div>
  </div>

  <div class="page-actions">
    <a class="btn" href="/reporting?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Back to Reporting</a>
    <a class="btn btn-primary" href="/reporting/export?type=cashiers&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Export CSV</a>
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

<table class="table">
  <thead>
    <tr>
      <th>Cashier</th>
      <th>Username</th>
      <th>Transactions</th>
      <th class="right">Sales</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($rows)): ?>
      <tr>
        <td colspan="4" class="muted" style="text-align:center;">No data found.</td>
      </tr>
    <?php endif; ?>

    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['cashier_name']) ?></td>
        <td><?= htmlspecialchars($r['username']) ?></td>
        <td><?= htmlspecialchars($r['sale_count']) ?></td>
        <td class="right"><?= (int)$r['total_sales'] ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php
$content = ob_get_clean();
$title = 'Sales by Cashiers';
require __DIR__ . '/../layouts/main.php';
?>