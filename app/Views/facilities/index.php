<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="page-head">
    <div>
        <h1>Facilities</h1>
        <p>Everything you can book. Check availability first, then send a request.</p>
    </div>
</div>

<?php if (empty($facilities)): ?>
    <div class="panel"><div class="empty"><i class="bi bi-geo-alt"></i><strong>No courts yet</strong>The facility manager has not added any courts.</div></div>
<?php else: ?>
<div class="row g-4">
    <?php foreach ($facilities as $f): ?>
        <div class="col-md-6 col-lg-4">
            <article class="plaque">
                <div class="plaque-top tint-<?= esc($f['type']) ?>">
                    <div class="plaque-type"><i class="bi <?= facility_icon($f['type']) ?>"></i><?= esc($types[$f['type']] ?? ucfirst($f['type'])) ?></div>
                    <h3><?= esc($f['name']) ?></h3>
                </div>
                <div class="plaque-body">
                    <p class="plaque-desc"><?= esc($f['description']) ?></p>
                    <div class="plaque-meta">
                        <span><i class="bi bi-people"></i> Up to <?= (int) $f['capacity'] ?> players</span>
                        <span class="plaque-price">₱<?= number_format((float) $f['price_per_hour'], 0) ?> <small>/ hour</small></span>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-primary btn-sm flex-fill" href="<?= site_url('calendar') ?>?facility_id=<?= (int) $f['id'] ?>">See availability</a>
                        <?php if (! is_admin()): ?>
                            <a class="btn btn-primary btn-sm flex-fill" href="<?= site_url('bookings/new') ?>?facility_id=<?= (int) $f['id'] ?>">Book</a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
