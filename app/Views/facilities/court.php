<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= $this->setVar('step', 3)->include('facilities/_stepper') ?>
<?php
    $dateStr  = $date->format('Y-m-d');
    $bookUrl  = static fn (int $fid, int $s, int $e): string => site_url('bookings/new') . '?' . http_build_query([
        'facility_id' => $fid, 'start' => date('Y-m-d\TH:i', $s), 'end' => date('Y-m-d\TH:i', $e),
    ]);
?>
<div class="page-head">
    <div>
        <a href="<?= site_url('facilities/' . esc($facility['type'], 'url')) ?>" class="back-link"><i class="bi bi-arrow-left"></i> <?= esc($activity) ?> courts</a>
        <h1><?= esc($facility['name']) ?></h1>
        <p><?= esc($activity) ?> · up to <?= (int) $facility['capacity'] ?> players · ₱<?= number_format((float) $facility['price_per_hour'], 0) ?> per hour</p>
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
    <!-- Hour by hour -->
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-head">
                <h2><?= $isToday ? 'Today, ' : '' ?><?= esc($date->format('D, M j, Y')) ?></h2>
                <form method="get" class="day-nav">
                    <a class="btn btn-sm btn-outline-secondary" href="?date=<?= $prev ?>" aria-label="Previous day"><i class="bi bi-chevron-left"></i></a>
                    <label class="visually-hidden" for="date">Date</label>
                    <input type="date" id="date" name="date" value="<?= $dateStr ?>" class="form-control form-control-sm" onchange="this.form.submit()">
                    <a class="btn btn-sm btn-outline-secondary" href="?date=<?= $next ?>" aria-label="Next day"><i class="bi bi-chevron-right"></i></a>
                    <?php if (! $isToday): ?><a class="btn btn-sm btn-outline-primary" href="?date=<?= date('Y-m-d') ?>">Today</a><?php endif; ?>
                </form>
            </div>

            <div class="slots">
                <?php foreach ($slots as $slot): ?>
                    <?php
                        $classes = 'slot is-' . $slot['state'] . ($slot['past'] ? ' is-past' : '') . ($slot['current'] ? ' is-now' : '');
                    ?>
                    <div class="<?= $classes ?>">
                        <div class="slot-time"><?= date('g:i A', $slot['start']) ?><?= $slot['current'] ? '<span class="now-tag">Now</span>' : '' ?></div>
                        <div class="slot-body">
                            <?php foreach ($slot['sessions'] as $ref): $sess = $sessions[$ref['idx']]; ?>
                                <div class="slot-session <?= $sess['status'] === 'pending' ? 'is-pending' : '' ?>">
                                    <?php if ($ref['continues']): ?>
                                        <span class="text-muted">Continues until <?= esc(fmt_time($sess['end'])) ?> · <?= esc($sess['who']) ?></span>
                                    <?php else: ?>
                                        <strong><?= $sess['status'] === 'pending' ? 'Pending request' : 'Booked' ?></strong>
                                        <span>In <?= esc(fmt_time($sess['start'])) ?> · Out <?= esc(fmt_time($sess['end'])) ?></span>
                                        <span class="text-muted">· <?= esc($sess['who']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($slot['state'] === 'free' || $slot['state'] === 'partial'): ?>
                                <div class="slot-free">
                                    <span><?= $slot['state'] === 'partial' ? 'Partly free' : 'Available' ?></span>
                                    <?php if (! $isManager && ! $slot['past']): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= $bookUrl((int) $facility['id'], $slot['start'], $slot['end']) ?>">Book</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Court info + sessions -->
    <div class="col-lg-5">
        <div class="panel mb-4 overflow-hidden">
            <img class="court-hero" src="<?= facility_image($facility) ?>" alt="<?= esc($facility['name']) ?>">
            <?php if ($facility['description']): ?><div class="panel-body"><?= esc($facility['description']) ?></div><?php endif; ?>
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

        <?php if (! $isManager): ?>
            <a class="btn btn-primary w-100 mt-4" href="<?= site_url('bookings/new') ?>?facility_id=<?= (int) $facility['id'] ?>&start=<?= $dateStr ?>T09:00">Book this court</a>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
