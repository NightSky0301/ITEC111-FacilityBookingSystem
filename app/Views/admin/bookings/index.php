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

<div class="panel">
<?php if (empty($bookings)): ?>
    <div class="empty"><i class="bi bi-inbox"></i><strong>No bookings found</strong>Try a different filter.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table data-table table-hover">
    <thead><tr><th>Customer</th><th>Facility</th><th>When</th><th>Players</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($bookings as $b): ?>
        <tr>
            <td><div class="cell-title"><?= esc($b['customer_name']) ?></div><div class="cell-sub"><?= esc($b['customer_email']) ?></div></td>
            <td>
                <div class="cell-title"><?= esc($b['facility_name']) ?></div>
                <?php if ($b['purpose']): ?><div class="cell-sub"><?= esc($b['purpose']) ?></div><?php endif; ?>
            </td>
            <td><?= esc(fmt_range($b['start_at'], $b['end_at'])) ?></td>
            <td><?= (int) $b['guests'] ?></td>
            <td><?= status_badge($b['status']) ?><?php if ($b['admin_remarks']): ?><div class="cell-sub mt-1"><?= esc($b['admin_remarks']) ?></div><?php endif; ?></td>
            <td>
                <?php if ($b['status'] === 'pending'): ?>
                    <form method="post" class="action-stack">
                        <?= csrf_field() ?>
                        <input type="text" name="admin_remarks" class="form-control form-control-sm" placeholder="Remarks (optional)" aria-label="Remarks">
                        <div class="btn-row">
                            <button formaction="<?= site_url('admin/bookings/' . (int) $b['id'] . '/approve') ?>" class="btn btn-sm btn-success">Approve</button>
                            <button formaction="<?= site_url('admin/bookings/' . (int) $b['id'] . '/reject') ?>" class="btn btn-sm btn-outline-danger">Reject</button>
                        </div>
                    </form>
                <?php elseif ($b['status'] === 'approved'): ?>
                    <form method="post" class="action-stack" onsubmit="return confirm('Cancel this approved booking?')">
                        <?= csrf_field() ?>
                        <input type="text" name="admin_remarks" class="form-control form-control-sm" placeholder="Reason (optional)" aria-label="Reason">
                        <div class="btn-row"><button formaction="<?= site_url('admin/bookings/' . (int) $b['id'] . '/cancel') ?>" class="btn btn-sm btn-outline-danger">Cancel booking</button></div>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
</div>
<?= $this->endSection() ?>
