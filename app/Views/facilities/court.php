<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= $this->setVar('step', 3)->setVar('backUrl', site_url('facilities/' . $facility['type']))->setVar('backLabel', ($activity ?? 'Back') . ' courts')->include('facilities/_stepper') ?>
<?php
    $dateStr  = $date->format('Y-m-d');
    $rate     = (float) $facility['price_per_hour'];
    // Month arrows keep the selected day; clicking a day selects it (and shows that day's month).
    $monthUrl = static fn (string $m): string => '?date=' . $dateStr . '&month=' . $m;
    // Only the date is passed on: the customer sets the time in on the booking form.
    $bookUrl  = site_url('bookings/new') . '?' . http_build_query(['facility_id' => (int) $facility['id'], 'start' => $dateStr]);
    $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
?>
<div class="page-head">
    <div>
        <h1><?= esc($facility['name']) ?></h1>
        <p><?= esc($activity) ?> · up to <?= (int) $facility['capacity'] ?> players · ₱<?= number_format($rate, 0) ?> per hour · Open <?= esc($opens) ?> – <?= esc($closes) ?></p>
    </div>
    <div class="page-actions">
        <?php if ($live['in_use']): ?>
            <span class="live-chip is-busy">In use until <?= esc(fmt_time($live['until'])) ?></span>
        <?php else: ?>
            <span class="live-chip is-free">Available now</span>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Calendar -->
    <div class="col-lg-7">
        <div class="panel cal-panel h-100">
            <div class="panel-head">
                <h2><?= esc($month->format('F Y')) ?></h2>
                <div class="cal-nav">
                    <?php if ($canPrev): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= esc($monthUrl($prevMonth)) ?>" aria-label="Previous month"><i class="bi bi-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true"><i class="bi bi-chevron-left"></i></span>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= esc($monthUrl($nextMonth)) ?>" aria-label="Next month"><i class="bi bi-chevron-right"></i></a>
                    <?php if ($showToday): ?><a class="btn btn-sm btn-outline-primary" href="?date=<?= date('Y-m-d') ?>">Today</a><?php endif; ?>
                </div>
            </div>

            <div class="cal-weekdays" aria-hidden="true">
                <?php foreach ($weekdays as $w): ?><span><?= $w ?></span><?php endforeach; ?>
            </div>

            <div class="cal-grid" style="--cal-rows: <?= (int) $weeks ?>">
                <?php foreach ($cells as $c):
                    // Customers cannot book the past, so past days are not clickable for them.
                    $disabled = $c['past'] && ! $isManager;
                    $classes  = 'cal-day'
                        . ($c['inMonth'] ? '' : ' is-out')
                        . ($c['past'] ? ' is-past' : '')
                        . ($c['today'] ? ' is-today' : '')
                        . ($c['selected'] ? ' is-selected' : '');
                    $label = $c['label'] . ($c['count'] ? ' · ' . $c['count'] . ($c['count'] === 1 ? ' session' : ' sessions') : '');
                ?>
                    <?php if ($disabled): ?>
                        <span class="<?= $classes ?> is-disabled" aria-disabled="true" aria-label="<?= esc($label) ?> (past)">
                    <?php else: ?>
                        <a class="<?= $classes ?>" href="?date=<?= $c['date'] ?>" aria-label="<?= esc($label) ?>" <?= $c['selected'] ? 'aria-current="date"' : '' ?>>
                    <?php endif; ?>
                        <span class="cal-num"><?= $c['day'] ?></span>
                        <?php if ($c['count'] && ! $disabled): ?>
                            <span class="cal-badge"><i class="cal-dot"></i><span class="cal-badge-n"><?= (int) $c['count'] ?></span><span class="cal-badge-t"><?= $c['count'] === 1 ? ' session' : ' sessions' ?></span></span>
                        <?php endif; ?>
                    <?= $disabled ? '</span>' : '</a>' ?>
                <?php endforeach; ?>
            </div>

            <div class="cal-foot">
                <span class="cal-key"><i class="cal-key-sel"></i> Selected</span>
                <span class="cal-key"><i class="cal-key-today"></i> Today</span>
                <span class="cal-key"><i class="cal-dot"></i> Has sessions</span>
            </div>
        </div>
    </div>

    <!-- Court info + sessions -->
    <div class="col-lg-5">
        <?php if (! $isManager && ! $isPast): ?>
            <a class="btn btn-primary w-100 mb-4" href="<?= esc($bookUrl) ?>"><i class="bi bi-calendar-plus me-1"></i> Book <?= esc($date->format('D, M j')) ?></a>
        <?php endif; ?>
        <div class="panel mb-4 overflow-hidden">
            <img class="court-hero" src="<?= facility_image($facility) ?>" alt="<?= esc($facility['name']) ?>">
            <?php if ($facility['description']): ?><div class="panel-body"><?= esc($facility['description']) ?></div><?php endif; ?>
            <div class="rate-note">
                <i class="bi bi-clock-history" aria-hidden="true"></i>
                <div>
                    <strong>₱<?= number_format($rate, 2) ?> per hour · 1 hour minimum</strong>
                    <span>Pick a date here, then set your time in on the next page and add hours with +. Your time out is worked out for you.</span>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head"><h2>Sessions on <?= esc($date->format('M j')) ?></h2></div>
            <?php if (empty($sessions)): ?>
                <div class="empty py-4"><i class="bi bi-calendar-check"></i><strong>Nobody has this court yet</strong>The whole day is open.</div>
            <?php else: ?>
            <div class="table-responsive">
            <table class="table data-table">
                <thead><tr><th>Time in</th><th>Time out</th><th>Customer</th></tr></thead>
                <tbody>
                <?php foreach ($sessions as $sess): ?>
                    <tr>
                        <td class="cell-title"><?= esc(fmt_time($sess['start'])) ?></td>
                        <td class="cell-title"><?= esc(fmt_time($sess['end'])) ?></td>
                        <td>
                            <?= esc($sess['who']) ?>
                            <?php if ($sess['status'] === 'pending'): ?> <span class="pill pill-pending">Pending</span><?php endif; ?>
                            <?php if ($sess['purpose']): ?><div class="cell-sub"><?= esc($sess['purpose']) ?></div><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
