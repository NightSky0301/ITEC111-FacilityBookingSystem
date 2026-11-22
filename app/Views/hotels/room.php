<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $guest = ! session()->get('user_id');
    $price = (float) $room['price_per_hour'];
    $cap   = max(1, (int) $room['capacity']);
    $cin   = date('g:i A', strtotime($hotel['check_in_time']));
    $cout  = date('g:i A', strtotime($hotel['check_out_time']));
    $min   = \App\Models\HotelModel::MAX_NIGHTS;
?>
<nav class="ht-crumbs" aria-label="Breadcrumb">
    <a href="<?= site_url('hotels/' . $hotel['id']) ?>"><i class="bi bi-arrow-left"></i> <?= esc($hotel['name']) ?></a>
</nav>

<div class="rm">
    <div class="rm-main">
        <div class="rm-photo" style="background-image:url('<?= esc(facility_image($room), 'attr') ?>')" role="img" aria-label="<?= esc($room['name'], 'attr') ?>"></div>
        <h1 class="rm-title"><?= esc($room['name']) ?></h1>
        <p class="rm-hotel"><i class="bi bi-building"></i> <?= esc($hotel['name']) ?></p>
        <p class="rm-desc"><?= esc($room['description']) ?></p>
        <ul class="ht-facts">
            <li><i class="bi bi-people"></i><span><b>Sleeps <?= $cap ?></b>Up to <?= $cap ?> <?= $cap === 1 ? 'guest' : 'guests' ?></span></li>
            <li><i class="bi bi-box-arrow-in-right"></i><span><b>Check-in from <?= esc($cin) ?></b>On your first day</span></li>
            <li><i class="bi bi-box-arrow-right"></i><span><b>Check-out by <?= esc($cout) ?></b>On your last day</span></li>
        </ul>
    </div>

    <aside class="rm-side">
        <form class="hb" id="hbForm" method="post" action="<?= site_url('hotels/book') ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="room_id" value="<?= (int) $room['id'] ?>">
            <input type="hidden" name="check_in" id="hbIn" value="<?= esc(old('check_in'), 'attr') ?>">
            <input type="hidden" name="check_out" id="hbOut" value="<?= esc(old('check_out'), 'attr') ?>">

            <div class="hb-price"><b>₱<?= number_format($price, 0) ?></b> <span>per night</span></div>

            <div class="hb-dates">
                <div class="hb-date" id="hbInBox"><span>Check-in</span><b id="hbInTxt">Choose a date</b></div>
                <div class="hb-date" id="hbOutBox"><span>Check-out</span><b id="hbOutTxt">Choose a date</b></div>
            </div>

            <div class="rg" id="rg" aria-label="Choose your dates">
                <div class="rg-head">
                    <button type="button" class="rg-nav" id="rgPrev" aria-label="Previous month"><i class="bi bi-chevron-left"></i></button>
                    <div class="rg-title" id="rgTitle" aria-live="polite"></div>
                    <button type="button" class="rg-nav" id="rgNext" aria-label="Next month"><i class="bi bi-chevron-right"></i></button>
                </div>
                <div class="rg-grid" id="rgGrid"></div>
                <div class="rg-key">
                    <span><i class="rg-k rg-k-sel"></i> Your stay</span>
                    <span><i class="rg-k rg-k-taken"></i> Booked</span>
                </div>
            </div>
            <p class="hb-hint" id="hbHint" role="status">Choose your check-in date.</p>

            <div class="hb-row">
                <label for="hbGuests">Guests</label>
                <div class="hb-step">
                    <button type="button" id="hbMinus" aria-label="Fewer guests"><i class="bi bi-dash"></i></button>
                    <input type="number" id="hbGuests" name="guests" min="1" max="<?= $cap ?>" value="<?= (int) old('guests', 1) ?>" readonly>
                    <button type="button" id="hbPlus" aria-label="More guests"><i class="bi bi-plus"></i></button>
                </div>
            </div>
            <div class="hb-field">
                <label for="hbReq">Special requests <small>(optional)</small></label>
                <textarea id="hbReq" name="requests" rows="2" maxlength="255" placeholder="Late arrival, extra bed, quiet floor"><?= esc(old('requests')) ?></textarea>
            </div>

            <dl class="hb-sum" id="hbSum" hidden>
                <div><dt id="hbSumLine"></dt><dd id="hbSumVal"></dd></div>
                <div class="hb-total"><dt>Total</dt><dd id="hbTotal"></dd></div>
            </dl>

            <?php if ($guest): ?>
                <a class="btn hb-go" href="<?= site_url('login') ?>"><i class="bi bi-person-circle"></i> Log in to book</a>
            <?php else: ?>
                <button class="btn hb-go" id="hbGo" type="submit" disabled><i class="bi bi-calendar2-check"></i> Request booking</button>
            <?php endif; ?>
            <p class="hb-note"><i class="bi bi-shield-check"></i> No payment needed. Staff will confirm your request.</p>
        </form>
    </aside>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const PRICE = <?= json_encode($price) ?>, CAP = <?= $cap ?>, MAXN = <?= (int) $min ?>, AHEAD = <?= (int) \App\Models\HotelModel::MAX_AHEAD_DAYS ?>;
    const TAKEN = <?= json_encode($taken) ?>;           // [[check-in, check-out], ...] already approved
    const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const DOW = ['Su','Mo','Tu','We','Th','Fr','Sa'];
    const pad = n => String(n).padStart(2, '0');
    const iso = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    const parse = s => { const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s || ''); return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null; };
    const addDays = (d, n) => { const x = new Date(d); x.setDate(x.getDate() + n); return x; };
    const nice = d => d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    const peso = n => '₱' + n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

    const today = new Date(); today.setHours(0, 0, 0, 0);
    const last = addDays(today, AHEAD);
    const nights = new Set();                            // every taken night (the day a stay starts, up to the day before it ends)
    TAKEN.forEach(([a, b]) => { for (let d = parse(a); d < parse(b); d = addDays(d, 1)) nights.add(iso(d)); });
    const free = d => !nights.has(iso(d));

    const inEl = document.getElementById('hbIn'), outEl = document.getElementById('hbOut');
    let cin = parse(inEl.value), cout = parse(outEl.value);
    if (cin && (cin < today || !free(cin))) { cin = null; cout = null; }
    if (cin && cout && !(cout > cin)) cout = null;
    let view = new Date((cin || today).getFullYear(), (cin || today).getMonth(), 1);

    // Can the stay run from cin to d? Every night in between must be free and the stay not too long.
    function canEnd(d) {
        if (!cin || d <= cin) return false;
        if ((d - cin) / 864e5 > MAXN) return false;
        for (let x = new Date(cin); x < d; x = addDays(x, 1)) if (!free(x)) return false;
        return true;
    }

    const grid = document.getElementById('rgGrid');
    function draw() {
        const y = view.getFullYear(), m = view.getMonth(), first = new Date(y, m, 1).getDay(), days = new Date(y, m + 1, 0).getDate();
        document.getElementById('rgTitle').textContent = MONTHS[m] + ' ' + y;
        document.getElementById('rgPrev').disabled = new Date(y, m, 1) <= new Date(today.getFullYear(), today.getMonth(), 1);
        document.getElementById('rgNext').disabled = new Date(y, m + 1, 1) > last;
        const picking = cin && !cout;
        let h = DOW.map(d => '<span class="rg-dow">' + d + '</span>').join('');
        for (let k = 0; k < first; k++) h += '<span></span>';
        for (let d = 1; d <= days; d++) {
            const dt = new Date(y, m, d), cls = ['rg-day']; let ok;
            if (dt < today || dt > last) { ok = false; cls.push('is-past'); }
            else if (picking) { ok = canEnd(dt) || (free(dt) && dt < cin); if (!free(dt) && !canEnd(dt)) cls.push('is-taken'); }
            else { ok = free(dt); if (!ok) cls.push('is-taken'); }
            if (cin && +dt === +cin) cls.push('is-in', cout ? 'has-range' : 'is-solo');
            else if (cout && +dt === +cout) cls.push('is-out');
            else if (cin && cout && dt > cin && dt < cout) cls.push('is-mid');
            h += '<button type="button" class="' + cls.join(' ') + '" data-d="' + iso(dt) + '"' + (ok ? '' : ' disabled') + '>' + d + '</button>';
        }
        grid.innerHTML = h;
    }

    function summary() {
        const go = document.getElementById('hbGo'), sum = document.getElementById('hbSum'), hint = document.getElementById('hbHint');
        document.getElementById('hbInTxt').textContent = cin ? nice(cin) : 'Choose a date';
        document.getElementById('hbOutTxt').textContent = cout ? nice(cout) : 'Choose a date';
        document.getElementById('hbInBox').classList.toggle('is-on', !!cin && !cout);
        document.getElementById('hbOutBox').classList.toggle('is-on', !!cin && !cout);
        inEl.value = cin ? iso(cin) : ''; outEl.value = cout ? iso(cout) : '';
        if (cin && cout) {
            const n = Math.round((cout - cin) / 864e5);
            document.getElementById('hbSumLine').textContent = peso(PRICE) + ' x ' + n + (n === 1 ? ' night' : ' nights');
            document.getElementById('hbSumVal').textContent = peso(PRICE * n);
            document.getElementById('hbTotal').textContent = peso(PRICE * n);
            sum.hidden = false; hint.textContent = n + (n === 1 ? ' night' : ' nights') + ', ' + nice(cin) + ' to ' + nice(cout) + '.';
            if (go) go.disabled = false;
        } else {
            sum.hidden = true; hint.textContent = cin ? 'Now choose your check-out date.' : 'Choose your check-in date.';
            if (go) go.disabled = true;
        }
    }

    grid.addEventListener('click', function (e) {
        const b = e.target.closest('.rg-day'); if (!b || b.disabled) return;
        const d = parse(b.dataset.d);
        if (cin && !cout && canEnd(d)) cout = d;
        else { cin = d; cout = null; }
        draw(); summary();
        const f = grid.querySelector('[data-d="' + b.dataset.d + '"]'); if (f) f.focus();
    });
    document.getElementById('rgPrev').addEventListener('click', () => { view = new Date(view.getFullYear(), view.getMonth() - 1, 1); draw(); });
    document.getElementById('rgNext').addEventListener('click', () => { view = new Date(view.getFullYear(), view.getMonth() + 1, 1); draw(); });

    const gi = document.getElementById('hbGuests');
    gi.value = Math.min(CAP, Math.max(1, parseInt(gi.value, 10) || 1));
    document.getElementById('hbMinus').addEventListener('click', () => { gi.value = Math.max(1, +gi.value - 1); });
    document.getElementById('hbPlus').addEventListener('click', () => { gi.value = Math.min(CAP, +gi.value + 1); });

    document.getElementById('hbForm').addEventListener('submit', function (e) { if (!(cin && cout)) e.preventDefault(); });
    draw(); summary();
})();
</script>
<?= $this->endSection() ?>
