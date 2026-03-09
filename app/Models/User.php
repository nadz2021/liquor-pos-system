<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class User {

    public static function all(){
        return DB::pdo()->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
    }

    public static function create(array $data): int
    {
        $pdo = \App\Core\DB::pdo();

        $name = trim((string)($data['name'] ?? ''));
        $username = trim((string)($data['username'] ?? ''));
        $role = trim((string)($data['role'] ?? ''));
        $pin = trim((string)($data['pin'] ?? ''));
        $sellingMode = trim((string)($data['selling_mode'] ?? 'in_store'));
        $isActive = isset($data['is_active']) ? 1 : 0;

        $pinHash = password_hash($pin, PASSWORD_DEFAULT);

        $st = $pdo->prepare("
            INSERT INTO users (name, username, role, selling_mode, pin_hash, is_active)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $st->execute([$name, $username, $role, $sellingMode, $pinHash, $isActive]);

        return (int)$pdo->lastInsertId();
    }

    public static function toggle($id){
        $pdo = DB::pdo();
        $pdo->prepare("UPDATE users SET is_active = IF(is_active=1,0,1) WHERE id=?")->execute([$id]);
    }

    public static function findByUsername(string $username): ?array
    {
        $pdo = \App\Core\DB::pdo();

        $st = $pdo->prepare("
            SELECT *
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        $st->execute([$username]);

        $user = $st->fetch();

        return $user ?: null;
    }
    
    public static function allManageable(): array
    {
        $pdo = \App\Core\DB::pdo();

        $protectedIdSt = $pdo->query("
            SELECT id
            FROM users
            WHERE role = 'super_admin'
            ORDER BY id ASC
            LIMIT 1
        ");

        $protectedId = (int)($protectedIdSt->fetchColumn() ?: 0);

        if ($protectedId > 0) {
            $st = $pdo->prepare("
                SELECT *
                FROM users
                WHERE id <> ?
                ORDER BY id ASC
            ");
            $st->execute([$protectedId]);
        } else {
            $st = $pdo->query("
                SELECT *
                FROM users
                ORDER BY id ASC
            ");
        }

        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        $pdo = \App\Core\DB::pdo();

        $st = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $st->execute([$id]);

        $row = $st->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function usernameExistsForOther(string $username, int $id): bool
    {
        $pdo = \App\Core\DB::pdo();

        $st = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
        $st->execute([$username, $id]);

        return (bool)$st->fetch();
    }

    public static function isProtectedSuperAdmin(int $id): bool
    {
        $pdo = \App\Core\DB::pdo();

        $st = $pdo->prepare("
            SELECT id
            FROM users
            WHERE role = 'super_admin'
            ORDER BY id ASC
            LIMIT 1
        ");
        $st->execute();

        $protectedId = (int)($st->fetchColumn() ?: 0);

        return $protectedId > 0 && $protectedId === $id;
    }

    public static function update(int $id, array $data): void
    {
        $pdo = \App\Core\DB::pdo();

        $name = trim((string)($data['name'] ?? ''));
        $username = trim((string)($data['username'] ?? ''));
        $role = trim((string)($data['role'] ?? ''));
        $sellingMode = trim((string)($data['selling_mode'] ?? 'in_store'));
        $pin = trim((string)($data['pin'] ?? ''));
        $isActive = isset($data['is_active']) ? 1 : 0;

        if ($pin !== '') {
            $pinHash = password_hash($pin, PASSWORD_DEFAULT);

            $st = $pdo->prepare("
                UPDATE users
                SET name = ?, username = ?, role = ?, selling_mode = ?, pin_hash = ?, is_active = ?
                WHERE id = ?
            ");
            $st->execute([$name, $username, $role, $sellingMode, $pinHash, $isActive, $id]);
        } else {
            $st = $pdo->prepare("
                UPDATE users
                SET name = ?, username = ?, role = ?, selling_mode = ?, is_active = ?
                WHERE id = ?
            ");
            $st->execute([$name, $username, $role, $sellingMode, $isActive, $id]);
        }
    }

    public static function resetPin(int $id, string $newPin): void
    {
        $pdo = \App\Core\DB::pdo();
        $pinHash = password_hash($newPin, PASSWORD_DEFAULT);

        $st = $pdo->prepare("UPDATE users SET pin_hash = ? WHERE id = ?");
        $st->execute([$pinHash, $id]);
    }
}
