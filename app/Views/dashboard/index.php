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

<section class="panel panel-table">
    <div class="panel-head">
        <div>
            <h2><?= $manager ? 'Waiting for review' : 'Recent bookings' ?></h2>
            <div class="panel-sub"><?= $manager ? 'The latest requests that need an answer' : 'Your most recent requests' ?></div>
        </div>
        <a href="<?= $manager ? site_url('manage/bookings') : site_url('my-bookings') ?>" class="btn btn-sm btn-outline-secondary">View all <i class="bi bi-arrow-right ms-1"></i></a>
    </div>

    <?php if (empty($latest)): ?>
        <div class="empty">
            <i class="bi bi-calendar-check"></i>
            <strong><?= $manager ? 'No requests to review' : 'No bookings yet' ?></strong>
            <?= $manager ? 'New requests will show up here.' : 'Pick an activity and a time to make your first booking.' ?>
            <?php if (! $manager): ?>
                <div class="mt-3"><a href="<?= site_url('facilities') ?>" class="btn btn-primary btn-sm">Browse courts</a></div>
            <?php endif; ?>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table data-table table-stack">
            <thead><tr>
                <th>Court</th>
                <?php if ($manager): ?><th>Customer</th><?php endif; ?>
                <th>When</th>
                <th>Status</th>
                <?php if ($manager): ?><th class="text-end"></th><?php endif; ?>
            </tr></thead>
            <tbody>
            <?php foreach ($latest as $b):
                $s = strtotime($b['start_at']);
                $e = strtotime($b['end_at']);
                $sameDay = date('Y-m-d', $s) === date('Y-m-d', $e);
                $img = facility_image(['type' => $b['facility_type'] ?? 'other', 'name' => $b['facility_name'] ?? '', 'image' => $b['facility_image'] ?? null]);
            ?>
                <tr>
                    <td class="c-court">
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= $img ?>" alt="" class="table-thumb">
                            <div>
                                <div class="cell-title"><?= esc($b['facility_name']) ?></div>
                                <div class="cell-sub"><?= esc(\App\Models\FacilityModel::activityName((string) ($b['facility_type'] ?? ''))) ?></div>
                            </div>
                        </div>
                    </td>
                    <?php if ($manager): ?>
                    <td class="c-cust">
                        <div class="d-flex align-items-center gap-2">
                            <span class="mini-avatar"><?= esc(strtoupper(mb_substr((string) $b['customer_name'], 0, 1))) ?></span>
                            <span><?= esc($b['customer_name']) ?></span>
                        </div>
                    </td>
                    <?php endif; ?>
                    <td class="c-when">
                        <div class="d-flex align-items-center gap-3">
                            <div class="date-chip"><span class="d"><?= date('j', $s) ?></span><span class="m"><?= date('M', $s) ?></span></div>
                            <div>
                                <?php if ($sameDay): ?>
                                    <div class="cell-title"><?= date('g:i A', $s) ?> – <?= date('g:i A', $e) ?></div>
                                    <div class="cell-sub"><?= date('l', $s) ?></div>
                                <?php else: ?>
                                    <div class="cell-title"><?= esc(fmt_range($b['start_at'], $b['end_at'])) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="c-status"><?= status_badge($b['status']) ?></td>
                    <?php if ($manager): ?>
                    <td class="c-act text-end"><a href="<?= site_url('manage/bookings?status=pending') ?>" class="btn btn-sm btn-outline-primary">Review</a></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
