<?php
/**
 * @var array $announcements
 */
?>
<div class="admin-page">
  <div class="admin-page-header">
    <div>
      <h1>Pemberitahuan</h1>
      <p class="muted-text">
        Pesan yang dikirim di sini muncul sebagai banner di setiap halaman admin
        sampai admin menandainya dibaca.
      </p>
    </div>
  </div>

  <section class="owner-panel">
    <h2>Kirim Pemberitahuan Baru</h2>
    <form class="form-grid owner-announcement-form" method="post" action="/owner/announcements">
      <label class="field">
        Judul
        <input name="title" maxlength="120" placeholder="Contoh: Stok Red Velvet menipis" required>
      </label>
      <label class="field">
        Isi Pesan
        <textarea name="body" rows="4" placeholder="Tulis instruksi atau informasi untuk admin..." required></textarea>
      </label>
      <label class="field">
        Prioritas
        <select name="priority">
          <option value="info">Info</option>
          <option value="penting">Penting</option>
        </select>
      </label>
      <div>
        <button type="submit" class="primary-button">Kirim ke Semua Admin</button>
      </div>
    </form>
  </section>

  <section class="owner-panel">
    <div class="owner-panel-head">
      <h2>Riwayat Pemberitahuan</h2>
      <span class="muted-text"><?= count($announcements) ?> pesan</span>
    </div>

    <?php if (empty($announcements)): ?>
      <p class="muted-text owner-chart-empty">Belum ada pemberitahuan.</p>
    <?php else: ?>
      <div class="owner-announcement-list">
        <?php foreach ($announcements as $item): ?>
          <article class="owner-announcement-item<?= $item['priority'] === 'penting' ? ' is-important' : '' ?>">
            <div>
              <p class="admin-announcement-meta">
                <span class="admin-announcement-tag"><?= $item['priority'] === 'penting' ? 'PENTING' : 'INFO' ?></span>
                <?= admin_date($item['createdAt'], true) ?>
                · dibaca <?= (int) ($item['readCount'] ?? 0) ?> admin
              </p>
              <h3><?= e($item['title']) ?></h3>
              <p class="admin-announcement-text"><?= nl2br(e($item['body'])) ?></p>
            </div>
            <form method="post" action="/owner/announcements/<?= e(rawurlencode($item['id'])) ?>/delete"
                  onsubmit="return confirm('Hapus pemberitahuan ini?')">
              <button type="submit" class="danger-button">Hapus</button>
            </form>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
