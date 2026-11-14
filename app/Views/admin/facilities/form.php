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
<form method="post" enctype="multipart/form-data" action="<?= $editing ? site_url('admin/facilities/' . (int) $facility['id']) : site_url('admin/facilities') ?>">
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
    <div class="mb-3">
        <label class="form-label" for="image">Photo <span class="text-muted fw-normal">(optional)</span></label>
        <div class="d-flex gap-3 align-items-start flex-wrap">
            <img src="<?= facility_image($facility ?? ['type' => 'basketball', 'name' => '']) ?>" alt="" class="form-thumb" id="imgPreview">
            <div class="flex-grow-1" style="min-width: 220px">
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" class="form-control">
                <div class="form-text">JPG, PNG or WEBP, up to 3 MB. Without a photo, an illustration of the court is shown.</div>
                <?php if ($editing && ! empty($facility['image'])): ?>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                        <label class="form-check-label" for="remove_image">Remove my photo and use the illustration</label>
                    </div>
                <?php endif; ?>
            </div>
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

<?= $this->section('scripts') ?>
<script>
document.getElementById('image').addEventListener('change', function () {
    const f = this.files[0];
    if (f) document.getElementById('imgPreview').src = URL.createObjectURL(f);
});
</script>
<?= $this->endSection() ?>
