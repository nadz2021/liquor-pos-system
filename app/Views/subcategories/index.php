<?php ob_start(); ?>

<div class="page-head">
  <div>
    <h1 class="page-title">Sub Categories</h1>
    <div class="page-subtitle">Manage sub categories under each main category.</div>
  </div>
  <div class="page-actions">
    <button type="button" class="btn-secondary" onclick="openSubCategoryImportModal()">Import CSV</button>
  </div>
</div>
<?php if (!empty($_SESSION['flash_import'])): ?>
  <?php $flash = $_SESSION['flash_import']; unset($_SESSION['flash_import']); ?>
  <div class="card" style="margin-bottom:12px; border-color: <?= ($flash['type'] ?? '') === 'error' ? '#ef4444' : '#16a34a' ?>;">
    <strong><?= ($flash['type'] ?? '') === 'error' ? 'Import Error' : 'Import Result' ?></strong>
    <div style="margin-top:6px;">
      <?= htmlspecialchars($flash['message'] ?? '') ?>
    </div>
  </div>
<?php endif; ?>
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
<div id="importSubCategoryModal" class="modal">
  <div class="modal-card">
    <div class="modal-head">
      <h3 class="modal-title">Import Sub Categories</h3>
      <div class="modal-subtitle">
        Upload a CSV file to bulk add sub categories into the system.
      </div>
    </div>

    <form action="/import/subcategories" method="post" enctype="multipart/form-data">
      <div class="modal-body">
        <div class="modal-field">
          <label>Upload CSV File</label>
          <input class="modal-file" type="file" name="file" accept=".csv" required>
          <div class="modal-help">
            Required column: name
          </div>
          <a class="template-link" href="/assets/templates/sub_category_import_template.csv" download>
            Download sub category CSV template
          </a>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-secondary" onclick="closeSubCategoryImportModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Import Sub Categories</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function openSubCategoryImportModal(){
  document.getElementById('importSubCategoryModal').style.display = 'flex';
}
function closeSubCategoryImportModal(){
  document.getElementById('importSubCategoryModal').style.display = 'none';
}
</script>
<?php
$content = ob_get_clean();
$title = 'Sub Categories';
require __DIR__ . '/../layouts/main.php';