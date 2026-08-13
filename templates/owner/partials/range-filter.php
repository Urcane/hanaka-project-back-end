<?php
/**
 * Date-range filter shared by the dashboard and the reports page.
 *
 * @var array  $range   ['from' => Y-m-d, 'to' => Y-m-d]
 * @var string $action  Form target path
 * @var string $exportUrl  Optional CSV link ('' to hide)
 */
$today = (new DateTimeImmutable('today'));
$presets = [
    ['label' => 'Hari ini', 'from' => $today->format('Y-m-d'), 'to' => $today->format('Y-m-d')],
    ['label' => '7 hari', 'from' => $today->modify('-6 days')->format('Y-m-d'), 'to' => $today->format('Y-m-d')],
    ['label' => '30 hari', 'from' => $today->modify('-29 days')->format('Y-m-d'), 'to' => $today->format('Y-m-d')],
    ['label' => 'Bulan ini', 'from' => $today->modify('first day of this month')->format('Y-m-d'), 'to' => $today->format('Y-m-d')],
];
?>
<form class="owner-range" method="get" action="<?= e($action) ?>">
  <label class="field">
    Dari
    <input type="date" name="from" value="<?= e($range['from']) ?>">
  </label>
  <label class="field">
    Sampai
    <input type="date" name="to" value="<?= e($range['to']) ?>">
  </label>
  <button type="submit" class="primary-button">Terapkan</button>

  <div class="owner-range-presets">
    <?php foreach ($presets as $preset): ?>
      <?php $isActive = $range['from'] === $preset['from'] && $range['to'] === $preset['to']; ?>
      <a class="owner-chip<?= $isActive ? ' is-active' : '' ?>"
         href="<?= e($action) ?>?from=<?= e($preset['from']) ?>&to=<?= e($preset['to']) ?>">
        <?= e($preset['label']) ?>
      </a>
    <?php endforeach; ?>
    <?php if (!empty($exportUrl)): ?>
      <a class="owner-chip is-export" href="<?= e($exportUrl) ?>">⬇ Export CSV</a>
    <?php endif; ?>
  </div>
</form>
