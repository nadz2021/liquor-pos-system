<?php ob_start(); ?>

<div class="page-head">
  <div>
    <h1 class="page-title">Add Product</h1>
    <div class="page-subtitle">Create a new product</div>
  </div>
  <div class="page-actions">
    <a href="/products" class="btn">Back to Products</a>
  </div>
</div>

<div class="card">
  <?php
    $action = '/products/store';
    $product = $product ?? null;
    require __DIR__ . '/form.php';
  ?>
</div>

<?php
$content = ob_get_clean();
$title = 'Add Product';
require __DIR__ . '/../layouts/main.php';