<?php ob_start(); ?>

<div class="page-head">
  <div>
    <h1 class="page-title">Categories</h1>
    <div class="page-subtitle">Manage your product categories</div>
  </div>
  <div class="page-actions">
    <a href="/categories/create" class="btn btn-primary">+ Add Category</a>
    <button type="button" class="btn-secondary" onclick="openCategoryImportModal()">Import CSV</button>
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
  <?php if (empty($categories)): ?>
    <div class="muted">No categories found.</div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Description</th>
          <th>Status</th>
          <th class="right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $c): ?>
          <tr>
            <td><?= (int)($c['id'] ?? 0) ?></td>
            <td><?= htmlspecialchars($c['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($c['description'] ?? '') ?></td>
            <td>
              <?php if (!empty($c['is_active'])): ?>
                <span class="badge badge-success">Active</span>
              <?php else: ?>
                <span class="badge badge-warn">Inactive</span>
              <?php endif; ?>
            </td>
            <td class="right">
              <a href="/categories/edit?id=<?= (int)($c['id'] ?? 0) ?>" class="btn">Edit</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<div id="importCategoryModal" class="modal">
  <div class="modal-card">
    <div class="modal-head">
      <h3 class="modal-title">Import Categories</h3>
      <div class="modal-subtitle">
        Upload a CSV file to bulk add categories into the system.
      </div>
    </div>

    <form action="/import/categories" method="post" enctype="multipart/form-data">
      <div class="modal-body">
        <div class="modal-field">
          <label>Upload CSV File</label>
          <input class="modal-file" type="file" name="file" accept=".csv" required>
          <div class="modal-help">
            Required column: name
          </div>
          <a class="template-link" href="/assets/templates/category_import_template.csv" download>
            Download category CSV template
          </a>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-secondary" onclick="closeCategoryImportModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Import Categories</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function openCategoryImportModal(){
  document.getElementById('importCategoryModal').style.display = 'flex';
}
function closeCategoryImportModal(){
  document.getElementById('importCategoryModal').style.display = 'none';
}
</script>
<?php
$content = ob_get_clean();
$title = 'Categories';
require __DIR__ . '/../layouts/main.php';