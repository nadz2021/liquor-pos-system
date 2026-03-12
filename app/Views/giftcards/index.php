<?php ob_start(); ?>

<div class="page-head">
  <div>
    <h1 class="page-title">Gift Cards</h1>
    <div class="page-subtitle">Generate and manage gift cards.</div>
  </div>
</div>

<div class="card">
  <h3 style="margin-top:0;">Generate Gift Card</h3>

  <form method="POST" action="/gift-cards/store" class="form-row">
    <div>
      <label>Amount</label>
      <input
        type="number"
        name="amount"
        step="0.01"
        min="1"
        required
        placeholder="Enter amount"
      >
    </div>

    <div style="align-self:flex-end;">
      <button class="btn btn-primary">Generate</button>
    </div>
  </form>
</div>

<div class="spacer"></div>

<div class="card">
  <h3 style="margin-top:0;">Gift Card List</h3>

  <table class="table">
    <thead>
      <tr>
        <th>Code</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Assigned</th>
        <th>Redeemed</th>
        <th class="actions"></th>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($giftCards as $gc): ?>
        <tr>
          <td style="font-weight:800;">
            <?= htmlspecialchars($gc['code']) ?>
          </td>

          <td>
            <?= number_format((float)$gc['amount'], 2) ?>
          </td>

          <td>
            <?php if ($gc['status'] === 'created'): ?>
              <span class="badge">Created</span>
            <?php elseif ($gc['status'] === 'assigned'): ?>
              <span class="badge badge-success">Assigned</span>
            <?php else: ?>
              <span class="badge badge-warn">Redeemed</span>
            <?php endif; ?>
          </td>

          <td>
            <?= $gc['assigned_at'] ?? '-' ?>
          </td>

          <td>
            <?= $gc['redeemed_at'] ?? '-' ?>
          </td>

          <td class="actions">

            <?php if ($gc['status'] === 'created'): ?>
              <form method="POST" action="/gift-cards/assign" style="display:inline;">
                <input type="hidden" name="id" value="<?= (int)$gc['id'] ?>">
                <button class="btn">Assign</button>
              </form>
            <?php endif; ?>

          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>

  </table>
</div>

<?php
$content = ob_get_clean();
$title = 'Gift Cards';
require __DIR__ . '/../layouts/main.php';
?>