<?php ob_start(); ?>

<div class="page-head">
  <div>
    <h1 class="page-title">Sub Categories</h1>
    <div class="page-subtitle">Manage sub categories under each main category.</div>
  </div>
</div>

<div class="card">
  <form class="form" method="post" action="/subcategories/store">
    <div class="form-row">
      <div class="field">
        <label>Main Category</label>
        <select name="category_id" required>
          <option value="">Select category</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label>Sub Category Name</label>
        <input name="name" required>
      </div>
    </div>

    <div class="page-actions">
      <button class="btn btn-primary" type="submit">Add Sub Category</button>
    </div>
  </form>
</div>

<div class="spacer"></div>

<table class="table">
  <thead>
    <tr>
      <th>Main Category</th>
      <th>Sub Category</th>
      <th class="actions"></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($subcategories as $s): ?>
      <tr>
        <td><?= htmlspecialchars($s['category_name']) ?></td>
        <td><?= htmlspecialchars($s['name']) ?></td>
        <td class="actions">
          <form method="post" action="/subcategories/delete">
            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
            <button class="btn btn-danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php
$content = ob_get_clean();
$title = 'Sub Categories';
require __DIR__ . '/../layouts/main.php';