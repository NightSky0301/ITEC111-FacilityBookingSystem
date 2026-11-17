<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="page-head">
    <div>
        <h1>Bookings</h1>
        <p>Approve or reject requests. Approved bookings block the time slot.</p>
    </div>
</div>

<form method="get" class="filter-bar">
    <select name="status" class="form-select" aria-label="Filter by status" onchange="this.form.submit()">
        <option value="">All statuses</option>
        <?php foreach (['pending', 'approved', 'rejected', 'cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="facility_id" class="form-select" aria-label="Filter by facility" onchange="this.form.submit()">
        <option value="">All facilities</option>
        <?php foreach ($facilities as $f): ?>
            <option value="<?= (int) $f['id'] ?>" <?= (int) $facilityId === (int) $f['id'] ? 'selected' : '' ?>><?= esc($f['name']) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<section class="tbl-panel">
<div class="sess-head"><div><h2>All bookings</h2><div class="sess-sub"><?= count($bookings) ?> <?= count($bookings) === 1 ? 'booking' : 'bookings' ?><?= $status ? ' · ' . ucfirst($status) : '' ?></div></div></div>
<?php if (empty($bookings)): ?>
    <div class="empty"><i class="bi bi-inbox"></i><strong>No bookings found</strong>Try a different filter.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table data-table table-hover tbl-brass">
    <thead><tr><th>Customer</th><th>Facility</th><th>When</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($bookings as $b): ?>
        <tr>
            <td><div class="cell-title"><?= esc($b['customer_name']) ?></div><div class="cell-sub"><?= esc($b['customer_email']) ?></div></td>
            <td>
                <div class="cell-title"><?= esc($b['facility_name']) ?></div>
                <?php if (\App\Models\FacilityModel::isPerHead((string) ($b['facility_type'] ?? ''))): ?><div class="cell-sub"><?= (int) $b['guests'] ?> <?= (int) $b['guests'] === 1 ? 'person' : 'people' ?></div><?php endif; ?>
                <?php if ($b['purpose']): ?><div class="cell-sub"><?= esc($b['purpose']) ?></div><?php endif; ?>
                <?php if (! empty($b['addons'])): ?><div class="cell-sub">Add-ons: <?= esc(implode(', ', array_column(json_decode($b['addons'], true) ?: [], 'label'))) ?> (₱<?= number_format((float) $b['addons_total'], 2) ?>)</div><?php endif; ?>
            </td>
            <td><?= esc(fmt_range($b['start_at'], $b['end_at'])) ?></td>
            <td>
                <?php if ($b['status'] === 'approved'): ?>
                    <span class="pill pill-approved"><?= esc(approval_label($b)) ?></span>
                    <?php if (! empty($b['arrived_at'])): ?><div class="cell-sub mt-1"><i class="bi bi-person-check"></i> Arrived <?= esc(date('g:i A', strtotime($b['arrived_at']))) ?></div><?php endif; ?>
                <?php else: ?><?= status_badge($b['status']) ?><?php endif; ?>
                <?php if ($b['admin_remarks']): ?><div class="cell-sub mt-1"><?= esc($b['admin_remarks']) ?></div><?php endif; ?></td>
            <td>
                <?php if ($b['status'] === 'pending'): ?>
                    <form method="post" class="action-stack">
                        <?= csrf_field() ?>
                        <input type="text" name="admin_remarks" class="form-control form-control-sm" placeholder="Remarks (optional)" aria-label="Remarks">
                        <div class="btn-row">
                            <button formaction="<?= site_url('manage/bookings/' . (int) $b['id'] . '/approve') ?>" class="btn btn-sm btn-gold">Approve</button>
                            <button formaction="<?= site_url('manage/bookings/' . (int) $b['id'] . '/reject') ?>" class="btn btn-sm btn-outline-danger">Reject</button>
                        </div>
                    </form>
                <?php elseif ($b['status'] === 'approved'): ?>
                    <form method="post" class="action-stack" onsubmit="return confirm('Cancel this approved booking?')">
                        <?= csrf_field() ?>
                        <input type="text" name="admin_remarks" class="form-control form-control-sm" placeholder="Reason (optional)" aria-label="Reason">
                        <div class="btn-row"><button formaction="<?= site_url('manage/bookings/' . (int) $b['id'] . '/cancel') ?>" class="btn btn-sm btn-outline-danger">Cancel booking</button></div>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
</section>
<?= $this->endSection() ?>
