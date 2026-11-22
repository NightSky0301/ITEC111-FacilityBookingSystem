<?php /** Cards for "Top bookings". Used by the page and by the live refresh. */ ?>
<?php if (empty($top)): ?>
    <div class="fn-empty">No bookings have been made this month yet. The most booked courts will show up here.</div>
<?php else: ?>
    <?php foreach ($top as $i => $c): ?>
        <a class="fn-card" href="<?= site_url('facilities/court/' . (int) $c['id']) ?>?date=<?= esc(date('Y-m-d'), 'url') ?>">
            <span class="fn-img" style="background-image:url('<?= esc($c['img'], 'attr') ?>')">
                <span class="tp-rank">#<?= $i + 1 ?></span>
            </span>
            <span class="fn-body">
                <span class="fn-name"><?= esc($c['name']) ?></span>
                <span class="fn-meta"><?= esc($c['activity']) ?></span>
                <span class="tp-count"><i class="bi bi-fire"></i> <?= (int) $c['count'] ?> <?= (int) $c['count'] === 1 ? 'booking' : 'bookings' ?> this month</span>
                <span class="fn-price">₱<?= number_format($c['price'], 0) ?><small><?= $c['per_head'] ? ' / person / hr' : ' / hr' ?></small></span>
            </span>
        </a>
    <?php endforeach; ?>
<?php endif; ?>
