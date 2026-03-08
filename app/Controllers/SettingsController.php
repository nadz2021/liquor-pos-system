<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Setting;
use App\Models\Audit;

final class SettingsController extends Controller {
  public function index(): void {
    Auth::requireLogin();
    $role = Auth::user()['role'] ?? '';
    if (!in_array($role, ['admin','owner','manager'], true)) { http_response_code(403); echo "Forbidden"; return; }

    $this->view('settings/index', ['user'=>Auth::user(), 'settings'=>Setting::all()]);
  }

  public function save(): void {
    Auth::requireLogin();
    $role = Auth::user()['role'] ?? '';
    if (!in_array($role, ['admin','owner','manager'], true)) { http_response_code(403); echo "Forbidden"; return; }

    $pairs = [
      'store_name' => trim((string)($_POST['store_name'] ?? '')),
      'store_address' => trim((string)($_POST['store_address'] ?? '')),
      'vat_enabled' => isset($_POST['vat_enabled']) ? '1' : '0',
      'vat_rate' => trim((string)($_POST['vat_rate'] ?? '12')),
      'cash_drawer_enabled' => isset($_POST['cash_drawer_enabled']) ? '1' : '0',
      'cash_drawer_kick_on' => (string)($_POST['cash_drawer_kick_on'] ?? 'cash'),
    ];
    foreach ($pairs as $k=>$v) Setting::set($k, (string)$v);
    Audit::log((int)Auth::user()['id'], 'settings.saved', $pairs);
    $this->redirect('/settings');
  }
}
