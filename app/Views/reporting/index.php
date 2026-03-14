<?php ob_start(); ?>

<div class="page-head">
    <div>
        <h1 class="page-title">Reporting</h1>
        <div class="page-subtitle">Sales analytics and performance reports.</div>
    </div>
</div>

<div class="card reporting-filter-card">
    <form method="GET" class="report-filter-grid">
        <div class="field">
            <label>From</label>
            <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
        </div>

        <div class="field">
            <label>To</label>
            <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
        </div>

        <div class="report-filter-action">
            <button class="btn btn-primary" type="submit">Filter</button>
        </div>
    </form>
</div>

<div class="spacer"></div>

<div class="report-section-head">
    <h3>Today Snapshot</h3>
    <span class="mini">Live view for today</span>
</div>

<div class="kpi-grid kpi-grid-4">
    <div class="kpi-card kpi-purple">
        <div class="kpi-label">Today Sales</div>
        <div class="kpi-value"><?= number_format((float)($todaySummary['total_sales'] ?? 0), 2) ?></div>
        <div class="kpi-note">Today only</div>
    </div>

    <div class="kpi-card kpi-green">
        <div class="kpi-label">Transactions</div>
        <div class="kpi-value"><?= (int)($todaySummary['total_transactions'] ?? 0) ?></div>
        <div class="kpi-note">Completed today</div>
    </div>

    <div class="kpi-card kpi-amber">
        <div class="kpi-label">Refunded</div>
        <div class="kpi-value"><?= number_format((float)($todaySummary['total_refunded'] ?? 0), 2) ?></div>
        <div class="kpi-note">Refunded today</div>
    </div>

    <div class="kpi-card kpi-blue">
        <div class="kpi-label">Best Product Today</div>
        <div class="kpi-value kpi-value-text"><?= htmlspecialchars($bestProductToday['name'] ?? '-') ?></div>
        <div class="kpi-note">
            <?= isset($bestProductToday['total_qty']) ? (int)$bestProductToday['total_qty'] . ' sold' : 'No sales yet' ?>
        </div>
    </div>
</div>

<div class="spacer"></div>

<div class="report-section-head">
    <h3>Selected Range Summary</h3>
    <span class="mini"><?= htmlspecialchars($from) ?> to <?= htmlspecialchars($to) ?></span>
</div>

<div class="kpi-grid kpi-grid-4">
    <div class="kpi-card kpi-purple">
        <div class="kpi-label">Total Sales</div>
        <div class="kpi-value"><?= number_format((float)($summary['total_sales'] ?? 0), 2) ?></div>
        <div class="kpi-note"><?= (int)($summary['total_transactions'] ?? 0) ?> transaction(s)</div>
    </div>

    <div class="kpi-card kpi-amber">
        <div class="kpi-label">Total Refunded</div>
        <div class="kpi-value"><?= number_format((float)($summary['total_refunded'] ?? 0), 2) ?></div>
        <div class="kpi-note">Refunded sales value</div>
    </div>

    <div class="kpi-card kpi-green">
        <div class="kpi-label">Avg Transaction</div>
        <div class="kpi-value"><?= number_format((float)($summary['avg_transaction'] ?? 0), 2) ?></div>
        <div class="kpi-note">Average sale amount</div>
    </div>

    <div class="kpi-card kpi-blue">
        <div class="kpi-label">Best Product</div>
        <div class="kpi-value kpi-value-text"><?= htmlspecialchars($summary['best_product'] ?? '-') ?></div>
        <div class="kpi-note">Top seller in selected range</div>
    </div>
</div>

<div class="spacer"></div>

<div class="insight-grid">
    <div class="card insight-card">
        <div class="insight-label">Top Cashier Today</div>
        <div class="insight-value"><?= htmlspecialchars($topCashierToday['cashier_name'] ?? '-') ?></div>
        <div class="insight-note">
            <?php if (!empty($topCashierToday)): ?>
                <?= (int)$topCashierToday['sale_count'] ?> transaction(s) • <?= number_format((float)$topCashierToday['total_sales'], 2) ?>
            <?php else: ?>
                No sales yet today
            <?php endif; ?>
        </div>
    </div>

    <div class="card insight-card">
        <div class="accordion">
            <div class="accordion-item">
                <div class="accordion-header" onclick="toggleAccordion(this)">
                    Detailed Reports
                    <span class="accordion-arrow">▾</span>
                </div>

                <div class="accordion-body">
                    <div class="report-link-grid">
                        <a class="btn btn-primary" href="/reporting/products?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Sales by Product</a>
                        <a class="btn btn-primary" href="/reporting/categories?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Sales by Category</a>
                        <a class="btn btn-primary" href="/reporting/hours?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Sales by Hour</a>
                        <a class="btn btn-primary" href="/reporting/customers?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Sales by Customer</a>
                        <a class="btn btn-primary" href="/reporting/cashiers?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Sales by Cashier</a>
                        <a class="btn btn-primary" href="/reporting/refunds?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Refund Report</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="spacer"></div>

<div class="report-two-col">
  <div class="card">
    <div class="card-head-inline">
      <h3>Sales by Category</h3>
    </div>
    <canvas id="salesCategoryChart" height="140"></canvas>
  </div>

  <div class="card">
    <div class="card-head-inline">
      <h3>Sales by Sub Category</h3>
    </div>
    <canvas id="salesSubcategoryChart" height="140"></canvas>
  </div>
</div>

<div class="spacer"></div>

<div class="report-two-col">
  <div class="card">
    <div class="card-head-inline">
      <h3>Sales by Hour</h3>
    </div>
    <canvas id="salesHourChart" height="140"></canvas>
  </div>

  <div class="card">
    <div class="card-head-inline">
      <h3>Top Sales by Cashier</h3>
    </div>
    <canvas id="salesCashierChart" height="140"></canvas>
  </div>
</div>

<div class="spacer"></div>

<div class="card">
  <div class="card-head-inline">
    <h3>Top 10 Products Chart</h3>
  </div>
  <canvas id="topProductsChart" height="110"></canvas>
</div>

<div class="spacer"></div>

<div class="card">
    <div class="card-head-inline">
        <h3>Top 10 Products Chart</h3>
    </div>
    <canvas id="topProductsChart" height="110"></canvas>
</div>

<div class="spacer"></div>

<div class="report-two-col">
    <div class="card">
        <div class="card-head-inline">
            <h3>Top 10 Products</h3>
            <a class="btn" href="/reporting/products?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">View Full</a>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Barcode</th>
                    <th class="right">Qty Sold</th>
                    <th class="right">Sales</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topProducts)): ?>
                    <tr>
                        <td colspan="4" class="muted report-empty">No data found.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($topProducts as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['name']) ?></td>
                        <td><?= htmlspecialchars($r['barcode']) ?></td>
                        <td class="right"><?= (int)$r['total_qty'] ?></td>
                        <td class="right"><?= number_format((float)$r['total_sales'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-head-inline">
            <h3>Refund Report</h3>
            <a class="btn" href="/reporting/refunds?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">View Full</a>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Sale No</th>
                    <th>Cashier</th>
                    <th>Refunded At</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($refundReport)): ?>
                    <tr>
                        <td colspan="4" class="muted report-empty">No refunded sales found.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach (array_slice($refundReport, 0, 8) as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['sale_no']) ?></td>
                        <td><?= htmlspecialchars($r['cashier_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['refunded_at'] ?? '') ?></td>
                        <td class="right"><?= number_format((float)$r['total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const hourlyRows = <?= json_encode($hourlyChart ?? []) ?>;
    const categoryRows = <?= json_encode($categoryChart ?? []) ?>;
    const productRows = <?= json_encode($productChart ?? []) ?>;

    // Sales by Hour
    new Chart(document.getElementById('salesHourChart'), {
        type: 'bar',
        data: {
            labels: hourlyRows.map(r => {
                const h = String(r.sale_hour).padStart(2, '0');
                return `${h}:00`;
            }),
            datasets: [{
                label: 'Sales',
                data: hourlyRows.map(r => parseFloat(r.total_sales || 0))
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Sales by Category
    new Chart(document.getElementById('salesCategoryChart'), {
        type: 'doughnut',
        data: {
            labels: categoryRows.map(r => r.category_name),
            datasets: [{
                data: categoryRows.map(r => parseFloat(r.total_sales || 0))
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Top Products
    new Chart(document.getElementById('topProductsChart'), {
        type: 'bar',
        data: {
            labels: productRows.map(r => r.name),
            datasets: [{
                label: 'Sales',
                data: productRows.map(r => parseFloat(r.total_sales || 0))
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    });

    function toggleAccordion(header) {
        const item = header.parentElement;
        item.classList.toggle('active');
    }
</script>

<?php
$content = ob_get_clean();
$title = 'Reporting';
require __DIR__ . '/../layouts/main.php';
?>