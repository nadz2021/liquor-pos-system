<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\User;

class UsersController extends Controller {

    public function index(){
        Auth::requireLogin();

        $role = Auth::user()['role'] ?? '';
        if (!in_array($role, ['super_admin', 'admin', 'owner'], true)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $this->view('users/index', [
            'users' => User::all(),
            'user' => Auth::user()
        ]);
    }

    public function store(){
        User::create($_POST);
        header("Location: /users");
    }

    public function toggle(){
        User::toggle($_GET['id']);
        header("Location: /users");
    }
}
