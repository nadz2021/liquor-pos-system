<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class GiftCard
{
    public static function generateCode(int $length = 10): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; 
        // removed confusing characters: 0,O,1,I

        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $code;
    }

    public static function create(float $amount, int $createdBy): int
    {
        $pdo = DB::pdo();

        $code = self::generateCode();

        $st = $pdo->prepare("
            INSERT INTO gift_cards
            (code, amount, status, created_by)
            VALUES (?, ?, 'created', ?)
        ");

        $st->execute([
            $code,
            $amount,
            $createdBy
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function findByCode(string $code): ?array
    {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            SELECT *
            FROM gift_cards
            WHERE code = ?
            LIMIT 1
        ");

        $st->execute([$code]);

        $row = $st->fetch();

        return $row ?: null;
    }

    public static function list(int $limit = 200): array
    {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            SELECT *
            FROM gift_cards
            ORDER BY id DESC
            LIMIT {$limit}
        ");

        $st->execute();

        return $st->fetchAll();
    }

    public static function assign(int $giftCardId, ?int $customerId = null, ?int $saleId = null): bool
    {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            UPDATE gift_cards
            SET
                status = 'assigned',
                assigned_at = NOW(),
                assigned_sale_id = ?,
                customer_id = ?
            WHERE id = ?
              AND status = 'created'
        ");

        return $st->execute([
            $saleId,
            $customerId,
            $giftCardId
        ]);
    }

    public static function redeemByCode(string $code, int $saleId): bool
    {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            UPDATE gift_cards
            SET
                status = 'redeemed',
                redeemed_at = NOW(),
                redeemed_sale_id = ?
            WHERE code = ?
              AND status = 'assigned'
        ");

        return $st->execute([
            $saleId,
            $code
        ]);
    }
}