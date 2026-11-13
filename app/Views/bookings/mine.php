<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="page-head">
    <div>
        <h1>My bookings</h1>
        <p>Your requests and their status.</p>
    </div>
</div>

<div class="panel">
<?php if (empty($bookings)): ?>
    <div class="empty"><i class="bi bi-calendar-check"></i><strong>No bookings yet</strong>Pick a facility and a time to make your first booking.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table data-table table-hover">
    <thead><tr><th>Facility</th><th>When</th><th>Players</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($bookings as $b): ?>
        <tr>
            <td>
                <div class="cell-title"><?= esc($b['facility_name']) ?></div>
                <?php if ($b['purpose']): ?><div class="cell-sub"><?= esc($b['purpose']) ?></div><?php endif; ?>
            </td>
            <td><?= esc(fmt_range($b['start_at'], $b['end_at'])) ?></td>
            <td><?= (int) $b['guests'] ?></td>
            <td>
                <?= status_badge($b['status']) ?>
                <?php if ($b['admin_remarks']): ?><div class="cell-sub mt-1"><?= esc($b['admin_remarks']) ?></div><?php endif; ?>
            </td>
            <td class="text-end">
                <?php if (in_array($b['status'], ['pending', 'approved'], true)): ?>
                    <form method="post" action="<?= site_url('bookings/' . (int) $b['id'] . '/cancel') ?>" onsubmit="return confirm('Cancel this booking?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger">Cancel</button>
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
