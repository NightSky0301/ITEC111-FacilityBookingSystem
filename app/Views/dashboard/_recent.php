<?php /** Customer dashboard: recent bookings as cards, same look as the staff "Session(s) today" panel. */ ?>
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
    <div class="sess-grid">
        <?php foreach ($latest as $b):
            $s = strtotime($b['start_at']);
            $e = strtotime($b['end_at']);
            $st = (string) $b['status'];
            $cls = ['pending' => 'is-pending', 'approved' => 'is-approved', 'rejected' => 'is-rejected', 'cancelled' => 'is-rejected'][$st] ?? 'is-soon';
            $img = facility_image(['type' => $b['facility_type'] ?? 'other', 'name' => $b['facility_name'] ?? '', 'image' => $b['facility_image'] ?? null]);
            $sameDay = date('Y-m-d', $s) === date('Y-m-d', $e);
        ?>
        <a class="sess-card <?= $cls ?>" href="<?= site_url('my-bookings') ?>">
            <span class="sess-photo" style="background-image:url('<?= esc($img, 'attr') ?>')">
                <span class="sess-state"><?= esc(ucfirst($st)) ?></span>
            </span>
            <span class="sess-body">
                <span class="sess-time"><?= $sameDay ? date('g:i A', $s) . ' – ' . date('g:i A', $e) : esc(fmt_range($b['start_at'], $b['end_at'])) ?></span>
                <span class="sess-court"><?= esc($b['facility_name']) ?></span>
                <span class="sess-who"><i class="bi bi-calendar3"></i> <?= date('D, M j', $s) ?></span>
            </span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
