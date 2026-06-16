<?php
/**
 * @var array $stats
 */
$statCards = [
    ['key' => 'totalOrders',      'label' => 'Total Orders',         'color' => '#c8683d'],
    ['key' => 'pendingOrders',    'label' => 'Menunggu Konfirmasi',  'color' => '#d4a843'],
    ['key' => 'processingOrders', 'label' => 'Diproses',             'color' => '#5b8a72'],
    ['key' => 'completedOrders',  'label' => 'Selesai',              'color' => '#2f7f51'],
    ['key' => 'cancelledOrders',  'label' => 'Dibatalkan',           'color' => '#b13f3f'],
    ['key' => 'totalCustomers',   'label' => 'Total Customer',       'color' => '#6f5848'],
    ['key' => 'totalProducts',    'label' => 'Total Produk',         'color' => '#8a5a44'],
];
$revenueCards = [
    ['key' => 'todayRevenue', 'label' => 'Pendapatan Hari Ini'],
    ['key' => 'totalRevenue', 'label' => 'Total Pendapatan'],
];
?>
<div class="admin-page">
  <h1>Dashboard</h1>

  <div class="admin-stats-grid">
    <?php foreach ($statCards as $card): ?>
      <div class="admin-stat-card">
        <p class="admin-stat-label"><?= e($card['label']) ?></p>
        <p class="admin-stat-value" style="color: <?= e($card['color']) ?>">
          <?= e($stats[$card['key']] ?? 0) ?>
        </p>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="admin-revenue-grid">
    <?php foreach ($revenueCards as $card): ?>
      <div class="admin-revenue-card">
        <p class="admin-stat-label"><?= e($card['label']) ?></p>
        <p class="admin-revenue-value"><?= e(rupiah($stats[$card['key']] ?? 0)) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</div>
