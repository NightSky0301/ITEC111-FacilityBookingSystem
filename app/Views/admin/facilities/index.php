<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="page-head">
    <div>
        <h1>Facilities</h1>
        <p>Add, edit or deactivate what players can book.</p>
    </div>
    <div class="page-actions"><a href="<?= site_url('admin/facilities/new') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add facility</a></div>
</div>

<div class="panel">
<?php if (empty($facilities)): ?>
    <div class="empty"><i class="bi bi-geo-alt"></i><strong>No facilities yet</strong>Add your first court, such as basketball, volleyball or pickleball.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table data-table table-hover">
    <thead><tr><th>Name</th><th>Type</th><th>Max players</th><th>Price per hour</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($facilities as $f): ?>
        <tr>
            <td class="cell-title"><?= esc($f['name']) ?></td>
            <td><span class="type-chip"><i class="bi <?= facility_icon($f['type']) ?>"></i><?= esc($types[$f['type']] ?? ucfirst($f['type'])) ?></span></td>
            <td><?= (int) $f['capacity'] ?></td>
            <td>₱<?= number_format((float) $f['price_per_hour'], 2) ?></td>
            <td><?= status_badge($f['status']) ?></td>
            <td class="text-end text-nowrap">
                <a href="<?= site_url('admin/facilities/' . (int) $f['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                <form method="post" action="<?= site_url('admin/facilities/' . (int) $f['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Delete this facility? Facilities with booking history are set to inactive instead.')">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
</div>
<?= $this->endSection() ?>
