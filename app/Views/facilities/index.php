<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= $this->setVar('step', 1)->include('facilities/_stepper') ?>

<div class="page-head">
    <div>
        <h1>What would you like to play?</h1>
        <p>Pick an activity first. Then you can choose a court and see when it is free.</p>
    </div>
</div>

<?php if (empty($cards)): ?>
    <div class="panel"><div class="empty"><i class="bi bi-geo-alt"></i><strong>No courts yet</strong>The facility manager has not added any courts.</div></div>
<?php else: ?>
<div class="row g-4">
    <?php foreach ($cards as $c): ?>
        <div class="col-md-6 col-lg-4">
            <a class="plaque plaque-link" href="<?= site_url('facilities/' . esc($c['type'], 'url')) ?>">
                <img class="plaque-img" src="<?= facility_image($c['first']) ?>" alt="" loading="lazy">
                <div class="plaque-body">
                    <h3><?= esc($c['name']) ?></h3>
                    <p class="plaque-desc"><?= esc($c['tagline']) ?></p>
                    <div class="plaque-meta">
                        <span><i class="bi <?= facility_icon($c['type']) ?>"></i> <?= (int) $c['count'] ?> <?= $c['count'] === 1 ? 'court' : 'courts' ?></span>
                        <span class="plaque-price">from ₱<?= number_format((float) $c['min_price'], 0) ?> <small>/ hour</small></span>
                    </div>
                    <span class="btn btn-primary btn-sm w-100">Choose <?= esc(strtolower($c['name'])) ?></span>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
