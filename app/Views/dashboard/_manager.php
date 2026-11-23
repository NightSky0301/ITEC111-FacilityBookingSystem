<?php
/** Staff / admin dashboard: what needs attention right now. */
?>
<div id="live-stats" data-live>
<div class="stat-grid is-three">
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
</div>

</div>

<?= $this->include('dashboard/_sessions') ?>

<div id="live-queues" data-live>
<?php
$cols = [
    'activities' => ['Sports Activities', 'bi-trophy'],
    'hotels'     => ['Hotels and Suites', 'bi-building'],
    'fitness'    => ['Fitness and Wellbeing', 'bi-heart-pulse'],
];
?>
<div class="mg-cols">
<?php foreach ($cols as $key => [$title, $icon]): $rows = $queues[$key]; ?>
    <section class="sess-panel mg-col" aria-label="<?= esc($title) ?> requests">
        <div class="sess-head">
            <div>
                <h2><i class="bi <?= $icon ?> me-2"></i><?= esc($title) ?></h2>
                <div class="sess-sub"><?= count($rows) ?> waiting for approval</div>
            </div>
        </div>
        <?php if (! $rows): ?>
            <div class="mg-body"><div class="empty"><i class="bi bi-inbox"></i><strong>All caught up</strong>No pending requests.</div></div>
        <?php else: ?>
        <div class="mg-body"><ul class="mg-list">
            <?php foreach ($rows as $q):
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
            ?>
            <li>
                <div class="mg-main">
                    <div class="mg-top">
                        <strong><?= esc($q['facility_name']) ?></strong>
                        <span class="mg-ago" title="Requested <?= esc(fmt_dt($q['created_at']), 'attr') ?>"><?= esc($wait) ?></span>
                    </div>
                    <span><i class="bi bi-person-circle"></i> <?= esc($q['customer_name']) ?></span>
                    <span><i class="bi bi-calendar-event"></i> <?= esc($when) ?></span>
                    <div class="mg-chips">
                        <em><i class="bi bi-hourglass-split"></i> <?= esc($len) ?></em>
                        <?php if ($isRoom || $head): ?><em><i class="bi bi-people"></i> <?= (int) $q['guests'] ?></em><?php endif; ?>
                        <?php if ($days > 1 && ! $isRoom): ?><em><i class="bi bi-calendar-range"></i> <?= $days ?> days</em><?php endif; ?>
                        <?php if ($addons): ?><em title="<?= esc(implode(', ', $addons), 'attr') ?>"><i class="bi bi-plus-circle"></i> <?= count($addons) ?> add-on<?= count($addons) === 1 ? '' : 's' ?></em><?php endif; ?>
                        <em class="mg-price">₱<?= number_format($total, 0) ?></em>
                    </div>
                    <?php if (trim((string) $q['purpose']) !== ''): ?>
                        <span class="mg-note" title="<?= esc($q['purpose'], 'attr') ?>"><i class="bi bi-sticky"></i> <?= esc($q['purpose']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="mg-act">
                    <form method="post" action="<?= site_url('manage/bookings/' . (int) $q['id'] . '/approve') ?>"><?= csrf_field() ?>
                        <button class="btn btn-sm btn-gold" type="submit">Approve</button></form>
                    <form method="post" action="<?= site_url('manage/bookings/' . (int) $q['id'] . '/reject') ?>"><?= csrf_field() ?>
                        <button class="btn btn-sm btn-reject" type="submit">Reject</button></form>
                </div>
            </li>
            <?php endforeach; ?>
        </ul></div>
        <?php endif; ?>
        <div class="mg-foot"><a class="mg-more" href="<?= site_url('manage/bookings?status=pending&category=' . $key) ?>">Open requests <i class="bi bi-arrow-right"></i></a></div>
    </section>
<?php endforeach; ?>
</div>
</div>
