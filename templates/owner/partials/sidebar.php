<?php
/**
 * @var string $active
 * @var array  $currentUser
 * @var string $logoutUrl
 */
$items = [
    ['key' => 'dashboard',     'to' => '/owner/dashboard',     'label' => 'Dashboard',      'icon' => '📈'],
    ['key' => 'reports',       'to' => '/owner/reports',       'label' => 'Laporan',        'icon' => '🧾'],
    ['key' => 'announcements', 'to' => '/owner/announcements', 'label' => 'Pemberitahuan',  'icon' => '📣'],
];
?>
<aside class="admin-sidebar owner-sidebar">
  <div class="admin-sidebar-brand">
    <img src="/assets/logo.png" alt="Hanaka Cake" class="admin-logo">
    <span class="admin-brand-text">Hanaka Owner</span>
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
    <p class="owner-role-tag">Owner</p>
    <a class="admin-logout-btn" href="<?= e($logoutUrl) ?>">Logout</a>
  </div>
</aside>
