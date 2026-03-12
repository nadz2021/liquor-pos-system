<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class Report
{
    public static function summary(string $from, string $to): array
    {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            SELECT
                COUNT(*) AS total_transactions,
                COALESCE(SUM(total), 0) AS total_sales,
                COALESCE(AVG(total), 0) AS avg_transaction,
                COALESCE(SUM(CASE WHEN is_refunded = 1 THEN total ELSE 0 END), 0) AS total_refunded
            FROM sales
            WHERE DATE(created_at) BETWEEN ? AND ?
        ");
        $st->execute([$from, $to]);

        $summary = $st->fetch() ?: [
            'total_transactions' => 0,
            'total_sales' => 0,
            'avg_transaction' => 0,
            'total_refunded' => 0,
        ];

        $best = self::topProducts($from, $to, 1);
        $summary['best_product'] = $best[0]['name'] ?? '-';

        return $summary;
    }

    public static function topProducts(string $from, string $to, int $limit = 10): array
    {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            SELECT
                si.product_id,
                si.name,
                si.barcode,
                SUM(si.qty) AS total_qty,
                SUM(si.line_total) AS total_sales
            FROM sale_items si
            INNER JOIN sales s ON s.id = si.sale_id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY si.product_id, si.name, si.barcode
            ORDER BY total_sales DESC, total_qty DESC
            LIMIT {$limit}
        ");
        $st->execute([$from, $to]);

        return $st->fetchAll();
    }

    public static function refundReport(string $from, string $to): array
    {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            SELECT
                s.id,
                s.sale_no,
                s.total,
                s.refunded_at,
                s.refund_reason,
                u.name AS cashier_name
            FROM sales s
            LEFT JOIN users u ON u.id = s.cashier_id
            WHERE s.is_refunded = 1
              AND DATE(s.created_at) BETWEEN ? AND ?
            ORDER BY s.refunded_at DESC, s.id DESC
        ");
        $st->execute([$from, $to]);

        return $st->fetchAll();
    }

    public static function salesByProduct(string $from, string $to): array
    {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            SELECT
                si.product_id,
                si.name,
                si.barcode,
                SUM(si.qty) AS total_qty,
                SUM(si.line_total) AS total_sales
            FROM sale_items si
            INNER JOIN sales s ON s.id = si.sale_id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY si.product_id, si.name, si.barcode
            ORDER BY total_sales DESC, total_qty DESC
        ");
        $st->execute([$from, $to]);

        return $st->fetchAll();
    }

    public static function salesByCategory(string $from, string $to): array
    {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            SELECT
                COALESCE(c.name, 'Uncategorized') AS category_name,
                SUM(si.qty) AS total_qty,
                SUM(si.line_total) AS total_sales
            FROM sale_items si
            INNER JOIN sales s ON s.id = si.sale_id
            INNER JOIN products p ON p.id = si.product_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY c.name
            ORDER BY total_sales DESC, total_qty DESC
        ");
        $st->execute([$from, $to]);

        return $st->fetchAll();
    }

    public static function salesByHour(string $from, string $to): array
    {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            SELECT
                HOUR(s.created_at) AS sale_hour,
                COUNT(*) AS sale_count,
                SUM(s.total) AS total_sales
            FROM sales s
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY HOUR(s.created_at)
            ORDER BY sale_hour ASC
        ");
        $st->execute([$from, $to]);

        return $st->fetchAll();
    }

    public static function salesByCustomer(string $from, string $to): array
    {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            SELECT
                COALESCE(c.name, 'Walk-in / None') AS customer_name,
                COALESCE(c.contact_number, '-') AS contact_number,
                COUNT(s.id) AS sale_count,
                SUM(s.total) AS total_sales
            FROM sales s
            LEFT JOIN customers c ON c.id = s.customer_id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY c.id, c.name, c.contact_number
            ORDER BY total_sales DESC, sale_count DESC
        ");
        $st->execute([$from, $to]);

        return $st->fetchAll();
    }

    public static function salesByCashier(string $from, string $to): array
    {
        $pdo = DB::pdo();

        $st = $pdo->prepare("
            SELECT
                u.name AS cashier_name,
                u.username,
                COUNT(s.id) AS sale_count,
                SUM(s.total) AS total_sales
            FROM sales s
            INNER JOIN users u ON u.id = s.cashier_id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY u.id, u.name, u.username
            ORDER BY total_sales DESC, sale_count DESC
        ");
        $st->execute([$from, $to]);

        return $st->fetchAll();
    }
}