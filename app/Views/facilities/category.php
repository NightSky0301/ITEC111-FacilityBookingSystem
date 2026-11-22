<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= $this->setVar('step', 2)->include('facilities/_stepper') ?>

<div class="row g-4 justify-content-center">
    <?php foreach ($courts as $c): $live = $c['live']; ?>
        <div class="col-md-6">
            <a class="plaque plaque-link" href="<?= site_url('facilities/court/' . (int) $c['id']) ?>">
                <img class="plaque-img" src="<?= facility_image($c) ?>" alt="" loading="lazy">
                <div class="plaque-body">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <h3 class="mb-0"><?= esc($c['name']) ?></h3>
                        <?php if ($live['in_use']): ?>
                            <span class="live-chip is-busy">In use until <?= esc(fmt_time($live['until'])) ?></span>
                        <?php else: ?>
                            <span class="live-chip is-free">Available now</span>
                        <?php endif; ?>
                    </div>
                    <p class="plaque-desc mt-2"><?= esc($c['description']) ?></p>
                    <?php if (! $live['in_use'] && $live['next'] && date('Y-m-d', strtotime($live['next'])) === date('Y-m-d')): ?>
                        <p class="small text-muted mb-2">Next session starts at <?= esc(fmt_time($live['next'])) ?>.</p>
                    <?php endif; ?>
                    <div class="plaque-meta">
                        <span><i class="bi bi-people"></i> <?= esc(\App\Models\FacilityModel::peopleLabel($c)) ?></span>
                        <span class="plaque-price">₱<?= number_format((float) $c['price_per_hour'], 0) ?> <small><?= \App\Models\FacilityModel::isPerHead($c) ? '/ person / hour' : '/ hour' ?></small></span>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
<?= $this->endSection() ?>
