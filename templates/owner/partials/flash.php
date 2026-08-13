<?php
/**
 * @var array{type:string,text:string} $flash
 */
$type = ($flash['type'] ?? 'success') === 'error' ? 'is-error' : 'is-success';
?>
<div class="admin-flash <?= $type ?>" role="status">
  <?= e($flash['text'] ?? '') ?>
</div>
