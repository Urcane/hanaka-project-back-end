<?php
/**
 * @var array $customers
 */
?>
<div class="admin-page">
  <h1>Customer List</h1>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Email</th>
          <th>Telepon</th>
          <th>Terdaftar</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($customers)): ?>
          <tr><td colspan="4" class="admin-table-empty">Belum ada customer.</td></tr>
        <?php else: ?>
          <?php foreach ($customers as $c): ?>
            <tr>
              <td><?= e($c['fullName']) ?></td>
              <td><?= e($c['email']) ?></td>
              <td><?= e($c['phone']) ?></td>
              <td class="admin-cell-date"><?= e(admin_date($c['createdAt'])) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
