<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class Category {

    public static function all(): array {
        $pdo = DB::pdo();
        $st = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        return $st->fetchAll();
    }

    public static function find(int $id): ?array {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            SELECT *
            FROM categories
            WHERE id = ?
            LIMIT 1
        ");

        $st->execute([$id]);

        $row = $st->fetch();

        return $row ?: null;
    }

    public static function create(string $name): int {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            INSERT INTO categories (name)
            VALUES (?)
        ");

        $st->execute([$name]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, string $name): void {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            UPDATE categories
            SET name = ?
            WHERE id = ?
        ");

        $st->execute([$name, $id]);
    }

    public static function delete(int $id): void {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            DELETE FROM categories
            WHERE id = ?
        ");

        $st->execute([$id]);
    }
}