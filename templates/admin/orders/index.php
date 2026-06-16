<?php
/**
 * @var array  $orders
 * @var int    $total
 * @var string $status   Active status filter ('' = all)
 * @var int    $offset
 * @var int    $limit
 */
$statuses = [
    ['value' => '',                    'label' => 'Semua Status'],
    ['value' => 'menunggu konfirmasi', 'label' => 'Menunggu Konfirmasi'],
    ['value' => 'diproses',            'label' => 'Diproses'],
    ['value' => 'siap diambil',        'label' => 'Siap Diambil'],
    ['value' => 'diantar',             'label' => 'Diantar'],
    ['value' => 'selesai',             'label' => 'Selesai'],
    ['value' => 'dibatalkan',          'label' => 'Dibatalkan'],
];
$listUrl = '/admin/orders' . old_query(['status' => $status, 'offset' => $offset ?: null]);
$totalPages = (int) ceil($total / $limit);
$currentPage = (int) floor($offset / $limit) + 1;
?>
<div class="admin-page">
  <h1>Order Management</h1>

  <form class="admin-toolbar" method="get" action="/admin/orders">
    <select class="admin-select" name="status" onchange="this.form.submit()">
      <?php foreach ($statuses as $s): ?>
        <option value="<?= e($s['value']) ?>"<?= $status === $s['value'] ? ' selected' : '' ?>><?= e($s['label']) ?></option>
      <?php endforeach; ?>
    </select>
    <span class="admin-total-label"><?= e($total) ?> order ditemukan</span>
  </form>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Total</th>
          <th>Metode</th>
          <th>Bayar</th>
          <th>Status</th>
          <th>Tanggal</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr><td colspan="8" class="admin-table-empty">Tidak ada order.</td></tr>
        <?php else: ?>
          <?php foreach ($orders as $order): ?>
            <tr>
              <td>
                <a class="admin-link" href="/admin/orders/<?= e(rawurlencode($order['id'])) ?>"><?= e($order['orderNumber']) ?></a>
              </td>
              <td><?= e($order['customerName']) ?></td>
              <td><?= e(rupiah($order['totalPrice'])) ?></td>
              <td class="admin-cell-cap"><?= e($order['fulfillmentMethod']) ?></td>
              <td class="admin-cell-cap"><?= e($order['paymentStatus']) ?></td>
              <td><span class="admin-badge <?= e(badge_class($order['status'])) ?>"><?= e($order['status']) ?></span></td>
              <td class="admin-cell-date"><?= e(admin_date($order['createdAt'])) ?></td>
              <td>
                <form method="post" action="/admin/orders/<?= e(rawurlencode($order['id'])) ?>/status">
                  <input type="hidden" name="back" value="<?= e($listUrl) ?>">
                  <select class="admin-select-sm" name="status" onchange="this.form.submit()">
                    <?php foreach (array_slice($statuses, 1) as $s): ?>
                      <option value="<?= e($s['value']) ?>"<?= $order['status'] === $s['value'] ? ' selected' : '' ?>><?= e($s['label']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="admin-pagination">
      <?php if ($offset > 0): ?>
        <a class="admin-page-btn" href="/admin/orders<?= old_query(['status' => $status, 'offset' => max(0, $offset - $limit) ?: null]) ?>">← Prev</a>
      <?php else: ?>
        <span class="admin-page-btn" aria-disabled="true">← Prev</span>
      <?php endif; ?>
      <span class="admin-page-info">Halaman <?= e($currentPage) ?> dari <?= e($totalPages) ?></span>
      <?php if ($currentPage < $totalPages): ?>
        <a class="admin-page-btn" href="/admin/orders<?= old_query(['status' => $status, 'offset' => $offset + $limit]) ?>">Next →</a>
      <?php else: ?>
        <span class="admin-page-btn" aria-disabled="true">Next →</span>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
