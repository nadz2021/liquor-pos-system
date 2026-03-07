<?php ob_start(); ?>

<div class="page-head">
  <div>
    <h1 class="page-title">Edit Category</h1>
    <div class="page-subtitle">Update category name</div>
  </div>
  <div class="page-actions">
    <a href="/categories" class="btn">Back to Categories</a>
  </div>
</div>

<div class="card">
  <form method="post" action="/categories/update?id=<?= (int)$category['id'] ?>" class="form">

    <div class="field">
      <label>Category Name</label>
      <input
        type="text"
        name="name"
        value="<?= htmlspecialchars($category['name']) ?>"
        required
      >
    </div>

    <div style="margin-top:10px;">
      <button class="btn btn-primary">Update Category</button>
      <a href="/categories" class="btn">Cancel</a>
    </div>

  </form>
</div>

<?php
$content = ob_get_clean();
$title = 'Edit Category';
require __DIR__ . '/../layouts/main.php';