<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="page-head">
    <div>
        <h1>Bookings</h1>
        <p>Approve or reject requests. Approved bookings block the time slot.</p>
    </div>
</div>

<form method="get" class="filter-bar">
    <select name="status" class="form-select" data-dd aria-label="Filter by status" onchange="this.form.submit()">
        <option value="">All statuses</option>
        <?php foreach (['pending', 'approved', 'rejected', 'cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="facility_id" class="form-select" data-dd aria-label="Filter by facility" onchange="this.form.submit()">
        <option value="">All facilities</option>
        <?php foreach ($facilities as $f): ?>
            <option value="<?= (int) $f['id'] ?>" <?= (int) $facilityId === (int) $f['id'] ? 'selected' : '' ?>><?= esc($f['name']) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<section class="tbl-panel">
<div class="sess-head"><div><h2>All bookings</h2><div class="sess-sub"><?= count($bookings) ?> <?= count($bookings) === 1 ? 'booking' : 'bookings' ?><?= $status ? ' · ' . ucfirst($status) : '' ?></div></div></div>
<?php if (empty($bookings)): ?>
    <div class="empty"><i class="bi bi-inbox"></i><strong>No bookings found</strong>Try a different filter.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table data-table table-hover tbl-brass bk-adm">
    <thead><tr><th>Customer</th><th>Facility</th><th>When</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($bookings as $b): ?>
        <?php
            $ts = strtotime($b['start_at']); $te = strtotime($b['end_at']);
            $sameDay = date('Y-m-d', $ts) === date('Y-m-d', $te);
            $mins = max(0, (int) round(($te - $ts) / 60));
            $len = intdiv($mins, 60) . ' hr' . (intdiv($mins, 60) === 1 ? '' : 's') . ($mins % 60 ? ' ' . ($mins % 60) . ' min' : '');
            $addons = ! empty($b['addons']) ? (json_decode($b['addons'], true) ?: []) : [];
        ?>
        <tr>
            <td>
                <div class="bk-person">
                    <span class="bk-avatar" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr(trim($b['customer_name']), 0, 1))) ?></span>
                    <div><div class="cell-title"><?= esc($b['customer_name']) ?></div><div class="cell-sub"><?= esc($b['customer_email']) ?></div></div>
                </div>
            </td>
            <td class="bk-fac">
                <div class="cell-title"><?= esc($b['facility_name']) ?></div>
                <?php
                    $perHead = \App\Models\FacilityModel::isPerHead((string) ($b['facility_type'] ?? ''));
                    $hasAdd  = ! empty($addons);
                ?>
                <?php if ($perHead || $hasAdd): ?>
                <div class="bk-chips">
                    <?php if ($perHead): ?><span class="bk-chip"><i class="bi bi-people"></i><?= (int) $b['guests'] ?> <?= (int) $b['guests'] === 1 ? 'person' : 'people' ?></span><?php endif; ?>
                    <?php if ($hasAdd): ?><span class="bk-chip"><i class="bi bi-plus-circle"></i><?= esc(implode(', ', array_column($addons, 'label'))) ?> · ₱<?= number_format((float) $b['addons_total'], 2) ?></span><?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($b['purpose']): ?><div class="bk-purpose">“<?= esc($b['purpose']) ?>”</div><?php endif; ?>
            </td>
            <td class="bk-when">
                <?php
                    $nowT = time();
                    $today = date('Y-m-d');
                    if ($nowT >= $te)      { $rel = ['Finished', 'is-past', 'bi-check2-circle']; }
                    elseif ($nowT >= $ts)  { $rel = ['In progress', 'is-live', 'bi-broadcast']; }
                    elseif (date('Y-m-d', $ts) === $today) { $rel = ['Today', 'is-today', 'bi-brightness-high']; }
                    elseif (date('Y-m-d', $ts) === date('Y-m-d', strtotime('+1 day'))) { $rel = ['Tomorrow', 'is-soon', 'bi-arrow-right-circle']; }
                    else { $days = (int) round((strtotime(date('Y-m-d', $ts)) - strtotime($today)) / 86400); $rel = ['In ' . $days . ' days', 'is-soon', 'bi-arrow-right-circle']; }
                ?>
                <div class="bk-when-wrap">
                    <div class="bk-datetile" aria-hidden="true">
                        <span class="bk-dt-mon"><?= esc(date('M', $ts)) ?></span>
                        <span class="bk-dt-day"><?= esc(date('j', $ts)) ?></span>
                    </div>
                    <div class="bk-when-info">
                        <div class="bk-when-day"><?= esc(date('l', $ts)) ?><?= $sameDay ? '' : ' → ' . esc(date('D, M j', $te)) ?></div>
                        <div class="bk-when-time"><i class="bi bi-clock"></i><?= esc(date('g:i A', $ts)) ?> – <?= esc(date('g:i A', $te)) ?></div>
                        <div class="bk-when-tags">
                            <span class="bk-tag"><i class="bi bi-hourglass-split"></i><?= esc($len) ?></span>
                            <span class="bk-tag <?= $rel[1] ?>"><i class="bi <?= $rel[2] ?>"></i><?= esc($rel[0]) ?></span>
                        </div>
                    </div>
                </div>
            </td>
            <td class="bk-status">
                <?php if ($b['status'] === 'approved'): ?>
                    <span class="pill pill-approved"><?= esc(approval_label($b)) ?></span>
                    <?php if (! empty($b['arrived_at'])): ?><span class="pill pill-arrived"><i class="bi bi-person-check"></i> Arrived <?= esc(date('g:i A', strtotime($b['arrived_at']))) ?></span>
                    <?php elseif (arrival_state($b)[0] === 'missed'): ?><span class="pill pill-missed"><i class="bi bi-person-x"></i> Did not arrive</span><?php endif; ?>
                <?php else: ?><?= status_badge($b['status']) ?><?php endif; ?>
                <?php if ($b['admin_remarks']): ?><div class="bk-remark"><i class="bi bi-chat-left-text"></i><span><?= esc($b['admin_remarks']) ?></span></div><?php endif; ?>
            </td>
            <td>
              <div class="bk-actions">
                <?php if ($b['status'] === 'pending'): ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="text" name="admin_remarks" class="form-control form-control-sm mb-2" placeholder="Remarks (optional)" aria-label="Remarks">
                        <div class="btn-row">
                            <button formaction="<?= site_url('manage/bookings/' . (int) $b['id'] . '/approve') ?>" class="btn btn-sm btn-act btn-act-edit"><i class="bi bi-check-lg"></i> Approve</button>
                            <button formaction="<?= site_url('manage/bookings/' . (int) $b['id'] . '/reject') ?>" class="btn btn-sm btn-act btn-act-red"><i class="bi bi-x-lg"></i> Reject</button>
                        </div>
                    </form>
                <?php elseif ($b['status'] === 'approved'): ?>
                    <form method="post" data-confirm="The customer will be notified that this approved booking was cancelled." data-confirm-title="Cancel this booking?" data-confirm-ok="Cancel booking" data-confirm-cancel="Keep it" data-confirm-icon="bi-calendar-x">
                        <?= csrf_field() ?>
                        <input type="text" name="admin_remarks" class="form-control form-control-sm mb-2" placeholder="Reason (optional)" aria-label="Reason">
                        <div class="btn-row"><button formaction="<?= site_url('manage/bookings/' . (int) $b['id'] . '/cancel') ?>" class="btn btn-sm btn-act btn-act-brass"><i class="bi bi-calendar-x"></i> Cancel booking</button></div>
                    </form>
                <?php endif; ?>
                <?php if (is_admin()): ?>
                    <form method="post" action="<?= site_url('admin/bookings/' . (int) $b['id'] . '/delete') ?>"
                          data-confirm="This booking will be moved to the Archive and its time slot freed. You can restore it from the Archive." data-confirm-title="Delete this booking?" data-confirm-ok="Delete" data-confirm-icon="bi-trash">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-act btn-act-ghost-red btn-del"><i class="bi bi-trash"></i> Delete</button>
                    </form>
                <?php endif; ?>
              </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
</section>
<?= $this->endSection() ?>
