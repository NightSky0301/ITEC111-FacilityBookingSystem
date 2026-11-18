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

    $dateLabel = date('Y-m-d') === $dateValue ? 'Today' : date('D', strtotime($dateValue));
    $dateLabel .= ', ' . date('M j', strtotime($dateValue));
?>
<?= $this->setVar('step', 4)->setVar('type', $selectedType)->setVar('courtUrl', $selectedId ? site_url('facilities/court/' . $selectedId) : null)->include('facilities/_stepper') ?>

<header class="activity-hero">
    <h1>New booking</h1>
    <p>Choose a time, add any extras and send your request.</p>
</header>

<form method="post" action="<?= site_url('bookings') ?>" id="bookForm" novalidate>
<?= csrf_field() ?>
<input type="hidden" name="start_time" id="startTime" value="<?= esc($timeValue) ?>">
<input type="hidden" name="hours" id="hours" value="<?= $hours ?>">
<input type="hidden" name="start_date" id="startDate" value="<?= esc($dateValue) ?>">
<input type="hidden" name="people" id="people" value="<?= max(1, (int) old('people')) ?>" disabled>

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
                                <span class="bf-price">₱<?= number_format($c['price'], 0) ?> <small><?= ! empty($c['head']) ? '/ person / hour' : '/ hour' ?></small></span>
                            </span>
                            <i class="bi bi-check-circle-fill bf-tick"></i>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="bf-sec" id="headSec" hidden>
                <div class="duration-box">
                    <div class="duration-row">
                        <span class="duration-icon"><i class="bi bi-people"></i></span>
                        <div class="duration-text">
                            <strong>How many people?</strong>
                            <span id="headHint">The gym is booked per person.</span>
                        </div>
                        <div class="ext-stepper" role="group" aria-label="Number of people">
                            <button type="button" class="ext-btn" id="pMinus" aria-label="One less person"><i class="bi bi-dash-lg"></i></button>
                            <output class="ext-value" id="pValue" for="pMinus pPlus">1</output>
                            <button type="button" class="ext-btn" id="pPlus" aria-label="One more person"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bf-sec">
                <div class="bf-h-row">
                    <h2 class="bf-h">Arrival time</h2>
                    <span class="bf-open"><?= esc($dateLabel) ?> · Open <?= esc(date('g A', strtotime(sprintf('%02d:00', $open)))) ?> – <?= esc(date('g A', strtotime(sprintf('%02d:00', $close)))) ?></span>
                </div>
                <div id="slots" class="bf-acc" aria-live="polite"></div>
                <div class="bf-legend">
                    <span><i class="lg lg-free"></i>Available</span>
                    <span><i class="lg lg-sel"></i>Your pick</span>
                    <span><i class="lg lg-busy"></i>Booked</span>
                    <span><i class="lg lg-off"></i>Passed or unavailable</span>
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
                <h2 class="bf-h">Add-ons <span class="text-muted fw-normal">(optional)</span></h2>
                <div class="bf-cool" id="coolBox" role="radiogroup" aria-label="Cooling">
                    <label class="bf-cool-opt"><input type="radio" name="cooling" value="" checked><span><b>No cooling</b><small>Natural airflow</small></span></label>
                    <?php foreach ($addons as $key => $a): if ($a['group'] !== 'cooling') continue; ?>
                        <label class="bf-cool-opt"><input type="radio" name="cooling" value="<?= esc($key) ?>" data-addon="<?= esc($key) ?>" data-indoor="<?= ! empty($a['indoor']) ? '1' : '0' ?>" <?= old('cooling') === $key ? 'checked' : '' ?>>
                            <span><b><?= esc($a['label']) ?></b><small data-note="+₱<?= number_format($a['price']) ?> / hr">+₱<?= number_format($a['price']) ?> / hr</small></span></label>
                    <?php endforeach; ?>
                </div>
                <div class="bf-extras" id="extras">
                    <?php foreach ($addons as $key => $a): if ($a['group'] !== 'extra') continue; ?>
                        <label class="bf-extra" data-addon="<?= esc($key) ?>" data-types="<?= esc(json_encode($a['types']), 'attr') ?>" data-evening="<?= $a['evening'] ? '1' : '0' ?>">
                            <input type="checkbox" name="addons[]" value="<?= esc($key) ?>" <?= in_array($key, (array) old('addons'), true) ? 'checked' : '' ?>>
                            <span class="bf-extra-body"><b><?= esc($a['label']) ?></b><small data-note="<?= esc($a['note'], 'attr') ?>"><?= esc($a['note']) ?></small></span>
                            <span class="bf-extra-price">+₱<?= number_format($a['price']) ?><small><?= $a['per'] === 'hour' ? ' / hr' : ($a['per'] === 'person' ? ' / person' : '') ?></small></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="bf-sec" id="suggestSec">
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
                <div class="row-line"><dt>Arrival</dt><dd id="sStart">–</dd></div>
                <div class="row-line"><dt>Departure</dt><dd id="sEnd">–</dd></div>
                <div class="row-line" id="sPeopleRow" hidden><dt>People</dt><dd id="sPeople">–</dd></div>
                <div class="row-line"><dt>Length</dt><dd id="sDuration">–</dd></div>
                <div class="row-line"><dt><span id="sCourtLbl">Court</span> <span id="sRate" class="sub"></span></dt><dd id="sCourt">–</dd></div>
                <div id="sAddons"></div>
            </dl>
            <div class="total"><span class="text-muted">Total payment</span><strong id="sTotal">–</strong></div>
            <button class="btn btn-primary w-100 mt-4" id="submitBtn" disabled>Send request</button>
            <p class="small text-danger text-center mt-2 mb-0" id="submitHint" hidden></p>
            <button type="button" id="schedBtn" class="btn btn-outline-secondary w-100 mt-2" disabled><i class="bi bi-calendar3 me-1"></i> Check this court's schedule</button>
        </div></div>
    </div>
</div>
</form>

<div class="modal fade" id="schedModal" tabindex="-1" aria-labelledby="schTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ev-dialog"><div class="modal-content ev-modal">
        <div class="ev-head">
            <span class="ev-ic"><i class="bi bi-calendar3"></i></span>
            <div class="ev-head-text">
                <div class="ev-kicker" id="schKicker"></div>
                <h5 class="ev-title" id="schTitle"></h5>
            </div>
            <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="ev-body" id="schBody"></div>
        <div class="ev-foot"><button type="button" class="btn btn-sm btn-primary" data-bs-dismiss="modal">Close</button></div>
    </div></div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// If anything on this page fails to start, say so on screen instead of leaving dead buttons.
window.addEventListener('error', function (e) {
    if (document.getElementById('bfError')) return;
    var box = document.createElement('div');
    box.id = 'bfError'; box.className = 'alert alert-danger';
    box.textContent = 'The booking form could not start: ' + (e.message || 'unknown error') + ' (line ' + (e.lineno || '?') + '). Press Ctrl+F5. If it stays like this, send this message to your developer.';
    var main = document.querySelector('main'); if (main) main.prepend(box);
});
</script>
<script>
(function () {
    const ADDONS = <?= (json_encode($addons, JSON_HEX_TAG | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}') ?>, EVENING = <?= (int) $eveningFrom ?>;
    const MIN = <?= (int) $minHours ?>, MAX = <?= (int) $maxHours ?>;
    const COURTS = <?= (json_encode($courts, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE) ?: '[]') ?>;
    const $ = id => document.getElementById(id);
    const dateIn = $('startDate'), timeIn = $('startTime'), hoursIn = $('hours'), slotsEl = $('slots');
    const radios = Array.from(document.querySelectorAll('input[name=facility_id]'));
    const acts = Array.from(document.querySelectorAll('.bf-act')), cards = Array.from(document.querySelectorAll('.bf-court'));
    const extras = Array.from(document.querySelectorAll('.bf-extra')), cool = Array.from(document.querySelectorAll('input[name=cooling]'));
    const baseUrl = '<?= site_url('facilities/court') ?>', busyUrl = '<?= site_url('bookings/busy') ?>';
    const peso = n => '₱' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtHrs = n => n + (n === 1 ? ' hr' : ' hrs');
        const clock = m => { const h = Math.floor(m / 60) % 24, mm = m % 60; return ((h + 11) % 12 + 1) + ':' + String(mm).padStart(2, '0') + ' ' + (h < 12 ? 'AM' : 'PM'); };
    // One arrival button covers a half-hour window, e.g. "6:00 AM – 6:30 AM". The arrival time is its start.
    const win = s => clock(s) + ' – ' + clock(s + 30);
    const toMin = s => { const p = (s || '').split(':'); return p.length >= 2 ? (+p[0]) * 60 + (+p[1]) : null; };
    const pad = n => String(n).padStart(2, '0');

    let hours = Math.max(MIN, Math.min(MAX, parseInt(hoursIn.value, 10) || MIN));
    let sport = '<?= esc($selectedType, 'js') ?>';
    let info = null;            // answer of bookings/busy for the current court + date
    let infoAt = 0;             // performance.now() when it arrived, to keep the server clock ticking
    let picked = toMin(timeIn.value);
    let note = '';
    let people = Math.max(1, parseInt($('people').value, 10) || 1);

    const court = () => { const r = radios.find(x => x.checked); return r ? COURTS.find(c => c.id === +r.value) : null; };
    const isHead = () => { const c = court(); return !!(c && c.head); };
    const mult = () => isHead() ? people : 1;
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

    // Arrival times are fixed 30-minute marks. A time is open when it has not passed and at least one hour
    // from it is free (the shortest booking), so the list never changes when you change the hours.
    const GROUPS = [['morning', 'Morning', 0, 720], ['afternoon', 'Afternoon', 720, 1020], ['evening', 'Evening', 1020, 1440]];
    let openKey = null;
    function slotState(s) {
        if (!info) return 'off';
        if (info.date === info.today && s < nowMin()) return 'past';
        if (s + MIN * 60 > info.close) return 'fit';
        for (const b of info.busy) if (s < b[1] && s + MIN * 60 > b[0]) return 'busy';
        return 'free';
    }
    // The longest stay from the chosen arrival: until closing or the next booking, whichever comes first.
    function maxStay() {
        if (picked === null || !info) return MAX;
        let end = info.close;
        info.busy.forEach(b => { if (b[0] >= picked && b[0] < end) end = b[0]; });
        return Math.max(MIN, Math.min(MAX, Math.floor((end - picked) / 60)));
    }
    function stayLimitText() {
        if (picked === null || !info) return '';
        const limit = maxStay();
        if (limit >= MAX) return '';
        const next = Math.min(...info.busy.filter(b => b[0] >= picked).map(b => b[0]), Infinity);
        const cut = next < info.close && Math.floor((next - picked) / 60) === limit;
        return cut ? 'Another booking starts after ' + fmtHrs(limit) + '.' : 'The court closes after ' + fmtHrs(limit) + ' from this arrival.';
    }

    function renderSlots() {
        slotsEl.innerHTML = '';
        if (!court()) { slotsEl.innerHTML = '<p class="bf-empty">Choose a court to see its times.</p>'; return; }
        if (!info) { slotsEl.innerHTML = '<p class="bf-empty">Loading times…</p>'; return; }
        let anyFree = false;
        const built = GROUPS.map(([key, label, from, to]) => {
            const list = [];
            for (let s = Math.ceil(Math.max(info.open, from) / 30) * 30; s < Math.min(info.close, to); s += 30) list.push([s, slotState(s)]);
            return { key, label, from, to, list, free: list.filter(x => x[1] === 'free').length };
        }).filter(g => g.list.length);
        built.forEach(g => { if (g.free) anyFree = true; });
        if (picked !== null && !built.some(g => g.list.some(x => x[0] === picked && x[1] === 'free'))) {
            note = 'That arrival time is no longer available. Pick another.'; picked = null; timeIn.value = '';
        }
        if (openKey === null || !built.some(g => g.key === openKey)) openKey = null;

        built.forEach(g => {
            const isOpen = openKey === g.key, hasPick = picked !== null && picked >= g.from && picked < g.to;
            const wrap = document.createElement('div'); wrap.className = 'bf-grp' + (isOpen ? ' is-open' : '') + (hasPick ? ' has-pick' : '');
            const head = document.createElement('button');
            head.type = 'button'; head.className = 'bf-grp-head'; head.dataset.key = g.key; head.disabled = !g.free;
            head.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            const range = clock(Math.max(info.open, g.from)).replace(':00', '') + ' – ' + clock(Math.min(info.close, g.to)).replace(':00', '');
            head.innerHTML = '<span class="bf-grp-name"><b>' + g.label + '</b><small>' + range + '</small></span>' +
                '<span class="bf-grp-meta">' + (hasPick ? '<em class="bf-grp-pick">' + win(picked) + '</em>' : '<small>' + (g.free ? g.free + ' open' : 'None left') + '</small>') + '</span>' +
                '<i class="bi bi-chevron-down bf-grp-chev"></i>';
            wrap.appendChild(head);
            if (isOpen) {
                const grid = document.createElement('div'); grid.className = 'bf-slot-grid';
                g.list.forEach(([s, st]) => {
                    const b = document.createElement('button');
                    b.type = 'button'; b.className = 'bf-slot is-' + st; b.dataset.min = s; b.textContent = win(s);
                    if (st !== 'free') { b.disabled = true; b.title = st === 'past' ? 'This time has passed' : st === 'busy' ? (isHead() ? 'No room for ' + people + (people === 1 ? ' person' : ' people') : 'Already booked') : 'Too close to closing time'; }
                    if (picked === s) { b.classList.add('is-picked'); b.setAttribute('aria-pressed', 'true'); }
                    grid.appendChild(b);
                });
                wrap.appendChild(grid);
            }
            slotsEl.appendChild(wrap);
        });
        if (!anyFree) {
            const p = document.createElement('p'); p.className = 'bf-empty';
            p.textContent = info.date === info.today ? 'No more arrival times today. Try another day from the court schedule.' : 'No arrival times are free on this day.';
            slotsEl.prepend(p);
        }
    }

    function load() {
        const c = court(), d = dateIn.value;
        info = null; renderSlots(); summary();
        if (!c || !/^\d{4}-\d{2}-\d{2}$/.test(d)) return;
        const key = c.id + d + '|' + people;
        fetch(busyUrl + '?facility_id=' + c.id + '&date=' + d + (c.head ? '&people=' + people : ''), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(j => { if (key !== (court() || {}).id + dateIn.value + '|' + people) return; info = j; infoAt = performance.now(); renderSlots(); summary(); })
            .catch(() => { slotsEl.innerHTML = '<p class="bf-empty">Could not load times. Refresh the page and try again.</p>'; });
    }

    const chosen = () => {
        const out = extras.filter(e => e.querySelector('input').checked && !e.classList.contains('is-off')).map(e => e.dataset.addon);
        const c = cool.find(r => r.checked && r.value); if (c) out.push(c.value);
        return out;
    };
    // Show only the extras that suit this court, and lights only for evening arrivals.
    function syncAddons(c) {
        // Air-conditioning is for indoor courts only; outdoor courts have no air-conditioning.
        cool.forEach(r => {
            if (r.dataset.indoor !== '1') return;
            const off = !!c && c.outdoor, small = r.closest('label').querySelector('small');
            r.disabled = off; r.closest('label').classList.toggle('is-off', off);
            if (off && r.checked) cool.find(x => x.value === '').checked = true;
            small.textContent = off ? 'Indoor courts only (not on outdoor courts)' : small.dataset.note;
        });
        const head = !!c && c.head;
        $('coolBox').hidden = head;
        if (head) { const none = cool.find(x => x.value === ''); if (none) none.checked = true; }
        extras.forEach(e => {
            const types = JSON.parse(e.dataset.types), small = e.querySelector('small');
            const wrongType = types && (!c || types.indexOf(c.type) < 0);
            const dark = e.dataset.evening === '1' && (picked === null || picked < EVENING);
            e.hidden = !!wrongType;
            e.classList.toggle('is-off', dark);
            e.querySelector('input').disabled = dark || !!wrongType;
            if (dark || wrongType) e.querySelector('input').checked = false;
            small.textContent = dark ? (picked === null ? 'Choose an arrival time first (evening only)' : 'Not available for morning or afternoon') : small.dataset.note;
        });
    }

    function summary() {
        const c = court();
        $('sFacility').textContent = c ? c.name : '–';
        const dv = dateIn.value, dObj = dv ? new Date(dv + 'T00:00') : null;
        $('sDate').textContent = dObj && !isNaN(dObj) ? dObj.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }) : '–';
        $('sStart').textContent = picked !== null ? clock(picked) : '–';
        $('sEnd').textContent = picked !== null ? clock(picked + hours * 60) : '–';
        $('sDuration').textContent = fmtHrs(hours);
        const head = !!c && c.head, m = head ? people : 1;
        $('headSec').hidden = !head; $('suggestSec').hidden = head; $('people').disabled = !head; $('people').value = people;
        $('sPeopleRow').hidden = !head; $('sPeople').textContent = people + (people === 1 ? ' person' : ' people');
        $('sCourtLbl').textContent = head ? 'Gym access' : 'Court';
        $('pValue').textContent = people; $('pMinus').disabled = people <= 1; $('pPlus').disabled = !head || people >= c.max;
        $('headHint').textContent = head ? 'Up to ' + c.max + ' people at a time. ' + peso(c.price) + ' per person, per hour.' : '';
        $('sRate').textContent = c ? '(' + (head ? people + (people === 1 ? ' person × ' : ' people × ') : '') + fmtHrs(hours) + ' × ' + peso(c.price).replace('.00', '') + ')' : '';
        $('sCourt').textContent = c ? peso(c.price * hours * m) : '–';
        syncAddons(c);
        let add = 0, rows = '';
        chosen().forEach(k => {
            const a = ADDONS[k], amt = a.per === 'hour' ? a.price * hours : a.per === 'person' ? a.price * m : a.per === 'person_hour' ? a.price * m * hours : a.price; add += amt;
            const sub = a.per === 'hour' ? '(' + fmtHrs(hours) + ' × ₱' + a.price + ')' : a.per === 'person' ? '(' + m + (m === 1 ? ' person' : ' people') + ' × ₱' + a.price + ')' : '(flat)';
            rows += '<div class="row-line"><dt>' + a.label + ' <span class="sub">' + sub + '</span></dt><dd>' + peso(amt) + '</dd></div>';
        });
        $('sAddons').innerHTML = rows;
        $('sTotal').textContent = c ? peso(c.price * hours * m + add) : '–';
        $('peopleText').textContent = c ? c.people + ' fit comfortably on ' + c.name + '. For your information only.' : 'Choose a court to see how many people it fits.';
        $('peopleBox').classList.toggle('is-on', !!c);

        const limit = maxStay();
        if (hours > limit) hours = limit;
        hoursIn.value = hours; $('hrValue').textContent = fmtHrs(hours);
        $('hrMinus').disabled = hours <= MIN; $('hrPlus').disabled = hours >= limit;
        $('durHint').textContent = hours >= MAX ? 'Longest booking reached (' + fmtHrs(MAX) + ').'
            : (hours >= limit && stayLimitText() ? stayLimitText() : (c ? peso(c.price) + (head ? ' per person, per hour.' : ' per hour.') + ' Each + adds one hour.' : 'Courts are rented per hour. Each + adds one hour.'));

        const ready = !!c && picked !== null && /^\d{4}-\d{2}-\d{2}$/.test(dv);
        $('submitBtn').disabled = !ready;
        $('submitHint').textContent = note;
        $('submitHint').hidden = !note;

        $('schedBtn').disabled = !c || !/^\d{4}-\d{2}-\d{2}$/.test(dv);
    }

    acts.forEach(a => a.addEventListener('click', () => { setSport(a.dataset.type); picked = null; timeIn.value = ''; note = ''; load(); }));
    radios.forEach(r => r.addEventListener('change', () => { people = Math.min(people, (court() || {max: people}).max || people); picked = null; timeIn.value = ''; note = ''; load(); }));
    slotsEl.addEventListener('click', e => {
        const h = e.target.closest('.bf-grp-head');
        if (h && !h.disabled) { openKey = openKey === h.dataset.key ? null : h.dataset.key; renderSlots(); return; }
        const b = e.target.closest('.bf-slot'); if (!b || b.disabled) return;
        picked = +b.dataset.min; timeIn.value = pad(Math.floor(picked / 60)) + ':' + pad(picked % 60); note = '';
        openKey = null; renderSlots(); summary();
    });
    extras.forEach(e => e.addEventListener('change', summary)); cool.forEach(r => r.addEventListener('change', summary));
    // Gym: changing the head count changes which times still have room, so reload them.
    function setPeople(n) { people = n; note = ''; load(); }
    $('pMinus').addEventListener('click', () => { if (people > 1) setPeople(people - 1); });
    $('pPlus').addEventListener('click', () => { const c = court(); if (c && c.head && people < c.max) setPeople(people + 1); });
    $('hrMinus').addEventListener('click', () => { if (hours > MIN) { hours--; note = ''; summary(); } });
    $('hrPlus').addEventListener('click', () => { if (hours < maxStay()) { hours++; note = ''; summary(); } });
    // Popup: what is already booked on this court on the chosen day.
    const schedModal = new bootstrap.Modal($('schedModal'));
    function openSched() {
        const c = court(), dv = dateIn.value; if (!c) return;
        const dObj = new Date(dv + 'T00:00');
        $('schKicker').textContent = dObj.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
        $('schTitle').textContent = c.name;
        $('schBody').innerHTML = '<p class="bf-empty m-3">Loading schedule…</p>';
        schedModal.show();
        fetch(busyUrl + '?facility_id=' + c.id + '&date=' + dv + (c.head ? '&people=' + people : ''), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(j => {
                const span = j.close - j.open, pct = m => Math.max(0, Math.min(100, (m - j.open) / span * 100));
                const busy = j.busy.slice().sort((a, b) => a[0] - b[0]);
                let free = [], from = j.open;
                if (j.date === j.today) from = Math.max(from, Math.ceil((j.now + 1) / 30) * 30);
                busy.forEach(b => { if (b[0] > from) free.push([from, b[0]]); from = Math.max(from, b[1]); });
                if (from < j.close) free.push([from, j.close]);
                free = free.filter(f => f[1] - f[0] >= 60);
                let h = '<div class="sch-bar" aria-hidden="true">' + busy.map(b => '<i class="sch-seg" style="left:' + pct(b[0]) + '%;width:' + (pct(b[1]) - pct(b[0])) + '%"></i>').join('') + '</div>' +
                    '<div class="sch-scale"><span>' + clock(j.open) + '</span><span>' + clock(j.close) + '</span></div>';
                h += busy.length
                    ? '<div class="sch-list"><small>Already booked</small>' + busy.map(b => '<div class="sch-row"><span>' + clock(b[0]) + ' – ' + clock(b[1]) + '</span><span class="pill pill-rejected">Booked</span></div>').join('') + '</div>'
                    : '<div class="sch-none"><i class="bi bi-check-circle"></i> Nothing is booked on this day yet.</div>';
                h += '<div class="sch-list"><small>Open for booking</small>' + (free.length
                    ? free.map(f => '<div class="sch-row"><span>' + clock(f[0]) + ' – ' + clock(f[1]) + '</span><span class="pill pill-approved">Open</span></div>').join('')
                    : '<div class="sch-row"><span>No open times left on this day.</span></div>') + '</div>';
                $('schBody').innerHTML = h;
            })
            .catch(() => { $('schBody').innerHTML = '<p class="bf-empty m-3">Could not load the schedule. Try again.</p>'; });
    }
    $('schedBtn').addEventListener('click', openSched);
    // Keep "passed" slots current while the page stays open.
    setInterval(() => { if (info) { renderSlots(); summary(); } }, 30000);

    if (picked !== null) openKey = (GROUPS.find(g => picked >= g[2] && picked < g[3]) || [null])[0];
    setSport(sport);
    load();
})();
</script>
<?= $this->endSection() ?>
