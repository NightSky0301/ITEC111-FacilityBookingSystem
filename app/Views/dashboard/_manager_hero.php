<?php
/** Staff / admin welcome banner: greeting, the date and a one-line summary of what needs attention. */
$h      = (int) date('G');
$greet  = $h < 12 ? 'Good morning' : ($h < 18 ? 'Good afternoon' : 'Good evening');
$first  = explode(' ', trim((string) session()->get('name')))[0];
$who    = is_admin() ? 'Administrator' : $first;
$p      = (int) ($pending ?? 0);
$t      = (int) ($bookedToday ?? 0);
$m      = (int) ($missed ?? 0);
$parts  = [];
$parts[] = $p === 0 ? 'No requests are waiting for approval' : '<b>' . $p . '</b> ' . ($p === 1 ? 'request is' : 'requests are') . ' waiting for approval';
$parts[] = $t === 0 ? 'nothing is booked for today' : '<b>' . $t . '</b> ' . ($t === 1 ? 'booking' : 'bookings') . ' today';
?>
<section class="mh" aria-label="Welcome">
    <div class="mh-glow" aria-hidden="true"></div>
    <div class="mh-main">
        <div class="mh-date"><i class="bi bi-calendar3"></i> <?= esc(date('l, F j, Y')) ?></div>
        <h1><?= esc($greet) ?>, <span><?= esc($who) ?></span></h1>
        <p id="live-hero" data-live><?= implode(', ', $parts) ?><?= $m > 0 ? '. <b class="mh-warn">' . $m . '</b> ' . ($m === 1 ? 'guest has' : 'guests have') . ' not arrived' : '' ?>.</p>
    </div>
    <div class="mh-side" aria-hidden="true">
        <div class="mh-clock" id="mhClock"><?= esc(date('g:i')) ?><small><?= esc(date('A')) ?></small></div>
        <div class="mh-role"><i class="bi bi-shield-check"></i> <?= is_admin() ? 'Administrator' : 'Staff' ?></div>
    </div>
</section>
<script>
(function () {
    const el = document.getElementById('mhClock'); if (!el) return;
    function tick() {
        const d = new Date(), h = d.getHours() % 12 || 12, m = String(d.getMinutes()).padStart(2, '0');
        el.innerHTML = h + ':' + m + '<small>' + (d.getHours() >= 12 ? 'PM' : 'AM') + '</small>';
    }
    tick(); setInterval(tick, 10000);
})();
</script>
