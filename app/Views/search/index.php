<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $dateQs = $date !== '' ? '?date=' . rawurlencode($date) : '';
?>
<header class="fx-head">
    <div>
        <h1><?= $q !== '' ? 'Results for “' . esc($q) . '”' : 'Everything you can book' ?></h1>
        <p><?= $total === 0 ? 'Nothing matched yet.' : $total . ($total === 1 ? ' place' : ' places') . ' found' . ($date !== '' ? ' · showing times for ' . esc(date('D, M j', strtotime($date))) : '') . '.' ?></p>
    </div>
</header>

<form class="sr-form" action="<?= site_url('search') ?>" method="get" role="search">
    <i class="bi bi-search" aria-hidden="true"></i>
    <input type="search" name="q" value="<?= esc($q, 'attr') ?>" placeholder="Search courts, hotels, rooms or activities" aria-label="Search" autocomplete="off">
    <?php if ($date !== ''): ?><input type="hidden" name="date" value="<?= esc($date, 'attr') ?>"><?php endif; ?>
    <button class="btn btn-gold" type="submit">Search</button>
</form>

<?php if ($total === 0): ?>
    <div class="panel">
        <div class="empty">
            <i class="bi bi-search"></i>
            <strong>No results for “<?= esc($q) ?>”</strong>
            Try a sport like basketball, a hotel name, or one of these:
            <div class="sr-chips mt-3">
                <?php foreach (array_slice($acts, 0, 8, true) as $k => $name): ?>
                    <a class="sr-chip" href="<?= site_url('facilities') ?>?type=<?= esc($k, 'url') ?>"><i class="bi <?= esc(facility_icon($k), 'attr') ?>"></i> <?= esc($name) ?></a>
                <?php endforeach; ?>
                <a class="sr-chip" href="<?= site_url('hotels') ?>"><i class="bi bi-building"></i> Hotels</a>
                <a class="sr-chip" href="<?= site_url('fitness') ?>"><i class="bi bi-heart-pulse"></i> Fitness</a>
            </div>
        </div>
    </div>
<?php else: ?>

<?php if (! empty($courts)): ?>
<section class="sr-group">
    <h2 class="sr-h"><i class="bi bi-trophy"></i> Activities <span><?= count($courts) ?></span></h2>
    <div class="ax-grid">
        <?php foreach ($courts as $c): ?><?= view('facilities/_card', ['c' => $c, 'dateQs' => $dateQs]) ?><?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (! empty($gyms)): ?>
<section class="sr-group">
    <h2 class="sr-h"><i class="bi bi-heart-pulse"></i> Fitness <span><?= count($gyms) ?></span></h2>
    <div class="ax-grid">
        <?php foreach ($gyms as $c): ?><?= view('facilities/_card', ['c' => $c, 'dateQs' => $dateQs]) ?><?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (! empty($hotels)): ?>
<section class="sr-group">
    <h2 class="sr-h"><i class="bi bi-building"></i> Hotels <span><?= count($hotels) ?></span></h2>
    <div class="ht-list">
        <?php foreach ($hotels as $h):
            $img = ! empty($h['image']) && is_file(FCPATH . $h['image']) ? base_url($h['image']) : base_url('assets/img/courts/other-a.svg');
            $n   = count($h['rooms']); ?>
            <div class="ht-card sr-hotel">
                <a class="ht-card-img" href="<?= site_url('hotels/' . (int) $h['id']) ?>" style="background-image:url('<?= esc($img, 'attr') ?>')" role="img" aria-label="<?= esc($h['name'], 'attr') ?>"></a>
                <span class="ht-card-body">
                    <a class="ht-card-name" href="<?= site_url('hotels/' . (int) $h['id']) ?>"><?= esc($h['name']) ?></a>
                    <?php if (! empty($h['tagline'])): ?><span class="ht-card-tag"><?= esc($h['tagline']) ?></span><?php endif; ?>
                    <?php if (! empty($h['hit_rooms'])): ?>
                        <span class="ht-chips">
                            <?php foreach (array_slice($h['hit_rooms'], 0, 4) as $r): ?>
                                <a class="ht-chip sr-room" href="<?= site_url('hotels/room/' . (int) $r['id']) ?>"><i class="bi bi-door-open"></i> <?= esc($r['name']) ?></a>
                            <?php endforeach; ?>
                        </span>
                    <?php else: ?>
                        <span class="ht-card-desc"><?= esc($h['description']) ?></span>
                    <?php endif; ?>
                    <span class="ht-card-foot">
                        <span class="ht-from"><?= $n ?> <?= $n === 1 ? 'room' : 'rooms' ?> · from <b>₱<?= number_format($h['from'], 0) ?></b> <small>per night</small></span>
                        <a class="ht-go" href="<?= site_url('hotels/' . (int) $h['id']) ?>">View rooms <i class="bi bi-arrow-right"></i></a>
                    </span>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php endif; ?>
<?= $this->endSection() ?>
