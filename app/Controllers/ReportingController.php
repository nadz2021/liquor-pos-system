<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Report;

final class ReportingController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        if (!Auth::can('reports.view')) {
            http_response_code(403);
            echo "Forbidden";
            return;
        }

        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to'] ?? date('Y-m-d');

        $this->view('reporting/index', [
            'user' => Auth::user(),
            'from' => $from,
            'to' => $to,

            'summary' => Report::summary($from, $to),
            'topProducts' => Report::topProducts($from, $to, 10),
            'refundReport' => array_slice(Report::refundReport($from, $to), 0, 5),

            'hourlyChart' => Report::salesByHour($from, $to),
            'categoryChart' => Report::salesByCategory($from, $to),
            'productChart' => Report::topProducts($from, $to, 10),
            'subcategoryChart' => Report::salesBySubcategory($from, $to),
            'cashierChart' => Report::topCashiers($from, $to, 10),

            'todaySummary' => Report::todaySummary(),
            'bestProductToday' => Report::bestProductToday(),
            'topCashierToday' => Report::topCashierToday(),
        ]);
    }
    
    public function products(): void
    {
        Auth::requireLogin();

        if (!Auth::can('reports.view')) {
            http_response_code(403);
            echo "Forbidden";
            return;
        }

        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to'] ?? date('Y-m-d');

        $this->view('reporting/products', [
            'user' => Auth::user(),
            'from' => $from,
            'to' => $to,
            'rows' => Report::salesByProduct($from, $to),
        ]);
    }

    public function categories(): void
    {
        Auth::requireLogin();

        if (!Auth::can('reports.view')) {
            http_response_code(403);
            echo "Forbidden";
            return;
        }

        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to'] ?? date('Y-m-d');

        $this->view('reporting/categories', [
            'user' => Auth::user(),
            'from' => $from,
            'to' => $to,
            'rows' => Report::salesByCategory($from, $to),
        ]);
    }

    public function hours(): void
    {
        Auth::requireLogin();

        if (!Auth::can('reports.view')) {
            http_response_code(403);
            echo "Forbidden";
            return;
        }

        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to'] ?? date('Y-m-d');

        $this->view('reporting/hours', [
            'user' => Auth::user(),
            'from' => $from,
            'to' => $to,
            'rows' => Report::salesByHour($from, $to),
        ]);
    }

    public function customers(): void
    {
        Auth::requireLogin();

        if (!Auth::can('reports.view')) {
            http_response_code(403);
            echo "Forbidden";
            return;
        }

        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to'] ?? date('Y-m-d');

        $this->view('reporting/customers', [
            'user' => Auth::user(),
            'from' => $from,
            'to' => $to,
            'rows' => Report::salesByCustomer($from, $to),
        ]);
    }

    public function cashiers(): void
    {
        Auth::requireLogin();

        if (!Auth::can('reports.view')) {
            http_response_code(403);
            echo "Forbidden";
            return;
        }

        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to'] ?? date('Y-m-d');

        $this->view('reporting/cashiers', [
            'user' => Auth::user(),
            'from' => $from,
            'to' => $to,
            'rows' => Report::salesByCashier($from, $to),
        ]);
    }

    public function refunds(): void
    {
        Auth::requireLogin();

        if (!Auth::can('reports.view')) {
            http_response_code(403);
            echo "Forbidden";
            return;
        }

        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to'] ?? date('Y-m-d');

        $this->view('reporting/refunds', [
            'user' => Auth::user(),
            'from' => $from,
            'to' => $to,
            'rows' => Report::refundReport($from, $to),
        ]);
    }

    public function export(): void
    {
        Auth::requireLogin();

        if (!Auth::can('reports.view')) {
            http_response_code(403);
            echo "Forbidden";
            return;
        }

        $type = trim((string)($_GET['type'] ?? ''));
        $format = strtolower(trim((string)($_GET['format'] ?? 'csv')));
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to'] ?? date('Y-m-d');

        switch ($type) {
            case 'products':
                $rows = Report::salesByProduct($from, $to);
                $headers = ['Product', 'Barcode', 'Qty Sold', 'Sales'];
                break;

            case 'categories':
                $rows = Report::salesByCategory($from, $to);
                $headers = ['Category', 'Qty Sold', 'Sales'];
                break;

            case 'subcategories':
                $rows = Report::salesBySubcategory($from, $to);
                $headers = ['Sub Category', 'Qty Sold', 'Sales'];
                break;

            case 'hours':
                $rows = Report::salesByHour($from, $to);
                $headers = ['Hour', 'Transactions', 'Sales'];
                break;

            case 'customers':
                $rows = Report::salesByCustomer($from, $to);
                $headers = ['Customer', 'Contact', 'Transactions', 'Sales'];
                break;

            case 'cashiers':
                $rows = Report::topCashiers($from, $to);
                $headers = ['Cashier', 'Username', 'Transactions', 'Sales'];
                break;

            case 'refunds':
                $rows = Report::refundReport($from, $to);
                $headers = ['Sale No', 'Cashier', 'Refunded At', 'Reason', 'Amount'];
                break;

            default:
                http_response_code(400);
                echo 'Invalid report type.';
                return;
        }

        $filenameBase = 'report_' . $type . '_' . date('Ymd_His');

        if ($format === 'excel') {
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filenameBase . '.xls"');

            echo "<table border='1'>";
            echo "<tr>";
            foreach ($headers as $h) {
                echo "<th>" . htmlspecialchars($h) . "</th>";
            }
            echo "</tr>";

            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($this->mapExportRow($type, $row) as $cell) {
                    echo "<td>" . htmlspecialchars((string)$cell) . "</td>";
                }
                echo "</tr>";
            }

            echo "</table>";
            exit;
        }

        if ($format === 'pdf') {
            header('Content-Type: text/html; charset=utf-8');

            echo "<html><head><title>{$filenameBase}</title>";
            echo "<style>
                    body{font-family:Arial,sans-serif;padding:24px;}
                    h1{font-size:20px;margin-bottom:4px;}
                    p{color:#666;margin-top:0;}
                    table{width:100%;border-collapse:collapse;margin-top:18px;}
                    th,td{border:1px solid #ccc;padding:8px;font-size:12px;text-align:left;}
                    th{background:#f5f5f5;}
                </style>";
            echo "</head><body>";
            echo "<h1>Report: " . htmlspecialchars(ucfirst($type)) . "</h1>";
            echo "<p>From " . htmlspecialchars($from) . " to " . htmlspecialchars($to) . "</p>";
            echo "<table><tr>";
            foreach ($headers as $h) {
                echo "<th>" . htmlspecialchars($h) . "</th>";
            }
            echo "</tr>";

            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($this->mapExportRow($type, $row) as $cell) {
                    echo "<td>" . htmlspecialchars((string)$cell) . "</td>";
                }
                echo "</tr>";
            }

            echo "</table>";
            echo "<script>window.print();</script>";
            echo "</body></html>";
            exit;
        }

        // default CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filenameBase . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);

        foreach ($rows as $row) {
            fputcsv($out, $this->mapExportRow($type, $row));
        }

        fclose($out);
        exit;
    }

    private function mapExportRow(string $type, array $row): array
    {
        switch ($type) {
            case 'products':
                return [
                    $row['name'] ?? '',
                    $row['barcode'] ?? '',
                    $row['total_qty'] ?? 0,
                    $row['total_sales'] ?? 0,
                ];

            case 'categories':
                return [
                    $row['category_name'] ?? '',
                    $row['total_qty'] ?? 0,
                    $row['total_sales'] ?? 0,
                ];

            case 'subcategories':
                return [
                    $row['subcategory_name'] ?? '',
                    $row['total_qty'] ?? 0,
                    $row['total_sales'] ?? 0,
                ];

            case 'hours':
                return [
                    isset($row['sale_hour']) ? sprintf('%02d:00 - %02d:59', $row['sale_hour'], $row['sale_hour']) : '',
                    $row['sale_count'] ?? 0,
                    $row['total_sales'] ?? 0,
                ];

            case 'customers':
                return [
                    $row['customer_name'] ?? '',
                    $row['contact_number'] ?? '',
                    $row['sale_count'] ?? 0,
                    $row['total_sales'] ?? 0,
                ];

            case 'cashiers':
                return [
                    $row['cashier_name'] ?? '',
                    $row['username'] ?? '',
                    $row['sale_count'] ?? 0,
                    $row['total_sales'] ?? 0,
                ];

            case 'refunds':
                return [
                    $row['sale_no'] ?? '',
                    $row['cashier_name'] ?? '',
                    $row['refunded_at'] ?? '',
                    $row['refund_reason'] ?? '',
                    $row['total'] ?? 0,
                ];
        }

        return [];
    }
}