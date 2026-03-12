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

            case 'hours':
                $rows = Report::salesByHour($from, $to);
                $headers = ['Hour', 'Transactions', 'Sales'];
                break;

            case 'customers':
                $rows = Report::salesByCustomer($from, $to);
                $headers = ['Customer', 'Contact', 'Transactions', 'Sales'];
                break;

            case 'cashiers':
                $rows = Report::salesByCashier($from, $to);
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

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="report_' . $type . '_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);

        foreach ($rows as $row) {
            switch ($type) {
                case 'products':
                    fputcsv($out, [
                        $row['name'] ?? '',
                        $row['barcode'] ?? '',
                        $row['total_qty'] ?? 0,
                        $row['total_sales'] ?? 0,
                    ]);
                    break;

                case 'categories':
                    fputcsv($out, [
                        $row['category_name'] ?? '',
                        $row['total_qty'] ?? 0,
                        $row['total_sales'] ?? 0,
                    ]);
                    break;

                case 'hours':
                    fputcsv($out, [
                        isset($row['sale_hour']) ? sprintf('%02d:00 - %02d:59', $row['sale_hour'], $row['sale_hour']) : '',
                        $row['sale_count'] ?? 0,
                        $row['total_sales'] ?? 0,
                    ]);
                    break;

                case 'customers':
                    fputcsv($out, [
                        $row['customer_name'] ?? '',
                        $row['contact_number'] ?? '',
                        $row['sale_count'] ?? 0,
                        $row['total_sales'] ?? 0,
                    ]);
                    break;

                case 'cashiers':
                    fputcsv($out, [
                        $row['cashier_name'] ?? '',
                        $row['username'] ?? '',
                        $row['sale_count'] ?? 0,
                        $row['total_sales'] ?? 0,
                    ]);
                    break;

                case 'refunds':
                    fputcsv($out, [
                        $row['sale_no'] ?? '',
                        $row['cashier_name'] ?? '',
                        $row['refunded_at'] ?? '',
                        $row['refund_reason'] ?? '',
                        $row['total'] ?? 0,
                    ]);
                    break;
            }
        }

        fclose($out);
        exit;
    }
}