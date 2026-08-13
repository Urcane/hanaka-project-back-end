<?php
/**
 * @var \App\Admin\Support\View $view
 * @var array $range
 * @var array $daily
 * @var array $summary
 * @var array $topProducts
 * @var array $paymentBreakdown
 * @var array $statusBreakdown
 */
$orderSeries = [];
foreach ($daily as $day) {
    $date = new DateTimeImmutable($day['date']);
    $orderSeries[] = [
        'label' => $date->format('d/m/Y'),
        'caption' => $date->format('d/m'),
        'value' => $day['orderCount'],
    ];
}

$exportUrl = '/owner/reports/export?from=' . rawurlencode($range['from']) . '&to=' . rawurlencode($range['to']);
?>
<div class="admin-page">
  <div class="admin-page-header">
    <div>
      <h1>Laporan Penjualan</h1>
      <p class="muted-text">
        Periode <?= admin_date($range['from']) ?> – <?= admin_date($range['to']) ?>
      </p>
    </div>
  </div>

  <?= $view->partial('partials/range-filter', [
      'range' => $range,
      'action' => '/owner/reports',
      'exportUrl' => $exportUrl,
  ]) ?>

  <div class="admin-stats-grid">
    <div class="admin-stat-card admin-revenue-card">
      <p class="admin-stat-label">Total Pendapatan</p>
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
      <p class="admin-stat-label">Selesai / Dibatalkan</p>
      <p class="admin-stat-value">
        <?= (int) $summary['completedCount'] ?> / <?= (int) $summary['cancelledCount'] ?>
      </p>
    </div>
  </div>

  <section class="owner-panel">
    <h2>Jumlah Order per Hari</h2>
    <?= $view->partial('partials/bar-chart', [
        'series' => $orderSeries,
        'currency' => false,
        'emptyText' => 'Belum ada order pada rentang ini.',
    ]) ?>
  </section>

  <section class="owner-panel">
    <div class="owner-panel-head">
      <h2>Rincian Harian</h2>
      <a class="owner-chip is-export" href="<?= e($exportUrl) ?>">⬇ Export CSV</a>
    </div>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Order</th>
            <th>Selesai</th>
            <th>Dibatalkan</th>
            <th>Pendapatan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daily as $day): ?>
            <tr>
              <td><?= admin_date($day['date']) ?></td>
              <td><?= (int) $day['orderCount'] ?></td>
              <td><?= (int) $day['completedCount'] ?></td>
              <td><?= (int) $day['cancelledCount'] ?></td>
              <td><?= e(rupiah($day['revenue'])) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($daily)): ?>
            <tr><td colspan="5" class="admin-table-empty">Tidak ada data pada rentang ini.</td></tr>
          <?php endif; ?>
        </tbody>
        <tfoot>
          <tr>
            <th>Total</th>
            <th><?= (int) $summary['orderCount'] ?></th>
            <th><?= (int) $summary['completedCount'] ?></th>
            <th><?= (int) $summary['cancelledCount'] ?></th>
            <th><?= e(rupiah($summary['revenue'])) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </section>

  <div class="owner-grid-2">
    <section class="owner-panel">
      <h2>Produk Terlaris</h2>
      <?php if (empty($topProducts)): ?>
        <p class="muted-text owner-chart-empty">Belum ada penjualan pada rentang ini.</p>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr><th>Produk</th><th>Terjual</th><th>Pendapatan</th></tr>
            </thead>
            <tbody>
              <?php foreach ($topProducts as $product): ?>
                <tr>
                  <td><?= e($product['productName']) ?></td>
                  <td><?= (int) $product['units'] ?> pcs</td>
                  <td><?= e(rupiah($product['revenue'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <section class="owner-panel">
      <h2>Metode Pembayaran &amp; Status</h2>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr><th>Metode</th><th>Order</th><th>Pendapatan</th></tr>
          </thead>
          <tbody>
            <?php foreach (['cash' => 'Cash', 'qris' => 'QRIS'] as $key => $label): ?>
              <tr>
                <td><?= e($label) ?></td>
                <td><?= (int) ($paymentBreakdown[$key]['count'] ?? 0) ?></td>
                <td><?= e(rupiah($paymentBreakdown[$key]['revenue'] ?? 0)) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="admin-table-wrap owner-status-table">
        <table class="admin-table">
          <thead>
            <tr><th>Status</th><th>Jumlah</th></tr>
          </thead>
          <tbody>
            <?php
            $statuses = ['menunggu konfirmasi', 'diproses', 'siap diambil', 'diantar', 'selesai', 'dibatalkan'];
            ?>
            <?php foreach ($statuses as $status): ?>
              <tr>
                <td><span class="admin-badge <?= e(badge_class($status)) ?>"><?= e($status) ?></span></td>
                <td><?= (int) ($statusBreakdown[$status] ?? 0) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</div>
