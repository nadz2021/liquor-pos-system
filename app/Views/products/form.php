<?php ob_start(); ?>

<div class="page-head">
  <div>
    <h1 class="page-title"><?= $product ? 'Edit Product' : 'Create Product' ?></h1>
    <div class="page-subtitle">Fill in the details below. Upload an image for POS cards.</div>
  </div>
  <div class="page-actions">
    <a class="btn" href="/products">Back</a>
  </div>
</div>

<div class="card">
  <form class="form" method="post" enctype="multipart/form-data"
        action="<?= $product ? '/products/update?id='.(int)$product['id'] : '/products/store' ?>">

    <div class="form-row">
      <div class="field">
        <label>Barcode / UPC</label>
        <input name="barcode" required value="<?= htmlspecialchars($product['barcode'] ?? '') ?>">
      </div>

      <div class="field">
        <label>Name</label>
        <input name="name" required value="<?= htmlspecialchars($product['name'] ?? '') ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="field">
        <label>Main Category</label>
        <select name="category_id" id="category_id">
          <option value="">Select category</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>"
              <?= ((int)($product['category_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label>Sub Category</label>
        <select name="subcategory_id" id="subcategory_id">
          <option value="">Select subcategory</option>
          <?php foreach ($subcategories as $s): ?>
            <option value="<?= (int)$s['id'] ?>"
              <?= ((int)($product['subcategory_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <input type="hidden" name="category" id="category_name_text" value="<?= htmlspecialchars($product['category'] ?? '') ?>">

    <div class="form-row">
      <div class="field">
        <label>Image</label>
        <input type="file" name="image" accept="image/*">
        <div class="mini">Tip: JPG/PNG/WEBP, max 2MB.</div>
      </div>

      <div class="field">
        <label>Preview</label>
        <?php $preview = !empty($product['image_path']) ? $product['image_path'] : '/assets/img/no-image.svg'; ?>
        <img src="<?= htmlspecialchars($preview) ?>" alt="" class="product-preview">
      </div>
    </div>

    <div class="form-row">
      <div class="field">
        <label>Cost</label>
        <input type="number" step="0.01" name="cost"
               value="<?= htmlspecialchars((string)($product['cost'] ?? '0')) ?>">
      </div>

      <div class="field">
        <label>Price</label>
        <input type="number" step="0.01" name="price"
               value="<?= htmlspecialchars((string)($product['price'] ?? '0')) ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="field">
        <label>Stock</label>
        <input type="number" name="stock"
               value="<?= htmlspecialchars((string)($product['stock'] ?? '0')) ?>">
      </div>

      <div class="field">
        <label>Reorder Point</label>
        <input type="number" name="reorder_point"
               value="<?= htmlspecialchars((string)($product['reorder_point'] ?? '0')) ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="field">
        <label>Low Stock Threshold</label>
        <input type="number" name="low_stock_threshold"
               value="<?= htmlspecialchars((string)($product['low_stock_threshold'] ?? '0')) ?>">
      </div>

      <div class="field">
        <label>Active</label>
        <label class="checkbox-line">
          <input type="checkbox" name="is_active" <?= ((int)($product['is_active'] ?? 1)===1)?'checked':'' ?>>
          Enable this product in POS
        </label>
      </div>
    </div>

    <div class="page-actions">
      <button class="btn btn-primary" type="submit">Save</button>
      <a class="btn" href="/products">Cancel</a>
    </div>
  </form>
</div>

<script>
  const categorySelect = document.getElementById('category_id');
  const subcategorySelect = document.getElementById('subcategory_id');
  const categoryNameText = document.getElementById('category_name_text');

  categorySelect.addEventListener('change', async function () {
    const categoryId = this.value || '';

    const selectedOption = this.options[this.selectedIndex];
    categoryNameText.value = selectedOption ? selectedOption.text : '';

    subcategorySelect.innerHTML = '<option value="">Loading...</option>';

    if (!categoryId) {
      subcategorySelect.innerHTML = '<option value="">Select subcategory</option>';
      return;
    }

    const res = await fetch('/subcategories/by-category?category_id=' + encodeURIComponent(categoryId));
    const data = await res.json();

    subcategorySelect.innerHTML = '<option value="">Select subcategory</option>';
    if (data.ok && Array.isArray(data.rows)) {
      data.rows.forEach(row => {
        const opt = document.createElement('option');
        opt.value = row.id;
        opt.textContent = row.name;
        subcategorySelect.appendChild(opt);
      });
    }
  });

  if (categorySelect.value) {
    const evt = new Event('change');
    categorySelect.dispatchEvent(evt);
  }
</script>

<?php $content = ob_get_clean(); $title='Product'; require __DIR__ . '/../layouts/main.php'; ?>