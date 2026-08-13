<?php
/**
 * Unread owner announcements, shown on every admin page until dismissed.
 *
 * @var array $announcements
 */
?>
<?php foreach ($announcements as $item): ?>
  <div class="admin-announcement<?= ($item['priority'] ?? 'info') === 'penting' ? ' is-important' : '' ?>" role="note">
    <div class="admin-announcement-body">
      <p class="admin-announcement-meta">
        <span class="admin-announcement-tag"><?= ($item['priority'] ?? 'info') === 'penting' ? 'PENTING' : 'INFO' ?></span>
        Dari <?= e($item['authorName']) ?> · <?= admin_date($item['createdAt'], true) ?>
      </p>
      <h3 class="admin-announcement-title"><?= e($item['title']) ?></h3>
      <p class="admin-announcement-text"><?= nl2br(e($item['body'])) ?></p>
    </div>
    <form method="post" action="/admin/announcements/<?= e(rawurlencode($item['id'])) ?>/read">
      <button type="submit" class="ghost-button">Tandai dibaca</button>
    </form>
  </div>
<?php endforeach; ?>
