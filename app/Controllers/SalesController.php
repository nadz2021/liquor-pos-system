<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Sale;

final class SalesController extends Controller
{
  public function index(): void
  {
    Auth::requireLogin();
    if (!Auth::can('pos.use')) { http_response_code(403); echo "Forbidden"; return; }

    $user = Auth::user();
    $sales = Sale::listForUser($user, 200);

    $this->view('sales/index', ['user'=>$user, 'sales'=>$sales]);
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
}