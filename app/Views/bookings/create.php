<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $selectedId   = (int) (old('facility_id') ?: $selected);
    $selectedType = '';
    foreach ($facilities as $f) {
        if ((int) $f['id'] === $selectedId) { $selectedType = $f['type']; }
    }
    $hours     = max((int) $minHours, min((int) $maxHours, (int) (old('hours') ?: $minHours)));
    $dateValue = old('start_date') ?: $startDate;
    $timeValue = old('start_time') ?: $startTime;
?>
<div class="page-head">
    <div>
        <a href="<?= site_url('facilities') ?>" class="back-link"><i class="bi bi-arrow-left"></i> Browse courts and availability</a>
        <h1>New booking</h1>
        <p>Choose the activity, the court and your time in, then add hours with the + button. The facility manager reviews every request, and you will be notified of the result.</p>
    </div>
</div>

<form method="post" action="<?= site_url('bookings') ?>">
<?= csrf_field() ?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel"><div class="panel-body">
            <div class="row">
                <div class="col-sm-6 mb-3">
                    <label class="form-label" for="sport">Activity</label>
                    <select id="sport" class="form-select" required>
                        <option value="">Choose an activity</option>
                        <?php foreach ($activities as $type => $label): ?>
                            <option value="<?= esc($type) ?>" <?= $selectedType === $type ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 mb-3">
                    <label class="form-label" for="facility">Court</label>
                    <select name="facility_id" id="facility" class="form-select" required>
                        <option value="">Choose an activity first</option>
                        <?php foreach ($facilities as $f): ?>
                            <option value="<?= (int) $f['id'] ?>"
                                    data-type="<?= esc($f['type']) ?>"
                                    data-name="<?= esc($f['name']) ?>"
                                    data-capacity="<?= (int) $f['capacity'] ?>"
                                    data-price="<?= (float) $f['price_per_hour'] ?>"
                                    <?= $selectedId === (int) $f['id'] ? 'selected' : '' ?>>
                                <?= esc($f['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-6 mb-3">
                    <label class="form-label" for="startDate">Date</label>
                    <input type="date" name="start_date" id="startDate" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= esc($dateValue) ?>" required>
                </div>
                <div class="col-sm-6 mb-3">
                    <label class="form-label" for="startTime">Time in</label>
                    <input type="time" name="start_time" id="startTime" class="form-control" value="<?= esc($timeValue) ?>" required>
                </div>
            </div>

            <div class="duration-box mb-3">
                <div class="duration-row">
                    <span class="duration-icon"><i class="bi bi-clock-history"></i></span>
                    <div class="duration-text">
                        <strong>How long?</strong>
                        <span id="durHint">Courts are rented per hour. Each + adds one hour.</span>
                    </div>
                    <div class="ext-stepper" role="group" aria-label="Number of hours">
                        <button type="button" class="ext-btn" id="hrMinus" aria-label="Remove 1 hour"><i class="bi bi-dash-lg"></i></button>
                        <output class="ext-value" id="hrValue" for="hrMinus hrPlus">1 hr</output>
                        <button type="button" class="ext-btn" id="hrPlus" aria-label="Add 1 hour"><i class="bi bi-plus-lg"></i></button>
                    </div>
                </div>
                <input type="hidden" name="hours" id="hours" value="<?= $hours ?>">
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
                <div class="row-line"><dt>Court</dt><dd id="sFacility">–</dd></div>
                <div class="row-line"><dt>Time in</dt><dd id="sStart">–</dd></div>
                <div class="row-line"><dt>Time out</dt><dd id="sEnd">–</dd></div>
                <div class="row-line"><dt>Length</dt><dd id="sDuration">–</dd></div>
                <div class="row-line"><dt>Rate</dt><dd id="sRate">–</dd></div>
            </dl>
            <div class="total"><span class="text-muted">Estimated total</span><strong id="sTotal">–</strong></div>
            <button class="btn btn-primary w-100 mt-4">Send request</button>
            <a href="#" id="availLink" class="btn btn-outline-secondary w-100 mt-2 disabled" aria-disabled="true">Check this court's schedule</a>
        </div></div>
    </div>
</div>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const MIN = <?= (int) $minHours ?>, MAX = <?= (int) $maxHours ?>;
    const $ = id => document.getElementById(id);
    const sport = $('sport'), f = $('facility'), d = $('startDate'), t = $('startTime'), g = $('guests'), hint = $('capacityHint'), avail = $('availLink'),
          hoursInput = $('hours'), minus = $('hrMinus'), plus = $('hrPlus'), hrValue = $('hrValue'), durHint = $('durHint');
    const peso = n => '₱' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const baseUrl = '<?= site_url('facilities/court') ?>';
    const fmtHrs = n => n + (n === 1 ? ' hr' : ' hrs');
    const fmtWhen = x => x.toLocaleString([], { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    let hours = Math.max(MIN, Math.min(MAX, parseInt(hoursInput.value, 10) || MIN));

    // Step 1 -> Step 2: only show the courts of the chosen activity.
    function filterCourts() {
        let any = false;
        Array.from(f.options).forEach(function (o) {
            if (!o.value) return;
            const show = o.dataset.type === sport.value;
            o.hidden = !show; o.disabled = !show;
            if (show) any = true;
        });
        const first = f.options[0];
        first.textContent = sport.value ? 'Choose a court' : 'Choose an activity first';
        f.disabled = !sport.value || !any;
        const cur = f.options[f.selectedIndex];
        if (cur && cur.value && cur.dataset.type !== sport.value) f.value = '';
        // A single court of that activity? Pick it for them.
        if (sport.value && !f.value) {
            const options = Array.from(f.options).filter(o => o.value && !o.disabled);
            if (options.length === 1) f.value = options[0].value;
        }
    }

    function update() {
        const opt = f.options[f.selectedIndex];
        const has = opt && opt.value;
        $('sFacility').textContent = has ? opt.dataset.name : '–';
        if (has) { g.max = opt.dataset.capacity; hint.textContent = 'Up to ' + opt.dataset.capacity + ' players.'; }
        else { g.removeAttribute('max'); hint.textContent = ''; }
        const price = has ? parseFloat(opt.dataset.price) : NaN;

        // Time out = time in + the hours chosen with the stepper (shown in the summary only).
        const start = (d.value && t.value) ? new Date(d.value + 'T' + t.value.substring(0, 5)) : null;
        const startOk = start && !isNaN(start.getTime());
        const end = startOk ? new Date(start.getTime() + hours * 3600000) : null;
        $('sStart').textContent = startOk ? fmtWhen(start) : '–';
        $('sEnd').textContent = end ? fmtWhen(end) : '–';
        $('sDuration').textContent = fmtHrs(hours);

        // Stepper
        hoursInput.value = hours;
        hrValue.textContent = fmtHrs(hours);
        minus.disabled = hours <= MIN;
        plus.disabled = hours >= MAX;
        durHint.textContent = hours >= MAX
            ? 'Longest booking reached (' + fmtHrs(MAX) + ').'
            : (has ? peso(price) + ' per hour. Each + adds one hour.' : 'Courts are rented per hour. Each + adds one hour.');

        $('sRate').textContent = has ? peso(price) + ' / hour' : '–';
        $('sTotal').textContent = has ? peso(price * hours) : '–';

        if (has) {
            avail.href = baseUrl + '/' + opt.value + (d.value ? '?date=' + d.value : '');
            avail.classList.remove('disabled'); avail.removeAttribute('aria-disabled');
        } else {
            avail.href = '#'; avail.classList.add('disabled'); avail.setAttribute('aria-disabled', 'true');
        }
    }

    sport.addEventListener('change', function () { filterCourts(); update(); });
    [f, d, t].forEach(el => el.addEventListener('input', update));
    minus.addEventListener('click', function () { if (hours > MIN) { hours--; update(); } });
    plus.addEventListener('click', function () { if (hours < MAX) { hours++; update(); } });
    filterCourts(); update();
})();
</script>
<?= $this->endSection() ?>
