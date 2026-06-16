<?php
/**
 * @var array $order
 */
$orderStatuses = ['menunggu konfirmasi', 'diproses', 'siap diambil', 'diantar', 'selesai', 'dibatalkan'];
$paymentStatuses = ['pending', 'paid', 'cod'];
$detailUrl = '/admin/orders/' . rawurlencode($order['id']);
?>
<div class="admin-page">
  <a href="/admin/orders" class="admin-back-link">← Kembali ke Orders</a>

  <h1>Order <?= e($order['orderNumber']) ?></h1>

  <div class="admin-detail-grid">
    <div class="admin-detail-card">
      <h3>Informasi Customer</h3>
      <p><strong>Nama:</strong> <?= e($order['customerName']) ?></p>
      <p><strong>Telepon:</strong> <?= e($order['customerPhone']) ?></p>
      <p><strong>Metode:</strong> <?= e($order['fulfillmentMethod']) ?></p>
      <?php if ($order['fulfillmentMethod'] === 'pickup'): ?>
        <p><strong>Tanggal:</strong> <?= e($order['pickupDate']) ?></p>
        <p><strong>Jam:</strong> <?= e($order['pickupTime']) ?></p>
      <?php else: ?>
        <p><strong>Alamat:</strong> <?= e($order['deliveryAddress']) ?></p>
        <?php if (!empty($order['addressNote'])): ?>
          <p><strong>Catatan:</strong> <?= e($order['addressNote']) ?></p>
        <?php endif; ?>
      <?php endif; ?>
      <p><strong>Tanggal Order:</strong> <?= e(admin_date($order['createdAt'], true)) ?></p>
    </div>

    <div class="admin-detail-card">
      <h3>Status &amp; Pembayaran</h3>

      <label class="admin-detail-field">
        <strong>Status Order</strong>
        <form method="post" action="<?= e($detailUrl) ?>/status">
          <input type="hidden" name="back" value="<?= e($detailUrl) ?>">
          <select class="admin-select" name="status" onchange="this.form.submit()">
            <?php foreach ($orderStatuses as $s): ?>
              <option value="<?= e($s) ?>"<?= $order['status'] === $s ? ' selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </label>

      <label class="admin-detail-field">
        <strong>Status Pembayaran</strong>
        <form method="post" action="<?= e($detailUrl) ?>/payment-status">
          <input type="hidden" name="back" value="<?= e($detailUrl) ?>">
          <select class="admin-select" name="paymentStatus" onchange="this.form.submit()">
            <?php foreach ($paymentStatuses as $s): ?>
              <option value="<?= e($s) ?>"<?= $order['paymentStatus'] === $s ? ' selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </label>

      <p><strong>Metode Bayar:</strong> <?= e($order['paymentMethod']) ?></p>
      <p><strong>Total:</strong> <?= e(rupiah($order['totalPrice'])) ?></p>
    </div>
  </div>

  <div class="admin-detail-card">
    <h3>Items</h3>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Produk</th>
            <th>Ukuran</th>
            <th>Warna</th>
            <th>Tema</th>
            <th>Catatan</th>
            <th>Qty</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($order['items'] as $item): ?>
            <tr>
              <td><?= e($item['productName']) ?></td>
              <td><?= e($item['sizeLabel']) ?></td>
              <td><?= e($item['colorText'] ?: '-') ?></td>
              <td><?= e($item['theme'] ?: '-') ?></td>
              <td><?= e($item['message'] ?: '-') ?></td>
              <td><?= e($item['quantity']) ?></td>
              <td><?= e(rupiah($item['totalPrice'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
