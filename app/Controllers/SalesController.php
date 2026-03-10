<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Sale;
use App\Models\Audit;

final class SalesController extends Controller
{
  public function index(): void
  {
    Auth::requireLogin();
    if (!Auth::can('pos.use')) { http_response_code(403); echo "Forbidden"; return; }

    $user = Auth::user();

    $filters = [
      'date_from' => trim((string)($_GET['date_from'] ?? '')),
      'date_to' => trim((string)($_GET['date_to'] ?? '')),
      'cashier_id' => trim((string)($_GET['cashier_id'] ?? '')),
      'sale_channel' => trim((string)($_GET['sale_channel'] ?? '')),
      'payment_method' => trim((string)($_GET['payment_method'] ?? '')),
      'is_refunded' => isset($_GET['is_refunded']) ? trim((string)$_GET['is_refunded']) : '',
    ];

    $sales = Sale::filteredListForUser($user, $filters, 200);
    $summary = Sale::summaryForUser($user, $filters);
    $cashiers = Sale::cashiers();
    $lowStockCount = Sale::lowStockCount();

    $this->view('sales/index', [
      'user' => $user,
      'sales' => $sales,
      'filters' => $filters,
      'summary' => $summary,
      'cashiers' => $cashiers,
      'lowStockCount' => $lowStockCount,
    ]);
  }
  public function export(): void
  {
    Auth::requireLogin();
    if (!Auth::can('pos.use')) { http_response_code(403); echo "Forbidden"; return; }

    $user = Auth::user();

    $filters = [
      'date_from' => trim((string)($_GET['date_from'] ?? '')),
      'date_to' => trim((string)($_GET['date_to'] ?? '')),
      'cashier_id' => trim((string)($_GET['cashier_id'] ?? '')),
      'sale_channel' => trim((string)($_GET['sale_channel'] ?? '')),
      'payment_method' => trim((string)($_GET['payment_method'] ?? '')),
      'is_refunded' => isset($_GET['is_refunded']) ? trim((string)$_GET['is_refunded']) : '',
    ];

    $rows = Sale::exportRowsForUser($user, $filters);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sales_export_' . date('Ymd_His') . '.csv"');

    $out = fopen('php://output', 'w');

    fputcsv($out, [
      'Sale No',
      'Date',
      'Cashier',
      'Customer',
      'Channel',
      'Payment Method',
      'Payment Ref',
      'Total',
      'Amount Received',
      'Change Due',
      'Refunded'
    ]);

    foreach ($rows as $r) {
      fputcsv($out, [
        $r['sale_no'] ?? '',
        $r['created_at'] ?? '',
        $r['cashier_name'] ?? '',
        $r['customer_name'] ?? '',
        $r['sale_channel'] ?? '',
        $r['payment_method'] ?? '',
        $r['payment_ref'] ?? '',
        $r['total'] ?? '',
        $r['amount_received'] ?? '',
        $r['change_due'] ?? '',
        ((int)($r['is_refunded'] ?? 0) === 1) ? 'Yes' : 'No',
      ]);
    }

    fclose($out);
    exit;
  }

  public function show(): void
  {
    Auth::requireLogin();
    if (!Auth::can('pos.use')) { http_response_code(403); echo "Forbidden"; return; }

    $user = Auth::user();
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo "Bad request"; return; }

    $sale = Sale::findForUser($user, $id);
    if (!$sale) { http_response_code(404); echo "Not found"; return; }

    $items = Sale::items($id);
    $this->view('sales/view', ['user'=>$user, 'sale'=>$sale, 'items'=>$items]);
  }

  public function refund(): void
  {
    Auth::requireLogin();

    if (!Auth::can('sales.refund')) {
      http_response_code(403);
      echo "Forbidden";
      return;
    }

    $saleId = (int)($_POST['sale_id'] ?? 0);
    $reason = trim((string)($_POST['refund_reason'] ?? ''));

    if (!$saleId) {
      http_response_code(400);
      echo "Bad request";
      return;
    }

    try {
      Sale::refund($saleId, (int)Auth::user()['id'], $reason);

      Audit::log((int)Auth::user()['id'], 'sale.refunded', [
        'sale_id' => $saleId,
        'reason' => $reason,
      ]);

      header('Location: /sales/show?id=' . $saleId);
      exit;

    } catch (\Throwable $e) {
      http_response_code(400);
      echo "Refund error: " . htmlspecialchars($e->getMessage());
    }
  }
}