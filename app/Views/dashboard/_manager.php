<?php
/** Staff / admin dashboard: what needs attention right now. */
?>
<div id="live-stats" data-live>
<div class="stat-grid is-four">
    <div class="stat-card is-static is-ok">
        <span class="stat-icon"><i class="bi bi-calendar-day"></i></span>
        <div class="stat-body"><div class="stat-num"><?= (int) $bookedToday ?></div><div class="stat-label">Facilities Booked Today</div></div>
    </div>
    <div class="stat-card is-static is-teal">
        <span class="stat-icon"><i class="bi bi-calendar-month"></i></span>
        <div class="stat-body"><div class="stat-num"><?= (int) $bookedMonth ?></div><div class="stat-label">Facilities Booked this Month</div></div>
    </div>
    <div class="stat-card is-static is-cancel">
        <span class="stat-icon"><i class="bi bi-calendar-x"></i></span>
        <div class="stat-body"><div class="stat-num"><?= (int) $cancelledMonth ?></div><div class="stat-label">Cancelled Facilities</div></div>
    </div>
    <div class="stat-card is-static is-missed-card <?= (int) $missed > 0 ? 'has-items' : '' ?>">
        <span class="stat-icon"><i class="bi bi-person-x"></i></span>
        <div class="stat-body"><div class="stat-num" data-missed-count><?= (int) $missed ?></div><div class="stat-label">Did Not Arrive Today</div></div>
    </div>
</div>

</div>

<?= $this->include('dashboard/_sessions') ?>

<div id="live-queues" data-live>
<?php
$cols = [
    'activities'    => ['Sports Activities', 'bi-trophy'],
    'hotels'        => ['Hotels and Suites', 'bi-building'],
    'fitness'       => ['Fitness and Wellbeing', 'bi-heart-pulse'],
    'entertainment' => ['Entertainment', 'bi-controller'],
];
$all = [];
foreach ($cols as $key => $_) {
    foreach ($queues[$key] ?? [] as $q) { $q['_cat'] = $key; $all[] = $q; }
}
usort($all, static fn ($a, $b) => strtotime($a['created_at']) <=> strtotime($b['created_at']));
?>
<section class="sess-panel aq" aria-label="Approval requests" data-aq>
    <div class="sess-head">
        <div>
            <h2><i class="bi bi-inbox me-2"></i>Approval requests</h2>
            <div class="sess-sub"><span data-aq-count><?= count($all) ?></span> waiting for approval · oldest first</div>
        </div>
        <a class="btn btn-gold btn-sm" data-aq-open href="<?= site_url('manage/bookings?status=pending') ?>">Open requests <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="aq-filters" role="tablist">
        <button type="button" class="aq-pill is-on" data-cat="all">All <b><?= count($all) ?></b></button>
        <?php foreach ($cols as $key => [$title, $icon]): ?>
        <button type="button" class="aq-pill" data-cat="<?= $key ?>"><i class="bi <?= $icon ?>"></i> <?= esc($title) ?> <b><?= count($queues[$key] ?? []) ?></b></button>
        <?php endforeach; ?>
    </div>
    <div class="aq-scroll">
        <table class="aq-table">
            <thead><tr><th>Facility</th><th>Customer</th><th>Schedule</th><th>Details</th><th class="t-end">Total</th><th>Requested</th><th class="t-end">Action</th></tr></thead>
            <tbody>
            <?php foreach ($all as $q):
                $ts     = strtotime($q['start_at']);
                $te     = strtotime($q['end_at']);
                $isRoom = ($q['facility_type'] ?? '') === \App\Models\FacilityModel::ROOM;
                $head   = \App\Models\FacilityModel::isPerHead((string) ($q['facility_type'] ?? ''));
                $days   = (int) $q['days'];
                $hrs    = max(1, (int) round(($te - $ts) / 3600));
                $nights = max(1, (int) round((strtotime(date('Y-m-d', $te)) - strtotime(date('Y-m-d', $ts))) / 86400));
                $addons = ! empty($q['addons']) ? array_values(array_filter(array_column(json_decode($q['addons'], true) ?: [], 'label'))) : [];
                if ($isRoom) {
                    $when  = date('D, M j', $ts) . ' → ' . date('D, M j', $te);
                    $len   = $nights . ($nights === 1 ? ' night' : ' nights');
                    $total = (float) $q['total_price'];
                } else {
                    $when  = ($days > 1 ? date('M j', $ts) . ' + ' . ($days - 1) . ' more days' : date('D, M j', $ts)) . ' · ' . date('g:i A', $ts) . ' – ' . date('g:i A', $te);
                    $len   = $hrs . ($hrs === 1 ? ' hr' : ' hrs') . ($days > 1 ? ' / day' : '');
                    $total = (\App\Models\FacilityModel::totalPrice((float) $q['facility_price'], $hrs, $head ? (int) $q['guests'] : 1) + (float) $q['addons_total']) * $days;
                }
                $ago  = max(0, time() - strtotime($q['created_at']));
                $wait = $ago < 3600 ? max(1, intdiv($ago, 60)) . 'm ago' : ($ago < 86400 ? intdiv($ago, 3600) . 'h ago' : intdiv($ago, 86400) . 'd ago');
                [$ctitle, $cicon] = $cols[$q['_cat']];
            ?>
            <tr data-cat="<?= $q['_cat'] ?>">
                <td><strong><?= esc($q['facility_name']) ?></strong><small><i class="bi <?= $cicon ?>"></i> <?= esc($ctitle) ?></small></td>
                <td><i class="bi bi-person-circle me-1"></i><?= esc($q['customer_name']) ?></td>
                <td><?= esc($when) ?></td>
                <td>
                    <div class="mg-chips">
                        <em><i class="bi bi-hourglass-split"></i> <?= esc($len) ?></em>
                        <?php if ($head): ?><em><i class="bi bi-people"></i> <?= (int) $q['guests'] ?></em><?php endif; ?>
                        <?php if ($addons): ?><em title="<?= esc(implode(', ', $addons), 'attr') ?>"><i class="bi bi-plus-circle"></i> <?= count($addons) ?></em><?php endif; ?>
                        <?php if (trim((string) $q['purpose']) !== ''): ?><em title="<?= esc($q['purpose'], 'attr') ?>"><i class="bi bi-sticky"></i> Note</em><?php endif; ?>
                    </div>
                </td>
                <td class="t-end aq-price">₱<?= number_format($total, 0) ?></td>
                <td class="aq-ago" title="Requested <?= esc(fmt_dt($q['created_at']), 'attr') ?>"><?= esc($wait) ?></td>
                <td class="t-end">
                    <div class="aq-act">
                        <form method="post" action="<?= site_url('manage/bookings/' . (int) $q['id'] . '/approve') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-gold" type="submit">Approve</button></form>
                        <form method="post" action="<?= site_url('manage/bookings/' . (int) $q['id'] . '/reject') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-reject" type="submit">Reject</button></form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="empty aq-empty" hidden><i class="bi bi-inbox"></i><strong>All caught up</strong>No pending requests.</div>
    </div>
</section>
</div>
<script>
(function () {
    if (window.__aqInit) return; window.__aqInit = true;
    const KEY = 'aqCat';
    function get() { try { return sessionStorage.getItem(KEY) || 'all'; } catch (e) { return 'all'; } }
    function apply() {
        const root = document.querySelector('[data-aq]'); if (!root) return;
        let cat = get();
        if (!root.querySelector('.aq-pill[data-cat="' + cat + '"]')) cat = 'all';
        let n = 0;
        root.querySelectorAll('.aq-pill').forEach(function (b) { b.classList.toggle('is-on', b.dataset.cat === cat); });
        root.querySelectorAll('tbody tr').forEach(function (r) {
            const show = cat === 'all' || r.dataset.cat === cat; r.hidden = !show; if (show) n++;
        });
        const t = root.querySelector('.aq-table'), e = root.querySelector('.aq-empty');
        if (t) t.hidden = n === 0; if (e) e.hidden = n !== 0;
        const o = root.querySelector('[data-aq-open]');
        if (o) o.href = o.href.split('?')[0] + '?status=pending' + (cat === 'all' ? '' : '&category=' + cat);
    }
    document.addEventListener('click', function (ev) {
        const b = ev.target.closest('.aq-pill'); if (!b) return;
        try { sessionStorage.setItem(KEY, b.dataset.cat); } catch (e) {}
        apply();
    });
    document.addEventListener('live:changed', function () { setTimeout(apply, 0); });
    apply();
})();
</script>
