<?php
/**
 * @var \App\Admin\Support\View $view
 * @var array $range
 * @var array $daily
 * @var array $summary
 * @var array $statusBreakdown
 * @var array $paymentBreakdown
 * @var array $topProducts
 * @var array $deliveryPoints
 * @var array $lowStock
 * @var array $storePoint
 * @var array $target
 * @var array $totals
 */
$statusColors = [
    'menunggu konfirmasi' => '#e0a458',
    'diproses' => '#4a90d9',
    'siap diambil' => '#4caf7d',
    'diantar' => '#7e6bd6',
    'selesai' => '#2f7f51',
    'dibatalkan' => '#b13f3f',
];

$statusSegments = [];
foreach ($statusColors as $status => $color) {
    $statusSegments[] = [
        'label' => ucfirst($status),
        'value' => (int) ($statusBreakdown[$status] ?? 0),
        'color' => $color,
    ];
}

$revenueSeries = [];
foreach ($daily as $day) {
    $date = new DateTimeImmutable($day['date']);
    $revenueSeries[] = [
        'label' => $date->format('d/m/Y'),
        'caption' => $date->format('d/m'),
        'value' => $day['revenue'],
    ];
}

$totalOrders = (int) $summary['orderCount'];
$completionPercent = $totalOrders > 0
    ? (int) round($summary['completedCount'] / $totalOrders * 100)
    : 0;
?>
<div class="admin-page">
  <div class="admin-page-header">
    <div>
      <h1>Dashboard Owner</h1>
      <p class="muted-text">
        Ringkasan <?= admin_date($range['from']) ?> – <?= admin_date($range['to']) ?>
      </p>
    </div>
  </div>

  <?= $view->partial('partials/range-filter', [
      'range' => $range,
      'action' => '/owner/dashboard',
      'exportUrl' => '',
  ]) ?>

  <div class="admin-stats-grid">
    <div class="admin-stat-card admin-revenue-card">
      <p class="admin-stat-label">Pendapatan Rentang Ini</p>
      <p class="admin-stat-value"><?= e(rupiah($summary['revenue'])) ?></p>
    </div>
    <div class="admin-stat-card">
      <p class="admin-stat-label">Jumlah Order</p>
      <p class="admin-stat-value"><?= (int) $summary['orderCount'] ?></p>
    </div>
    <div class="admin-stat-card">
      <p class="admin-stat-label">Rata-rata per Order</p>
      <p class="admin-stat-value"><?= e(rupiah($summary['averageOrderValue'])) ?></p>
    </div>
    <div class="admin-stat-card">
      <p class="admin-stat-label">Selesai</p>
      <p class="admin-stat-value"><?= (int) $summary['completedCount'] ?></p>
    </div>
    <div class="admin-stat-card">
      <p class="admin-stat-label">Dibatalkan</p>
      <p class="admin-stat-value"><?= (int) $summary['cancelledCount'] ?></p>
    </div>
    <div class="admin-stat-card">
      <p class="admin-stat-label">Total Pendapatan (Semua Waktu)</p>
      <p class="admin-stat-value"><?= e(rupiah($totals['allTimeRevenue'])) ?></p>
    </div>
  </div>

  <!-- ── Progress ── -->
  <section class="owner-panel">
    <h2>Progress</h2>
    <div class="owner-progress-grid">
      <div class="owner-progress">
        <div class="owner-progress-head">
          <span>Target penjualan hari ini</span>
          <strong><?= (int) $target['percent'] ?>%</strong>
        </div>
        <div class="owner-progress-track">
          <div class="owner-progress-fill" style="width: <?= (int) $target['percent'] ?>%"></div>
        </div>
        <p class="muted-text owner-progress-caption">
          <?= e(rupiah($target['todayRevenue'])) ?> dari target <?= e(rupiah($target['daily'])) ?>
        </p>
      </div>

      <div class="owner-progress">
        <div class="owner-progress-head">
          <span>Order selesai pada rentang ini</span>
          <strong><?= $completionPercent ?>%</strong>
        </div>
        <div class="owner-progress-track">
          <div class="owner-progress-fill is-secondary" style="width: <?= $completionPercent ?>%"></div>
        </div>
        <p class="muted-text owner-progress-caption">
          <?= (int) $summary['completedCount'] ?> dari <?= $totalOrders ?> order
        </p>
      </div>
    </div>
  </section>

  <div class="owner-grid-2">
    <section class="owner-panel">
      <h2>Pendapatan Harian</h2>
      <?= $view->partial('partials/bar-chart', [
          'series' => $revenueSeries,
          'currency' => true,
          'emptyText' => 'Belum ada pendapatan pada rentang ini.',
      ]) ?>
    </section>

    <section class="owner-panel">
      <h2>Status Order</h2>
      <?= $view->partial('partials/donut-chart', [
          'segments' => $statusSegments,
          'centerLabel' => 'order',
          'emptyText' => 'Belum ada order pada rentang ini.',
      ]) ?>
    </section>
  </div>

  <!-- ── Minimap titik order delivery ── -->
  <section class="owner-panel">
    <div class="owner-panel-head">
      <h2>Peta Sebaran Order Delivery</h2>
      <span class="muted-text"><?= count($deliveryPoints) ?> titik</span>
    </div>
    <?php if (empty($deliveryPoints)): ?>
      <p class="muted-text owner-chart-empty">
        Belum ada order delivery bertitik lokasi pada rentang ini.
      </p>
    <?php else: ?>
      <div id="owner-map" class="owner-map"></div>
      <ul class="owner-legend owner-legend-inline">
        <?php foreach ($statusColors as $status => $color): ?>
          <li>
            <span class="owner-legend-dot" style="background: <?= e($color) ?>"></span>
            <span class="owner-legend-label"><?= e(ucfirst($status)) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <script id="owner-map-data" type="application/json"><?= json_encode([
          'store' => $storePoint,
          'colors' => $statusColors,
          'points' => $deliveryPoints,
      ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
      <script src="/assets/owner-map.js" defer></script>
    <?php endif; ?>
  </section>

  <div class="owner-grid-2">
    <section class="owner-panel">
      <h2>Produk Terlaris</h2>
      <?php if (empty($topProducts)): ?>
        <p class="muted-text owner-chart-empty">Belum ada penjualan pada rentang ini.</p>
      <?php else: ?>
        <?php $maxUnits = max(array_column($topProducts, 'units')); ?>
        <ul class="owner-rank-list">
          <?php foreach ($topProducts as $product): ?>
            <li>
              <div class="owner-rank-head">
                <span><?= e($product['productName']) ?></span>
                <strong><?= (int) $product['units'] ?> pcs</strong>
              </div>
              <div class="owner-progress-track is-slim">
                <div class="owner-progress-fill"
                     style="width: <?= $maxUnits > 0 ? (int) round($product['units'] / $maxUnits * 100) : 0 ?>%"></div>
              </div>
              <p class="muted-text owner-progress-caption"><?= e(rupiah($product['revenue'])) ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="owner-panel">
      <div class="owner-panel-head">
        <h2>Stok Perlu Restock</h2>
        <span class="muted-text"><?= count($lowStock) ?> ukuran</span>
      </div>
      <?php if (empty($lowStock)): ?>
        <p class="muted-text owner-chart-empty">Semua stok masih aman.</p>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr><th>Produk</th><th>Ukuran</th><th>Sisa</th></tr>
            </thead>
            <tbody>
              <?php foreach ($lowStock as $item): ?>
                <tr>
                  <td><?= e($item['productName']) ?></td>
                  <td><?= e($item['fullLabel']) ?></td>
                  <td>
                    <span class="admin-badge <?= $item['stock'] > 0 ? 'badge-pending' : 'badge-cancelled' ?>">
                      <?= $item['stock'] > 0 ? (int) $item['stock'] . ' pcs' : 'Habis' ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <div class="admin-stats-grid">
    <div class="admin-stat-card">
      <p class="admin-stat-label">Total Customer</p>
      <p class="admin-stat-value"><?= (int) $totals['customers'] ?></p>
    </div>
    <div class="admin-stat-card">
      <p class="admin-stat-label">Total Produk</p>
      <p class="admin-stat-value"><?= (int) $totals['products'] ?></p>
    </div>
    <div class="admin-stat-card">
      <p class="admin-stat-label">Pemberitahuan Terkirim</p>
      <p class="admin-stat-value"><?= (int) $totals['announcements'] ?></p>
    </div>
    <div class="admin-stat-card">
      <p class="admin-stat-label">QRIS / Cash</p>
      <p class="admin-stat-value">
        <?= (int) ($paymentBreakdown['qris']['count'] ?? 0) ?> / <?= (int) ($paymentBreakdown['cash']['count'] ?? 0) ?>
      </p>
    </div>
  </div>
</div>
