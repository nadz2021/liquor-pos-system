<?php
declare(strict_types=1);

use App\Core\DB;

ob_start();

$categories = [];
$products = [];
var_dump($user);
try {
  $pdo = DB::pdo();

  $stCats = $pdo->query("
    SELECT DISTINCT COALESCE(NULLIF(TRIM(category), ''), 'Uncategorized') AS name
    FROM products
    WHERE is_active = 1
    ORDER BY name ASC
  ");
  $categories = $stCats ? $stCats->fetchAll() : [];

  $st = $pdo->query("
    SELECT
      id,
      barcode,
      name,
      COALESCE(NULLIF(TRIM(category), ''), 'Uncategorized') AS category,
      price,
      stock,
      COALESCE(image_path, '') AS image
    FROM products
    WHERE is_active = 1
    ORDER BY name ASC
  ");
  $products = $st ? $st->fetchAll() : [];

} catch (\Throwable $e) {
  $categories = [];
  $products = [];
}
?>

<h1 class="page-title">POS</h1>
<link rel="stylesheet" href="/assets/css/pos.css?v=1">
<div class="pos-grid">

  <!-- LEFT -->
  <div>
    <div class="card">
      <div style="font-weight:800; margin-bottom:8px;">
        Scan / Type <span class="muted" style="font-weight:400;">(USB scanner types like keyboard)</span>
      </div>

      <div class="pos-top">
        <input id="scan" placeholder="Scan barcode / type UPC" autofocus>
        <button class="btn btn-primary" onclick="openCheckout()">Checkout</button>
      </div>

      <div class="muted" style="margin-top:8px;">Print bridge: <?= htmlspecialchars($printBridge) ?></div>
    </div>

    <div class="card" style="margin-top:12px;">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
        <div style="font-weight:800;">Products</div>
        <input id="search" placeholder="Search product..." style="max-width:280px;" oninput="renderProducts()">
      </div>

      <div id="cats" class="cat-row"></div>
      <div id="products" class="products-grid"></div>
    </div>
  </div>

  <!-- RIGHT -->
  <div class="cart-panel">
    <div class="card">
      <div class="cart-header">
        <div style="font-weight:800;">Order</div>
        <button class="btn" onclick="clearCart()">Clear</button>
      </div>

      <div id="cartItems" class="cart-items"></div>

      <div class="cart-totals">
        <div class="total-row"><span class="muted">Subtotal</span><span id="subtotal">0.00</span></div>
        <div class="total-row"><strong>Total</strong><strong id="total">0.00</strong></div>

        <div style="margin-top:10px;">
          <button class="btn btn-primary" style="width:100%;" onclick="openCheckout()">Checkout</button>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Checkout Modal -->
<div id="checkoutModal" class="pos-modal" aria-hidden="true" style="display:none;">
  <div class="pos-modal__backdrop" onclick="closeCheckout()"></div>
  <div class="pos-modal__panel card">
    <div class="pos-modal__head">
      <div style="font-weight:900;font-size:16px;">Checkout</div>
      <button class="btn" type="button" onclick="closeCheckout()">✕</button>
    </div>

    <div class="pos-modal__body">
      <div class="total-row"><span class="muted">Subtotal</span><strong id="co_subtotal">0.00</strong></div>
      <div class="total-row"><span class="muted">Total</span><strong id="co_total">0.00</strong></div>

      <div style="margin-top:10px;">
        <label class="muted" style="display:block;margin-bottom:6px;">Payment Method</label>
        <select id="co_pm">
          <option value="cash">Cash</option>
          <option value="card_terminal">Card (Terminal)</option>
          <option value="gcash_ref">GCash (Ref)</option>
          <option value="gift_card">Gift Card</option>
          <option value="store_credit">Store Credit</option>
        </select>
      </div>

      <div id="co_ref_wrap" style="margin-top:10px; display:none;">
        <label id="co_ref_label" class="muted" style="display:block;margin-bottom:6px;">Reference #</label>
        <input id="co_ref" placeholder="e.g. GCash ref">
      </div>

      <div style="margin-top:10px;">
        <label class="muted" style="display:block;margin-bottom:6px;">Amount Received</label>
        <input id="co_received" inputmode="decimal" placeholder="0.00">
        <div class="muted" id="co_hint" style="margin-top:6px;">Cash: can be higher than total (change computed). Non-cash: must equal total.</div>
      </div>

      <div id="co_denoms" style="margin-top:10px;">
        <div class="muted" style="margin-bottom:6px;">Quick cash</div>
        <div class="denom-row">
          <button type="button" class="btn denom" data-add="20">+20</button>
          <button type="button" class="btn denom" data-add="50">+50</button>
          <button type="button" class="btn denom" data-add="100">+100</button>
          <button type="button" class="btn denom" data-add="200">+200</button>
          <button type="button" class="btn denom" data-add="500">+500</button>
          <button type="button" class="btn denom" data-add="1000">+1000</button>
        </div>
        <div style="margin-top:8px; display:flex; gap:8px;">
          <button type="button" class="btn" onclick="setExact()">Exact</button>
          <button type="button" class="btn" onclick="clearReceived()">Clear</button>
        </div>
      </div>
      <div id="fieldCustomerFields" style="margin-top:12px; display:none;">
        <div class="field">
          <label>Customer Name</label>
          <input id="co_customer_name" type="text" placeholder="Enter customer name">
        </div>

        <div class="field" style="margin-top:10px;">
          <label>Contact Number</label>
          <input id="co_customer_contact" type="text" placeholder="Enter contact number">
        </div>

        <div class="field" style="margin-top:10px;">
          <label>Address (Optional)</label>
          <textarea id="co_customer_address" placeholder="Enter address"></textarea>
        </div>
      </div>

      <div class="total-row" style="margin-top:8px;">
        <span class="muted">Change</span>
        <strong id="co_change">0.00</strong>
      </div>
    </div>

    <div class="pos-modal__foot">
      <button class="btn" type="button" onclick="closeCheckout()">Cancel</button>
      <button class="btn btn-primary" type="button" onclick="confirmCheckout()">Confirm</button>
    </div>
  </div>
</div>

<script>
const PRINT_BRIDGE_URL = <?= json_encode($printBridge) ?>;
const CATEGORIES = <?= json_encode($categories, JSON_UNESCAPED_SLASHES) ?>;
const PRODUCTS = <?= json_encode($products, JSON_UNESCAPED_SLASHES) ?>;
const CURRENT_USER = <?= json_encode($user ?? []) ?>;
const SELLING_MODE = <?= json_encode($user['selling_mode'] ?? 'in_store') ?>;
console.log(SELLING_MODE);
let activeCat = 'all';
const cart = [];

const scanEl = document.getElementById('scan');
scanEl.addEventListener('keydown', async (e) => {
  if (e.key !== 'Enter') return;
  const code = scanEl.value.trim();
  if (!code) return;
  scanEl.value = '';

  const res = await fetch('/pos/scan', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({code})
  });
  const data = await res.json();
  if (!data.ok) { alert(data.msg || 'Not found'); return; }

  addToCart({
    product_id: data.product.id,
    barcode: data.product.barcode,
    name: data.product.name,
    unit_price: parseFloat(data.product.price),
    image: data.product.image_path || ''
  });
});

function money(n){ return (Math.round(n*100)/100).toFixed(2); }

function addToCart(p){
  const ex = cart.find(x => x.product_id === p.product_id);
  if (ex) ex.qty += 1;
  else cart.push({product_id:p.product_id, barcode:p.barcode, name:p.name, qty:1, unit_price:p.unit_price, image:p.image || ''});
  renderCart();
}

function inc(id){
  const it = cart.find(x => x.product_id === id);
  if (!it) return;
  it.qty += 1;
  renderCart();
}
function dec(id){
  const it = cart.find(x => x.product_id === id);
  if (!it) return;
  it.qty -= 1;
  if (it.qty <= 0) cart.splice(cart.findIndex(x => x.product_id === id), 1);
  renderCart();
}
function removeItem(id){
  const idx = cart.findIndex(x => x.product_id === id);
  if (idx >= 0) cart.splice(idx,1);
  renderCart();
}
function clearCart(){
  if (!confirm('Clear cart?')) return;
  cart.length = 0;
  renderCart();
}

function renderCart(){
  const el = document.getElementById('cartItems');
  el.innerHTML = '';
  let subtotal = 0;

  cart.forEach(it => {
    subtotal += it.qty * it.unit_price;

    const row = document.createElement('div');
    row.className = 'cart-item';
    row.innerHTML = `
      <div>
        <div class="cart-title">${esc(it.name)}</div>
        <div class="cart-sub">${esc(it.barcode)} • ₱${money(it.unit_price)}</div>
      </div>
      <div>
        <div class="qty-controls">
          <button type="button" onclick="dec(${it.product_id})">−</button>
          <div class="qty-pill">${it.qty}</div>
          <button type="button" onclick="inc(${it.product_id})">+</button>
          <button type="button" onclick="removeItem(${it.product_id})" title="Remove">✕</button>
        </div>
      </div>
    `;
    el.appendChild(row);
  });

  document.getElementById('subtotal').innerText = money(subtotal);
  document.getElementById('total').innerText = money(subtotal);
}

function esc(s){ return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

function renderCats(){
  const catsEl = document.getElementById('cats');
  catsEl.innerHTML = '';
  catsEl.appendChild(chip('All', 'all'));

  (CATEGORIES || []).forEach(c => catsEl.appendChild(chip(c.name, c.name)));
  setActiveChip();
}

function chip(label, value){
  const d = document.createElement('div');
  d.className = 'cat-chip';
  d.innerText = label;
  d.onclick = () => { activeCat = value; setActiveChip(); renderProducts(); };
  d.dataset.cat = value;
  return d;
}

function setActiveChip(){
  document.querySelectorAll('.cat-chip').forEach(x => x.classList.remove('active'));

  const val = (window.CSS && CSS.escape) ? CSS.escape(String(activeCat)) : String(activeCat).replace(/"/g, '\\"');
  const active = document.querySelector('.cat-chip[data-cat="' + val + '"]');

  if (active) active.classList.add('active');
}

function renderProducts(){
  const q = (document.getElementById('search').value || '').toLowerCase().trim();
  const grid = document.getElementById('products');
  grid.innerHTML = '';

  let list = PRODUCTS || [];
  if (activeCat !== 'all') list = list.filter(p => String(p.category || '') === String(activeCat));

  if (q) {
    list = list.filter(p =>
      (p.name || '').toLowerCase().includes(q) ||
      (p.barcode || '').toLowerCase().includes(q)
    );
  }

  list.forEach(p => {
    const card = document.createElement('div');
    card.className = 'product-card';
    const imgSrc = (p.image && String(p.image).trim() !== '') ? esc(p.image) : '/assets/img/no-image.svg';

    card.innerHTML = `
      <div class="product-img"><img src="${imgSrc}" alt=""></div>
      <div class="product-body">
        <div class="product-name">${esc(p.name)}</div>
        <div class="product-meta">
          <span class="muted">${esc(p.barcode || '')}</span>
          <span class="price">₱${money(parseFloat(p.price || 0))}</span>
        </div>
      </div>
    `;

    card.onclick = () => addToCart({
      product_id: parseInt(p.id, 10),
      barcode: p.barcode,
      name: p.name,
      unit_price: parseFloat(p.price),
      image: p.image || ''
    });

    grid.appendChild(card);
  });
}

// Checkout modal
function openCheckout(){
  const fieldWrap = document.getElementById('fieldCustomerFields');
  if (fieldWrap) {
    fieldWrap.style.display = (SELLING_MODE === 'field') ? 'block' : 'none';
  }

  const nameEl = document.getElementById('co_customer_name');
  const contactEl = document.getElementById('co_customer_contact');
  const addressEl = document.getElementById('co_customer_address');

  if (nameEl) nameEl.value = '';
  if (contactEl) contactEl.value = '';
  if (addressEl) addressEl.value = '';
  if (!cart.length) return alert('Empty cart');

  const sub = parseFloat(document.getElementById('subtotal').innerText || '0');
  const tot = parseFloat(document.getElementById('total').innerText || '0');

  document.getElementById('co_subtotal').innerText = money(sub);
  document.getElementById('co_total').innerText = money(tot);

  document.getElementById('co_pm').value = 'cash';
  document.getElementById('co_ref').value = '';
  document.getElementById('co_ref_wrap').style.display = 'none';
  document.getElementById('co_received').value = money(tot);

  updateCheckoutComputed();
  const modalEl = document.getElementById('checkoutModal');
  modalEl.style.display = 'block';
  modalEl.classList.add('open');
}

function closeCheckout(){
  const modalEl = document.getElementById('checkoutModal');
  modalEl.classList.remove('open');
  modalEl.style.display = 'none';
}

function updateCheckoutComputed(){
  const tot = parseFloat(document.getElementById('co_total').innerText || '0');
  const pm = document.getElementById('co_pm').value;
  const rec = parseFloat(document.getElementById('co_received').value || '0');

  // ✅ show ref for all non-cash (cash ref optional)
  const needsRef = (pm !== 'cash');
  document.getElementById('co_ref_wrap').style.display = needsRef ? 'block' : 'none';

  // update label depending on method
  const refLabel = document.getElementById('co_ref_label');
  if (refLabel) {
    refLabel.innerText =
      pm === 'gcash_ref' ? 'GCash Reference #' :
      pm === 'gift_card' ? 'Gift Card Code #' :
      pm === 'store_credit' ? 'Store Credit Ref #' :
      pm === 'card_terminal' ? 'Terminal Slip / Ref #' :
      'Reference #';
  }

  let change = 0;
  if (pm === 'cash' && isFinite(rec)) change = Math.max(0, rec - tot);
  document.getElementById('co_change').innerText = money(change);
}

document.getElementById('co_pm')?.addEventListener('change', () => {
  const tot = parseFloat(document.getElementById('co_total').innerText || '0');
  const pm = document.getElementById('co_pm').value;
  if (pm !== 'cash') document.getElementById('co_received').value = money(tot);
  updateCheckoutComputed();
});
document.getElementById('co_received')?.addEventListener('input', updateCheckoutComputed);

async function confirmCheckout(){
  const customer_name = (document.getElementById('co_customer_name')?.value || '').trim();
  const customer_contact = (document.getElementById('co_customer_contact')?.value || '').trim();
  const customer_address = (document.getElementById('co_customer_address')?.value || '').trim();

  if (SELLING_MODE === 'field') {
    if (!customer_name || !customer_contact) {
      return alert('Customer name and contact are required for outside field sales.');
    }
  }
  const total = parseFloat(document.getElementById('co_total').innerText || '0');
  const pm = document.getElementById('co_pm').value;
  const payment_ref = (document.getElementById('co_ref').value || '').trim() || null;
  const amount_received = parseFloat(document.getElementById('co_received').value || '0');

  if (!isFinite(amount_received) || amount_received <= 0) return alert("Amount received is required.");
  if (pm === 'cash' && amount_received + 1e-9 < total) return alert("Cash amount is less than total.");
  if (pm !== 'cash' && Math.abs(amount_received - total) > 0.009) return alert("Amount must equal total for this payment type.");

  const res = await fetch('/pos/checkout', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({
      cart,
      payment_method: pm,
      payment_ref,
      amount_received,
      customer_name,
      customer_contact,
      customer_address
    })
  });

  const data = await res.json();
  if (!data.ok) return alert(data.msg || 'Checkout failed');

  try{
    await fetch(PRINT_BRIDGE_URL + '/print', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({text:data.receipt_text, cut:true, drawer:!!data.drawer})
    });
  }catch(e){
    alert('Print bridge not reachable. Start print-bridge first.');
  }

  closeCheckout();
  cart.length = 0;
  renderCart();

  alert(`Sale: ${data.sale_no}\nTotal: ${data.total}\nReceived: ${data.amount_received}\nChange: ${data.change_due}`);
  window.location.href = '/sales/show?id=' + data.sale_id;
}

function setExact(){
  const tot = parseFloat(document.getElementById('co_total').innerText || '0');
  document.getElementById('co_received').value = money(tot);
  updateCheckoutComputed();
}
function clearReceived(){
  document.getElementById('co_received').value = '';
  updateCheckoutComputed();
}

document.addEventListener('click', (e) => {
  const btn = e.target.closest('.denom');
  if (!btn) return;

  const add = parseFloat(btn.dataset.add || '0');
  const input = document.getElementById('co_received');
  const cur = parseFloat(input.value || '0') || 0;

  input.value = money(cur + add);
  updateCheckoutComputed();
});

// init
renderCats();
renderProducts();
renderCart();
closeCheckout();
</script>

<?php
$content = ob_get_clean();
$title = 'POS';
require __DIR__ . '/../layouts/main.php';