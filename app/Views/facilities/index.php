<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    helper('booking');
    $total     = count($items);
    $dateQs    = $date !== '' ? '?date=' . rawurlencode($date) . (($end ?? '') !== '' ? '&end_date=' . rawurlencode($end) : '') : '';
    $dateTxt   = $date !== '' ? date('D, M j', strtotime($date)) . (($end ?? '') !== '' ? ' to ' . date('D, M j', strtotime($end)) : '') : '';
    $freeCount = count(array_filter($items, static fn ($c) => $c['free']));
    $fitness   = $section === 'fitness';
    $fun       = $section === 'entertainment';
    $spaces    = $fitness || $fun;   // these pages count "spaces", the Activities page counts "courts"
    $first     = $items[0] ?? null;
    $hasSide   = count($chips) > 1;
?>
<?php if ($dateTxt !== ''): ?><p class="ax-dateline"><i class="bi bi-calendar-event" aria-hidden="true"></i> Showing availability for <?= esc($dateTxt) ?></p><?php endif; ?>

<?php if (empty($items)): ?>
    <div class="panel"><div class="empty"><i class="bi bi-geo-alt"></i><strong>Nothing here yet</strong>The facility manager has not added any yet.</div></div>
<?php else: ?>

<?php if ($first):
    $copy = [
        'entertainment' => ['Play, swim, sing and jam', 'Reserve a bowling lane, a pool table, a swimming pool, a dance studio, the music studio or a karaoke room. Choose a start time and how long you want.', [['bi-people', 'Great for groups'], ['bi-controller', 'Games, music and water'], ['bi-clock', 'Pick any open hour'], ['bi-patch-check', 'Confirmed by staff']]],
        'fitness'       => ['Train, stretch and recover on your schedule', 'Work out in the gym, flow through a yoga class, or unwind in a massage room or foot spa lounge. Choose a start time and how long you want.', [['bi-person-check', 'Gym booked per person'], ['bi-flower1', 'Hall and studios by the hour'], ['bi-clock', 'Pick any open hour'], ['bi-patch-check', 'Confirmed by staff']]],
    ][$section] ?? ['Game on, whatever your sport', 'From basketball and badminton to baseball and football, find a court, see what is open and book your time.', [['bi-grid', 'A court for every sport'], ['bi-lightning-charge', 'Live free-now status'], ['bi-clock', 'Pick any open hour'], ['bi-patch-check', 'Confirmed by staff']]];
    // Optional category banner photo: public/assets/img/banners/{sports|fitness|entertainment}.jpg, else the first facility's photo.
    $bn = FCPATH . 'assets/img/banners/' . ($fitness ? 'fitness' : ($fun ? 'entertainment' : 'sports')) . '.jpg';
    $bannerPhoto = is_file($bn) ? base_url('assets/img/banners/' . basename($bn)) . '?v=' . filemtime($bn) : $first['img'];
    echo view('facilities/_banner', ['photo' => $bannerPhoto, 'alt' => $first['name'], 'title' => $copy[0], 'text' => $copy[1], 'perks' => $copy[2], 'free' => $freeCount, 'total' => $total, 'when' => 'now']);
endif; ?>

<div class="<?= $hasSide ? 'ax-layout' : '' ?>">
<?php if ($hasSide): ?>
    <aside class="ax-side" aria-label="Filter activities">
        <h2><?= $fun ? 'Type' : 'Activity' ?></h2>
        <div class="ax-cats" role="group" aria-label="Choose an activity">
            <button type="button" class="ax-cat <?= $selType === '' ? 'is-on' : '' ?>" data-type="" data-label="">
                <span class="ax-cat-ico"><i class="bi bi-grid-fill" aria-hidden="true"></i></span><?= $fun ? 'All' : 'All activities' ?><span class="ax-cat-n"><?= $total ?></span>
            </button>
            <?php foreach ($chips as $k => $c): ?>
            <button type="button" class="ax-cat <?= $selType === $k ? 'is-on' : '' ?>" data-type="<?= esc($k, 'attr') ?>" data-label="<?= esc(strtolower($c['name']), 'attr') ?>">
                <span class="ax-cat-ico"><i class="bi <?= esc(facility_icon($k), 'attr') ?>" aria-hidden="true"></i></span><?= esc($c['name']) ?><span class="ax-cat-n"><?= (int) $c['count'] ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <div class="ax-filters">
            <div class="ax-seg" role="group" aria-label="Indoor or outdoor">
                <button type="button" class="is-on" data-place="">Any</button>
                <button type="button" data-place="indoor">Indoor</button>
                <button type="button" data-place="outdoor">Outdoor</button>
            </div>
        </div>
    </aside>
<?php endif; ?>

    <div class="ax-main">
        <?php if ($hasSide): ?>
        <div class="ax-rail" role="group" aria-label="Choose an activity">
            <button type="button" class="ax-cat <?= $selType === '' ? 'is-on' : '' ?>" data-type="" data-label=""><span class="ax-cat-ico"><i class="bi bi-grid-fill" aria-hidden="true"></i></span>All</button>
            <?php foreach ($chips as $k => $c): ?>
            <button type="button" class="ax-cat <?= $selType === $k ? 'is-on' : '' ?>" data-type="<?= esc($k, 'attr') ?>" data-label="<?= esc(strtolower($c['name']), 'attr') ?>"><span class="ax-cat-ico"><i class="bi <?= esc(facility_icon($k), 'attr') ?>" aria-hidden="true"></i></span><?= esc($c['name']) ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="ax-top">
            <p class="ax-count" id="fxCount" aria-live="polite"></p>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <label class="ax-switch"><input type="checkbox" id="fxFree"><span class="ax-track" aria-hidden="true"></span> Free now</label>
                <select id="fxSort" data-dd class="ax-sort" aria-label="Sort">
                    <option value="name">Sort: Name</option>
                    <option value="low">Price: low to high</option>
                    <option value="high">Price: high to low</option>
                </select>
            </div>
        </div>

        <div class="ax-grid" id="fxGrid">
            <?php foreach ($items as $c): ?>
                <?= view('facilities/_card', ['c' => $c, 'dateQs' => $dateQs]) ?>
            <?php endforeach; ?>
        </div>

        <div class="ax-none is-hidden" id="fxNone">
            <i class="bi bi-search"></i>
            <strong>Nothing matches</strong>
            <span>Try another activity, or turn off "Free now".</span>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="fxReset">Show everything</button>
        </div>
    </div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const grid = document.getElementById('fxGrid'); if (!grid) return;
    const cards = Array.from(grid.children), none = document.getElementById('fxNone');
    const free = document.getElementById('fxFree'), sort = document.getElementById('fxSort');
    const cats = Array.from(document.querySelectorAll('.ax-cat')), count = document.getElementById('fxCount');
    const places = Array.from(document.querySelectorAll('.ax-seg button')), stat = document.getElementById('axFreeStat');
    const noun = <?= $spaces ? "['facility', 'facilities']" : "['court', 'courts']" ?>;
    let type = (cats.find(c => c.classList.contains('is-on')) || { dataset: { type: '' } }).dataset.type, place = '';

    function apply() {
        const order = cards.slice().sort((a, b) => {
            if (sort.value === 'low') return a.dataset.price - b.dataset.price || a.dataset.name.localeCompare(b.dataset.name);
            if (sort.value === 'high') return b.dataset.price - a.dataset.price || a.dataset.name.localeCompare(b.dataset.name);
            return a.dataset.name.localeCompare(b.dataset.name, undefined, { numeric: true });
        });
        let shown = 0;
        order.forEach(c => {
            const ok = (!type || c.dataset.type === type) && (!place || c.dataset.place === place) && (!free.checked || c.dataset.free === '1');
            c.hidden = !ok; if (ok) shown++;
            grid.appendChild(c);
        });
        cats.forEach(c => c.classList.toggle('is-on', c.dataset.type === type));
        places.forEach(b => b.classList.toggle('is-on', b.dataset.place === place));
        none.classList.toggle('is-hidden', shown > 0);
        const cur = cats.find(c => c.dataset.type === type), lab = cur && type ? cur.dataset.label : '';
        count.innerHTML = '<b>' + shown + '</b> ' + (lab ? lab + ' ' : '') + noun[shown === 1 ? 0 : 1] + (place ? ', ' + place : '') + (free.checked ? ', free now' : '');
        if (stat) { const f = cards.filter(c => c.dataset.free === '1').length; stat.textContent = f; const bar = document.getElementById('axFreeBar'); if (bar) bar.style.width = (cards.length ? Math.round(f * 100 / cards.length) : 0) + '%'; }
    }
    cats.forEach(c => c.addEventListener('click', () => { type = c.dataset.type; apply(); }));
    places.forEach(b => b.addEventListener('click', () => { place = b.dataset.place; apply(); }));
    free.addEventListener('change', apply); sort.addEventListener('change', apply);
    document.getElementById('fxReset').addEventListener('click', () => { type = ''; place = ''; free.checked = false; apply(); });
    apply();

    // Keep "Free now" honest without reloading.
    function tick() {
        if (document.hidden) return;
        fetch('<?= site_url('facilities/availability') ?>', { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : null).then(d => {
                if (!d || !d.courts) return;
                cards.forEach(c => {
                    const f = d.courts[c.dataset.id]; if (f === undefined) return;
                    c.dataset.free = f ? '1' : '0';
                    const live = c.querySelector('[data-live]');
                    live.className = 'ax-live ' + (f ? 'is-free' : 'is-busy'); live.querySelector('b').textContent = f ? 'Free now' : 'In use';
                });
                apply();
            }).catch(() => {});
    }
    setInterval(tick, 30000); document.addEventListener('visibilitychange', tick);
})();
</script>
<?= $this->endSection() ?>
