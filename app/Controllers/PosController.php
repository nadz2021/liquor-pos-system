<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Env;
use App\Core\DB;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Audit;
use App\Models\Customer;

final class PosController extends Controller {
  public function index() {
    Auth::requireLogin();
    if (!Auth::can('pos.use')) { http_response_code(403); echo "Forbidden"; return; }

    $this->view('pos/index', [
      'user' => Auth::user(),
      'printBridge' => Env::get('PRINT_BRIDGE_URL','http://127.0.0.1:9123'),
      // ✅ IMPORTANT: load POS-specific CSS so layout/cards/cart work
      'pageCss' => '/assets/css/pos.css',
    ]);
  }
  

  public function scan() {
    Auth::requireLogin();
    if (!Auth::can('pos.use')) return $this->json(['ok'=>false,'msg'=>'Forbidden'], 403);

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $code = trim((string)($body['code'] ?? ''));
    if ($code==='') return $this->json(['ok'=>false,'msg'=>'Empty code'], 422);

    $p = Product::findByBarcode($code);
    if (!$p) return $this->json(['ok'=>false,'msg'=>'Not found'], 404);
    $this->json(['ok'=>true,'product'=>$p]);
  }

  public function checkout() {
    Auth::requireLogin();
    if (!Auth::can('pos.use')) return $this->json(['ok'=>false,'msg'=>'Forbidden'], 403);

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $cart = $body['cart'] ?? [];
    $pm = (string)($body['payment_method'] ?? 'cash');
    $ref = $body['payment_ref'] ?? null;
    $amountReceived = (float)($body['amount_received'] ?? 0);
    $customerName = trim((string)($body['customer_name'] ?? ''));
    $customerContact = trim((string)($body['customer_contact'] ?? ''));
    $customerAddress = trim((string)($body['customer_address'] ?? ''));
    $saleChannel = (string)(Auth::user()['selling_mode'] ?? 'in_store');

    if (!is_array($cart) || count($cart)===0) return $this->json(['ok'=>false,'msg'=>'Empty cart'], 422);

    $allowed = ['cash','gcash_ref','gift_card','store_credit','card_terminal'];
    if (!in_array($pm, $allowed, true)) return $this->json(['ok'=>false,'msg'=>'Invalid payment method'], 422);

    $pdo = DB::pdo();
    $pdo->beginTransaction();

    try {
      $saleNo = 'S' . date('YmdHis') . '-' . random_int(100,999);

      $subtotal = 0.0;
      foreach ($cart as $line) $subtotal += ((float)$line['unit_price']) * ((int)$line['qty']);

      $vatEnabled = (Setting::get('vat_enabled', Env::get('VAT_ENABLED','0')) ?? '0') === '1';
      $vatRate = (float)(Setting::get('vat_rate', Env::get('VAT_RATE','12')) ?? '12');
      $taxTotal = $vatEnabled ? round($subtotal * ($vatRate/100), 2) : 0.0;

      $total = round($subtotal + $taxTotal, 2);

      if ($amountReceived <= 0) {
        throw new \RuntimeException("Amount received is required.");
      }

      $changeDue = 0.0;
      if ($pm === 'cash') {
        if ($amountReceived + 1e-9 < $total) {
          throw new \RuntimeException("Cash amount is less than total.");
        }
        $changeDue = round($amountReceived - $total, 2);
      } else {
        // strict exact payment for non-cash (simple + clean)
        if (abs($amountReceived - $total) > 0.009) {
          throw new \RuntimeException("Amount must equal total for this payment type.");
        }
      }

      $cashierId = (int)Auth::user()['id'];
      $customerId = null;

      if ($saleChannel === 'field') {
          if ($customerName === '' || $customerContact === '') {
              return $this->json(['ok' => false, 'msg' => 'Customer name and contact are required for outside field sales.'], 422);
          }

          $customerId = Customer::findOrCreate(
              $customerName,
              $customerContact,
              $customerAddress !== '' ? $customerAddress : null
          );
      }

      $st = $pdo->prepare("
          INSERT INTO sales (
              sale_no,
              cashier_id,
              customer_id,
              sale_channel,
              subtotal,
              tax_total,
              total,
              amount_received,
              change_due,
              payment_method,
              payment_ref
          )
          VALUES (?,?,?,?,?,?,?,?,?,?,?)
      ");
      $st->execute([
          $saleNo,
          $cashierId,
          $customerId,
          $saleChannel,
          $subtotal,
          $taxTotal,
          $total,
          $amountReceived,
          $changeDue,
          $pm,
          $ref
      ]);
      $saleId = (int)$pdo->lastInsertId();

      $itemSt = $pdo->prepare("INSERT INTO sale_items (sale_id,product_id,barcode,name,qty,unit_price,line_total)
                              VALUES (?,?,?,?,?,?,?)");
      $stockSt = $pdo->prepare("UPDATE products SET stock=stock-? WHERE id=? AND stock>=?");
      $movSt = $pdo->prepare("INSERT INTO inventory_movements (product_id,type,ref_table,ref_id,qty_change,note,created_by)
                              VALUES (?, 'sale', 'sales', ?, ?, ?, ?)");

      foreach ($cart as $line) {
        $pid = (int)$line['product_id'];
        $qty = (int)$line['qty'];
        $unit = (float)$line['unit_price'];
        $lt = round($unit * $qty, 2);

        $itemSt->execute([$saleId,$pid,$line['barcode'],$line['name'],$qty,$unit,$lt]);

        $stockSt->execute([$qty,$pid,$qty]);
        if ($stockSt->rowCount()===0) throw new \RuntimeException("Insufficient stock for {$line['name']}");

        $movSt->execute([$pid,$saleId,-$qty,"Sale {$saleNo}",$cashierId]);
      }

      Audit::log($cashierId, 'sale.created', [
          'sale_no' => $saleNo,
          'total' => $total,
          'pm' => $pm,
          'amount_received' => $amountReceived,
          'change_due' => $changeDue,
          'sale_channel' => $saleChannel,
          'customer_name' => $customerName !== '' ? $customerName : null,
      ]);

      $q = $pdo->prepare("INSERT INTO sync_queue (entity, entity_id, payload) VALUES ('sale', ?, ?)");
      $q->execute([$saleId, json_encode(['sale_id'=>$saleId,'sale_no'=>$saleNo,'total'=>$total,'created_at'=>date('c')])]);

      $pdo->commit();

      $drawerEnabled = (Setting::get('cash_drawer_enabled', Env::get('CASH_DRAWER_ENABLED','0')) ?? '0') === '1';
      $kickOn = (Setting::get('cash_drawer_kick_on', Env::get('CASH_DRAWER_KICK_ON','cash')) ?? 'cash');
      $drawer = $drawerEnabled && ($kickOn==='always' || $kickOn===$pm);

      $receipt = $this->receiptText($saleNo, $cart, $subtotal, $taxTotal, $total, $pm, $ref);
      $receipt .= "AMT RCVD: " . number_format($amountReceived, 2) . "\n";
      $receipt .= "CHANGE:   " . number_format($changeDue, 2) . "\n\n";

      

      $this->json([
        'ok'=>true,
        'sale_id'=>$saleId,
        'sale_no'=>$saleNo,
        'total'=>$total,
        'amount_received'=>$amountReceived,
        'change_due'=>$changeDue,
        'receipt_text'=>$receipt,
        'drawer'=>$drawer
      ]);
    } catch (\Throwable $e) {
      $pdo->rollBack();
      $this->json(['ok'=>false,'msg'=>$e->getMessage()], 500);
    }
  }

  private function receiptText(string $saleNo, array $cart, float $subtotal, float $taxTotal, float $total, string $pm, $ref): string {
    $w = Env::int('RECEIPT_WIDTH', 48);
    $line = str_repeat('-', $w) . "\n";

    $storeName = Setting::get('store_name', 'YOUR LIQUOR STORE') ?? 'YOUR LIQUOR STORE';
    $storeAddr = Setting::get('store_address', 'TOWN, PHILIPPINES') ?? 'TOWN, PHILIPPINES';

    $out  = $storeName . "\n";
    $out .= $storeAddr . "\n";
    $out .= $line;
    $out .= "SALE: {$saleNo}\n";
    $out .= "DATE: " . date('Y-m-d H:i') . "\n";
    $out .= $line;

    foreach ($cart as $it) {
      $name = (string)$it['name'];
      if (function_exists('mb_strimwidth')) $name = mb_strimwidth($name, 0, min(32, $w-2), '…');
      else $name = substr($name, 0, min(32, $w-2));
      $qty = (int)$it['qty'];
      $unit = number_format((float)$it['unit_price'], 2);
      $lt = number_format(((float)$it['unit_price'])*$qty, 2);
      $out .= "{$name}\n";
      $out .= "  {$qty} x {$unit}    {$lt}\n";
    }

    $out .= $line;
    $out .= "SUBTOTAL:" . str_pad(number_format($subtotal,2), $w-9, ' ', STR_PAD_LEFT) . "\n";
    $out .= "TAX:" . str_pad(number_format($taxTotal,2), $w-4, ' ', STR_PAD_LEFT) . "\n";
    $out .= "TOTAL:" . str_pad(number_format($total,2), $w-6, ' ', STR_PAD_LEFT) . "\n";
    $out .= $line;
    $out .= "PAYMENT: {$pm}\n";
    if ($ref) $out .= "REF: {$ref}\n";
    $out .= "\nTHANK YOU!\n\n\n";
    return $out;
  }
}
