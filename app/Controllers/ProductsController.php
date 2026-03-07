<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Product;
use App\Models\Audit;
use App\Models\Category;
use App\Models\Subcategory;

final class ProductsController extends Controller {
  public function index(): void {
    Auth::requireLogin();
    if (!Auth::can('products.manage')) { http_response_code(403); echo "Forbidden"; return; }
    $this->view('products/index', ['user'=>Auth::user(), 'products'=>Product::list()]);
  }

  public function create(): void {
    Auth::requireLogin();
    if (!Auth::can('products.manage')) { http_response_code(403); echo "Forbidden"; return; }

    $this->view('products/form', [
      'user' => Auth::user(),
      'product' => null,
      'categories' => Category::all(),
      'subcategories' => [],
    ]);
  }

  public function store(): void {
  Auth::requireLogin();
  if (!Auth::can('products.manage')) { http_response_code(403); echo "Forbidden"; return; }

  $d = $this->sanitize($_POST);
  if ($d['barcode']==='' || $d['name']==='') { $this->redirect('/products/create'); }

  // ✅ image upload here
  try {
    $imagePath = $this->handleProductImageUpload();
    if ($imagePath !== null) $d['image_path'] = $imagePath;
  } catch (\Throwable $e) {
    // If you want: store error in session and show on form later
    // For now, simple fallback:
    http_response_code(400);
    echo "Upload error: " . htmlspecialchars($e->getMessage());
    return;
  }

  $id = Product::create($d);
  Audit::log((int)Auth::user()['id'], 'product.created', ['product_id'=>$id]);
  $this->redirect('/products');
}

  public function edit(): void {
  Auth::requireLogin();
  if (!Auth::can('products.manage')) { http_response_code(403); echo "Forbidden"; return; }

  $id = (int)($_GET['id'] ?? 0);
  $p = $id ? Product::find($id) : null;
  if (!$p) { http_response_code(404); echo "Not found"; return; }

  $subcategories = !empty($p['category_id'])
    ? Subcategory::byCategory((int)$p['category_id'])
    : [];

  $this->view('products/form', [
    'user' => Auth::user(),
    'product' => $p,
    'categories' => Category::all(),
    'subcategories' => $subcategories,
  ]);
}

  public function update(): void {
  Auth::requireLogin();
  if (!Auth::can('products.manage')) { http_response_code(403); echo "Forbidden"; return; }

  $id = (int)($_GET['id'] ?? 0);
  if (!$id) { http_response_code(400); echo "Bad request"; return; }

  $d = $this->sanitize($_POST);

  // ✅ image upload here (only overwrite if a new image is chosen)
  try {
    $imagePath = $this->handleProductImageUpload();
    if ($imagePath !== null) $d['image_path'] = $imagePath;
  } catch (\Throwable $e) {
    http_response_code(400);
    echo "Upload error: " . htmlspecialchars($e->getMessage());
    return;
  }

  Product::update($id, $d);
  Audit::log((int)Auth::user()['id'], 'product.updated', ['product_id'=>$id]);
  $this->redirect('/products');
}

  public function toggle(): void {
    Auth::requireLogin();
    if (!Auth::can('products.manage')) { http_response_code(403); echo "Forbidden"; return; }
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo "Bad request"; return; }
    Product::toggle($id);
    Audit::log((int)Auth::user()['id'], 'product.toggled', ['product_id'=>$id]);
    $this->redirect('/products');
  }

  private function handleProductImageUpload(): ?string {
  if (empty($_FILES['image']) || ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    return null; // no upload
  }

  if (($_FILES['image']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    throw new \RuntimeException("Image upload failed.");
  }

  $tmp = (string)$_FILES['image']['tmp_name'];
  $name = (string)$_FILES['image']['name'];
  $size = (int)($_FILES['image']['size'] ?? 0);

  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  $allowed = ['jpg','jpeg','png','webp'];

  if (!in_array($ext, $allowed, true)) {
    throw new \RuntimeException("Invalid image type. Use JPG/PNG/WEBP.");
  }

  if ($size > 2 * 1024 * 1024) {
    throw new \RuntimeException("Image too large. Max 2MB.");
  }

  $newName = 'p_' . bin2hex(random_bytes(8)) . '.' . $ext;

  // /app/Controllers -> /public/uploads/products
  $destDir = dirname(__DIR__, 2) . '/public/uploads/products';
  if (!is_dir($destDir)) mkdir($destDir, 0777, true);

  $dest = $destDir . '/' . $newName;

  if (!move_uploaded_file($tmp, $dest)) {
    throw new \RuntimeException("Failed to save image.");
  }

  return '/uploads/products/' . $newName;
}

  private function sanitize(array $in): array {
    return [
      'barcode' => trim((string)($in['barcode'] ?? '')),
      'name' => trim((string)($in['name'] ?? '')),
      'category' => trim((string)($in['category'] ?? '')), // compatibility
      'category_id' => (int)($in['category_id'] ?? 0),
      'subcategory_id' => (int)($in['subcategory_id'] ?? 0),
      'cost' => (float)($in['cost'] ?? 0),
      'price' => (float)($in['price'] ?? 0),
      'stock' => (int)($in['stock'] ?? 0),
      'reorder_point' => (int)($in['reorder_point'] ?? 0),
      'low_stock_threshold' => (int)($in['low_stock_threshold'] ?? 0),
      'is_active' => isset($in['is_active']) ? 1 : 0,
    ];
}
}
