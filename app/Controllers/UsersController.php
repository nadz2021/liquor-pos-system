<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\User;

class UsersController extends Controller {

    private function requireManageAccess(): void {
        Auth::requireLogin();

        $role = Auth::user()['role'] ?? '';
        if (!in_array($role, ['super_admin', 'admin' ], true)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    public function index(){
        $this->requireManageAccess();

        $this->view('users/index', [
            'users' => User::allManageable(),
            'user' => Auth::user()
        ]);
    }

    public function store(){
        $this->requireManageAccess();

        $username = trim((string)($_POST['username'] ?? ''));
        if ($username === '') {
            $_SESSION['flash_users'] = ['type' => 'error', 'message' => 'Username is required.'];
            header("Location: /users");
            exit;
        }

        if (User::findByUsername($username)) {
            $_SESSION['flash_users'] = ['type' => 'error', 'message' => 'Username already exists.'];
            header("Location: /users");
            exit;
        }

        User::create($_POST);

        $_SESSION['flash_users'] = ['type' => 'success', 'message' => 'User created successfully.'];
        header("Location: /users");
        exit;
    }

    public function edit(){
        $this->requireManageAccess();

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'Bad request';
            return;
        }

        if (User::isProtectedSuperAdmin($id)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $editUser = User::find($id);
        if (!$editUser) {
            http_response_code(404);
            echo 'User not found';
            return;
        }

        $this->view('users/index', [
            'users' => User::allManageable(),
            'editUser' => $editUser,
            'user' => Auth::user()
        ]);
    }

    public function update(){
        $this->requireManageAccess();

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'Bad request';
            return;
        }

        if (User::isProtectedSuperAdmin($id)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $username = trim((string)($_POST['username'] ?? ''));
        if ($username === '') {
            $_SESSION['flash_users'] = ['type' => 'error', 'message' => 'Username is required.'];
            header("Location: /users/edit?id=" . $id);
            exit;
        }

        if (User::usernameExistsForOther($username, $id)) {
            $_SESSION['flash_users'] = ['type' => 'error', 'message' => 'Username already exists.'];
            header("Location: /users/edit?id=" . $id);
            exit;
        }

        $me = Auth::user();
        if ((int)$me['id'] === $id && !isset($_POST['is_active'])) {
            $_SESSION['flash_users'] = ['type' => 'error', 'message' => 'You cannot disable your own account.'];
            header("Location: /users/edit?id=" . $id);
            exit;
        }

        User::update($id, $_POST);

        $_SESSION['flash_users'] = ['type' => 'success', 'message' => 'User updated successfully.'];
        header("Location: /users");
        exit;
    }

    public function toggle(){
        $this->requireManageAccess();

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'Bad request';
            return;
        }

        if (User::isProtectedSuperAdmin($id)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $me = Auth::user();
        if ((int)$me['id'] === $id) {
            $_SESSION['flash_users'] = ['type' => 'error', 'message' => 'You cannot disable your own account.'];
            header("Location: /users");
            exit;
        }

        User::toggle($id);

        $_SESSION['flash_users'] = ['type' => 'success', 'message' => 'User status updated.'];
        header("Location: /users");
        exit;
    }

    public function resetPin(){
        $this->requireManageAccess();

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'Bad request';
            return;
        }

        if (User::isProtectedSuperAdmin($id)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $newPin = trim((string)($_POST['new_pin'] ?? ''));
        if ($newPin === '') {
            $_SESSION['flash_users'] = ['type' => 'error', 'message' => 'New PIN / password is required.'];
            header("Location: /users");
            exit;
        }

        User::resetPin($id, $newPin);

        $_SESSION['flash_users'] = ['type' => 'success', 'message' => 'PIN / password reset successfully.'];
        header("Location: /users");
        exit;
    }
}