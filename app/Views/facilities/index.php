<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $total   = count($items);
    $dateQs  = $date !== '' ? '?date=' . rawurlencode($date) : '';
    $dateTxt = $date !== '' ? date('D, M j', strtotime($date)) : '';
?>
<header class="fx-head">
    <div>
        <h1><?= esc($heading) ?></h1>
        <p><?= $dateTxt !== '' ? 'Pick one to see its schedule for ' . esc($dateTxt) . '.' : esc($lead) ?></p>
    </div>
</header>

<?php if (empty($items)): ?>
    <div class="panel"><div class="empty"><i class="bi bi-geo-alt"></i><strong>Nothing here yet</strong>The facility manager has not added any yet.</div></div>
<?php else: ?>
<div class="fx-rail" role="group" aria-label="Choose an activity"<?= count($chips) < 2 ? ' hidden' : '' ?>>
    <button type="button" class="fx-chip fx-cat <?= $selType === '' ? 'is-on' : '' ?>" data-type="" data-label="all courts">
        <span class="fx-cat-ico"><i class="bi bi-grid-fill" aria-hidden="true"></i></span>
        <span class="fx-cat-name">All</span><span class="fx-cat-n"><?= $total ?></span>
    </button>
    <?php foreach ($chips as $k => $c): ?>
        <button type="button" class="fx-chip fx-cat <?= $selType === $k ? 'is-on' : '' ?>" data-type="<?= esc($k, 'attr') ?>" data-label="<?= esc(strtolower($c['name']), 'attr') ?>">
            <span class="fx-cat-ico"><i class="bi <?= esc(facility_icon($k), 'attr') ?>" aria-hidden="true"></i></span>
            <span class="fx-cat-name"><?= esc($c['name']) ?></span><span class="fx-cat-n"><?= (int) $c['count'] ?></span>
        </button>
    <?php endforeach; ?>
</div>

<div class="fx-bar">
    <p class="fx-count" id="fxCount" aria-live="polite"></p>
    <div class="fx-tools">
        <label class="fx-switch"><input type="checkbox" id="fxFree"><span class="fx-track" aria-hidden="true"></span> Free now</label>
        <select id="fxSort" data-dd aria-label="Sort courts">
            <option value="name">Sort: Name</option>
            <option value="low">Price: low to high</option>
            <option value="high">Price: high to low</option>
        </select>
    </div>
</div>

<div class="fx-grid" id="fxGrid">
    <?php foreach ($items as $c): ?>
        <a class="fx-card" href="<?= site_url('facilities/court/' . $c['id']) . $dateQs ?>"
           data-type="<?= esc($c['type'], 'attr') ?>" data-id="<?= (int) $c['id'] ?>" data-free="<?= $c['free'] ? '1' : '0' ?>" data-price="<?= (float) $c['price'] ?>" data-name="<?= esc($c['name'], 'attr') ?>">
            <span class="fx-photo" style="background-image:url('<?= esc($c['img'], 'attr') ?>')">
                <span class="fx-tag"><?= $c['outdoor'] ? 'Outdoor' : 'Indoor' ?></span>
                <span class="fx-live <?= $c['free'] ? 'is-free' : 'is-busy' ?>" data-live><i></i><b><?= $c['free'] ? 'Free now' : 'In use' ?></b></span>
            </span>
            <span class="fx-body">
                <span class="fx-name"><?= esc($c['name']) ?></span>
                <span class="fx-sub"><?= esc($c['activity']) ?> &middot; <i class="bi bi-people"></i> <?= esc($c['people']) ?></span>
                <span class="fx-price">₱<?= number_format($c['price'], 0) ?> <small><?= $c['per_head'] ? 'per person, per hour' : 'per hour' ?></small></span>
            </span>
        </a>
    <?php endforeach; ?>
</div>

<div class="fx-none is-hidden" id="fxNone">
    <i class="bi bi-search"></i>
    <strong>No courts match</strong>
    <span>Try another activity, or turn off "Free now".</span>
    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="fxReset">Show all courts</button>
</div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const grid = document.getElementById('fxGrid'); if (!grid) return;
    const cards = Array.from(grid.children), none = document.getElementById('fxNone');
    const free = document.getElementById('fxFree'), sort = document.getElementById('fxSort');
    const chips = Array.from(document.querySelectorAll('.fx-chip')), count = document.getElementById('fxCount');
    let type = (chips.find(c => c.classList.contains('is-on')) || chips[0] || { dataset: { type: '' } }).dataset.type;

    function apply() {
        const order = cards.slice().sort((a, b) => {
            if (sort.value === 'low') return a.dataset.price - b.dataset.price || a.dataset.name.localeCompare(b.dataset.name);
            if (sort.value === 'high') return b.dataset.price - a.dataset.price || a.dataset.name.localeCompare(b.dataset.name);
            return a.dataset.name.localeCompare(b.dataset.name, undefined, { numeric: true });
        });
        let shown = 0;
        order.forEach(c => {
            const ok = (!type || c.dataset.type === type) && (!free.checked || c.dataset.free === '1');
            c.hidden = !ok; if (ok) shown++;
            grid.appendChild(c);
        });
        chips.forEach(c => c.classList.toggle('is-on', c.dataset.type === type));
        none.classList.toggle('is-hidden', shown > 0);
        const cur = chips.find(c => c.dataset.type === type), lab = cur && type ? cur.dataset.label : '';
        count.innerHTML = '<b>' + shown + '</b> ' + (lab ? lab + ' ' : '') + (shown === 1 ? 'court' : 'courts') + (free.checked ? ' free now' : '');
    }
    chips.forEach(c => c.addEventListener('click', () => { type = c.dataset.type; apply(); }));
    free.addEventListener('change', apply); sort.addEventListener('change', apply);
    document.getElementById('fxReset').addEventListener('click', () => { type = ''; free.checked = false; apply(); });
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
                    live.className = 'fx-live ' + (f ? 'is-free' : 'is-busy'); live.querySelector('b').textContent = f ? 'Free now' : 'In use';
                });
                if (free.checked) apply();
            }).catch(() => {});
    }
    setInterval(tick, 30000); document.addEventListener('visibilitychange', tick);
})();
</script>
<?= $this->endSection() ?>
