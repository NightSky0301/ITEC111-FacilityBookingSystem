<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= $this->setVar('step', 2)->setVar('backUrl', site_url('facilities'))->setVar('backLabel', 'All activities')->include('facilities/_stepper') ?>

<header class="activity-hero tint-<?= esc($type, 'attr') ?>">
    <span class="activity-badge"><?= facility_icon_html($type) ?></span>
    <h1><?= esc($activity) ?></h1>
    <p>Choose a court to see its schedule.</p>
</header>

<div class="row g-4">
    <?php foreach ($courts as $c): $live = $c['live']; ?>
        <div class="col-md-6">
            <article class="plaque">
                <a href="<?= site_url('facilities/court/' . (int) $c['id']) ?>" tabindex="-1" aria-hidden="true"><img class="plaque-img" src="<?= facility_image($c) ?>" alt="" loading="lazy"></a>
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
                        <span><i class="bi bi-people"></i> Up to <?= (int) $c['capacity'] ?> players</span>
                        <span class="plaque-price">₱<?= number_format((float) $c['price_per_hour'], 0) ?> <small>/ hour</small></span>
                    </div>
                    <a class="btn btn-primary btn-sm w-100" href="<?= site_url('facilities/court/' . (int) $c['id']) ?>">See availability</a>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</div>
<?= $this->endSection() ?>
