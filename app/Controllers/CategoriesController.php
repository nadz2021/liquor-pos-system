<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Category;

class CategoriesController extends Controller {

    public function index(): void {
        Auth::requireLogin();
        if (!in_array(Auth::user()['role'], ['super_admin', 'admin', 'owner'], true)) {
            http_response_code(403);
            echo "Forbidden";
            return;
        }

        $this->view('categories/index', [
            'categories' => Category::all(),
            'user' => Auth::user()
        ]);
    }

    public function create(): void {
        Auth::requireLogin();
        $this->view('categories/create', [
            'user' => Auth::user()
        ]);
    }

    public function store(): void {
        Auth::requireLogin();
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            http_response_code(400);
            echo "Category name is required";
            return;
        }

        Category::create($name);
        header("Location: /categories");
        exit;
    }

    public function edit(): void {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo "Bad request";
            return;
        }

        $category = Category::find($id);
        if (!$category) {
            http_response_code(404);
            echo "Not found";
            return;
        }

        $this->view('categories/edit', [
            'category' => $category,
            'user' => Auth::user()
        ]);
    }

    public function update(): void {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));

        if (!$id || $name === '') {
            http_response_code(400);
            echo "Bad request";
            return;
        }

        Category::update($id, $name);
        header("Location: /categories");
        exit;
    }

    public function delete(): void {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo "Bad request";
            return;
        }

        Category::delete($id);
        header("Location: /categories");
        exit;
    }
}