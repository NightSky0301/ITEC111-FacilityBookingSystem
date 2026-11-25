<?php
/**
 * Photo banner at the top of every category page (Activities, Hotels, Fitness, Entertainment).
 * Needs: $photo, $alt, $title, $text, $perks [[icon, label], ...], $free, $total, $when ('now' or 'tonight').
 * The availability bar is updated live on the Activities, Fitness and Entertainment pages (see the script in facilities/index.php).
 */
$total = (int) $total;
$free  = (int) $free;
?>
<section class="gy-feature" aria-label="<?= esc($title, 'attr') ?>">
    <div class="gy-photo" style="background-image:url('<?= esc($photo, 'attr') ?>')" role="img" aria-label="<?= esc($alt, 'attr') ?>"></div>
    <div class="gy-copy">
        <h2><?= esc($title) ?></h2>
        <p><?= esc($text) ?></p>
        <div class="gy-avail" aria-live="polite">
            <span class="gy-avail-top"><span class="fn-dot" aria-hidden="true"></span> <b id="axFreeStat"><?= $free ?></b> of <?= $total ?> <?= $total === 1 ? 'facility' : 'facilities' ?> available <?= esc($when ?? 'now') ?></span>
            <span class="gy-avail-bar" aria-hidden="true"><i id="axFreeBar" style="width:<?= $total ? (int) round($free * 100 / $total) : 0 ?>%"></i></span>
        </div>
    </div>
</section>
