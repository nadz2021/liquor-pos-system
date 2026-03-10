<?php ob_start(); ?>

<div class="page-head">
  <div>
    <h1 class="page-title">Sales History</h1>
    <div class="page-subtitle">View transactions, payment method, amounts, and totals.</div>
  </div>
  <div class="page-actions">
    <a class="btn" href="/">Back to POS</a>
  </div>
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