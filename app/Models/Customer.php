<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class Customer
{
    public static function findByNameAndContact(string $name, string $contact): ?array
    {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            SELECT *
            FROM customers
            WHERE LOWER(name) = LOWER(?) AND contact_number = ?
            LIMIT 1
        ");
        $st->execute([$name, $contact]);

        $row = $st->fetch();
        return $row ?: null;
    }

    public static function create(string $name, string $contact, ?string $address = null): int
    {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            INSERT INTO customers (name, contact_number, address)
            VALUES (?, ?, ?)
        ");
        $st->execute([$name, $contact, $address]);

        return (int)$pdo->lastInsertId();
    }

    public static function findOrCreate(string $name, string $contact, ?string $address = null): int
    {
        $existing = self::findByNameAndContact($name, $contact);
        if ($existing) return (int)$existing['id'];

        return self::create($name, $contact, $address);
    }
}