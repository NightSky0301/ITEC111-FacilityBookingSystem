<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<header class="fx-head">
    <h1>Hotels</h1>
    <p>Pick a hotel, choose a room and your dates. No payment is needed to request a stay.</p>
</header>

<?php if (empty($hotels)): ?>
    <div class="panel"><div class="empty"><i class="bi bi-building"></i><strong>No hotels yet</strong>Hotels will show up here once they are added.</div></div>
<?php else: ?>
<div class="ht-list">
    <?php foreach ($hotels as $h):
        $img = ! empty($h['image']) && is_file(FCPATH . $h['image']) ? base_url($h['image']) : base_url('assets/img/courts/other-a.svg');
        $n   = count($h['rooms']); ?>
        <a class="ht-card" href="<?= site_url('hotels/' . $h['id']) ?>">
            <span class="ht-card-img" style="background-image:url('<?= esc($img, 'attr') ?>')" role="img" aria-label="<?= esc($h['name'], 'attr') ?>"></span>
            <span class="ht-card-body">
                <span class="ht-card-name"><?= esc($h['name']) ?></span>
                <?php if (! empty($h['tagline'])): ?><span class="ht-card-tag"><?= esc($h['tagline']) ?></span><?php endif; ?>
                <span class="ht-card-desc"><?= esc($h['description']) ?></span>
                <span class="ht-chips">
                    <span class="ht-chip"><i class="bi bi-door-open"></i> <?= $n ?> <?= $n === 1 ? 'room' : 'rooms' ?></span>
                    <span class="ht-chip"><i class="bi bi-box-arrow-in-right"></i> Check-in <?= esc(date('g:i A', strtotime($h['check_in_time']))) ?></span>
                    <span class="ht-chip"><i class="bi bi-box-arrow-right"></i> Check-out <?= esc(date('g:i A', strtotime($h['check_out_time']))) ?></span>
                </span>
                <span class="ht-card-foot">
                    <span class="ht-from">From <b>₱<?= number_format($h['from'], 0) ?></b> <small>per night</small></span>
                    <span class="ht-go">View rooms <i class="bi bi-arrow-right"></i></span>
                </span>
            </span>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
