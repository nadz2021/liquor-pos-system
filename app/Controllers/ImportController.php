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
            $_SESSION['flash_import'] = [
                'type' => 'error',
                'message' => 'No CSV file uploaded.'
            ];
            header('Location: /products');
            exit;
        }

        $tmp = (string)$_FILES['file']['tmp_name'];
        $name = (string)($_FILES['file']['name'] ?? '');

        if (!$this->isCsvFile($name, $tmp)) {
            $_SESSION['flash_import'] = [
                'type' => 'error',
                'message' => 'Invalid file. Please upload a CSV file.'
            ];
            header('Location: /products');
            exit;
        }

        $fp = fopen($tmp, 'r');
        if (!$fp) {
            $_SESSION['flash_import'] = [
                'type' => 'error',
                'message' => 'Unable to read uploaded file.'
            ];
            header('Location: /products');
            exit;
        }

        $headers = fgetcsv($fp);
        if (!$headers) {
            fclose($fp);
            $_SESSION['flash_import'] = [
                'type' => 'error',
                'message' => 'CSV is empty.'
            ];
            header('Location: /products');
            exit;
        }

        $headers = array_map(fn($v) => strtolower(trim((string)$v)), $headers);

        $required = ['name', 'category', 'price', 'stock'];
        foreach ($required as $col) {
            if (!in_array($col, $headers, true)) {
                fclose($fp);
                $_SESSION['flash_import'] = [
                    'type' => 'error',
                    'message' => 'Missing required column: '. $col
                ];
                header('Location: /products');
                exit;
            }
        }

        $map = array_flip($headers);
        $pdo = DB::pdo();

        $findCategory = $pdo->prepare("SELECT id FROM categories WHERE name=? LIMIT 1");

        $findSubcategory = $pdo->prepare("
            SELECT id FROM subcategories
            WHERE category_id=? AND name=?
            LIMIT 1
        ");

        $checkProduct = $pdo->prepare("SELECT COUNT(*) FROM products WHERE barcode=? OR name=?");
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

        $newCount = 0;
        $skipCount = 0;
        $emptyCount = 0;

        while (($row = fgetcsv($fp)) !== false) {
            $barcode = trim((string)($row[$map['barcode']] ?? ''));
            $nameVal = trim((string)($row[$map['name']] ?? ''));
            $description = trim((string)($row[$map['description']] ?? ''));
            $categoryName = trim((string)($row[$map['category']] ?? ''));
            $subcategoryName = trim((string)($row[$map['subcategory']] ?? ''));
            $price = trim((string)($row[$map['price']] ?? '0'));
            $stock = trim((string)($row[$map['stock']] ?? '0'));

            if ($nameVal === '') {
                $emptyCount++;
                continue;
            }

            if (!is_numeric($price) || !is_numeric($stock)) {
                continue;
            }

            if ($barcode === '') {
                $barcode = $this->generateUniqueBarcode();
            }

            $checkProduct->execute([$barcode, $nameVal]);
            if ((int)$checkProduct->fetchColumn() > 0) {
                $skipCount++;
                continue;
            }

            if ($categoryName === '') {
                $categoryName = 'Uncategorized';
            }

            $findCategory->execute([$categoryName]);
            $categoryId = (int)($findCategory->fetchColumn() ?: 0);

            $subcategoryId = null;
            if ($subcategoryName !== '') {
                $findSubcategory->execute([$categoryId, $subcategoryName]);
                $subcategoryId = (int)($findSubcategory->fetchColumn() ?: 0);
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

            $newCount++;
        }

        fclose($fp);

        $_SESSION['flash_import'] = [
            'type' => 'success',
            'message' => "Import completed. New categories: {$newCount}. Skipped duplicates: {$skipCount}. Empty rows skipped: {$emptyCount}."
        ];

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
        $_SESSION['flash_import'] = [
            'type' => 'error',
            'message' => 'No CSV file uploaded.'
        ];
        header('Location: /categories');
        exit;
    }

    $tmp = (string)$_FILES['file']['tmp_name'];
    $name = (string)($_FILES['file']['name'] ?? '');

    if (!$this->isCsvFile($name, $tmp)) {
        $_SESSION['flash_import'] = [
            'type' => 'error',
            'message' => 'Invalid file. Please upload a CSV file.'
        ];
        header('Location: /categories');
        exit;
    }

    $fp = fopen($tmp, 'r');
    if (!$fp) {
        $_SESSION['flash_import'] = [
            'type' => 'error',
            'message' => 'Unable to read uploaded file.'
        ];
        header('Location: /categories');
        exit;
    }

    $headers = fgetcsv($fp);
    if (!$headers) {
        fclose($fp);
        $_SESSION['flash_import'] = [
            'type' => 'error',
            'message' => 'CSV is empty.'
        ];
        header('Location: /categories');
        exit;
    }

    $headers = array_map(fn($v) => strtolower(trim((string)$v)), $headers);

    if (!in_array('name', $headers, true)) {
        fclose($fp);
        $_SESSION['flash_import'] = [
            'type' => 'error',
            'message' => 'Missing required column: name'
        ];
        header('Location: /categories');
        exit;
    }

    $map = array_flip($headers);
    $pdo = DB::pdo();

    // case-insensitive duplicate check
    $checkCategory = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE LOWER(name) = LOWER(?)");
    $insertCategory = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");

    $newCount = 0;
    $skipCount = 0;
    $emptyCount = 0;

    while (($row = fgetcsv($fp)) !== false) {
        $category = trim((string)($row[$map['name']] ?? ''));
        $description = trim((string)($row[$map['description']] ?? ''));

        if ($category === '') {
            $emptyCount++;
            continue;
        }

        $checkCategory->execute([$category]);
        if ((int)$checkCategory->fetchColumn() > 0) {
            $skipCount++;
            continue;
        }

        $insertCategory->execute([
            $category,
            $description !== '' ? $description : null
        ]);

        $newCount++;
    }

    fclose($fp);

    $_SESSION['flash_import'] = [
        'type' => 'success',
        'message' => "Import completed. New categories: {$newCount}. Skipped duplicates: {$skipCount}. Empty rows skipped: {$emptyCount}."
    ];

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
             $_SESSION['flash_import'] = [
                'type' => 'error',
                'message' => 'No CSV file uploaded.'
            ];
            header('Location: /subcategories');
        }

        $tmp = (string)$_FILES['file']['tmp_name'];
        $name = (string)($_FILES['file']['name'] ?? '');

        if (!$this->isCsvFile($name, $tmp)) {
            $_SESSION['flash_import'] = [
                'type' => 'error',
                'message' => 'Invalid file. Please upload a CSV file.'
            ];
            header('Location: /subcategories');
        }

        $fp = fopen($tmp, 'r');
        if (!$fp) {
            $_SESSION['flash_import'] = [
                'type' => 'error',
                'message' => 'Unable to read uploaded file.'
            ];
            header('Location: /subcategories');
        }

        $headers = fgetcsv($fp);
        if (!$headers) {
            fclose($fp);
            $_SESSION['flash_import'] = [
                'type' => 'error',
                'message' => 'CSV is empty.'
            ];
            header('Location: /subcategories');
        }

        $headers = array_map(fn($v) => strtolower(trim((string)$v)), $headers);

        $required = ['category', 'name'];
        foreach ($required as $col) {
            if (!in_array($col, $headers, true)) {
                fclose($fp);
                $_SESSION['flash_import'] = [
                    'type' => 'error',
                    'message' => 'Missing required column: '. $col
                ];
                header('Location: /subcategories');
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
        $newCount = 0;
        $skipCount = 0;
        $emptyCount = 0;
        while (($row = fgetcsv($fp)) !== false) {
            $categoryName = trim((string)($row[$map['category']] ?? ''));
            $subcategoryName = trim((string)($row[$map['name']] ?? ''));
            $description = trim((string)($row[$map['description']] ?? ''));

            if ($categoryName === '' || $subcategoryName === '') {
                $emptyCount++;
                continue;
            }

            $findCategory->execute([$categoryName]);
            $categoryId = (int)($findCategory->fetchColumn() ?: 0);

            
            $checkSubcategory->execute([$categoryId, $subcategoryName]);
            if ((int)$checkSubcategory->fetchColumn() > 0) {
                $skipCount++;
                continue;
            }

            $insertSubcategory->execute([
                $categoryId,
                $subcategoryName,
                $description !== '' ? $description : null
            ]);
            $newCount++;

        }

        fclose($fp);
        $_SESSION['flash_import'] = [
            'type' => 'success',
            'message' => "Import completed. New categories: {$newCount}. Skipped duplicates: {$skipCount}. Empty rows skipped: {$emptyCount}."
        ];

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