<?php ob_start(); ?>

<div class="page-head">
  <div>
    <h1 class="page-title">Edit Product</h1>
    <div class="page-subtitle">Update product information</div>
  </div>
  <div class="page-actions">
    <a href="/products" class="btn">Back to Products</a>
  </div>
</div>

<div class="card">
  <?php
    $action = '/products/update';
    require __DIR__ . '/form.php';
  ?>
</div>

<?php
$content = ob_get_clean();
$title = 'Edit Product';
require __DIR__ . '/../layouts/main.php';