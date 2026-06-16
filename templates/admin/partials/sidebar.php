<?php
/**
 * @var string $active
 * @var array  $currentUser
 * @var string $logoutUrl
 */
$items = [
    ['key' => 'dashboard', 'to' => '/admin/dashboard', 'label' => 'Dashboard', 'icon' => '📊'],
    ['key' => 'orders',    'to' => '/admin/orders',    'label' => 'Orders',    'icon' => '📦'],
    ['key' => 'products',  'to' => '/admin/products',  'label' => 'Products',  'icon' => '🎂'],
    ['key' => 'customers', 'to' => '/admin/customers', 'label' => 'Customers', 'icon' => '👥'],
];
?>
<aside class="admin-sidebar">
  <div class="admin-sidebar-brand">
    <img src="/assets/logo.png" alt="Hanaka Cake" class="admin-logo">
    <span class="admin-brand-text">Hanaka Admin</span>
  </div>

  <nav class="admin-nav">
    <?php foreach ($items as $item): ?>
      <a href="<?= e($item['to']) ?>" class="admin-nav-link<?= $active === $item['key'] ? ' is-active' : '' ?>">
        <span class="admin-nav-icon"><?= $item['icon'] ?></span>
        <?= e($item['label']) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="admin-sidebar-footer">
    <p class="admin-user-name"><?= e($currentUser['fullName'] ?? '') ?></p>
    <a class="admin-logout-btn" href="<?= e($logoutUrl) ?>">Logout</a>
  </div>
</aside>
