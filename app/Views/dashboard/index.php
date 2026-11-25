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
<?= $this->include('dashboard/_manager_hero') ?>
<?php endif; ?>

<?php if ($manager): ?>
<?= $this->include('dashboard/_manager') ?>
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

<?php if (! $manager && ! $guest): ?>
<?= $this->include('dashboard/_recent') ?>
<?php endif; ?>
<?php if (! $manager): ?>
<?= $this->include('dashboard/_why') ?>
<?php endif; ?>
<?= $this->endSection() ?>

<?php if (! $manager): ?>
<?= $this->section('scripts') ?>
<script>
// Banner: a new court photo every 3.5 seconds, looping. Dots jump to a photo; hovering or focusing pauses it.
(function () {
    const hx = document.querySelector('.hx');
    if (!hx) return;
    const slides = Array.from(hx.querySelectorAll('.hx-photo')), dots = Array.from(hx.querySelectorAll('.hx-dot'));
    if (slides.length < 2) return;
    let i = 0, timer = null, hold = false;

    function show(n) {
        i = (n + slides.length) % slides.length;
        slides.forEach((s, k) => s.classList.toggle('is-on', k === i));
        dots.forEach((d, k) => { d.classList.toggle('is-on', k === i); d.setAttribute('aria-current', k === i ? 'true' : 'false'); });
    }
    function start() { clearInterval(timer); timer = setInterval(() => { if (!hold && !document.hidden) show(i + 1); }, 3500); }

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
