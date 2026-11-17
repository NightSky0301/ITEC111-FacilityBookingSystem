<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $first     = explode(' ', trim((string) session()->get('name')))[0];
    $manager   = is_manager();
    $statsUrl  = $manager ? site_url('manage/bookings') : site_url('my-bookings');
    $facUrl    = is_admin() ? site_url('admin/facilities') : site_url('facilities');
?>
<header class="dash-head">
    <div>
        <div class="dash-date"><?= esc(date('l, F j')) ?></div>
        <h1>Hello, <?= esc($first) ?></h1>
        <p><?= $manager ? 'Here is what needs your attention today.' : 'Pick a court and a time, and we will send your request.' ?></p>
    </div>
    <div class="page-actions">
        <?php if ($manager): ?>
            <a href="<?= site_url('facilities') ?>" class="btn btn-gold"><i class="bi bi-plus-lg me-1"></i> New booking</a>
            <a href="<?= site_url('manage/bookings?status=pending') ?>" class="btn btn-gold"><i class="bi bi-inbox me-1"></i> Review requests</a>
        <?php else: ?>
            <a href="<?= site_url('facilities') ?>" class="btn btn-gold"><i class="bi bi-plus-lg me-1"></i> New booking</a>
        <?php endif; ?>
    </div>
</header>

<div class="stat-grid <?= $manager ? 'is-three' : '' ?>">
    <a class="stat-card is-wait <?= (int) $pending > 0 ? 'has-items' : '' ?>" href="<?= $manager ? site_url('manage/bookings?status=pending') : site_url('my-bookings?filter=pending') ?>">
        <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
        <div class="stat-body">
            <div class="stat-num"><?= (int) $pending ?></div>
            <div class="stat-label"><?= $manager ? 'Requests waiting for review' : 'Waiting for approval' ?></div>
        </div>
        <i class="bi bi-arrow-right stat-go" aria-hidden="true"></i>
    </a>

    <a class="stat-card is-ok" href="<?= $manager ? site_url('manage/bookings?status=approved') : site_url('my-bookings?filter=approved') ?>">
        <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
        <div class="stat-body">
            <div class="stat-num"><?= (int) $approved ?></div>
            <div class="stat-label">Approved bookings</div>
        </div>
        <i class="bi bi-arrow-right stat-go" aria-hidden="true"></i>
    </a>

    <?php if ($manager): ?>
    <a class="stat-card is-teal" href="<?= $facUrl ?>">
        <span class="stat-icon"><i class="bi bi-grid-3x3-gap"></i></span>
        <div class="stat-body">
            <div class="stat-num"><?= (int) $facilities ?></div>
            <div class="stat-label">Active courts</div>
        </div>
        <i class="bi bi-arrow-right stat-go" aria-hidden="true"></i>
    </a>
    <?php endif; ?>
</div>

<?php if (! $manager && ! empty($next)):
    $ns = strtotime($next['start_at']);
    $ne = strtotime($next['end_at']);
    $now = time();
    if ($ns <= $now && $now < $ne) {
        $nextRel = 'Happening now';
    } else {
        $days = (int) (new \DateTime(date('Y-m-d', $now)))->diff(new \DateTime(date('Y-m-d', $ns)))->format('%r%a');
        $nextRel = $days === 0 ? 'Today' : ($days === 1 ? 'Tomorrow' : 'In ' . $days . ' days');
    }
?>
<section class="bk-next" aria-label="Your next session">
    <span class="bk-next-icon"><i class="bi bi-calendar-event"></i></span>
    <div class="bk-next-body">
        <div class="bk-next-kicker">Next session · <?= esc($nextRel) ?></div>
        <h2 class="bk-next-title"><?= esc($next['facility_name']) ?></h2>
        <div class="bk-next-when"><?= esc(fmt_range($next['start_at'], $next['end_at'])) ?></div>
    </div>
    <a class="btn btn-sm btn-light-on-dark" href="<?= site_url('calendar') ?>?<?= esc(http_build_query(['date' => date('Y-m-d', $ns), 'booking' => (int) $next['id']]), 'attr') ?>">Court schedule <i class="bi bi-arrow-right ms-1"></i></a>
</section>
<?php endif; ?>

<?php if ($manager): ?>
<?= $this->include('dashboard/_sessions') ?>
<?php else: ?>
<?= $this->include('dashboard/_recent') ?>
<?php endif; ?>
<?= $this->endSection() ?>
