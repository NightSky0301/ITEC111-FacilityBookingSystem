<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $img = ! empty($hotel['image']) && is_file(FCPATH . $hotel['image']) ? base_url($hotel['image']) : base_url('assets/img/courts/other-a.svg');
    $cin  = date('g:i A', strtotime($hotel['check_in_time']));
    $cout = date('g:i A', strtotime($hotel['check_out_time']));
?>
<nav class="ht-crumbs" aria-label="Breadcrumb"><a href="<?= site_url('hotels') ?>"><i class="bi bi-arrow-left"></i> All hotels</a></nav>

<section class="ht-hero">
    <div class="ht-hero-img" style="background-image:url('<?= esc($img, 'attr') ?>')" role="img" aria-label="<?= esc($hotel['name'], 'attr') ?>"></div>
    <div class="ht-hero-body">
        <h1><?= esc($hotel['name']) ?></h1>
        <?php if (! empty($hotel['tagline'])): ?><p class="ht-hero-tag"><?= esc($hotel['tagline']) ?></p><?php endif; ?>
        <p class="ht-hero-desc"><?= esc($hotel['description']) ?></p>
        <ul class="ht-facts">
            <li><i class="bi bi-door-open"></i><span><b><?= count($rooms) ?> <?= count($rooms) === 1 ? 'room' : 'rooms' ?></b>Standard to suite</span></li>
            <li><i class="bi bi-box-arrow-in-right"></i><span><b>Check-in from <?= esc($cin) ?></b>On your first day</span></li>
            <li><i class="bi bi-box-arrow-right"></i><span><b>Check-out by <?= esc($cout) ?></b>On your last day</span></li>
        </ul>
    </div>
</section>

<h2 class="ht-h2">Rooms</h2>
<?php if (empty($rooms)): ?>
    <div class="panel"><div class="empty"><i class="bi bi-door-closed"></i><strong>No rooms to book</strong>Rooms will show up here once they are added.</div></div>
<?php else: ?>
<div class="fx-grid" id="live-rooms" data-live>
    <?php foreach ($rooms as $r): ?>
        <a class="fx-card" href="<?= site_url('hotels/room/' . $r['id']) ?>">
            <span class="fx-photo" style="background-image:url('<?= esc(facility_image($r), 'attr') ?>')">
                <span class="fx-tag"><i class="bi bi-people"></i> Sleeps <?= (int) $r['capacity'] ?></span>
                <span class="fx-live <?= $r['free_tonight'] ? 'is-free' : 'is-busy' ?>"><i></i><b><?= $r['free_tonight'] ? 'Free tonight' : 'Booked tonight' ?></b></span>
            </span>
            <span class="fx-body">
                <span class="fx-name"><?= esc($r['name']) ?></span>
                <span class="fx-sub"><?= esc(mb_strimwidth((string) $r['description'], 0, 78, '…')) ?></span>
                <span class="fx-price">₱<?= number_format((float) $r['price_per_hour'], 0) ?> <small>per night</small></span>
            </span>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
