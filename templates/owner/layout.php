<?php
/**
 * @var \App\Admin\Support\View $view
 * @var string $content   Rendered page HTML
 * @var string $title     Page title
 * @var string $active    Active sidebar key
 * @var array  $currentUser
 * @var array|null $flash
 * @var string $logoutUrl
 */
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex">
  <title><?= e($title ?? 'Owner') ?> — Hanaka Owner</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="stylesheet" href="/assets/admin.css">
  <link rel="stylesheet" href="/assets/owner.css">
  <!-- Loaded here (not at the end of body) so the map init script a page
       renders inside its own markup already has L available. -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
  <div class="admin-shell owner-shell">
    <?= $view->partial('partials/sidebar', ['active' => $active ?? '', 'currentUser' => $currentUser ?? [], 'logoutUrl' => $logoutUrl ?? '/admin/logout']) ?>
    <main class="admin-main">
      <?php if (!empty($flash)): ?>
        <?= $view->partial('partials/flash', ['flash' => $flash]) ?>
      <?php endif; ?>
      <?= $content ?>
    </main>
  </div>
</body>
</html>
