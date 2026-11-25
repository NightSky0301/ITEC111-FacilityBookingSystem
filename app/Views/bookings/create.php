<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $selectedId   = (int) (old('facility_id') ?: $selected);
    $selectedType = '';
    foreach ($facilities as $f) {
        if ((int) $f['id'] === $selectedId) { $selectedType = $f['type']; }
    }
    $hours     = max(0, min((int) $maxHours, (int) (old('hours') ?? $minHours)));
    $dateValue = old('start_date') ?: $startDate ?: date('Y-m-d');
    $timeValue = old('start_time') ?: $startTime;

    $dateLabel = date('Y-m-d') === $dateValue ? 'Today' : date('D', strtotime($dateValue));
    $dateLabel .= ', ' . date('M j', strtotime($dateValue));
    $mode    = old('mode') !== null ? (old('mode') === 'days' ? 'days' : 'hour') : (($endDate ?? '') !== '' ? 'days' : 'hour');
    $endDate = $mode === 'days' ? (string) (old('end_date') ?: ($endDate ?? '')) : '';
?>
<?= $this->setVar('step', 2)->setVar('type', $selectedType)->include('facilities/_stepper') ?>

<form method="post" action="<?= site_url('bookings') ?>" id="bookForm" novalidate>
<?= csrf_field() ?>
<input type="hidden" name="start_time" id="startTime" value="<?= esc($timeValue) ?>">
<input type="hidden" name="hours" id="hours" value="<?= $hours ?>">
<input type="hidden" name="start_date" id="startDate" value="<?= esc($dateValue) ?>">
<input type="hidden" name="mode" id="mode" value="<?= $mode ?>">
<input type="hidden" name="end_date" id="endDate" value="<?= esc($endDate) ?>">
<input type="hidden" name="people" id="people" value="<?= max(1, (int) old('people')) ?>" disabled>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel bf"><div class="panel-body">

            <section class="bf-sec">
                <h2 class="bf-h">Court</h2>
                <p class="bf-empty" id="courtEmpty" hidden>No courts are open right now.</p>
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

            <section class="bf-sec bf-when" id="whenSec">
                <h2 class="bf-h">When</h2>
                <div class="bf-tabs" role="tablist" aria-label="Booking type">
                    <button type="button" role="tab" class="bf-tab<?= $mode === 'hour' ? ' is-on' : '' ?>" data-mode="hour" aria-selected="<?= $mode === 'hour' ? 'true' : 'false' ?>"><i class="bi bi-clock"></i> Hourly</button>
                    <button type="button" role="tab" class="bf-tab<?= $mode === 'days' ? ' is-on' : '' ?>" data-mode="days" aria-selected="<?= $mode === 'days' ? 'true' : 'false' ?>"><i class="bi bi-calendar2-range"></i> Flexible Days</button>
                </div>
                <div class="bf-datewrap">
                <button type="button" class="bf-datebtn" id="dateBtn" aria-haspopup="dialog" aria-expanded="false">
                    <i class="bi bi-calendar3"></i><span id="dateBtnText">Pick a date</span><i class="bi bi-chevron-down bf-datebtn-chev"></i>
                </button>
                <div class="bf-cal" id="cal" role="dialog" aria-label="Choose dates" hidden></div>
                </div>
                <div class="bf-chips" id="chips" hidden>
                    <span>Quick pick:</span>
                    <button type="button" class="bf-chip" data-len="7">1 week</button>
                    <button type="button" class="bf-chip" data-len="14">2 weeks</button>
                    <button type="button" class="bf-chip" data-len="month">1 month</button>
                </div>
            </section>

            <section class="bf-sec">
                <div class="bf-h-row">
                    <h2 class="bf-h">Arrival time</h2>
                    <span class="bf-open" id="openLbl"><?= esc($dateLabel) ?> · Open <?= esc(date('g A', strtotime(sprintf('%02d:00', $open)))) ?> – <?= esc(date('g A', strtotime(sprintf('%02d:00', $close)))) ?></span>
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
                <div class="duration-box" id="hoursBox">
                    <div class="duration-row">
                        <span class="duration-icon"><i class="bi bi-clock-history"></i></span>
                        <div class="duration-text">
                            <strong id="hrTitle">How long?</strong>
                            <span id="durHint">Courts are rented per hour. Each + adds one hour.</span>
                        </div>
                        <div class="ext-stepper" role="group" aria-labelledby="hrTitle">
                            <button type="button" class="ext-btn" id="hrMinus" aria-label="Remove 1 hour"><i class="bi bi-dash-lg"></i></button>
                            <output class="ext-value" id="hrValue" for="hrMinus hrPlus">1 hr</output>
                            <button type="button" class="ext-btn" id="hrPlus" aria-label="Add 1 hour"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </div>
                    <div class="len-sum" id="lenSum" hidden></div>
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
                            <span class="bf-extra-body"><b><?= esc($a['label']) ?><?php if ($key === 'referee'): ?> <span class="bf-ref-badge" id="refBadge" hidden></span><?php endif; ?></b><small data-note="<?= esc($a['note'], 'attr') ?>"><?= esc($a['note']) ?></small></span>
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
                <div class="row-line"><dt id="sDateLbl">Date</dt><dd id="sDate">–</dd></div>
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
            <p class="bf-wait" id="waitHint" hidden></p>
        </div></div>
    </div>
</div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// If anything on this page fails to start, say so on screen instead of leaving dead buttons.
window.addEventListener('error', function (e) {
    if (document.getElementById('bfError')) return;
    var box = document.createElement('div');
    box.id = 'bfError'; box.className = 'alert alert-danger';
    box.textContent = 'The booking form could not start: ' + (e.message || 'unknown error') + ' (line ' + (e.lineno || '?') + '). Press Ctrl+F5. If it stays like this, send this message to your developer.';
    if (window.athletixPopup) { window.athletixPopup('bad', box.textContent, 8000); return; }
    var main = document.querySelector('main'); if (main) main.prepend(box);
});
</script>
<script>
(function () {
    const ADDONS = <?= (json_encode($addons, JSON_HEX_TAG | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}') ?>, EVENING = <?= (int) $eveningFrom ?>;
    const MIN = <?= (int) $minHours ?>, MAX = <?= (int) $maxHours ?>;
    const DAYH = <?= (int) $dayHours ?>, OPENMIN = <?= (int) $open * 60 ?>, CLOSEMIN = <?= (int) $close * 60 ?>, POOL = <?= (int) $refPool ?>;
    const COURTS = <?= (json_encode($courts, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE) ?: '[]') ?>;
    const $ = id => document.getElementById(id);
    const dateIn = $('startDate'), timeIn = $('startTime'), hoursIn = $('hours'), slotsEl = $('slots');
    const radios = Array.from(document.querySelectorAll('input[name=facility_id]'));
    const acts = Array.from(document.querySelectorAll('.bf-act')), cards = Array.from(document.querySelectorAll('.bf-court'));
    const extras = Array.from(document.querySelectorAll('.bf-extra')), cool = Array.from(document.querySelectorAll('input[name=cooling]'));
    const busyUrl = '<?= site_url('bookings/busy') ?>', spanUrl = '<?= site_url('bookings/span') ?>', daysUrl = '<?= site_url('bookings/days') ?>';
    const SAFETY = <?= (int) $safetyDays ?>;
    const peso = n => '₱' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtHrs = n => (Number.isInteger(n) ? n : n.toFixed(1)) + (n === 1 ? ' hr' : ' hrs');
        const clock = m => { const h = Math.floor(m / 60) % 24, mm = m % 60; return ((h + 11) % 12 + 1) + ':' + String(mm).padStart(2, '0') + ' ' + (h < 12 ? 'AM' : 'PM'); };
    // One arrival button covers a half-hour window, e.g. "6:00 AM – 6:30 AM". The arrival time is its start.
    const win = s => clock(s) + ' – ' + clock(s + 30);
    const toMin = s => { const p = (s || '').split(':'); return p.length >= 2 ? (+p[0]) * 60 + (+p[1]) : null; };
    const pad = n => String(n).padStart(2, '0');

    let hours = Math.max(0, Math.min(MAX, isNaN(parseInt(hoursIn.value, 10)) ? MIN : parseInt(hoursIn.value, 10)));   // summary() raises it to the minimum when it is a plain hourly booking
    let sport = '<?= esc($selectedType, 'js') ?>';
    let info = null;            // answer of bookings/busy for the current court + date
    let infoAt = 0;             // performance.now() when it arrived, to keep the server clock ticking
    let picked = toMin(timeIn.value);
    let note = '';
    // Two ways to book: by the hour (one day) or multiple days (a first and a last date; the same arrival
    // time and hours repeat every day, one booking per day). There is no limit on how long a stay is.
    const modeIn = $('mode'), endIn = $('endDate');
    let mode = modeIn.value === 'days' ? 'days' : 'hour';
    let span = null, spanSeq = 0;
    const parse = s => new Date(s + 'T00:00');
    const ymd = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    const addDays = (s, n) => { const d = parse(s); d.setDate(d.getDate() + n); return ymd(d); };
    const daysBetween = (a, b) => Math.round((parse(b) - parse(a)) / 86400000) + 1;
    const nDays = () => (mode === 'days' && endIn.value && endIn.value >= dateIn.value) ? daysBetween(dateIn.value, endIn.value) : 1;
    const isStay = () => nDays() >= 2;               // 2+ days: the same arrival time and hours repeat every day
    const capH = () => { const c = court(); return c ? Math.min(MAX, c.hours) : MAX; };   // a court can be shorter than 16 h a day
    const closeMin = () => info ? info.close : (court() ? court().close * 60 : CLOSEMIN);
    const minH = () => MIN;
    const maxH = () => isStay() ? Math.max(MIN, Math.min(capH(), Math.floor((closeMin() - (picked !== null ? picked : OPENMIN)) / 60))) : maxStay();
    const billed = () => hours * nDays();             // hours charged across all days
    const plural = (n, w) => n + ' ' + w + (n === 1 ? '' : 's');
    const lenLabel = () => isStay() ? plural(nDays(), 'day') + ' · ' + fmtHrs(hours) + ' a day' : fmtHrs(hours);
    const lastDate = () => parse(isStay() ? endIn.value : dateIn.value);
    let people = Math.max(1, parseInt($('people').value, 10) || 1);

    const court = () => { const r = radios.find(x => x.checked); return r ? COURTS.find(c => c.id === +r.value) : null; };
    const isHead = () => { const c = court(); return !!(c && c.head); };
    const mult = () => isHead() ? people : 1;
    const nowMin = () => info ? info.now + Math.floor((performance.now() - infoAt) / 60000) : null;

    function setSport(type) {
        sport = type;
        acts.forEach(a => a.setAttribute('aria-checked', a.dataset.type === type ? 'true' : 'false'));
        let any = false;
        cards.forEach(c => { const show = !type || c.dataset.type === type; c.hidden = !show; if (show) any = true; });
        $('courtEmpty').hidden = any;
        $('courtEmpty').textContent = type ? 'No courts are open for this activity.' : 'No courts are open right now.';
        const cur = radios.find(x => x.checked);
        if (cur && type && cur.closest('.bf-court').dataset.type !== type) cur.checked = false;
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
        if (picked === null || !info) return capH();
        let end = info.close;
        info.busy.forEach(b => { if (b[0] >= picked && b[0] < end) end = b[0]; });
        return Math.max(MIN, Math.min(capH(), Math.floor((end - picked) / 60)));
    }
    function stayLimitText() {
        if (picked === null || !info) return '';
        const limit = maxStay();
        if (limit >= capH()) return '';
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

    // Ask the server if the court (and referee) are free on every day of the stay, and whether other requests wait for the same time.
    const wantsRef = () => extras.some(e => e.dataset.addon === 'referee' && e.querySelector('input').checked);
    function checkSpan() {
        const c = court(), seq = ++spanSeq; span = null;
        if (mode === 'days') loadTaken();
        if (!c || picked === null) { summary(); return; }
        const q = '?facility_id=' + c.id + '&date=' + dateIn.value + '&end_date=' + (isStay() ? endIn.value : dateIn.value) + '&time=' + timeIn.value + '&hours=' + hours + (wantsRef() ? '&ref=1' : '') + (c.head ? '&people=' + people : '');
        fetch(spanUrl + q, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(j => { if (seq !== spanSeq) return; span = j; summary(); })
            .catch(() => { if (seq === spanSeq) { span = { free: false, error: true }; summary(); } });
    }

    // ---- Date picker: one calendar for both tabs (a single day by the hour, a range for multiple days)
    const calEl = $('cal'), dateBtn = $('dateBtn');
    let view = parse(dateIn.value); view.setDate(1);
    let taken = new Set(), takenSeq = 0, pendingEnd = mode === 'days' && !endIn.value;
    let pickStep = 'start';   // Flexible Days: the next click sets the 'start' (first day) or the 'end' (last day)
    const todayStr = () => info ? info.today : ymd(new Date());
    const dShort = s => parse(s).toLocaleDateString([], { month: 'short', day: 'numeric' });
    function loadTaken() {
        const c = court(), seq = ++takenSeq;
        if (mode !== 'days' || !c || picked === null) { taken = new Set(); renderCal(); return; }
        const first = ymd(view), last = ymd(new Date(view.getFullYear(), view.getMonth() + 1, 0)), from = first < todayStr() ? todayStr() : first;
        if (last < from) { taken = new Set(); renderCal(); return; }
        fetch(daysUrl + '?facility_id=' + c.id + '&from=' + from + '&to=' + last + '&time=' + timeIn.value + '&hours=' + hours + (wantsRef() ? '&ref=1' : '') + (c.head ? '&people=' + people : ''), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(j => { if (seq !== takenSeq) return; taken = new Set((j.taken || []).concat(j.refShort || [])); renderCal(); })
            .catch(() => { if (seq === takenSeq) { taken = new Set(); renderCal(); } });
    }
    function renderCal() {
        const y = view.getFullYear(), m = view.getMonth(), dim = new Date(y, m + 1, 0).getDate(), lead = new Date(y, m, 1).getDay();
        const today = todayStr(), s = dateIn.value, e = endIn.value, days = mode === 'days';
        let h = '<div class="rc-head"><button type="button" class="rc-nav" data-nav="-1" aria-label="Previous month"' + (y + '-' + pad(m + 1) <= today.slice(0, 7) ? ' disabled' : '') + '><i class="bi bi-chevron-left"></i></button>' +
            '<b>' + new Date(y, m, 1).toLocaleDateString([], { month: 'long', year: 'numeric' }) + '</b>' +
            '<button type="button" class="rc-nav" data-nav="1" aria-label="Next month"><i class="bi bi-chevron-right"></i></button></div>' +
            '<div class="rc-dow">' + ['S', 'M', 'T', 'W', 'T', 'F', 'S'].map(x => '<span>' + x + '</span>').join('') + '</div><div class="rc-grid">';
        for (let i = 0; i < lead; i++) h += '<span></span>';
        for (let d = 1; d <= dim; d++) {
            const k = y + '-' + pad(m + 1) + '-' + pad(d);
            let cls = 'rc-day', off = false, title = '';
            if (k < today) { cls += ' is-past'; off = true; }
            else if (days && taken.has(k)) { cls += ' is-taken'; off = true; title = 'Already booked at your time'; }
            else if (days && pickStep === 'end' && k > addDays(s, SAFETY - 1)) { cls += ' is-past'; off = true; }
            if (k === s) cls += ' is-start';
            if (days && e && k === e) cls += ' is-end';
            if (days && e && k > s && k < e) cls += ' is-in';
            h += '<button type="button" class="' + cls + '" data-d="' + k + '"' + (off ? ' disabled' : '') + (title ? ' title="' + title + '"' : '') + '>' + d + '</button>';
        }
        h += '</div>';
        if (days) h += '<p class="rc-note">' + (pickStep === 'end' ? '<b>Now pick the last day.</b> ' : '<b>Pick the first day, then the last day.</b> ') + (picked === null ? 'Choose an arrival time first, then days that are already booked turn grey.' : '<i class="rc-key"></i> Grey days are booked at your time.') + '</p>';
        calEl.innerHTML = h;
    }
    function openCal(open) {
        calEl.hidden = !open; dateBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) { pickStep = 'start'; view = parse(dateIn.value || ymd(new Date())); view.setDate(1); renderCal(); if (mode === 'days') loadTaken(); }
    }
    function dateChanged() { span = null; note = ''; load(); checkSpan(); }
    function setMode(m) {
        if (m === mode) return;
        mode = m; modeIn.value = m; note = '';
        if (m === 'hour') { endIn.value = ''; pendingEnd = false; } else { pendingEnd = !endIn.value; }
        document.querySelectorAll('.bf-tab').forEach(b => { const on = b.dataset.mode === m; b.classList.toggle('is-on', on); b.setAttribute('aria-selected', on ? 'true' : 'false'); });
        $('chips').hidden = m !== 'days';
        openCal(false); checkSpan(); summary();
    }
    document.querySelectorAll('.bf-tab').forEach(b => b.addEventListener('click', () => setMode(b.dataset.mode)));
    dateBtn.addEventListener('click', () => openCal(calEl.hidden));
    document.addEventListener('click', e => { if (!e.target.isConnected) return; if (!calEl.hidden && !e.target.closest('#whenSec')) openCal(false); });   // a re-rendered day button is detached by now: not an outside click
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !calEl.hidden) { openCal(false); dateBtn.focus(); } });
    calEl.addEventListener('click', e => {
        e.stopPropagation();   // clicks inside the calendar never reach the 'click outside closes' handler
        const nav = e.target.closest('.rc-nav');
        if (nav) { if (nav.disabled) return; view.setMonth(view.getMonth() + (+nav.dataset.nav)); renderCal(); if (mode === 'days') loadTaken(); return; }
        const b = e.target.closest('.rc-day'); if (!b || b.disabled) return;
        const k = b.dataset.d;
        if (mode === 'hour') { dateIn.value = k; openCal(false); dateChanged(); return; }
        if (pickStep === 'end' && k >= dateIn.value) { endIn.value = k; pendingEnd = false; pickStep = 'start'; openCal(false); checkSpan(); summary(); return; }   // second click: last day, closes
        dateIn.value = k; endIn.value = ''; pendingEnd = true; pickStep = 'end'; renderCal(); dateChanged();   // first click: first day, stays open
    });
    document.querySelectorAll('.bf-chip').forEach(b => b.addEventListener('click', () => {
        const s = dateIn.value; if (!s) return;
        let end;
        if (b.dataset.len === 'month') {
            const d = parse(s), tm = new Date(d.getFullYear(), d.getMonth() + 1, 1), last = new Date(tm.getFullYear(), tm.getMonth() + 1, 0).getDate();
            tm.setDate(Math.min(d.getDate(), last)); tm.setDate(tm.getDate() - 1); end = ymd(tm);
        } else end = addDays(s, (+b.dataset.len) - 1);
        endIn.value = end; pendingEnd = false; note = ''; openCal(false); checkSpan(); summary();
    }));

    function load(quiet) {
        const c = court(), d = dateIn.value;
        if (quiet !== true) { info = null; renderSlots(); summary(); }   // live refresh keeps the current times on screen until new ones arrive
        if (!c || !/^\d{4}-\d{2}-\d{2}$/.test(d)) return;
        const key = c.id + d + '|' + people;
        fetch(busyUrl + '?facility_id=' + c.id + '&date=' + d + (c.head ? '&people=' + people : ''), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(j => { if (key !== (court() || {}).id + dateIn.value + '|' + people) return; info = j; infoAt = performance.now(); renderSlots(); summary(); checkSpan(); })
            .catch(() => { slotsEl.innerHTML = '<p class="bf-empty">Could not load times. Refresh the page and try again.</p>'; });
    }

    const chosen = () => {
        const out = extras.filter(e => e.querySelector('input').checked && !e.classList.contains('is-off')).map(e => e.dataset.addon);
        const c = cool.find(r => r.checked && r.value); if (c) out.push(c.value);
        return out;
    };
    // Show only the extras that suit this court, and lights only for evening arrivals.
    // Referees still free for the whole of the chosen arrival + hours (the pool is shared by every court).
    function refFree() {
        if (!info || !info.ref || picked === null) return null;
        const i0 = Math.floor((picked - info.open) / 30), n = Math.ceil(hours * 2);
        let peak = 0;
        for (let i = Math.max(0, i0); i < Math.min(info.ref.length, i0 + n); i++) peak = Math.max(peak, info.ref[i]);
        return (info.refPool || POOL) - peak;
    }
    // Small badge on the Referee button: how many of the shared referees are free.
    function refBadge() {
        const el = $('refBadge'); if (!el) return;
        let txt = '', tone = 'ok';
        if (info && info.ref && info.ref.length) {
            const pool = info.refPool || POOL;
            if (picked !== null) { const f = refFree(); txt = f <= 0 ? 'None free' : f + ' of ' + pool + ' free'; tone = f <= 0 ? 'none' : f <= 2 ? 'low' : 'ok'; }
            else { const best = pool - Math.min.apply(null, info.ref); txt = best <= 0 ? 'None free today' : 'Up to ' + best + ' free'; tone = best <= 0 ? 'none' : best <= 2 ? 'low' : 'ok'; }
        }
        el.hidden = !txt; el.textContent = txt; el.className = 'bf-ref-badge tone-' + tone;
    }
    function syncAddons(c) {
        // Each court offers the add-ons the admin picked for it; air-conditioning also needs an indoor court.
        const none = cool.find(x => x.value === '');
        cool.forEach(r => {
            if (r.value === '') return;
            const gone = !c || c.addons.indexOf(r.value) < 0 || (r.dataset.indoor === '1' && c.outdoor);
            r.disabled = gone; r.closest('label').hidden = gone;
            if (gone && r.checked) none.checked = true;
        });
        const head = !!c && c.head;
        $('coolBox').hidden = head || !cool.some(r => r.value && !r.disabled);
        if (head && none) none.checked = true;
        extras.forEach(e => {
            const small = e.querySelector('small');
            const wrongType = !c || c.addons.indexOf(e.dataset.addon) < 0;
            const dark = e.dataset.evening === '1' && (picked === null || picked < EVENING);
            let blocked = false, msg = null;
            if (e.dataset.addon === 'referee' && !wrongType) {
                const f = refFree();
                if (f === null) msg = 'Choose an arrival time to see the free referees';
                else if (f <= 0) { blocked = true; msg = 'All ' + POOL + ' referees are booked at this time'; }
                else msg = isStay() ? 'Needed every day of your stay (' + f + ' free on the first day)' : null;
            }
            const off = dark || blocked, input = e.querySelector('input');
            if (e.dataset.addon === 'referee') refBadge();
            if (blocked && input.checked) note = 'The referee is not free for the whole time, so he was removed from your booking.';
            e.hidden = !!wrongType;
            e.classList.toggle('is-off', off);
            input.disabled = off || !!wrongType;
            if (off || wrongType) input.checked = false;
            small.textContent = dark ? (picked === null ? 'Choose an arrival time first (evening only)' : 'Not available for morning or afternoon') : (msg !== null ? msg : small.dataset.note);
        });
    }

    function summary() {
        const c = court();
        $('sFacility').textContent = c ? c.name : '–';
        const dv = dateIn.value, dObj = dv ? new Date(dv + 'T00:00') : null;
        $('sDate').textContent = dObj && !isNaN(dObj) ? dObj.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }) : '–';
        const dl = dObj && !isNaN(dObj) ? ((info && dv === info.today) ? 'Today' : dObj.toLocaleDateString([], { weekday: 'short' })) + ', ' + dObj.toLocaleDateString([], { month: 'short', day: 'numeric' }) : '';
        $('openLbl').textContent = dl + (info ? ' · Open ' + clock(info.open).replace(':00', '') + ' – ' + clock(info.close).replace(':00', '') : '');
        $('sDateLbl').textContent = isStay() ? 'First day' : 'Date';
        $('dateBtnText').textContent = mode === 'days'
            ? (dObj && !isNaN(dObj) ? dShort(dv) : 'Pick a date') + ' → ' + (isStay() ? dShort(endIn.value) + ' · ' + plural(nDays(), 'day') : endIn.value ? dShort(endIn.value) + ' · 1 day' : 'pick the last day')
            : (dObj && !isNaN(dObj) ? dObj.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric' }) : 'Pick a date');
        $('chips').hidden = mode !== 'days';
        $('hrTitle').textContent = mode === 'days' ? 'Hours per day' : 'How long?';
        $('sStart').textContent = picked !== null ? clock(picked) : '–';
        if (isStay()) {
            $('sEnd').textContent = picked !== null && dObj ? clock(picked + hours * 60) + ' each day, until ' + lastDate().toLocaleDateString([], { month: 'short', day: 'numeric' }) : '–';
        } else {
            $('sEnd').textContent = picked !== null ? clock(picked + hours * 60) : '–';
        }
        $('sDuration').textContent = lenLabel();
        const bh = hours, dn = nDays();
        const head = !!c && c.head, m = head ? people : 1;
        $('headSec').hidden = !head; $('suggestSec').hidden = head; $('people').disabled = !head; $('people').value = people;
        $('sPeopleRow').hidden = !head; $('sPeople').textContent = people + (people === 1 ? ' person' : ' people');
        $('sCourtLbl').textContent = head ? 'Gym access' : 'Court';
        $('pValue').textContent = people; $('pMinus').disabled = people <= 1; $('pPlus').disabled = !head || people >= c.max;
        $('headHint').textContent = head ? 'Up to ' + c.max + ' people at a time. ' + peso(c.price) + ' per person, per hour.' : '';
        $('sRate').textContent = c ? '(' + (head ? people + (people === 1 ? ' person × ' : ' people × ') : '') + (dn > 1 ? dn + ' days × ' : '') + fmtHrs(bh) + ' × ' + peso(c.price).replace('.00', '') + ')' : '';
        $('sCourt').textContent = c ? peso(c.price * bh * m * dn) : '–';
        syncAddons(c);
        let add = 0, rows = '';
        chosen().forEach(k => {
            const a = ADDONS[k], amt = (a.per === 'hour' ? a.price * bh : a.per === 'person' ? a.price * m : a.per === 'person_hour' ? a.price * m * bh : a.price) * dn; add += amt;
            const sub = a.per === 'hour' ? '(' + (dn > 1 ? dn + ' days × ' : '') + fmtHrs(bh) + ' × ₱' + a.price + ')' : a.per === 'person' ? '(' + m + (m === 1 ? ' person' : ' people') + ' × ₱' + a.price + ')' : '(flat)';
            rows += '<div class="row-line"><dt>' + a.label + ' <span class="sub">' + sub + '</span></dt><dd>' + peso(amt) + '</dd></div>';
        });
        $('sAddons').innerHTML = rows;
        $('sTotal').textContent = c ? peso(c.price * bh * m * dn + add) : '–';
        $('peopleText').textContent = c ? c.people + ' fit comfortably on ' + c.name + '. For your information only.' : 'Choose a court to see how many people it fits.';
        $('peopleBox').classList.toggle('is-on', !!c);

        const hMax = maxH(), hMin = minH();
        if (hours > hMax) hours = hMax;
        if (hours < hMin) hours = hMin;
        hoursIn.value = hours; $('hrValue').textContent = fmtHrs(hours);
        $('hrMinus').disabled = hours <= hMin; $('hrPlus').disabled = hours >= hMax;
        const lim = stayLimitText();
        $('durHint').textContent = isStay()
            ? 'Same arrival time and hours every day, for ' + nDays() + ' days.' + (hours >= hMax ? ' Latest finish is ' + clock(closeMin()) + '.' : '')
            : hours >= capH() ? 'Longest booking reached (' + fmtHrs(capH()) + ' a day).'
            : (hours >= hMax && lim ? lim : (c ? peso(c.price) + (head ? ' per person, per hour.' : ' per hour.') + ' Each + adds one hour.' : 'Courts are rented per hour. Each + adds one hour.'));
        const sum = $('lenSum');
        sum.hidden = !isStay();
        if (isStay()) sum.innerHTML = picked !== null && dObj
            ? '<i class="bi bi-calendar2-week"></i> <b>' + clock(picked) + ' – ' + clock(picked + hours * 60) + '</b> every day, from ' + dObj.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ' to ' + lastDate().toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric' }) + ' (' + nDays() + ' days).'
            : '<i class="bi bi-calendar2-week"></i> Choose an arrival time. It repeats every day of your stay.';

        let ready = !!c && picked !== null && /^\d{4}-\d{2}-\d{2}$/.test(dv);
        let stayMsg = '';
        if (ready && mode === 'days' && !endIn.value) { ready = false; stayMsg = 'Pick the last day on the calendar.'; }
        else if (ready && isStay()) {
            if (!span) { ready = false; stayMsg = 'Checking that the whole stay is free…'; }
            else if (span.error) { ready = false; stayMsg = 'Could not check the stay. Refresh the page and try again.'; }
            else if (!span.free) { ready = false; stayMsg = span.clashes && span.clashes.length ? 'Not free on ' + span.clashes.slice(0, 3).join(', ') + (span.clashes.length > 3 ? ' and ' + (span.clashes.length - 3) + ' more' : '') + '. Choose other days or another time.' : 'Some days are not free. Choose other days or another time.'; }
            else if (span.refShort && span.refShort.length) { ready = false; stayMsg = 'No referee is free on ' + span.refShort.slice(0, 3).join(', ') + (span.refShort.length > 3 ? ' and more' : '') + '. Remove the referee or pick other days.'; }
        }
        $('submitBtn').disabled = !ready;
        $('submitHint').textContent = note || stayMsg;
        $('submitHint').hidden = !(note || stayMsg);
        const w = $('waitHint'), wn = span && span.waiting ? span.waiting : 0;
        w.hidden = !wn || !ready;
        if (wn) w.textContent = wn + (wn === 1 ? ' other request is' : ' other requests are') + ' also waiting for this time. The first one the admin approves gets it.';

    }

    acts.forEach(a => a.addEventListener('click', () => { setSport(a.dataset.type); picked = null; timeIn.value = ''; note = ''; load(); }));
    radios.forEach(r => r.addEventListener('change', () => { people = Math.min(people, (court() || {max: people}).max || people); picked = null; timeIn.value = ''; note = ''; span = null; load(); }));
    slotsEl.addEventListener('click', e => {
        const h = e.target.closest('.bf-grp-head');
        if (h && !h.disabled) { openKey = openKey === h.dataset.key ? null : h.dataset.key; renderSlots(); return; }
        const b = e.target.closest('.bf-slot'); if (!b || b.disabled) return;
        picked = +b.dataset.min; timeIn.value = pad(Math.floor(picked / 60)) + ':' + pad(picked % 60); note = '';
        openKey = null; renderSlots(); checkSpan(); summary();
    });
    extras.forEach(e => e.addEventListener('change', () => { checkSpan(); summary(); })); cool.forEach(r => r.addEventListener('change', summary));
    // Gym: changing the head count changes which times still have room, so reload them.
    function setPeople(n) { people = n; note = ''; load(); }
    $('pMinus').addEventListener('click', () => { if (people > 1) setPeople(people - 1); });
    $('pPlus').addEventListener('click', () => { const c = court(); if (c && c.head && people < c.max) setPeople(people + 1); });
    $('hrMinus').addEventListener('click', () => { if (hours > minH()) { hours--; note = ''; checkSpan(); summary(); } });
    $('hrPlus').addEventListener('click', () => { if (hours < maxH()) { hours++; note = ''; checkSpan(); summary(); } });
    // Keep "passed" slots current while the page stays open.
    setInterval(() => { if (info) { renderSlots(); summary(); } }, 30000);

    if (picked !== null) openKey = (GROUPS.find(g => picked >= g[2] && picked < g[3]) || [null])[0];
    setSport(sport);
    $('chips').hidden = mode !== 'days';
    load();
    document.addEventListener('live:changed', () => load(true));   // someone else's booking changes which times are free
})();
</script>
<?= $this->endSection() ?>
