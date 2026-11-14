<?php
/**
 * Shows where the visitor is in: Activity -> Court -> Availability.
 * $step = 1, 2 or 3. Finished steps are links back to that step.
 */
$steps = [
    1 => ['label' => 'Choose an activity', 'short' => 'Activity', 'url' => site_url('facilities')],
    2 => ['label' => 'Choose a court',     'short' => 'Court',    'url' => null],
    3 => ['label' => 'Pick a time',        'short' => 'Time',     'url' => null],
];
$typeKey = $facility['type'] ?? ($type ?? null);
if ($typeKey) {
    $steps[2]['url'] = site_url('facilities/' . $typeKey);
}
?>
<div class="stepper-row">
<div class="stepper-side">
    <?php if (! empty($backUrl)): ?>
        <a href="<?= esc($backUrl, 'attr') ?>" class="btn-back"><i class="bi bi-arrow-left"></i><span><?= esc($backLabel ?? 'Back') ?></span></a>
    <?php endif; ?>
</div>
<nav class="stepper-wrap" aria-label="Booking steps">
    <ol class="stepper">
        <?php foreach ($steps as $n => $s):
            $state = $n < $step ? 'is-done' : ($n === $step ? 'is-current' : 'is-todo');
            $link  = $n < $step && $s['url'];
        ?>
            <li class="stepper-item <?= $state ?>" <?= $n === $step ? 'aria-current="step"' : '' ?>>
                <<?= $link ? 'a href="' . esc($s['url'], 'attr') . '"' : 'span' ?> class="stepper-chip">
                    <span class="stepper-n"><?= $n < $step ? '<i class="bi bi-check-lg"></i>' : $n ?></span>
                    <span class="stepper-label">
                        <span class="d-none d-md-inline"><?= esc($s['label']) ?></span>
                        <span class="d-md-none"><?= esc($s['short']) ?></span>
                    </span>
                </<?= $link ? 'a' : 'span' ?>>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
<div class="stepper-side d-none d-lg-block"></div>
</div>
