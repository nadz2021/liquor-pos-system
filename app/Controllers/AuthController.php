<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Audit;

final class AuthController extends Controller {
  public function showLogin(): void {
    $this->view('auth/login', ['error'=>null]);
  }

  public function login(): void {
    $u = trim((string)($_POST['username'] ?? ''));
    $p = trim((string)($_POST['pin'] ?? ''));
    if ($u==='' || $p==='') { $this->view('auth/login', ['error'=>'Enter username and PIN.']); return; }

    if (Auth::attempt($u, $p)) {
      $me = Auth::user();
      Audit::log((int)$me['id'], 'auth.login', ['username'=>$u]);
      $this->redirect('/');
    }
    $this->view('auth/login', ['error'=>'Invalid login.']);
  }

  public function logout(): void {
    $me = Auth::user();
    if ($me) Audit::log((int)$me['id'], 'auth.logout');
    Auth::logout();
    $this->redirect('/login');
  }
}
