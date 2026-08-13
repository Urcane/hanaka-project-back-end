<?php
/**
 * Dependency-free donut chart. The radius is 15.9155 so the circumference is
 * exactly 100 — stroke-dasharray then takes percentages directly.
 *
 * @var array  $segments  List of ['label' => string, 'value' => int, 'color' => string]
 * @var string $centerLabel
 * @var string $emptyText
 */
$segments = $segments ?? [];
$total = 0;
foreach ($segments as $segment) {
    $total += (int) $segment['value'];
}
?>
<?php if ($total <= 0): ?>
  <p class="muted-text owner-chart-empty"><?= e($emptyText ?? 'Belum ada data pada rentang ini.') ?></p>
<?php else: ?>
  <div class="owner-donut">
    <svg viewBox="0 0 42 42" role="img" aria-label="Grafik donat">
      <circle class="owner-donut-track" cx="21" cy="21" r="15.9155" fill="none" stroke-width="5"></circle>
      <?php $offset = 25; // start at 12 o'clock ?>
      <?php foreach ($segments as $segment): ?>
        <?php
        $value = (int) $segment['value'];
        if ($value <= 0) {
            continue;
        }
        $percent = $value / $total * 100;
        ?>
        <circle cx="21" cy="21" r="15.9155" fill="none" stroke-width="5"
                stroke="<?= e($segment['color']) ?>"
                stroke-dasharray="<?= round($percent, 2) ?> <?= round(100 - $percent, 2) ?>"
                stroke-dashoffset="<?= round($offset, 2) ?>">
          <title><?= e($segment['label'] . ' — ' . $value . ' (' . round($percent) . '%)') ?></title>
        </circle>
        <?php $offset = fmod($offset - $percent + 100, 100); ?>
      <?php endforeach; ?>
      <text x="21" y="20.5" class="owner-donut-value"><?= e((string) $total) ?></text>
      <text x="21" y="24.5" class="owner-donut-caption"><?= e($centerLabel ?? 'order') ?></text>
    </svg>
    <ul class="owner-legend">
      <?php foreach ($segments as $segment): ?>
        <?php if ((int) $segment['value'] <= 0) {
            continue;
        } ?>
        <li>
          <span class="owner-legend-dot" style="background: <?= e($segment['color']) ?>"></span>
          <span class="owner-legend-label"><?= e($segment['label']) ?></span>
          <strong><?= (int) $segment['value'] ?></strong>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
