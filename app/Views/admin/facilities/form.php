<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$editing = ! empty($facility);
$val     = static fn (string $k, $d = '') => old($k, $facility[$k] ?? $d);
$type    = (string) $val('type', 'basketball');
$status  = (string) $val('status', 'active');
$chosen  = old('addons') !== null ? (array) old('addons') : ($editing ? $chosenAddons : ($defaults[$type] ?? []));
$newRows = is_array(old('new_addons')) ? array_values(old('new_addons')) : [];
$action  = $editing ? site_url('admin/facilities/' . (int) $facility['id']) : site_url('admin/facilities');
$img     = facility_image($facility ?? ['type' => $type, 'name' => '']);
?>
<nav class="ht-crumbs" aria-label="Breadcrumb"><a href="<?= site_url('admin/facilities') ?>"><i class="bi bi-arrow-left"></i> All facilities</a></nav>
<div class="page-head">
    <div>
        <h1><?= $editing ? 'Edit facility' : 'Add facility' ?></h1>
        <p><?= $editing ? 'Changes apply to new bookings right away.' : 'Fill in the details below. Players will see this on the Facilities page and the booking form.' ?></p>
    </div>
</div>

<form method="post" enctype="multipart/form-data" action="<?= $action ?>" class="fm" id="fmForm" novalidate>
    <?= csrf_field() ?>
    <div class="fm-main">

        <section class="fm-card">
            <header class="fm-head"><span class="fm-no">1</span><div><h2>Basic information</h2><p>What it is called and what kind of facility it is.</p></div></header>
            <div class="fm-body">
                <div class="mb-3">
                    <label class="form-label" for="name">Name</label>
                    <input type="text" id="name" name="name" class="form-control" maxlength="120" placeholder="e.g. Basketball Court (Indoor)" value="<?= esc($val('name'), 'attr') ?>" required>
                </div>
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label" for="type">Type</label>
                        <select id="type" name="type" class="form-select" required>
                            <?php foreach ($types as $key => $label): ?>
                                <option value="<?= esc($key) ?>" <?= $type === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <span class="form-label d-block">Status</span>
                        <div class="fm-seg" role="radiogroup" aria-label="Status">
                            <label><input type="radio" name="status" value="active" <?= $status !== 'inactive' ? 'checked' : '' ?>><span><i class="bi bi-check-circle"></i> Active</span></label>
                            <label><input type="radio" name="status" value="inactive" <?= $status === 'inactive' ? 'checked' : '' ?>><span><i class="bi bi-eye-slash"></i> Inactive</span></label>
                        </div>
                    </div>
                </div>
                <div class="form-text mt-2" id="statusHint"></div>
            </div>
        </section>

        <section class="fm-card">
            <header class="fm-head"><span class="fm-no">2</span><div><h2>Capacity and price</h2><p>How many people it fits and what one hour costs.</p></div></header>
            <div class="fm-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="capacity">Max people</label>
                        <div class="input-group fm-ig"><span class="input-group-text"><i class="bi bi-people"></i></span>
                            <input type="number" min="1" id="capacity" name="capacity" class="form-control" value="<?= esc($val('capacity', 1), 'attr') ?>" required></div>
                        <div class="form-text" id="capHint">Courts are booked as a whole; this is only a guide for players.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="price" id="priceLbl">Price per hour</label>
                        <div class="input-group fm-ig"><span class="input-group-text">₱</span>
                            <input type="number" min="0" step="0.01" id="price" name="price_per_hour" class="form-control" value="<?= esc($val('price_per_hour', 0), 'attr') ?>" required></div>
                        <div class="form-text" id="priceHint">Charged for every hour booked.</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="fm-card">
            <header class="fm-head"><span class="fm-no">3</span><div><h2>Opening hours</h2><p>Customers can only book inside these hours.</p></div></header>
            <div class="fm-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="open_hour">Opens</label>
                        <select id="open_hour" name="open_hour" class="form-select">
                            <option value="">Usual hours for this type</option>
                            <?php for ($h = 0; $h <= 23; $h++): ?>
                                <option value="<?= $h ?>" <?= (string) $val('open_hour') === (string) $h ? 'selected' : '' ?>><?= date('g:i A', strtotime(sprintf('%02d:00', $h))) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="close_hour">Closes</label>
                        <select id="close_hour" name="close_hour" class="form-select">
                            <option value="">Usual hours for this type</option>
                            <?php for ($h = 1; $h <= 24; $h++): ?>
                                <option value="<?= $h ?>" <?= (string) $val('close_hour') === (string) $h ? 'selected' : '' ?>><?= $h === 24 ? '12:00 AM (midnight)' : date('g:i A', strtotime(sprintf('%02d:00', $h))) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="form-text mt-2">Leave both on "Usual hours" to use the standard hours for this kind of facility.</div>
            </div>
        </section>

        <section class="fm-card">
            <header class="fm-head"><span class="fm-no">4</span><div><h2>Photo and description</h2><p>Helps players pick the right place.</p></div></header>
            <div class="fm-body">
                <div class="fm-photo">
                    <label class="fm-drop" for="image" id="dropZone">
                        <img src="<?= esc($img, 'attr') ?>" alt="" id="imgPreview">
                        <span class="fm-drop-text"><i class="bi bi-cloud-arrow-up"></i><b>Choose a photo</b><small>or drop it here · JPG, PNG or WEBP, up to 3 MB</small><em id="fileName"></em></span>
                    </label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" class="visually-hidden">
                </div>
                <div class="form-text">Without a photo, an illustration of the facility is shown.</div>
                <?php if ($editing && ! empty($facility['image'])): ?>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                        <label class="form-check-label" for="remove_image">Remove my photo and use the illustration</label>
                    </div>
                <?php endif; ?>
                <div class="mt-3">
                    <label class="form-label d-flex justify-content-between" for="description"><span>Description</span><small class="text-muted fw-normal" id="descCount">0 / 2000</small></label>
                    <textarea id="description" name="description" rows="3" maxlength="2000" class="form-control" placeholder="Indoor or outdoor, surface, lights, equipment available"><?= esc($val('description'), 'html') ?></textarea>
                </div>
            </div>
        </section>

        <section class="fm-card">
            <header class="fm-head">
                <span class="fm-no">5</span>
                <div><h2>Add-ons</h2><p>Optional extras players can add when they book this facility.</p></div>
                <div class="fm-tools">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addonsSuggested"><i class="bi bi-stars"></i> Use suggested</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addonsNone">Clear</button>
                </div>
            </header>
            <div class="fm-body">
                <?php if (empty($addons)): ?>
                    <p class="text-muted mb-3">No add-ons exist yet. Create the first one below.</p>
                <?php else: ?>
                <div class="fm-addons" id="addonGrid">
                    <?php foreach ($addons as $key => $a): ?>
                        <label class="fm-addon" data-key="<?= esc($key, 'attr') ?>">
                            <input type="checkbox" name="addons[]" value="<?= esc($key, 'attr') ?>" <?= in_array($key, $chosen, true) ? 'checked' : '' ?>>
                            <span class="fm-addon-body">
                                <span class="fm-addon-top"><b><?= esc($a['label']) ?></b><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                                <small><?= esc($a['note'] ?: ($a['group'] === 'cooling' ? 'Cooling · one per booking' : 'Extra')) ?></small>
                                <span class="fm-addon-price">₱<?= number_format($a['price']) ?> <em><?= esc($perLabels[$a['per']] ?? $a['per']) ?></em></span>
                                <span class="fm-addon-tags">
                                    <?php if ($a['group'] === 'cooling'): ?><span>Cooling</span><?php endif; ?>
                                    <?php if (! empty($a['evening'])): ?><span>Evening only</span><?php endif; ?>
                                    <?php if (! empty($a['indoor'])): ?><span>Indoor only</span><?php endif; ?>
                                    <span class="fm-sugg" hidden>Suggested</span>
                                </span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="fm-new">
                    <div class="fm-new-head">
                        <div><b>New add-on</b><small>Create one that does not exist yet. It is added to this facility and can be reused on others.</small></div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="newAddonBtn"><i class="bi bi-plus-lg"></i> Create add-on</button>
                    </div>
                    <div id="newAddons"></div>
                </div>
                <div class="form-text mt-2">Prices of existing add-ons are changed on the <a href="<?= site_url('admin/addons') ?>">Add-ons page</a>.</div>
            </div>
        </section>
    </div>

    <aside class="fm-side">
        <div class="fm-sticky">
            <div class="fm-prev" aria-label="Preview">
                <span class="fm-prev-label">Preview</span>
                <div class="fm-prev-photo"><img src="<?= esc($img, 'attr') ?>" alt="" id="pvImg"><span class="fm-prev-state" id="pvState"></span></div>
                <div class="fm-prev-body">
                    <h3 id="pvName">Facility name</h3>
                    <div class="fm-prev-type" id="pvType"></div>
                    <ul>
                        <li><i class="bi bi-cash-coin"></i><span id="pvPrice"></span></li>
                        <li><i class="bi bi-clock"></i><span id="pvHours"></span></li>
                        <li><i class="bi bi-people"></i><span id="pvCap"></span></li>
                        <li><i class="bi bi-plus-circle"></i><span id="pvAddons"></span></li>
                    </ul>
                </div>
            </div>
            <div class="fm-actions">
                <button class="btn btn-primary" id="saveBtn"><i class="bi bi-check2-circle"></i> <?= $editing ? 'Save changes' : 'Add facility' ?></button>
                <a href="<?= site_url('admin/facilities') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </aside>
</form>

<template id="newAddonTpl">
    <div class="fm-newrow">
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label">Name</label><input type="text" class="form-control" data-f="label" maxlength="80" placeholder="e.g. Scoreboard operator"></div>
            <div class="col-6 col-md-3"><label class="form-label">Price (₱)</label><input type="number" min="0" step="0.01" class="form-control" data-f="price" placeholder="0"></div>
            <div class="col-6 col-md-3"><label class="form-label">Charged</label>
                <select class="form-select" data-f="per">
                    <?php foreach ($perLabels as $k => $l): ?><option value="<?= esc($k) ?>"><?= esc(ucfirst($l)) ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-md-8"><label class="form-label">Short note <span class="text-muted fw-normal">(optional)</span></label><input type="text" class="form-control" data-f="note" maxlength="160" placeholder="Shown to players under the name"></div>
            <div class="col-md-4"><label class="form-label">Type</label>
                <select class="form-select" data-f="group"><option value="extra">Extra (any combination)</option><option value="cooling">Cooling (pick one)</option></select></div>
        </div>
        <div class="fm-newrow-foot">
            <label class="form-check"><input class="form-check-input" type="checkbox" data-f="evening"> <span class="form-check-label">Evening arrivals only (5 PM or later)</span></label>
            <label class="form-check"><input class="form-check-input" type="checkbox" data-f="indoor"> <span class="form-check-label">Indoor only</span></label>
            <button type="button" class="btn btn-sm btn-outline-danger ms-auto" data-remove><i class="bi bi-x-lg"></i> Remove</button>
        </div>
    </div>
</template>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const $ = id => document.getElementById(id);
    const TYPES = <?= json_encode($types, JSON_HEX_TAG | JSON_HEX_AMP) ?>, DEFAULTS = <?= json_encode($defaults) ?>;
    const EDITING = <?= $editing ? 'true' : 'false' ?>, PER_HEAD = ['gym'];
    const form = $('fmForm'), typeEl = $('type'), nameEl = $('name'), priceEl = $('price'), capEl = $('capacity');
    const openEl = $('open_hour'), closeEl = $('close_hour'), grid = $('addonGrid');
    let touched = EDITING || <?= old('addons') !== null ? 'true' : 'false' ?>;   // once the admin changes add-ons by hand, stop auto-suggesting
    const boxes = () => grid ? Array.from(grid.querySelectorAll('input[type=checkbox]')) : [];
    const hr = h => { h = +h; const t = (h % 24 + 24) % 24, ap = t >= 12 ? 'PM' : 'AM'; return (h === 24 ? 12 : (t % 12 || 12)) + ':00 ' + ap; };
    const peso = n => '₱' + (+n || 0).toLocaleString('en-US', { maximumFractionDigits: 2 });

    function suggested() { return DEFAULTS[typeEl.value] || []; }
    function paintSuggested() {
        const s = suggested();
        grid && grid.querySelectorAll('.fm-addon').forEach(l => { l.querySelector('.fm-sugg').hidden = s.indexOf(l.dataset.key) < 0; });
    }
    function applySuggested() { const s = suggested(); boxes().forEach(b => { b.checked = s.indexOf(b.value) >= 0; }); }

    function preview() {
        const head = PER_HEAD.indexOf(typeEl.value) >= 0;
        $('pvName').textContent = nameEl.value.trim() || 'Facility name';
        $('pvType').textContent = TYPES[typeEl.value] || '';
        $('pvPrice').textContent = peso(priceEl.value) + (head ? ' per person, per hour' : ' per hour');
        const o = openEl.value, c = closeEl.value;
        $('pvHours').textContent = o !== '' && c !== '' && +c > +o ? hr(o) + ' – ' + hr(c) : 'Usual hours for this type';
        $('pvCap').textContent = (+capEl.value || 1) + (+capEl.value === 1 ? ' person' : ' people') + (head ? ' at a time' : ' fit comfortably');
        const n = boxes().filter(b => b.checked).length + form.querySelectorAll('.fm-newrow').length;
        $('pvAddons').textContent = n ? n + (n === 1 ? ' add-on offered' : ' add-ons offered') : 'No add-ons';
        const off = form.querySelector('input[name=status]:checked').value === 'inactive';
        $('pvState').textContent = off ? 'Hidden from players' : 'Open for booking';
        $('pvState').className = 'fm-prev-state ' + (off ? 'is-off' : 'is-on');
        $('statusHint').textContent = off ? 'Players will not see this facility and cannot book it.' : 'Players can see and book this facility.';
        $('priceLbl').textContent = head ? 'Price per person, per hour' : 'Price per hour';
        $('priceHint').textContent = head ? 'Each person pays this for every hour.' : 'Charged for every hour booked.';
        $('capHint').textContent = head ? 'The most people allowed in at the same time.' : 'Courts are booked as a whole; this is only a guide for players.';
    }

    typeEl.addEventListener('change', () => { paintSuggested(); if (!touched) applySuggested(); preview(); });
    grid && grid.addEventListener('change', () => { touched = true; preview(); });
    $('addonsSuggested').addEventListener('click', () => { applySuggested(); touched = true; preview(); });
    $('addonsNone').addEventListener('click', () => { boxes().forEach(b => { b.checked = false; }); touched = true; preview(); });
    form.addEventListener('input', preview); form.addEventListener('change', preview);

    // Photo: pick or drop, with a live preview.
    const file = $('image'), drop = $('dropZone');
    function showFile() {
        const f = file.files[0]; if (!f) return;
        const url = URL.createObjectURL(f); $('imgPreview').src = url; $('pvImg').src = url;
        $('fileName').textContent = f.name + ' · ' + (f.size / 1048576).toFixed(1) + ' MB';
    }
    file.addEventListener('change', showFile);
    ['dragenter', 'dragover'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('is-over'); }));
    ['dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('is-over'); }));
    drop.addEventListener('drop', e => { if (e.dataTransfer.files.length) { file.files = e.dataTransfer.files; showFile(); } });
    const rm = $('remove_image'); rm && rm.addEventListener('change', () => { if (rm.checked) { file.value = ''; $('fileName').textContent = ''; } });
    const desc = $('description'), count = () => { $('descCount').textContent = desc.value.length + ' / 2000'; };
    desc.addEventListener('input', count); count();

    // New add-ons typed into the form.
    const tpl = $('newAddonTpl'), host = $('newAddons');
    let n = 0;
    function addRow(v) {
        const node = tpl.content.firstElementChild.cloneNode(true), i = n++;
        node.querySelectorAll('[data-f]').forEach(el => {
            el.name = 'new_addons[' + i + '][' + el.dataset.f + ']';
            if (v && v[el.dataset.f] != null) { if (el.type === 'checkbox') el.checked = !!v[el.dataset.f]; else el.value = v[el.dataset.f]; }
        });
        node.querySelector('[data-remove]').addEventListener('click', () => { node.remove(); preview(); });
        host.appendChild(node); preview();
        if (!v) node.querySelector('[data-f=label]').focus();
    }
    $('newAddonBtn').addEventListener('click', () => addRow());
    <?php foreach ($newRows as $r): ?>addRow(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_AMP) ?>);
    <?php endforeach; ?>

    form.addEventListener('submit', function (e) {
        if (!nameEl.value.trim()) { e.preventDefault(); nameEl.focus(); nameEl.classList.add('is-invalid'); return; }
        $('saveBtn').disabled = true;
    });
    nameEl.addEventListener('input', () => nameEl.classList.remove('is-invalid'));

    paintSuggested(); preview();
})();
</script>
<?= $this->endSection() ?>
