<?php /** Customer dashboard: recent bookings as cards, same look as the staff "Session(s) today" panel. */ ?>
<div id="live-recent" data-live>
<section class="sess-panel" aria-label="Recent bookings">
    <div class="sess-head">
        <div>
            <h2>Recent bookings</h2>
            <div class="sess-sub">Your most recent requests</div>
        </div>
        <a href="<?= site_url('my-bookings') ?>" class="btn btn-gold btn-sm">View all <i class="bi bi-arrow-right ms-1"></i></a>
    </div>

    <?php if (empty($latest)): ?>
        <div class="empty">
            <i class="bi bi-calendar-check"></i>
            <strong>No bookings yet</strong>
            Pick an activity and a time to make your first booking.
            <div class="mt-3"><a href="<?= site_url('facilities') ?>" class="btn btn-gold btn-sm">Browse courts</a></div>
        </div>
    <?php else: ?>
    <div class="sess-grid" data-pager="9" data-cols="3">
        <?php foreach (array_slice(group_bookings($latest), 0, 27) as $b):
            $s      = strtotime($b['start_at']);
            $e      = strtotime($b['end_at']);
            $st     = (string) $b['status'];
            $stayN  = (int) $b['_n'];
            $cls    = ['pending' => 'is-pending', 'approved' => 'is-approved', 'rejected' => 'is-rejected', 'cancelled' => 'is-rejected'][$st] ?? 'is-soon';
            $img    = facility_image(['type' => $b['facility_type'] ?? 'other', 'name' => $b['facility_name'] ?? '', 'image' => $b['facility_image'] ?? null]);
            $hrs    = max(0, ($e - $s) / 3600);
            $isRoom = \App\Models\FacilityModel::isRoom((string) ($b['facility_type'] ?? ''));
            $nts    = max(1, (int) round((strtotime(date('Y-m-d', $e)) - strtotime(date('Y-m-d', $s))) / 86400));
            $hrsTxt = $isRoom ? $nts . ($nts === 1 ? ' night' : ' nights') : ($hrs >= 24
                ? (int) round($hrs / 24) . ' days'
                : rtrim(rtrim(number_format($hrs, 2, '.', ''), '0'), '.') . ($hrs == 1.0 ? ' hr' : ' hrs'));
            $lastTs = $stayN > 1 ? strtotime($b['_days'][$stayN - 1]['start_at']) : $s;
            $made   = ! empty($b['created_at']) ? strtotime($b['created_at']) : null;
        ?>
        <a class="sess-card rb-card <?= $cls ?>" href="<?= site_url('my-bookings') ?>">
            <span class="sess-photo" style="background-image:url('<?= esc($img, 'attr') ?>')">
                <span class="sess-state"><?= esc(ucfirst($st)) ?></span>
            </span>
            <span class="rb-body">
                <span class="rb-court"><?= esc($b['facility_name']) ?></span>
                <span class="rb-when">
                    <span class="rb-date"><span class="m"><?= date('M', $s) ?></span><span class="d"><?= date('j', $s) ?></span><span class="w"><?= date('D', $s) ?></span></span>
                    <span>
                        <span class="rb-time d-block"><?= date('g:i A', $s) ?> – <?= date('g:i A', $e) ?></span>
                        <span class="rb-sub d-block">
                            <?php if ($stayN > 1): ?>
                                Daily · <?= (int) $stayN ?> days, until <?= date('M j', $lastTs) ?>
                            <?php else: ?>
                                <?= esc($hrsTxt) ?><?= date('Y-m-d', $s) !== date('Y-m-d', $e) ? ' · ends ' . date('M j', $e) : '' ?>
                            <?php endif; ?>
                        </span>
                    </span>
                </span>
                <?php if ($made): ?>
                    <span class="rb-foot"><i class="bi bi-clock-history"></i> Booked <?= date('M j, g:i A', $made) ?></span>
                <?php endif; ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
</div>
