<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php if (empty($hotels)): ?>
    <div class="panel"><div class="empty"><i class="bi bi-building"></i><strong>No hotels yet</strong>Hotels will show up here once they are added.</div></div>
<?php else:
    $roomsAll = 0;
    $freeAll  = 0;
    foreach ($hotels as $h) {
        $roomsAll += count($h['rooms']);
        $freeAll  += (int) ($h['free_tonight'] ?? 0);
    }
    $bannerImg = '';
    foreach ($hotels as $h) {
        if (! empty($h['image']) && is_file(FCPATH . $h['image'])) { $bannerImg = base_url($h['image']); break; }
    }
    $bn = FCPATH . 'assets/img/banners/hotels.jpg';
    if (is_file($bn)) { $bannerImg = base_url('assets/img/banners/hotels.jpg') . '?v=' . filemtime($bn); }
    echo view('facilities/_banner', [
        'photo' => $bannerImg, 'alt' => 'Hotels and suites', 'title' => 'Rest up after the final whistle',
        'text'  => 'Pick a hotel, choose a room and your dates. No payment is needed to request a stay.',
        'perks' => [['bi-moon-stars', 'Booked by the night'], ['bi-box-arrow-in-right', 'Easy check-in and out'], ['bi-credit-card-2-front', 'No payment to request'], ['bi-patch-check', 'Confirmed by staff']],
        'free'  => $freeAll, 'total' => $roomsAll, 'when' => 'tonight',
    ]);
?>
<div class="ht-list" id="live-hotels" data-live>
    <?php foreach ($hotels as $h):
        $img  = ! empty($h['image']) && is_file(FCPATH . $h['image']) ? base_url($h['image']) : base_url('assets/img/courts/other-a.svg');
        $n    = count($h['rooms']);
        $free = (int) ($h['free_tonight'] ?? 0);
        $max  = (int) ($h['max_guests'] ?? 0); ?>
        <a class="ht-card" href="<?= site_url('hotels/' . $h['id']) ?>">
            <span class="ht-card-img" style="background-image:url('<?= esc($img, 'attr') ?>')" role="img" aria-label="<?= esc($h['name'], 'attr') ?>"></span>
            <span class="ht-card-body">
                <span class="ht-card-name"><?= esc($h['name']) ?></span>
                <?php if (! empty($h['tagline'])): ?><span class="ht-card-tag"><?= esc($h['tagline']) ?></span><?php endif; ?>
                <span class="ht-card-desc"><?= esc($h['description']) ?></span>
                <span class="ht-chips">
                    <?php if ($n === 0): ?>
                        <span class="ht-chip is-full"><i class="bi bi-door-closed"></i> No rooms listed yet</span>
                    <?php elseif ($free > 0): ?>
                        <span class="ht-chip is-free"><i class="bi bi-check-circle-fill"></i> <?= $free ?> of <?= $n ?> <?= $n === 1 ? 'room' : 'rooms' ?> free tonight</span>
                    <?php else: ?>
                        <span class="ht-chip is-full"><i class="bi bi-x-circle-fill"></i> Fully booked tonight</span>
                    <?php endif; ?>
                    <?php if ($n > 0): ?><span class="ht-chip"><i class="bi bi-door-open"></i> <?= $n ?> <?= $n === 1 ? 'room' : 'rooms' ?></span><?php endif; ?>
                    <?php if ($max > 0): ?><span class="ht-chip"><i class="bi bi-people"></i> Sleeps up to <?= $max ?></span><?php endif; ?>
                    <span class="ht-chip"><i class="bi bi-box-arrow-in-right"></i> Check-in <?= esc(date('g:i A', strtotime($h['check_in_time']))) ?></span>
                    <span class="ht-chip"><i class="bi bi-box-arrow-right"></i> Check-out <?= esc(date('g:i A', strtotime($h['check_out_time']))) ?></span>
                </span>
                <span class="ht-card-foot">
                    <span class="ht-from">From <b>₱<?= number_format($h['from'], 0) ?></b> <small>per night<?= ($h['to'] ?? 0) > $h['from'] ? ' · up to ₱' . number_format($h['to'], 0) : '' ?></small></span>
                </span>
            </span>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
