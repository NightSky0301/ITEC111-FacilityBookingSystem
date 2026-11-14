<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $now = time();

    // ---- Prepare every booking once: timing, price, and which filter tabs it belongs to ----
    $rows = [];
    foreach ($bookings as $b) {
        $s        = strtotime($b['start_at']);
        $e        = strtotime($b['end_at']);
        $status   = (string) $b['status'];
        $upcoming = in_array($status, ['pending', 'approved'], true) && $e > $now;
        $hours    = max(0, ($e - $s) / 3600);
        $price    = (float) ($b['facility_price'] ?? 0);

        $tags = [];
        if ($upcoming) { $tags[] = 'upcoming'; }
        if ($status === 'pending') { $tags[] = 'pending'; }
        if ($status === 'approved' && $upcoming) { $tags[] = 'approved'; }
        if (! $upcoming) { $tags[] = 'past'; }

        $rows[] = [
            'b'        => $b,
            's'        => $s,
            'e'        => $e,
            'status'   => $status,
            'upcoming' => $upcoming,
            'done'     => $status === 'approved' && $e <= $now,
            'hours'    => $hours,
            'total'    => $hours * $price,
            'tags'     => $tags,
        ];
    }

    // Upcoming first (soonest on top), then everything else (most recent on top).
    usort($rows, static function (array $a, array $b): int {
        if ($a['upcoming'] !== $b['upcoming']) {
            return $a['upcoming'] ? -1 : 1;
        }
        return $a['upcoming'] ? $a['s'] <=> $b['s'] : $b['s'] <=> $a['s'];
    });

    $count = static function (string $tag) use ($rows): int {
        return count(array_filter($rows, static fn (array $r): bool => in_array($tag, $r['tags'], true)));
    };
    $tabs = [
        'all'      => ['All',      count($rows),        'You have no bookings here.'],
        'upcoming' => ['Upcoming', $count('upcoming'),  'No upcoming bookings.'],
        'pending'  => ['Pending',  $count('pending'),   'Nothing is waiting for approval.'],
        'approved' => ['Approved', $count('approved'),  'No approved bookings coming up.'],
        'past'     => ['Past & closed', $count('past'), 'No past or closed bookings yet.'],
    ];

    // The next approved session, for the banner on top.
    $next = null;
    foreach ($rows as $r) {
        if ($r['status'] === 'approved' && $r['upcoming']) { $next = $r; break; }
    }

    $fmtHours = static function (float $h): string {
        $n = rtrim(rtrim(number_format($h, 2, '.', ''), '0'), '.');
        return $n . ($h == 1.0 ? ' hr' : ' hrs');
    };

    // "Today", "Tomorrow", "In 3 days" or "Happening now" for sessions that have not ended.
    $rel = static function (int $s, int $e) use ($now): array {
        if ($s <= $now && $now < $e) {
            return ['Happening now', 'is-now'];
        }
        $days = (int) (new \DateTime(date('Y-m-d', $now)))->diff(new \DateTime(date('Y-m-d', $s)))->format('%r%a');
        if ($days === 0) { return ['Today', 'is-soon']; }
        if ($days === 1) { return ['Tomorrow', 'is-soon']; }
        if ($days > 1)   { return ['In ' . $days . ' days', '']; }
        return ['', ''];
    };
?>
<div class="page-head">
    <div>
        <h1>My bookings</h1>
        <p>Your requests and their status.</p>
    </div>
</div>

<?php if (empty($rows)): ?>
<div class="panel">
    <div class="empty">
        <i class="bi bi-calendar-check"></i>
        <strong>No bookings yet</strong>
        Pick a facility and a time to make your first booking.
        <div class="mt-3"><a href="<?= site_url('facilities') ?>" class="btn btn-primary btn-sm">Browse courts</a></div>
    </div>
</div>
<?php else: ?>

<?php if ($next):
    $nb = $next['b'];
    [$nextRel] = $rel($next['s'], $next['e']);
?>
<section class="bk-next" aria-label="Your next session">
    <span class="bk-next-icon"><i class="bi bi-calendar-event"></i></span>
    <div class="bk-next-body">
        <div class="bk-next-kicker">Next session<?= $nextRel ? ' · ' . esc($nextRel) : '' ?></div>
        <h2 class="bk-next-title"><?= esc($nb['facility_name']) ?></h2>
        <div class="bk-next-when"><?= esc(fmt_range($nb['start_at'], $nb['end_at'])) ?></div>
    </div>
    <a class="btn btn-sm btn-light-on-dark" href="<?= site_url('facilities/court/' . (int) $nb['facility_id']) ?>?date=<?= esc(date('Y-m-d', $next['s']), 'url') ?>">Court schedule <i class="bi bi-arrow-right ms-1"></i></a>
</section>
<?php endif; ?>

<section class="panel panel-table bk-panel">
    <div class="bk-tabs" role="group" aria-label="Filter bookings">
        <?php foreach ($tabs as $key => [$label, $n]): ?>
            <button type="button" class="bk-tab <?= $key === 'pending' && $n > 0 ? 'has-wait' : '' ?> <?= $key === 'all' ? 'is-active' : '' ?>"
                    data-filter="<?= esc($key, 'attr') ?>" data-empty="<?= esc($tabs[$key][2], 'attr') ?>" aria-pressed="<?= $key === 'all' ? 'true' : 'false' ?>">
                <?= esc($label) ?> <span class="n"><?= (int) $n ?></span>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="table-responsive">
        <table class="table data-table bk-table">
            <thead><tr>
                <th>Court</th>
                <th>When</th>
                <th>Players</th>
                <th class="text-end">Total</th>
                <th>Status</th>
                <th class="text-end"></th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $r):
                $b      = $r['b'];
                $s      = $r['s'];
                $e      = $r['e'];
                $status = $r['status'];
                $void   = in_array($status, ['rejected', 'cancelled'], true);
                $sameDay = date('Y-m-d', $s) === date('Y-m-d', $e);
                $img    = facility_image(['type' => $b['facility_type'] ?? 'other', 'name' => $b['facility_name'] ?? '', 'image' => $b['facility_image'] ?? null]);
                [$relText, $relClass] = $r['upcoming'] ? $rel($s, $e) : ['', ''];
                $rowClass = $r['done'] ? 'is-done' : 'is-' . $status;
                $closed   = ! $r['upcoming'];
                $canCancel = $status === 'pending' || ($status === 'approved' && $r['upcoming']);
                $confirmText = 'Cancel your booking for ' . $b['facility_name'] . ' on ' . date('M j', $s) . '?';
            ?>
                <tr class="<?= esc($rowClass, 'attr') ?> <?= $closed ? 'is-closed' : '' ?>" data-tags="<?= esc(implode(' ', $r['tags']), 'attr') ?>">
                    <td class="c-court">
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= $img ?>" alt="" class="table-thumb" loading="lazy">
                            <div class="min-w-0">
                                <div class="cell-title"><?= esc($b['facility_name']) ?></div>
                                <div class="cell-sub"><?= esc(\App\Models\FacilityModel::activityName((string) ($b['facility_type'] ?? ''))) ?></div>
                                <?php if (! empty($b['purpose'])): ?>
                                    <div class="bk-purpose" title="<?= esc($b['purpose'], 'attr') ?>"><i class="bi bi-chat-left-text"></i><span><?= esc($b['purpose']) ?></span></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>

                    <td class="c-when">
                        <div class="d-flex align-items-center gap-3">
                            <div class="date-chip"><span class="d"><?= date('j', $s) ?></span><span class="m"><?= date('M', $s) ?></span></div>
                            <div>
                                <?php if ($sameDay): ?>
                                    <div class="cell-title bk-num"><?= date('g:i A', $s) ?> – <?= date('g:i A', $e) ?></div>
                                    <div class="cell-sub"><?= date('l', $s) ?> · <?= esc($fmtHours($r['hours'])) ?></div>
                                <?php else: ?>
                                    <div class="cell-title"><?= esc(fmt_range($b['start_at'], $b['end_at'])) ?></div>
                                    <div class="cell-sub"><?= esc($fmtHours($r['hours'])) ?></div>
                                <?php endif; ?>
                                <?php if ($relText): ?><span class="bk-rel <?= esc($relClass, 'attr') ?>"><?= esc($relText) ?></span><?php endif; ?>
                            </div>
                        </div>
                    </td>

                    <td class="c-players">
                        <span class="bk-players"><i class="bi bi-people"></i><?= (int) $b['guests'] ?></span>
                    </td>

                    <td class="c-total text-end">
                        <span class="bk-total bk-num <?= $void ? 'is-void' : '' ?>">₱<?= number_format($r['total'], 2) ?></span>
                    </td>

                    <td class="c-status">
                        <?php if ($r['done']): ?>
                            <span class="pill pill-done">Completed</span>
                        <?php else: ?>
                            <?= status_badge($status) ?>
                        <?php endif; ?>
                        <?php if (! empty($b['admin_remarks'])): ?>
                            <div class="bk-note"><i class="bi bi-chat-square-text"></i><span><?= esc($b['admin_remarks']) ?></span></div>
                        <?php endif; ?>
                    </td>

                    <td class="c-act text-end">
                        <?php if ($canCancel): ?>
                            <form method="post" action="<?= site_url('bookings/' . (int) $b['id'] . '/cancel') ?>" onsubmit="return confirm('<?= esc($confirmText, 'js') ?>')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger">Cancel</button>
                            </form>
                        <?php else: ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('bookings/new') ?>?facility_id=<?= (int) $b['facility_id'] ?>">Book again</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="empty bk-none is-hidden" id="bkNone" role="status">
        <i class="bi bi-funnel"></i>
        <strong>Nothing to show</strong>
        <span id="bkNoneText">No bookings in this view.</span>
    </div>
</section>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const tabs = document.querySelectorAll('.bk-tab');
    const rows = document.querySelectorAll('.bk-table tbody tr');
    const none = document.getElementById('bkNone');
    const noneText = document.getElementById('bkNoneText');
    if (!tabs.length) return;

    function apply(tab) {
        const filter = tab.dataset.filter;
        let shown = 0;
        rows.forEach(function (row) {
            const ok = filter === 'all' || row.dataset.tags.split(' ').indexOf(filter) !== -1;
            row.classList.toggle('is-hidden', !ok);
            if (ok) shown++;
        });
        tabs.forEach(function (t) {
            const on = t === tab;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        noneText.textContent = tab.dataset.empty;
        none.classList.toggle('is-hidden', shown > 0);
    }

    tabs.forEach(function (t) { t.addEventListener('click', function () { apply(t); }); });
})();
</script>
<?= $this->endSection() ?>
