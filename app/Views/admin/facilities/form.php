<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $editing = ! empty($facility); ?>
<div class="page-head">
    <div>
        <h1><?= $editing ? 'Edit facility' : 'Add facility' ?></h1>
        <p><?= $editing ? 'Changes apply to new bookings right away.' : 'Players will see this on the Facilities page and the booking form.' ?></p>
    </div>
</div>

<div class="row"><div class="col-lg-8">
<div class="panel"><div class="panel-body">
<form method="post" action="<?= $editing ? site_url('admin/facilities/' . (int) $facility['id']) : site_url('admin/facilities') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="name">Name</label>
        <input type="text" id="name" name="name" class="form-control" value="<?= esc(old('name', $facility['name'] ?? '')) ?>" required>
    </div>
    <div class="row">
        <div class="col-sm-6 mb-3">
            <label class="form-label" for="type">Type</label>
            <select id="type" name="type" class="form-select" required>
                <?php foreach ($types as $key => $label): ?>
                    <option value="<?= esc($key) ?>" <?= old('type', $facility['type'] ?? 'basketball') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 mb-3">
            <label class="form-label" for="status">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="active" <?= old('status', $facility['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active (can be booked)</option>
                <option value="inactive" <?= old('status', $facility['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive (hidden from players)</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6 mb-3">
            <label class="form-label" for="capacity">Max players</label>
            <input type="number" min="1" id="capacity" name="capacity" class="form-control" value="<?= esc(old('capacity', $facility['capacity'] ?? 1)) ?>" required>
        </div>
        <div class="col-sm-6 mb-3">
            <label class="form-label" for="price">Price per hour (₱)</label>
            <input type="number" min="0" step="0.01" id="price" name="price_per_hour" class="form-control" value="<?= esc(old('price_per_hour', $facility['price_per_hour'] ?? 0)) ?>" required>
        </div>
    </div>
    <div class="mb-4">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" rows="3" class="form-control" placeholder="Indoor or outdoor, surface, lights, equipment available"><?= esc(old('description', $facility['description'] ?? '')) ?></textarea>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary">Save facility</button>
        <a href="<?= site_url('admin/facilities') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
</div></div>
</div></div>
<?= $this->endSection() ?>
