<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<nav class="ht-crumbs" aria-label="Breadcrumb"><a href="<?= site_url('admin/facilities') ?>"><i class="bi bi-arrow-left"></i> All facilities</a></nav>
<div class="page-head">
    <div>
        <h1>Add-ons</h1>
        <p>Extras players can add to a booking. Choose which facility offers which on the facility's own form.</p>
    </div>
</div>

<section class="fm-card mb-4">
    <header class="fm-head"><span class="fm-no"><i class="bi bi-plus-lg"></i></span><div><h2>New add-on</h2><p>Create an extra, then tick it on the facilities that offer it.</p></div></header>
    <form method="post" action="<?= site_url('admin/addons') ?>" class="fm-body">
        <?= csrf_field() ?>
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">Name</label><input type="text" name="label" class="form-control" maxlength="80" required value="<?= esc(old('label'), 'attr') ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label">Price (₱)</label><input type="number" name="price" min="0" step="0.01" class="form-control" required value="<?= esc(old('price'), 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label">Charged</label>
                <select name="per" class="form-select"><?php foreach ($perLabels as $k => $l): ?><option value="<?= esc($k) ?>"><?= esc(ucfirst($l)) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><label class="form-label">Type</label>
                <select name="group" class="form-select"><option value="extra">Extra</option><option value="cooling">Cooling (pick one)</option></select></div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary">Create</button></div>
            <div class="col-md-8"><input type="text" name="note" class="form-control" maxlength="160" placeholder="Short note shown to players (optional)"></div>
            <div class="col-md-4 d-flex gap-3">
                <label class="form-check"><input class="form-check-input" type="checkbox" name="evening" value="1"> <span class="form-check-label">Evening only</span></label>
                <label class="form-check"><input class="form-check-input" type="checkbox" name="indoor" value="1"> <span class="form-check-label">Indoor only</span></label>
            </div>
        </div>
    </form>
</section>

<section class="tbl-panel">
    <div class="sess-head"><div><h2>All add-ons</h2><div class="sess-sub"><?= count($addons) ?> in total</div></div></div>
    <div class="table-responsive">
    <table class="table data-table align-middle">
        <thead><tr><th>Name</th><th>Price</th><th>Charged</th><th>Rules</th><th>Used by</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($addons as $key => $a): $fid = 'f' . (int) $a['id']; ?>
            <tr class="<?= $a['status'] === 'inactive' ? 'is-muted' : '' ?>">
                <td style="min-width:220px">
                    <form id="<?= $fid ?>" method="post" action="<?= site_url('admin/addons/' . (int) $a['id']) ?>"><?= csrf_field() ?></form>
                    <input form="<?= $fid ?>" class="form-control form-control-sm mb-1" name="label" maxlength="80" value="<?= esc($a['label'], 'attr') ?>" required>
                    <input form="<?= $fid ?>" class="form-control form-control-sm" name="note" maxlength="160" value="<?= esc($a['note'], 'attr') ?>" placeholder="Note (optional)">
                </td>
                <td style="width:130px"><div class="input-group input-group-sm"><span class="input-group-text">₱</span><input form="<?= $fid ?>" type="number" name="price" min="0" step="0.01" class="form-control" value="<?= esc($a['price'], 'attr') ?>" required></div></td>
                <td style="min-width:190px"><select form="<?= $fid ?>" name="per" class="form-select form-select-sm"><?php foreach ($perLabels as $k => $l): ?><option value="<?= esc($k) ?>" <?= $a['per'] === $k ? 'selected' : '' ?>><?= esc(ucfirst($l)) ?></option><?php endforeach; ?></select></td>
                <td style="min-width:170px">
                    <select form="<?= $fid ?>" name="group" class="form-select form-select-sm mb-1"><option value="extra" <?= $a['group'] === 'extra' ? 'selected' : '' ?>>Extra</option><option value="cooling" <?= $a['group'] === 'cooling' ? 'selected' : '' ?>>Cooling (pick one)</option></select>
                    <label class="form-check d-inline-block me-2"><input form="<?= $fid ?>" class="form-check-input" type="checkbox" name="evening" value="1" <?= $a['evening'] ? 'checked' : '' ?>> <span class="form-check-label small">Evening</span></label>
                    <label class="form-check d-inline-block"><input form="<?= $fid ?>" class="form-check-input" type="checkbox" name="indoor" value="1" <?= $a['indoor'] ? 'checked' : '' ?>> <span class="form-check-label small">Indoor</span></label>
                </td>
                <td><?= (int) ($uses[(int) $a['id']] ?? 0) ?> <?= ($uses[(int) $a['id']] ?? 0) === 1 ? 'facility' : 'facilities' ?></td>
                <td><span class="pill pill-<?= $a['status'] === 'active' ? 'approved' : 'cancelled' ?>"><?= $a['status'] === 'active' ? 'On' : 'Off' ?></span></td>
                <td class="text-end text-nowrap">
                    <button form="<?= $fid ?>" class="btn btn-sm btn-primary">Save</button>
                    <form method="post" action="<?= site_url('admin/addons/' . (int) $a['id'] . '/toggle') ?>" class="d-inline"><?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-secondary"><?= $a['status'] === 'active' ? 'Turn off' : 'Turn on' ?></button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?= $this->endSection() ?>
