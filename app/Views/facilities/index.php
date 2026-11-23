<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    helper('booking');
    $total     = count($items);
    $dateQs    = $date !== '' ? '?date=' . rawurlencode($date) : '';
    $dateTxt   = $date !== '' ? date('D, M j', strtotime($date)) : '';
    $freeCount = count(array_filter($items, static fn ($c) => $c['free']));
    $fitness   = $section === 'fitness';
    $first     = $items[0] ?? null;
    $hasSide   = count($chips) > 1;
?>
<header class="ax-hero">
    <div>
        <h1><?= esc($heading) ?></h1>
        <p><?= $dateTxt !== '' ? 'Pick one to see its schedule for ' . esc($dateTxt) . '.' : esc($lead) ?></p>
    </div>
    <?php if ($total): ?>
    <div class="ax-stats">
        <span class="ax-stat"><span class="fn-dot" aria-hidden="true"></span> <span id="axFreeStat"><?= $freeCount ?></span>&nbsp;free now</span>
        <span class="ax-stat"><i class="bi <?= $fitness ? 'bi-heart-pulse' : 'bi-grid' ?>" aria-hidden="true"></i> <?= $total ?> <?= $fitness ? ($total === 1 ? 'space' : 'spaces') : ($total === 1 ? 'court' : 'courts') ?></span>
    </div>
    <?php endif; ?>
</header>

<?php if (empty($items)): ?>
    <div class="panel"><div class="empty"><i class="bi bi-geo-alt"></i><strong>Nothing here yet</strong>The facility manager has not added any yet.</div></div>
<?php else: ?>

<?php if ($fitness && $first): ?>
<section class="gy-feature" aria-label="About Fitness">
    <div class="gy-photo" style="background-image:url('<?= esc($first['img'], 'attr') ?>')" role="img" aria-label="<?= esc($first['name'], 'attr') ?>"></div>
    <div class="gy-copy">
        <h2>Train, swim and dance on your schedule</h2>
        <p>Work out in the gym, swim laps or reserve a mirrored dance studio. Choose a start time and how long you want.</p>
        <ul class="gy-perks">
            <li><i class="bi bi-person-check" aria-hidden="true"></i> Gym booked per person</li>
            <li><i class="bi bi-water" aria-hidden="true"></i> Pools and studios by the hour</li>
            <li><i class="bi bi-clock" aria-hidden="true"></i> Pick any open hour</li>
            <li><i class="bi bi-patch-check" aria-hidden="true"></i> Confirmed by staff</li>
        </ul>
        <a class="btn btn-gold" href="#fxGrid">Choose a space <i class="bi bi-arrow-down ms-1"></i></a>
    </div>
</section>
<?php endif; ?>

<div class="<?= $hasSide ? 'ax-layout' : '' ?>">
<?php if ($hasSide): ?>
    <aside class="ax-side" aria-label="Filter activities">
        <h2>Activity</h2>
        <div class="ax-cats" role="group" aria-label="Choose an activity">
            <button type="button" class="ax-cat <?= $selType === '' ? 'is-on' : '' ?>" data-type="" data-label="">
                <span class="ax-cat-ico"><i class="bi bi-grid-fill" aria-hidden="true"></i></span>All activities<span class="ax-cat-n"><?= $total ?></span>
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
    const noun = <?= $fitness ? "['space', 'spaces']" : "['court', 'courts']" ?>;
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
        if (stat) stat.textContent = cards.filter(c => c.dataset.free === '1').length;
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
