<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Category;
use App\Models\Subcategory;

final class SubCategoriesController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $role = Auth::user()['role'] ?? '';
        if (!in_array($role, ['super_admin', 'admin', 'owner'], true)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $this->view('subcategories/index', [
            'user' => Auth::user(),
            'categories' => Category::all(),
            'subcategories' => Subcategory::all(),
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();

        $role = Auth::user()['role'] ?? '';
        if (!in_array($role, ['super_admin', 'admin'], true)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $categoryId = (int)($_POST['category_id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));

        if ($categoryId > 0 && $name !== '') {
            Subcategory::create($categoryId, $name);
        }

        header('Location: /subcategories');
        exit;
    }

    public function delete(): void
    {
        Auth::requireLogin();

        $role = Auth::user()['role'] ?? '';
        if (!in_array($role, ['super_admin', 'admin'], true)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            Subcategory::delete($id);
        }

        header('Location: /subcategories');
        exit;
    }

    public function byCategory(): void
    {
        Auth::requireLogin();

        $categoryId = (int)($_GET['category_id'] ?? 0);
        $rows = $categoryId > 0 ? Subcategory::byCategory($categoryId) : [];

        $this->json([
            'ok' => true,
            'rows' => $rows,
        ]);
    }
}