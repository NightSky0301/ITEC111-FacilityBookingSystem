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
                        <span class="d-none d-sm-inline"><?= esc($s['label']) ?></span>
                        <span class="d-sm-none"><?= esc($s['short']) ?></span>
                    </span>
                </<?= $link ? 'a' : 'span' ?>>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
