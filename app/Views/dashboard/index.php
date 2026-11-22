<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $guest     = ! empty($guest);
    $first     = explode(' ', trim((string) session()->get('name')))[0];
    $manager   = is_manager();
    $statsUrl  = $manager ? site_url('manage/bookings') : site_url('my-bookings');
    $facUrl    = is_admin() ? site_url('admin/facilities') : site_url('facilities');
?>
<?php if (! $manager): ?>
<?= $this->include('dashboard/_hero') ?>
<?php else: ?>
<header class="dash-head">
    <div>
        <div class="dash-date"><?= esc(date('l, F j')) ?></div>
        <h1>Hello, <?= esc($first) ?></h1>
        <p><?= $manager ? 'Here is what needs your attention today.' : 'Pick a court and a time, and we will send your request.' ?></p>
    </div>
    <div class="page-actions">
        <?php if ($manager): ?>
            <a href="<?= site_url('facilities') ?>" class="btn btn-gold"><i class="bi bi-plus-lg me-1"></i> New booking</a>
            <a href="<?= site_url('manage/bookings?status=pending') ?>" class="btn btn-gold"><i class="bi bi-inbox me-1"></i> Review requests</a>
        <?php else: ?>
            <a href="<?= site_url('facilities') ?>" class="btn btn-gold"><i class="bi bi-plus-lg me-1"></i> New booking</a>
        <?php endif; ?>
    </div>
</header>
<?php endif; ?>

<?php if ($manager): ?>
<div class="stat-grid is-three" id="live-stats" data-live>
    <a class="stat-card is-wait <?= (int) $pending > 0 ? 'has-items' : '' ?>" href="<?= $manager ? site_url('manage/bookings?status=pending') : site_url('my-bookings?filter=pending') ?>">
        <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
        <div class="stat-body">
            <div class="stat-num"><?= (int) $pending ?></div>
            <div class="stat-label"><?= $manager ? 'Requests waiting for review' : 'Waiting for approval' ?></div>
        </div>
        <i class="bi bi-arrow-right stat-go" aria-hidden="true"></i>
    </a>

    <a class="stat-card is-ok" href="<?= $manager ? site_url('manage/bookings?status=approved') : site_url('my-bookings?filter=approved') ?>">
        <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
        <div class="stat-body">
            <div class="stat-num"><?= (int) $approved ?></div>
            <div class="stat-label">Approved bookings</div>
        </div>
        <i class="bi bi-arrow-right stat-go" aria-hidden="true"></i>
    </a>

    <?php if ($manager): ?>
    <a class="stat-card is-teal" href="<?= $facUrl ?>">
        <span class="stat-icon"><i class="bi bi-grid-3x3-gap"></i></span>
        <div class="stat-body">
            <div class="stat-num"><?= (int) $facilities ?></div>
            <div class="stat-label">Active courts</div>
        </div>
        <i class="bi bi-arrow-right stat-go" aria-hidden="true"></i>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<div id="live-next" data-live>
<?php if (! $guest && ! $manager && ! empty($next)):
    $ns = strtotime($next['start_at']);
    $ne = strtotime($next['end_at']);
    $now = time();
    if ($ns <= $now && $now < $ne) {
        $nextRel = 'Happening now';
    } else {
        $days = (int) (new \DateTime(date('Y-m-d', $now)))->diff(new \DateTime(date('Y-m-d', $ns)))->format('%r%a');
        $nextRel = $days === 0 ? 'Today' : ($days === 1 ? 'Tomorrow' : 'In ' . $days . ' days');
    }
?>
<section class="bk-next" aria-label="Your next session">
    <span class="bk-next-icon"><i class="bi bi-calendar-event"></i></span>
    <div class="bk-next-body">
        <div class="bk-next-kicker">Next session · <?= esc($nextRel) ?></div>
        <h2 class="bk-next-title"><?= esc($next['facility_name']) ?></h2>
        <div class="bk-next-when"><?= esc(fmt_range($next['start_at'], $next['end_at'])) ?></div>
    </div>
    <a class="btn btn-sm btn-light-on-dark" href="<?= site_url('calendar') ?>?<?= esc(http_build_query(['date' => date('Y-m-d', $ns), 'booking' => (int) $next['id']]), 'attr') ?>">Court schedule <i class="bi bi-arrow-right ms-1"></i></a>
</section>
<?php endif; ?>
</div>

<?php if (! $manager): ?>
<?= $this->include('dashboard/_freenow') ?>
<?php endif; ?>

<?php if ($manager): ?>
<?= $this->include('dashboard/_sessions') ?>
<?php elseif (! $guest): ?>
<?= $this->include('dashboard/_recent') ?>
<?php endif; ?>
<?php if (! $manager): ?>
<?= $this->include('dashboard/_why') ?>
<?php endif; ?>
<?= $this->endSection() ?>

<?php if (! $manager): ?>
<?= $this->section('scripts') ?>
<script>
// Banner: a new court photo every 3 seconds. Dots jump to a photo; hovering or focusing pauses it.
(function () {
    const hx = document.querySelector('.hx');
    if (!hx) return;
    const slides = Array.from(hx.querySelectorAll('.hx-photo')), dots = Array.from(hx.querySelectorAll('.hx-dot'));
    if (slides.length < 2) return;
    let i = 0, timer = null, hold = false;
    const still = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

    function show(n) {
        i = (n + slides.length) % slides.length;
        slides.forEach((s, k) => s.classList.toggle('is-on', k === i));
        dots.forEach((d, k) => { d.classList.toggle('is-on', k === i); d.setAttribute('aria-current', k === i ? 'true' : 'false'); });
    }
    function start() { clearInterval(timer); if (!still) timer = setInterval(() => { if (!hold && !document.hidden) show(i + 1); }, 3000); }

    dots.forEach((d, k) => d.addEventListener('click', () => { show(k); start(); }));
    ['mouseenter', 'focusin'].forEach(e => hx.addEventListener(e, () => { hold = true; }));
    ['mouseleave', 'focusout'].forEach(e => hx.addEventListener(e, () => { hold = false; }));
    window.addEventListener('load', () => slides.forEach(s => { const im = new Image(); im.src = s.dataset.src; }));  // no flash on the first lap
    start();
})();

// Keep the two shelves current without reloading the page.
(function () {
    const shelves = { free: document.querySelector('[data-shelf="free"]'), top: document.querySelector('[data-shelf="top"]') };
    if (!shelves.free || !shelves.top) return;
    async function tick() {
        if (document.hidden) return;
        try {
            const res = await fetch('<?= site_url('dashboard/live') ?>', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!res.ok) return;
            const d = await res.json();
            Object.keys(shelves).forEach(function (k) {
                if (typeof d[k] === 'string' && d[k].trim() !== shelves[k].innerHTML.trim()) shelves[k].innerHTML = d[k];
            });
        } catch (e) { /* try again next time */ }
    }
    setInterval(tick, 10000);
    document.addEventListener('visibilitychange', tick);
})();
</script>
<?= $this->endSection() ?>
<?php endif; ?>
