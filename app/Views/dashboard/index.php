<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $first = explode(' ', trim((string) session()->get('name')))[0]; ?>
<div class="page-head">
    <div>
        <h1>Hello, <?= esc($first) ?></h1>
        <p><?= is_admin() ? 'Here is what needs your attention today.' : 'Check the calendar for open times, then send a request.' ?></p>
    </div>
    <div class="page-actions">
        <?php if (is_admin()): ?>
            <a href="<?= site_url('admin/bookings?status=pending') ?>" class="btn btn-primary">Review requests</a>
        <?php else: ?>
            <a href="<?= site_url('calendar') ?>" class="btn btn-outline-secondary">Open calendar</a>
        <?php endif; ?>
    </div>
</div>

<div class="ledger">
    <div>
        <div class="num <?= (int) $pending > 0 ? 'is-attn' : '' ?>"><?= (int) $pending ?></div>
        <div class="label"><?= is_admin() ? 'Requests waiting for review' : 'Waiting for approval' ?></div>
    </div>
    <div>
        <div class="num"><?= (int) $approved ?></div>
        <div class="label">Approved bookings</div>
    </div>
    <?php if (is_admin()): ?>
    <div>
        <div class="num"><?= (int) $facilities ?></div>
        <div class="label">Active facilities</div>
    </div>
    <?php endif; ?>
</div>

<section class="panel">
    <div class="panel-head">
        <h2><?= is_admin() ? 'Waiting for review' : 'Recent bookings' ?></h2>
        <a href="<?= site_url(is_admin() ? 'admin/bookings' : 'my-bookings') ?>" class="btn btn-sm btn-outline-secondary">View all</a>
    </div>
    <?php if (empty($latest)): ?>
        <div class="empty">
            <i class="bi bi-calendar-check"></i>
            <strong><?= is_admin() ? 'No requests to review' : 'No bookings yet' ?></strong>
            <?= is_admin() ? 'New requests will show up here.' : 'Pick a facility and a time to make your first booking.' ?>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table data-table table-hover">
            <thead><tr>
                <th>Facility</th><?php if (is_admin()): ?><th>Customer</th><?php endif; ?><th>When</th><th>Status</th>
            </tr></thead>
            <tbody>
            <?php foreach ($latest as $b): ?>
                <tr>
                    <td class="cell-title"><?= esc($b['facility_name']) ?></td>
                    <?php if (is_admin()): ?><td><?= esc($b['customer_name']) ?></td><?php endif; ?>
                    <td><?= esc(fmt_range($b['start_at'], $b['end_at'])) ?></td>
                    <td><?= status_badge($b['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
