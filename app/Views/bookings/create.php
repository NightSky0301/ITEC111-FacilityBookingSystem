<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $selectedId   = (int) (old('facility_id') ?: $selected);
    $selectedType = '';
    foreach ($facilities as $f) {
        if ((int) $f['id'] === $selectedId) { $selectedType = $f['type']; }
    }
    $hours     = max((int) $minHours, min((int) $maxHours, (int) (old('hours') ?: $minHours)));
    $dateValue = old('start_date') ?: $startDate ?: date('Y-m-d');
    $timeValue = old('start_time') ?: $startTime;

    // Quick-pick dates: today, tomorrow and the next few days.
    $quick = [];
    for ($i = 0; $i < 6; $i++) {
        $ts      = strtotime("+$i day");
        $quick[] = ['value' => date('Y-m-d', $ts), 'top' => $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : date('D', $ts)), 'num' => date('j', $ts), 'mon' => date('M', $ts)];
    }
?>
<div class="page-head">
    <div>
        <a href="<?= site_url('facilities') ?>" class="back-link"><i class="bi bi-arrow-left"></i> Browse courts and availability</a>
        <h1>New booking</h1>
        <p>Pick a court, a day and a time. The facility manager reviews every request, and you will be notified of the result.</p>
    </div>
</div>

<form method="post" action="<?= site_url('bookings') ?>" id="bookForm" novalidate>
<?= csrf_field() ?>
<input type="hidden" name="start_time" id="startTime" value="<?= esc($timeValue) ?>">
<input type="hidden" name="hours" id="hours" value="<?= $hours ?>">

<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel bf"><div class="panel-body">

            <section class="bf-sec">
                <h2 class="bf-h">Activity</h2>
                <div class="bf-acts" id="acts" role="radiogroup" aria-label="Activity">
                    <?php foreach ($activities as $type => $label): ?>
                        <button type="button" class="bf-act" role="radio" data-type="<?= esc($type) ?>" aria-checked="<?= $selectedType === $type ? 'true' : 'false' ?>">
                            <span class="bf-act-ic"><?= facility_icon_html($type) ?></span>
                            <span><?= esc($label) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="bf-sec">
                <h2 class="bf-h">Court</h2>
                <p class="bf-empty" id="courtEmpty">Choose an activity to see its courts.</p>
                <div class="bf-courts" id="courts">
                    <?php foreach ($courts as $c): ?>
                        <label class="bf-court" data-type="<?= esc($c['type']) ?>" hidden>
                            <input type="radio" name="facility_id" value="<?= $c['id'] ?>" <?= $selectedId === $c['id'] ? 'checked' : '' ?>>
                            <img src="<?= esc($c['img'], 'attr') ?>" alt="" loading="lazy">
                            <span class="bf-court-body">
                                <strong><?= esc($c['name']) ?></strong>
                                <span><i class="bi bi-people"></i> <?= esc($c['people']) ?></span>
                                <span class="bf-price">₱<?= number_format($c['price'], 0) ?> <small>/ hour</small></span>
                            </span>
                            <i class="bi bi-check-circle-fill bf-tick"></i>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="bf-sec">
                <h2 class="bf-h">Date</h2>
                <div class="bf-dates" id="dates">
                    <?php foreach ($quick as $q): ?>
                        <button type="button" class="bf-date" data-date="<?= $q['value'] ?>">
                            <small><?= esc($q['top']) ?></small><b><?= esc($q['num']) ?></b><small><?= esc($q['mon']) ?></small>
                        </button>
                    <?php endforeach; ?>
                    <label class="bf-date bf-date-pick" title="Pick another date">
                        <i class="bi bi-calendar3"></i><small>Other</small>
                        <input type="date" name="start_date" id="startDate" min="<?= date('Y-m-d') ?>" value="<?= esc($dateValue) ?>" required aria-label="Date">
                    </label>
                </div>
            </section>

            <section class="bf-sec">
                <div class="bf-h-row">
                    <h2 class="bf-h">Time in</h2>
                    <span class="bf-open">Open <?= esc(date('g A', strtotime(sprintf('%02d:00', $open)))) ?> – <?= esc(date('g A', strtotime(sprintf('%02d:00', $close)))) ?></span>
                </div>
                <div id="slots" class="bf-slots" aria-live="polite"></div>
                <div class="bf-legend">
                    <span><i class="lg lg-free"></i>Available</span>
                    <span><i class="lg lg-sel"></i>Your pick</span>
                    <span><i class="lg lg-busy"></i>Booked</span>
                    <span><i class="lg lg-off"></i>Passed or won't fit</span>
                </div>
            </section>

            <section class="bf-sec">
                <div class="duration-box">
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
                </div>
            </section>

            <section class="bf-sec">
                <div class="bf-people" id="peopleBox">
                    <span class="duration-icon"><i class="bi bi-people"></i></span>
                    <div>
                        <strong>Suggested group size</strong>
                        <span id="peopleText">Choose a court to see how many people it fits.</span>
                    </div>
                </div>
            </section>

            <section class="bf-sec mb-0">
                <label class="bf-h d-block" for="purpose">Notes <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" name="purpose" id="purpose" maxlength="255" class="form-control" value="<?= esc(old('purpose')) ?>" placeholder="Team name, event, special requests">
            </section>
        </div></div>
    </div>

    <div class="col-lg-5">
        <div class="panel summary"><div class="panel-body">
            <h2 class="h5 mb-3">Summary</h2>
            <dl>
                <div class="row-line"><dt>Court</dt><dd id="sFacility">–</dd></div>
                <div class="row-line"><dt>Date</dt><dd id="sDate">–</dd></div>
                <div class="row-line"><dt>Time in</dt><dd id="sStart">–</dd></div>
                <div class="row-line"><dt>Time out</dt><dd id="sEnd">–</dd></div>
                <div class="row-line"><dt>Length</dt><dd id="sDuration">–</dd></div>
                <div class="row-line"><dt>Rate</dt><dd id="sRate">–</dd></div>
            </dl>
            <div class="total"><span class="text-muted">Estimated total</span><strong id="sTotal">–</strong></div>
            <button class="btn btn-primary w-100 mt-4" id="submitBtn" disabled>Send request</button>
            <p class="small text-muted text-center mt-2 mb-0" id="submitHint">Choose a court, date and time to continue.</p>
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
    const COURTS = <?= json_encode($courts, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const $ = id => document.getElementById(id);
    const dateIn = $('startDate'), timeIn = $('startTime'), hoursIn = $('hours'), slotsEl = $('slots');
    const radios = Array.from(document.querySelectorAll('input[name=facility_id]'));
    const acts = Array.from(document.querySelectorAll('.bf-act')), cards = Array.from(document.querySelectorAll('.bf-court'));
    const quick = Array.from(document.querySelectorAll('.bf-date[data-date]'));
    const baseUrl = '<?= site_url('facilities/court') ?>', busyUrl = '<?= site_url('bookings/busy') ?>';
    const peso = n => '₱' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtHrs = n => n + (n === 1 ? ' hr' : ' hrs');
    const clock = m => { const h = Math.floor(m / 60) % 24, mm = m % 60; return ((h + 11) % 12 + 1) + ':' + String(mm).padStart(2, '0') + ' ' + (h < 12 ? 'AM' : 'PM'); };
    const toMin = s => { const p = (s || '').split(':'); return p.length >= 2 ? (+p[0]) * 60 + (+p[1]) : null; };
    const pad = n => String(n).padStart(2, '0');

    let hours = Math.max(MIN, Math.min(MAX, parseInt(hoursIn.value, 10) || MIN));
    let sport = '<?= esc($selectedType, 'js') ?>';
    let info = null;            // answer of bookings/busy for the current court + date
    let infoAt = 0;             // performance.now() when it arrived, to keep the server clock ticking
    let picked = toMin(timeIn.value);
    let note = '';

    const court = () => { const r = radios.find(x => x.checked); return r ? COURTS.find(c => c.id === +r.value) : null; };
    const nowMin = () => info ? info.now + Math.floor((performance.now() - infoAt) / 60000) : null;

    function setSport(type) {
        sport = type;
        acts.forEach(a => a.setAttribute('aria-checked', a.dataset.type === type ? 'true' : 'false'));
        let any = false;
        cards.forEach(c => { const show = c.dataset.type === type; c.hidden = !show; if (show) any = true; });
        $('courtEmpty').hidden = any;
        $('courtEmpty').textContent = type ? 'No courts are open for this activity.' : 'Choose an activity to see its courts.';
        const cur = radios.find(x => x.checked);
        if (cur && cur.closest('.bf-court').dataset.type !== type) cur.checked = false;
        if (type && !radios.some(x => x.checked)) {
            const shown = radios.filter(x => !x.closest('.bf-court').hidden);
            if (shown.length === 1) shown[0].checked = true;   // single court: pick it
        }
    }

    // Which slots can be chosen, and why not.
    function slotState(s) {
        const len = hours * 60;
        if (!info) return 'off';
        if (info.date === info.today && s < nowMin()) return 'past';
        for (const b of info.busy) if (s < b[1] && s + 30 > b[0]) return 'busy';
        if (s + len > info.close) return 'fit';
        for (const b of info.busy) if (s < b[1] && s + len > b[0]) return 'fit';
        return 'free';
    }

    function renderSlots() {
        slotsEl.innerHTML = '';
        if (!court()) { slotsEl.innerHTML = '<p class="bf-empty">Choose a court to see its times.</p>'; return; }
        if (!info) { slotsEl.innerHTML = '<p class="bf-empty">Loading times…</p>'; return; }
        const groups = [['Morning', 0, 720], ['Afternoon', 720, 1020], ['Evening', 1020, 1440]];
        let anyFree = false, pickedOk = false;
        groups.forEach(([label, from, to]) => {
            const list = [];
            for (let s = Math.ceil(Math.max(info.open, from) / 30) * 30; s < Math.min(info.close, to); s += 30) list.push(s);
            if (!list.length) return;
            const wrap = document.createElement('div'); wrap.className = 'bf-slot-group';
            wrap.innerHTML = '<h3>' + label + '</h3>';
            const grid = document.createElement('div'); grid.className = 'bf-slot-grid';
            list.forEach(s => {
                const st = slotState(s), b = document.createElement('button');
                b.type = 'button'; b.className = 'bf-slot is-' + st; b.dataset.min = s; b.textContent = clock(s);
                if (st === 'free') anyFree = true;
                if (st !== 'free') { b.disabled = true; b.title = st === 'past' ? 'This time has passed' : st === 'busy' ? 'Already booked' : 'Not enough time before the next booking or closing'; }
                if (picked === s && st === 'free') { b.classList.add('is-picked'); b.setAttribute('aria-pressed', 'true'); pickedOk = true; }
                grid.appendChild(b);
            });
            wrap.appendChild(grid); slotsEl.appendChild(wrap);
        });
        if (picked !== null && !pickedOk) {
            note = 'Your earlier time no longer fits. Pick another.';
            picked = null; timeIn.value = '';
        }
        if (!anyFree) {
            const p = document.createElement('p'); p.className = 'bf-empty';
            p.textContent = info.date === info.today ? 'No more ' + fmtHrs(hours) + ' slots today. Try another day.' : 'Nothing is free for ' + fmtHrs(hours) + ' on this day. Try another date or fewer hours.';
            slotsEl.prepend(p);
        }
    }

    function load() {
        const c = court(), d = dateIn.value;
        info = null; renderSlots(); summary();
        if (!c || !/^\d{4}-\d{2}-\d{2}$/.test(d)) return;
        const key = c.id + d;
        fetch(busyUrl + '?facility_id=' + c.id + '&date=' + d, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(j => { if (key !== (court() || {}).id + dateIn.value) return; info = j; infoAt = performance.now(); renderSlots(); summary(); })
            .catch(() => { slotsEl.innerHTML = '<p class="bf-empty">Could not load times. Refresh the page and try again.</p>'; });
    }

    function summary() {
        const c = court();
        $('sFacility').textContent = c ? c.name : '–';
        const dv = dateIn.value, dObj = dv ? new Date(dv + 'T00:00') : null;
        $('sDate').textContent = dObj && !isNaN(dObj) ? dObj.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }) : '–';
        $('sStart').textContent = picked !== null ? clock(picked) : '–';
        $('sEnd').textContent = picked !== null ? clock(picked + hours * 60) : '–';
        $('sDuration').textContent = fmtHrs(hours);
        $('sRate').textContent = c ? peso(c.price) + ' / hour' : '–';
        $('sTotal').textContent = c ? peso(c.price * hours) : '–';
        $('peopleText').textContent = c ? c.people + ' fit comfortably on ' + c.name + '. For your information only.' : 'Choose a court to see how many people it fits.';
        $('peopleBox').classList.toggle('is-on', !!c);

        hoursIn.value = hours; $('hrValue').textContent = fmtHrs(hours);
        $('hrMinus').disabled = hours <= MIN; $('hrPlus').disabled = hours >= MAX;
        $('durHint').textContent = hours >= MAX ? 'Longest booking reached (' + fmtHrs(MAX) + ').' : (c ? peso(c.price) + ' per hour. Each + adds one hour.' : 'Courts are rented per hour. Each + adds one hour.');

        const ready = !!c && picked !== null && /^\d{4}-\d{2}-\d{2}$/.test(dv);
        $('submitBtn').disabled = !ready;
        $('submitHint').textContent = note || (ready ? '' : 'Choose a court, date and time to continue.');
        $('submitHint').hidden = ready && !note;

        const link = $('availLink');
        if (c) { link.href = baseUrl + '/' + c.id + (dv ? '?date=' + dv : ''); link.classList.remove('disabled'); link.removeAttribute('aria-disabled'); }
        else { link.href = '#'; link.classList.add('disabled'); link.setAttribute('aria-disabled', 'true'); }
        quick.forEach(q => q.classList.toggle('is-on', q.dataset.date === dv));
        dateIn.closest('.bf-date').classList.toggle('is-on', !!dv && !quick.some(q => q.dataset.date === dv));
    }

    acts.forEach(a => a.addEventListener('click', () => { setSport(a.dataset.type); picked = null; timeIn.value = ''; note = ''; load(); }));
    radios.forEach(r => r.addEventListener('change', () => { picked = null; timeIn.value = ''; note = ''; load(); }));
    quick.forEach(q => q.addEventListener('click', () => { dateIn.value = q.dataset.date; picked = null; timeIn.value = ''; note = ''; load(); }));
    dateIn.addEventListener('change', () => { if (dateIn.value && dateIn.value < dateIn.min) dateIn.value = dateIn.min; picked = null; timeIn.value = ''; note = ''; load(); });
    slotsEl.addEventListener('click', e => {
        const b = e.target.closest('.bf-slot'); if (!b || b.disabled) return;
        picked = +b.dataset.min; timeIn.value = pad(Math.floor(picked / 60)) + ':' + pad(picked % 60); note = '';
        renderSlots(); summary();
    });
    $('hrMinus').addEventListener('click', () => { if (hours > MIN) { hours--; note = ''; renderSlots(); summary(); } });
    $('hrPlus').addEventListener('click', () => { if (hours < MAX) { hours++; note = ''; renderSlots(); summary(); } });
    // Keep "passed" slots current while the page stays open.
    setInterval(() => { if (info) { renderSlots(); summary(); } }, 30000);

    setSport(sport);
    load();
})();
</script>
<?= $this->endSection() ?>
