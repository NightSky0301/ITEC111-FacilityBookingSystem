<?php
/** One court or gym card. Used by Activities, Search and the Fitness page. Needs $c (see facility_card_item) and $dateQs. */
$dateQs = $dateQs ?? '';
?>
<a class="ax-card" href="<?= site_url('facilities/court/' . (int) $c['id']) . $dateQs ?>"
   data-type="<?= esc($c['type'], 'attr') ?>" data-id="<?= (int) $c['id'] ?>" data-free="<?= $c['free'] ? '1' : '0' ?>" data-price="<?= (float) $c['price'] ?>" data-name="<?= esc($c['name'], 'attr') ?>" data-place="<?= $c['outdoor'] ? 'outdoor' : 'indoor' ?>">
    <span class="ax-photo" style="background-image:url('<?= esc($c['img'], 'attr') ?>')">
        <span class="ax-live <?= $c['free'] ? 'is-free' : 'is-busy' ?>" data-live><i></i><b><?= $c['free'] ? 'Free now' : 'In use' ?></b></span>
        <span class="ax-tag"><?= $c['outdoor'] ? 'Outdoor' : 'Indoor' ?></span>
        <span class="ax-title"><?= esc($c['name']) ?></span>
    </span>
    <span class="ax-body">
        <span class="ax-meta">
            <span><i class="bi <?= esc(facility_icon($c['type']), 'attr') ?>" aria-hidden="true"></i> <?= esc($c['activity']) ?></span>
            <span><i class="bi bi-people" aria-hidden="true"></i> <?= esc($c['people']) ?></span>
        </span>
        <span class="ax-foot">
            <span class="ax-price">₱<?= number_format($c['price'], 0) ?> <small><?= $c['per_head'] ? 'per person, per hour' : 'per hour' ?></small></span>
            <span class="ax-book">Book <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
        </span>
    </span>
</a>
