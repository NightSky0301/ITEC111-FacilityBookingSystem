<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="page-head">
    <div>
        <h1>New booking</h1>
        <p>Choose a facility and a time. The facility manager reviews every request, and you will be notified of the result.</p>
    </div>
</div>

<form method="post" action="<?= site_url('bookings') ?>">
<?= csrf_field() ?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel"><div class="panel-body">
            <div class="mb-3">
                <label class="form-label" for="facility">Facility</label>
                <select name="facility_id" id="facility" class="form-select" required>
                    <option value="">Choose a facility</option>
                    <?php foreach ($facilities as $f): ?>
                        <option value="<?= (int) $f['id'] ?>"
                                data-name="<?= esc($f['name']) ?>"
                                data-capacity="<?= (int) $f['capacity'] ?>"
                                data-price="<?= (float) $f['price_per_hour'] ?>"
                                <?= (int) (old('facility_id') ?: $selected) === (int) $f['id'] ? 'selected' : '' ?>>
                            <?= esc($f['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-sm-6 mb-3">
                    <label class="form-label" for="start">Start</label>
                    <input type="datetime-local" name="start_at" id="start" class="form-control" value="<?= esc(old('start_at') ?: $start) ?>" required>
                </div>
                <div class="col-sm-6 mb-3">
                    <label class="form-label" for="end">End</label>
                    <input type="datetime-local" name="end_at" id="end" class="form-control" value="<?= esc(old('end_at')) ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="guests">Number of players</label>
                <input type="number" min="1" name="guests" id="guests" class="form-control" value="<?= esc(old('guests') ?: 1) ?>" required>
                <div class="form-text" id="capacityHint"></div>
            </div>
            <div class="mb-0">
                <label class="form-label" for="purpose">Notes <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" name="purpose" id="purpose" maxlength="255" class="form-control" value="<?= esc(old('purpose')) ?>" placeholder="Team name, event, special requests">
            </div>
        </div></div>
    </div>

    <div class="col-lg-5">
        <div class="panel summary"><div class="panel-body">
            <h2 class="h5 mb-3">Summary</h2>
            <dl>
                <div class="row-line"><dt>Facility</dt><dd id="sFacility">–</dd></div>
                <div class="row-line"><dt>Duration</dt><dd id="sDuration">–</dd></div>
                <div class="row-line"><dt>Rate</dt><dd id="sRate">–</dd></div>
            </dl>
            <div class="total"><span class="text-muted">Estimated total</span><strong id="sTotal">–</strong></div>
            <button class="btn btn-primary w-100 mt-4">Send request</button>
            <a href="<?= site_url('calendar') ?>" class="btn btn-outline-secondary w-100 mt-2">Check the calendar first</a>
        </div></div>
    </div>
</div>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const f = document.getElementById('facility'), s = document.getElementById('start'),
          e = document.getElementById('end'), g = document.getElementById('guests'),
          hint = document.getElementById('capacityHint');
    const peso = n => '₱' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function update() {
        const opt = f.options[f.selectedIndex];
        const has = opt && opt.value;
        document.getElementById('sFacility').textContent = has ? opt.dataset.name : '–';
        if (has) { g.max = opt.dataset.capacity; hint.textContent = 'Up to ' + opt.dataset.capacity + ' players.'; }
        else { g.removeAttribute('max'); hint.textContent = ''; }

        const price = has ? parseFloat(opt.dataset.price) : NaN;
        document.getElementById('sRate').textContent = has ? peso(price) + ' / hour' : '–';

        const hours = (new Date(e.value) - new Date(s.value)) / 36e5;
        const ok = has && isFinite(hours) && hours > 0;
        document.getElementById('sDuration').textContent = ok ? (+hours.toFixed(2)) + (hours === 1 ? ' hour' : ' hours') : '–';
        document.getElementById('sTotal').textContent = ok ? peso(hours * price) : '–';
    }
    [f, s, e].forEach(el => el.addEventListener('input', update));
    update();
})();
</script>
<?= $this->endSection() ?>
