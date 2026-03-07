<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\DB;

final class ImportController extends Controller
{
    public function products(): void
    {
        Auth::requireLogin();

        $role = Auth::user()['role'] ?? '';
        if (!in_array($role, ['super_admin', 'admin', 'owner'], true)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        if (
            empty($_FILES['file']) ||
            ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        ) {
            http_response_code(400);
            echo 'No CSV file uploaded.';
            return;
        }

        $tmp = (string)$_FILES['file']['tmp_name'];
        $name = (string)($_FILES['file']['name'] ?? '');

        if (!$this->isCsvFile($name, $tmp)) {
            http_response_code(400);
            echo 'Invalid file. Please upload a CSV file.';
            return;
        }

        $fp = fopen($tmp, 'r');
        if (!$fp) {
            http_response_code(400);
            echo 'Unable to read uploaded file.';
            return;
        }

        $headers = fgetcsv($fp);
        if (!$headers) {
            fclose($fp);
            http_response_code(400);
            echo 'CSV is empty.';
            return;
        }

        $headers = array_map(fn($v) => strtolower(trim((string)$v)), $headers);

        $required = ['name', 'category', 'price', 'stock'];
        foreach ($required as $col) {
            if (!in_array($col, $headers, true)) {
                fclose($fp);
                http_response_code(400);
                echo "Missing required column: {$col}";
                return;
            }
        }

        $map = array_flip($headers);
        $pdo = DB::pdo();

        $findCategory = $pdo->prepare("SELECT id FROM categories WHERE name=? LIMIT 1");
        $insertCategory = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");

        $findSubcategory = $pdo->prepare("
            SELECT id FROM subcategories
            WHERE category_id=? AND name=?
            LIMIT 1
        ");
        $insertSubcategory = $pdo->prepare("
            INSERT INTO subcategories (category_id, name, description)
            VALUES (?, ?, ?)
        ");

        $checkProduct = $pdo->prepare("SELECT COUNT(*) FROM products WHERE barcode=?");
        $insertProduct = $pdo->prepare("
            INSERT INTO products
            (
                barcode,
                name,
                description,
                category,
                category_id,
                subcategory_id,
                cost,
                price,
                stock,
                reorder_point,
                low_stock_threshold,
                is_active
            )
            VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, 0, 0, 1)
        ");

        while (($row = fgetcsv($fp)) !== false) {
            $barcode = trim((string)($row[$map['barcode']] ?? ''));
            $nameVal = trim((string)($row[$map['name']] ?? ''));
            $description = trim((string)($row[$map['description']] ?? ''));
            $categoryName = trim((string)($row[$map['category']] ?? ''));
            $categoryDescription = trim((string)($row[$map['category_description']] ?? ''));
            $subcategoryName = trim((string)($row[$map['subcategory']] ?? ''));
            $subcategoryDescription = trim((string)($row[$map['subcategory_description']] ?? ''));
            $price = trim((string)($row[$map['price']] ?? '0'));
            $stock = trim((string)($row[$map['stock']] ?? '0'));

            if ($nameVal === '') {
                continue;
            }

            if (!is_numeric($price) || !is_numeric($stock)) {
                continue;
            }

            if ($barcode === '') {
                $barcode = $this->generateUniqueBarcode();
            }

            $checkProduct->execute([$barcode]);
            if ((int)$checkProduct->fetchColumn() > 0) {
                continue;
            }

            if ($categoryName === '') {
                $categoryName = 'Uncategorized';
            }

            $findCategory->execute([$categoryName]);
            $categoryId = (int)($findCategory->fetchColumn() ?: 0);

            if ($categoryId === 0) {
                $insertCategory->execute([$categoryName, $categoryDescription !== '' ? $categoryDescription : null]);
                $categoryId = (int)$pdo->lastInsertId();
            }

            $subcategoryId = null;
            if ($subcategoryName !== '') {
                $findSubcategory->execute([$categoryId, $subcategoryName]);
                $subcategoryId = (int)($findSubcategory->fetchColumn() ?: 0);

                if ($subcategoryId === 0) {
                    $insertSubcategory->execute([
                        $categoryId,
                        $subcategoryName,
                        $subcategoryDescription !== '' ? $subcategoryDescription : null
                    ]);
                    $subcategoryId = (int)$pdo->lastInsertId();
                }
            }

            $insertProduct->execute([
                $barcode,
                $nameVal,
                $description !== '' ? $description : null,
                $categoryName,
                $categoryId,
                $subcategoryId ?: null,
                (float)$price,
                (int)$stock,
            ]);
        }

        fclose($fp);

        header('Location: /products');
        exit;
    }

    public function categories(): void
    {
        Auth::requireLogin();

        $role = Auth::user()['role'] ?? '';
        if (!in_array($role, ['super_admin', 'admin', 'owner'], true)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        if (
            empty($_FILES['file']) ||
            ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        ) {
            http_response_code(400);
            echo 'No CSV file uploaded.';
            return;
        }

        $tmp = (string)$_FILES['file']['tmp_name'];
        $name = (string)($_FILES['file']['name'] ?? '');

        if (!$this->isCsvFile($name, $tmp)) {
            http_response_code(400);
            echo 'Invalid file. Please upload a CSV file.';
            return;
        }

        $fp = fopen($tmp, 'r');
        if (!$fp) {
            http_response_code(400);
            echo 'Unable to read uploaded file.';
            return;
        }

        $headers = fgetcsv($fp);
        if (!$headers) {
            fclose($fp);
            http_response_code(400);
            echo 'CSV is empty.';
            return;
        }

        $headers = array_map(fn($v) => strtolower(trim((string)$v)), $headers);

        if (!in_array('name', $headers, true)) {
            fclose($fp);
            http_response_code(400);
            echo 'Missing required column: name';
            return;
        }

        $map = array_flip($headers);
        $pdo = DB::pdo();

        $checkCategory = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name=?");
        $insertCategory = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");

        while (($row = fgetcsv($fp)) !== false) {
            $category = trim((string)($row[$map['name']] ?? ''));
            $description = trim((string)($row[$map['description']] ?? ''));

            if ($category === '') {
                continue;
            }

            $checkCategory->execute([$category]);
            if ((int)$checkCategory->fetchColumn() > 0) {
                continue;
            }

            $insertCategory->execute([
                $category,
                $description !== '' ? $description : null
            ]);
        }

        fclose($fp);

        header('Location: /categories');
        exit;
    }

    public function subcategories(): void
    {
        Auth::requireLogin();

        $role = Auth::user()['role'] ?? '';
        if (!in_array($role, ['super_admin', 'admin', 'owner'], true)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        if (
            empty($_FILES['file']) ||
            ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        ) {
            http_response_code(400);
            echo 'No CSV file uploaded.';
            return;
        }

        $tmp = (string)$_FILES['file']['tmp_name'];
        $name = (string)($_FILES['file']['name'] ?? '');

        if (!$this->isCsvFile($name, $tmp)) {
            http_response_code(400);
            echo 'Invalid file. Please upload a CSV file.';
            return;
        }

        $fp = fopen($tmp, 'r');
        if (!$fp) {
            http_response_code(400);
            echo 'Unable to read uploaded file.';
            return;
        }

        $headers = fgetcsv($fp);
        if (!$headers) {
            fclose($fp);
            http_response_code(400);
            echo 'CSV is empty.';
            return;
        }

        $headers = array_map(fn($v) => strtolower(trim((string)$v)), $headers);

        $required = ['category', 'name'];
        foreach ($required as $col) {
            if (!in_array($col, $headers, true)) {
                fclose($fp);
                http_response_code(400);
                echo "Missing required column: {$col}";
                return;
            }
        }

        $map = array_flip($headers);
        $pdo = DB::pdo();

        $findCategory = $pdo->prepare("SELECT id FROM categories WHERE name=? LIMIT 1");
        $insertCategory = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");

        $checkSubcategory = $pdo->prepare("
            SELECT COUNT(*) FROM subcategories
            WHERE category_id=? AND name=?
        ");
        $insertSubcategory = $pdo->prepare("
            INSERT INTO subcategories (category_id, name, description)
            VALUES (?, ?, ?)
        ");

        while (($row = fgetcsv($fp)) !== false) {
            $categoryName = trim((string)($row[$map['category']] ?? ''));
            $subcategoryName = trim((string)($row[$map['name']] ?? ''));
            $description = trim((string)($row[$map['description']] ?? ''));
            $categoryDescription = trim((string)($row[$map['category_description']] ?? ''));

            if ($categoryName === '' || $subcategoryName === '') {
                continue;
            }

            $findCategory->execute([$categoryName]);
            $categoryId = (int)($findCategory->fetchColumn() ?: 0);

            if ($categoryId === 0) {
                $insertCategory->execute([
                    $categoryName,
                    $categoryDescription !== '' ? $categoryDescription : null
                ]);
                $categoryId = (int)$pdo->lastInsertId();
            }

            $checkSubcategory->execute([$categoryId, $subcategoryName]);
            if ((int)$checkSubcategory->fetchColumn() > 0) {
                continue;
            }

            $insertSubcategory->execute([
                $categoryId,
                $subcategoryName,
                $description !== '' ? $description : null
            ]);
        }

        fclose($fp);

        header('Location: /subcategories');
        exit;
    }

    private function generateUniqueBarcode(): string
    {
        $pdo = DB::pdo();

        do {
            $barcode = '20' . str_pad((string)random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);

            $st = $pdo->prepare("SELECT COUNT(*) FROM products WHERE barcode = ?");
            $st->execute([$barcode]);
            $exists = (int)$st->fetchColumn() > 0;
        } while ($exists);

        return $barcode;
    }

    private function isCsvFile(string $originalName, string $tmpPath): bool
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            return true;
        }

        $mime = mime_content_type($tmpPath) ?: '';
        return in_array($mime, [
            'text/plain',
            'text/csv',
            'application/csv',
            'application/vnd.ms-excel',
        ], true);
    }
}