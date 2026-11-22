<?php /** Cards for "Free right now". Used by the page and by the live refresh. */ ?>
<?php if (empty($freeNow)): ?>
    <div class="fn-empty">Every court is in use at the moment. Search a later date above to find an open time.</div>
<?php else: ?>
    <?php foreach ($freeNow as $c): ?>
        <a class="fn-card" href="<?= site_url('facilities/court/' . (int) $c['id']) ?>?date=<?= esc(date('Y-m-d'), 'url') ?>">
            <span class="fn-img" style="background-image:url('<?= esc($c['img'], 'attr') ?>')"></span>
            <span class="fn-body">
                <span class="fn-name"><?= esc($c['name']) ?></span>
                <span class="fn-meta"><?= esc($c['activity']) ?></span>
                <span class="fn-price">₱<?= number_format($c['price'], 0) ?><small><?= $c['per_head'] ? ' / person / hr' : ' / hr' ?></small></span>
            </span>
        </a>
    <?php endforeach; ?>
<?php endif; ?>
