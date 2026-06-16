<?php
/**
 * @var array $products
 */
?>
<div class="admin-page">
  <div class="admin-page-header">
    <h1>Product Management</h1>
    <button type="button" class="primary-button" data-modal-open="modal-create">+ Tambah Produk</button>
  </div>

  <div class="admin-product-list">
    <?php foreach ($products as $product): ?>
      <?php $img = product_image($product['coverImage'] ?? null); ?>
      <div class="admin-product-card">
        <div class="admin-product-header">
          <div style="display:flex; align-items:center; gap:12px;">
            <?php if ($img): ?>
              <img src="<?= e($img) ?>" alt="<?= e($product['name']) ?>" class="admin-product-thumb">
            <?php elseif (!empty($product['coverGradient'])): ?>
              <div class="admin-product-thumb" style="background: <?= e($product['coverGradient']) ?>"></div>
            <?php endif; ?>
            <div>
              <h3><?= e($product['name']) ?></h3>
              <p class="muted-text"><?= e($product['shortDescription']) ?></p>
              <?php if (!empty($product['featured'])): ?>
                <span class="admin-badge badge-done">Featured</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="admin-product-actions">
            <button type="button" class="ghost-button" data-modal-open="modal-edit-<?= e($product['id']) ?>">Edit</button>
            <form method="post" action="/admin/products/<?= e(rawurlencode($product['id'])) ?>/delete"
                  onsubmit="return confirm('Hapus produk ini beserta semua ukurannya?')">
              <button type="submit" class="danger-button">Hapus</button>
            </form>
          </div>
        </div>

        <div class="admin-sizes-section">
          <div class="admin-sizes-header">
            <strong>Ukuran &amp; Harga</strong>
            <button type="button" class="admin-add-size-btn" data-modal-open="modal-size-<?= e($product['id']) ?>">+ Ukuran</button>
          </div>
          <?php if (!empty($product['sizes'])): ?>
            <div class="admin-sizes-list">
              <?php foreach ($product['sizes'] as $size): ?>
                <div class="admin-size-row">
                  <span><?= e($size['fullLabel']) ?></span>
                  <span><?= e(rupiah($size['price'])) ?></span>
                  <form method="post" action="/admin/products/<?= e(rawurlencode($product['id'])) ?>/sizes/<?= e(rawurlencode($size['id'])) ?>/delete"
                        onsubmit="return confirm('Hapus ukuran ini?')">
                    <button type="submit" class="admin-delete-size-btn" aria-label="Hapus ukuran">✕</button>
                  </form>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="muted-text">Belum ada ukuran.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Edit modal -->
      <div class="admin-modal-overlay" id="modal-edit-<?= e($product['id']) ?>">
        <div class="admin-modal">
          <h2>Edit Produk</h2>
          <form class="form-grid" method="post" action="/admin/products/<?= e(rawurlencode($product['id'])) ?>" enctype="multipart/form-data">
            <label class="field">
              Nama Produk
              <input name="name" value="<?= e($product['name']) ?>" required>
            </label>
            <label class="field">
              Deskripsi Pendek
              <input name="shortDescription" value="<?= e($product['shortDescription']) ?>" required>
            </label>
            <label class="field">
              Deskripsi Panjang
              <textarea name="longDescription" rows="3"><?= e($product['longDescription']) ?></textarea>
            </label>
            <label class="field">
              Cover Gradient (CSS fallback)
              <input name="coverGradient" value="<?= e($product['coverGradient']) ?>" placeholder="linear-gradient(135deg, #8B6914, #D4A843)">
            </label>
            <div class="field">
              <span>Cover Image</span>
              <img id="preview-edit-<?= e($product['id']) ?>" class="admin-image-preview"<?= $img ? ' src="' . e($img) . '"' : ' style="display:none"' ?> alt="Preview">
              <input type="file" id="file-edit-<?= e($product['id']) ?>" name="image" accept="image/jpeg,image/png,image/webp"
                     style="display:none" onchange="adminPreviewImage(this,'preview-edit-<?= e($product['id']) ?>')">
              <button type="button" class="ghost-button" onclick="document.getElementById('file-edit-<?= e($product['id']) ?>').click()">Ganti Gambar</button>
              <span class="muted-text" style="font-size:0.75rem;">JPG, PNG, WebP — maks. 2MB</span>
            </div>
            <label class="field">
              Max Message Length
              <input name="maxMessageLength" type="number" value="<?= e($product['maxMessageLength']) ?>">
            </label>
            <label class="admin-checkbox-field">
              <input type="checkbox" name="featured"<?= !empty($product['featured']) ? ' checked' : '' ?>>
              Tampilkan di Best Seller
            </label>
            <div class="admin-form-actions">
              <button type="submit" class="primary-button">Simpan Perubahan</button>
              <button type="button" class="ghost-button" data-modal-close>Batal</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Add size modal -->
      <div class="admin-modal-overlay" id="modal-size-<?= e($product['id']) ?>">
        <div class="admin-modal">
          <h2>Tambah Ukuran — <?= e($product['name']) ?></h2>
          <form class="form-grid" method="post" action="/admin/products/<?= e(rawurlencode($product['id'])) ?>/sizes">
            <label class="field">
              Label (cth. 16)
              <input name="label" required>
            </label>
            <label class="field">
              Full Label (cth. Ukuran 16 cm)
              <input name="fullLabel" required>
            </label>
            <label class="field">
              Harga (Rp)
              <input name="price" type="number" required>
            </label>
            <div class="admin-form-actions">
              <button type="submit" class="primary-button">Tambah</button>
              <button type="button" class="ghost-button" data-modal-close>Batal</button>
            </div>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Create modal -->
<div class="admin-modal-overlay" id="modal-create">
  <div class="admin-modal">
    <h2>Tambah Produk Baru</h2>
    <form class="form-grid" method="post" action="/admin/products" enctype="multipart/form-data">
      <label class="field">
        ID (slug)
        <input name="id" placeholder="cth. tiramisu-cake" required>
      </label>
      <label class="field">
        Nama Produk
        <input name="name" required>
      </label>
      <label class="field">
        Deskripsi Pendek
        <input name="shortDescription" required>
      </label>
      <label class="field">
        Deskripsi Panjang
        <textarea name="longDescription" rows="3"></textarea>
      </label>
      <label class="field">
        Cover Gradient (CSS fallback)
        <input name="coverGradient" placeholder="linear-gradient(135deg, #8B6914, #D4A843)">
      </label>
      <div class="field">
        <span>Cover Image</span>
        <img id="preview-create" class="admin-image-preview" style="display:none" alt="Preview">
        <input type="file" id="file-create" name="image" accept="image/jpeg,image/png,image/webp"
               style="display:none" onchange="adminPreviewImage(this,'preview-create')">
        <button type="button" class="ghost-button" onclick="document.getElementById('file-create').click()">Pilih Gambar</button>
        <span class="muted-text" style="font-size:0.75rem;">JPG, PNG, WebP — maks. 2MB</span>
      </div>
      <label class="field">
        Max Message Length
        <input name="maxMessageLength" type="number" value="60">
      </label>
      <label class="admin-checkbox-field">
        <input type="checkbox" name="featured">
        Tampilkan di Best Seller
      </label>
      <div class="admin-form-actions">
        <button type="submit" class="primary-button">Buat Produk</button>
        <button type="button" class="ghost-button" data-modal-close>Batal</button>
      </div>
    </form>
  </div>
</div>

<script>
  function adminPreviewImage(input, imgId) {
    var img = document.getElementById(imgId);
    var file = input.files && input.files[0];
    if (file && img) {
      img.src = URL.createObjectURL(file);
      img.style.display = 'block';
    }
  }
  document.addEventListener('click', function (e) {
    var openBtn = e.target.closest('[data-modal-open]');
    if (openBtn) {
      var modal = document.getElementById(openBtn.getAttribute('data-modal-open'));
      if (modal) modal.classList.add('is-open');
      return;
    }
    if (e.target.closest('[data-modal-close]')) {
      var overlay = e.target.closest('.admin-modal-overlay');
      if (overlay) overlay.classList.remove('is-open');
      return;
    }
    if (e.target.classList.contains('admin-modal-overlay')) {
      e.target.classList.remove('is-open');
    }
  });
</script>
