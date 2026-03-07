<?php ob_start(); ?>

<?php
$isEdit = !empty($category['id']);
$formAction = $isEdit
  ? '/categories/update?id=' . (int)$category['id']
  : '/categories/store';
?>

<div class="page-head">
  <div>
    <h1 class="page-title"><?= $isEdit ? 'Edit Category' : 'Add Category' ?></h1>
    <div class="page-subtitle">
      <?= $isEdit ? 'Update category information' : 'Create a new category' ?>
    </div>
  </div>
  <div class="page-actions">
    <a href="/categories" class="btn">Back to Categories</a>
  </div>
</div>

<div class="card">
  <form action="<?= htmlspecialchars($formAction) ?>" method="post" class="form">
    <div class="form-row">
      <div class="field">
        <label>Category Name</label>
        <input
          type="text"
          name="name"
          value="<?= htmlspecialchars($category['name'] ?? '') ?>"
          required
        >
      </div>

      <div class="field">
        <label>Status</label>
        <label style="display:inline-flex; align-items:center; gap:8px; margin-top:10px;">
          <input
            type="checkbox"
            name="is_active"
            value="1"
            <?= !isset($category['is_active']) || (int)$category['is_active'] === 1 ? 'checked' : '' ?>
            style="width:auto;"
          >
          Active
        </label>
      </div>
    </div>

    <div class="field">
      <label>Description</label>
      <textarea name="description" placeholder="Optional category description"><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
    </div>

    <div style="display:flex; gap:8px; margin-top:8px;">
      <button type="submit" class="btn btn-primary">Save Category</button>
      <a href="/categories" class="btn">Cancel</a>
    </div>
  </form>
</div>

<?php
$content = ob_get_clean();
$title = $isEdit ? 'Edit Category' : 'Add Category';
require __DIR__ . '/../layouts/main.php';