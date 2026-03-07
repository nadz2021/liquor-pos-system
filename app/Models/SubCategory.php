<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class Subcategory
{
    public static function all(): array
    {
        $pdo = DB::pdo();
        return $pdo->query("
            SELECT s.*, c.name AS category_name
            FROM subcategories s
            JOIN categories c ON c.id = s.category_id
            ORDER BY c.name ASC, s.name ASC
        ")->fetchAll();
    }

    public static function byCategory(int $categoryId): array
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare("
            SELECT * FROM subcategories
            WHERE category_id = ?
            ORDER BY name ASC
        ");
        $st->execute([$categoryId]);
        return $st->fetchAll();
    }

    public static function create(int $categoryId, string $name): void
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare("
            INSERT INTO subcategories (category_id, name)
            VALUES (?, ?)
        ");
        $st->execute([$categoryId, $name]);
    }

    public static function delete(int $id): bool
    {
        $pdo = DB::pdo();

        $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE subcategory_id = ?");
        $check->execute([$id]);

        if ((int)$check->fetchColumn() > 0) {
            return false;
        }

        $st = $pdo->prepare("DELETE FROM subcategories WHERE id = ?");
        $st->execute([$id]);

        return true;
    }
}