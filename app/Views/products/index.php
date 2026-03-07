<?php ob_start(); ?>

<div class="page-head">
  <div>
    <h1 class="page-title">Products</h1>
    <div class="page-subtitle">Manage your store inventory</div>
  </div>

  <div class="page-actions">
    <a href="/products/create" class="btn btn-primary">+ Add Product</a>
    <button type="button" class="btn-secondary" onclick="openProductImportModal()">Import CSV</button>
  </div>
</div>

<div class="card" style="margin-bottom:12px;">
  <div class="form-row">
    <div class="field">
      <label>Search</label>
      <input type="text" id="productSearch" placeholder="Search name or barcode...">
    </div>
    <div class="field">
      <label>Category Filter</label>
      <select id="categoryFilter">
        <option value="">All Categories</option>
        <?php
        $seen = [];
        foreach ($products as $p):
          $cat = trim((string)($p['category'] ?? ''));
          if ($cat === '' || isset($seen[$cat])) continue;
          $seen[$cat] = true;
        ?>
          <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<div class="card">
  <?php if (empty($products)): ?>
    <div class="muted">No products found.</div>
  <?php else: ?>
    <table class="table" id="productsTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Image</th>
          <th>Barcode</th>
          <th>Name</th>
          <th>Category</th>
          <th class="right">Price</th>
          <th class="right">Stock</th>
          <th>Status</th>
          <th class="right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <?php
            $stock = (int)($p['stock'] ?? 0);
            $lowThreshold = (int)($p['low_stock_threshold'] ?? 0);
            $isLow = $lowThreshold > 0 && $stock <= $lowThreshold;
          ?>
          <tr
            data-name="<?= htmlspecialchars(strtolower((string)($p['name'] ?? ''))) ?>"
            data-barcode="<?= htmlspecialchars(strtolower((string)($p['barcode'] ?? ''))) ?>"
            data-category="<?= htmlspecialchars((string)($p['category'] ?? '')) ?>"
            <?= $isLow ? 'style="background: rgba(245,158,11,.08);"' : '' ?>
          >
            <td><?= (int)($p['id'] ?? 0) ?></td>

            <td>
              <?php if (!empty($p['image_path'])): ?>
                <img
                  src="<?= htmlspecialchars($p['image_path']) ?>"
                  alt=""
                  style="width:48px;height:48px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb;"
                >
              <?php else: ?>
                <div style="width:48px;height:48px;border-radius:10px;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.7);" class="muted">
                  —
                </div>
              <?php endif; ?>
            </td>

            <td><?= htmlspecialchars($p['barcode'] ?? '') ?></td>

            <td>
              <strong><?= htmlspecialchars($p['name'] ?? '') ?></strong>
              <?php if ($isLow): ?>
                <div class="mini" style="color:#b45309; margin-top:4px;">Low stock</div>
              <?php endif; ?>
            </td>

            <td>
              <?php if (!empty($p['category'])): ?>
                <span class="badge"><?= htmlspecialchars($p['category']) ?></span>
              <?php else: ?>
                <span class="muted">Uncategorized</span>
              <?php endif; ?>
            </td>

            <td class="right">₱<?= number_format((float)($p['price'] ?? 0), 2) ?></td>
            <td class="right"><?= (int)($p['stock'] ?? 0) ?></td>

            <td>
              <?php if (!empty($p['is_active'])): ?>
                <span class="badge badge-success">Active</span>
              <?php else: ?>
                <span class="badge badge-warn">Inactive</span>
              <?php endif; ?>
            </td>

            <td class="right">
              <a href="/products/edit?id=<?= (int)($p['id'] ?? 0) ?>" class="btn btn-ghost">Edit</a>

              <form action="/products/toggle?id=<?= (int)($p['id'] ?? 0) ?>" method="post" style="display:inline;">
                <button class="btn btn-ghost" type="submit">
                  <?= !empty($p['is_active']) ? 'Disable' : 'Enable' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div id="importProductModal" class="modal">
  <div class="modal-card">
    <div class="modal-head">
      <h3 class="modal-title">Import Products</h3>
      <div class="modal-subtitle">
        Upload a CSV file to bulk add products into the system.
      </div>
    </div>

    <form action="/import/products" method="post" enctype="multipart/form-data">
      <div class="modal-body">
        <div class="modal-field">
          <label>Upload CSV File</label>
          <input class="modal-file" type="file" name="file" accept=".csv" required>
          <div class="modal-help">
            Required columns: barcode, name, category, price, stock
          </div>
          <a class="template-link" href="/assets/templates/product_import_template.csv" download>
            Download product CSV template
          </a>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-secondary" onclick="closeProductImportModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Import Products</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
const searchInput = document.getElementById('productSearch');
const categoryFilter = document.getElementById('categoryFilter');
const productRows = document.querySelectorAll('#productsTable tbody tr');

function filterProducts() {
  const q = (searchInput?.value || '').toLowerCase().trim();
  const cat = categoryFilter?.value || '';

  productRows.forEach(row => {
    const name = row.dataset.name || '';
    const barcode = row.dataset.barcode || '';
    const category = row.dataset.category || '';

    const matchSearch = !q || name.includes(q) || barcode.includes(q);
    const matchCategory = !cat || category === cat;

    row.style.display = (matchSearch && matchCategory) ? '' : 'none';
  });
}

searchInput?.addEventListener('input', filterProducts);
categoryFilter?.addEventListener('change', filterProducts);


function openProductImportModal(){
  document.getElementById('importProductModal').style.display = 'flex';
}
function closeProductImportModal(){
  document.getElementById('importProductModal').style.display = 'none';
}
</script>

<?php
$content = ob_get_clean();
$title = 'Products';
require __DIR__ . '/../layouts/main.php';