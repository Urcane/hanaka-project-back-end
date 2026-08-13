<?php
/**
 * Dependency-free bar chart drawn as inline SVG.
 *
 * @var array  $series    List of ['label' => string, 'value' => int, 'caption' => string?]
 * @var bool   $currency  Format values as rupiah in the tooltip
 * @var string $emptyText Message when every value is zero
 */
$series = $series ?? [];
$currency = $currency ?? false;
$max = 0;
foreach ($series as $point) {
    $max = max($max, (int) $point['value']);
}

// Geometry in viewBox units; the SVG scales to whatever width it is given.
$count = max(1, count($series));
$slot = 10;                 // horizontal space per bar
$barWidth = 6;
$chartHeight = 80;
$width = $count * $slot;
?>
<?php if ($max <= 0): ?>
  <p class="muted-text owner-chart-empty"><?= e($emptyText ?? 'Belum ada data pada rentang ini.') ?></p>
<?php else: ?>
  <div class="owner-chart">
    <svg viewBox="0 0 <?= $width ?> <?= $chartHeight + 14 ?>" preserveAspectRatio="none" role="img"
         aria-label="Grafik batang">
      <?php foreach (array_values($series) as $i => $point): ?>
        <?php
        $value = (int) $point['value'];
        $barHeight = $max > 0 ? ($value / $max) * $chartHeight : 0;
        $x = $i * $slot + ($slot - $barWidth) / 2;
        $y = $chartHeight - $barHeight;
        $tooltip = ($point['label'] ?? '') . ' — ' . ($currency ? rupiah($value) : $value);
        ?>
        <rect class="owner-bar" x="<?= round($x, 2) ?>" y="<?= round($y, 2) ?>"
              width="<?= $barWidth ?>" height="<?= round(max($barHeight, 0.6), 2) ?>" rx="1.2">
          <title><?= e($tooltip) ?></title>
        </rect>
      <?php endforeach; ?>
    </svg>
    <div class="owner-chart-axis" style="--chart-slots: <?= $count ?>;">
      <?php foreach ($series as $point): ?>
        <span><?= e($point['caption'] ?? $point['label'] ?? '') ?></span>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>
