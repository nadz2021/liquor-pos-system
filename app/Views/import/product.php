<?php ob_start(); ?>

<div class="page-head">
  <div>
    <h1 class="page-title">Import Products</h1>
    <div class="page-subtitle">Upload CSV file to bulk add products</div>
  </div>
</div>

<div class="card form-card">

<form method="post" action="/import/products" enctype="multipart/form-data">

<label>CSV File</label>
<input type="file" name="file" accept=".csv" required>

<button type="submit" class="btn btn-primary">Import Products</button>

</form>

</div>

<?php
$content = ob_get_clean();
$title = "Import Products";
require __DIR__ . '/../layouts/main.php';
?>