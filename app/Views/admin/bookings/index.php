<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<form method="get" class="filter-bar">
    <select name="status" class="form-select" data-dd aria-label="Filter by status" onchange="this.form.submit()">
        <option value="">All statuses</option>
        <?php foreach (['pending', 'approved', 'rejected', 'cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="category" class="form-select" data-dd aria-label="Filter by category" onchange="this.form.facility_id.value='';this.form.submit()">
        <option value="">All categories</option>
        <?php foreach ($categories as $k => $label): ?>
            <option value="<?= $k ?>" <?= $category === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
        <?php endforeach; ?>
    </select>
    <div class="filter-right"><select name="facility_id" class="form-select" data-dd aria-label="Filter by facility" onchange="this.form.submit()">
        <option value="">All facilities</option>
        <?php foreach ($facilities as $f): ?>
            <option value="<?= (int) $f['id'] ?>" <?= (int) $facilityId === (int) $f['id'] ? 'selected' : '' ?>><?= esc($f['name']) ?></option>
        <?php endforeach; ?>
    </select></div>
</form>

<?php $bookings = group_bookings($bookings); ?>
<div id="live-bookings" data-live>
<section class="tbl-panel">
<div class="sess-head"><div><h2>All bookings</h2><div class="sess-sub"><?= count($bookings) ?> <?= count($bookings) === 1 ? 'booking' : 'bookings' ?><?= $status ? ' · ' . ucfirst($status) : '' ?><?= $category ? ' · ' . esc($categories[$category]) : '' ?></div></div></div>
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
            if ($sameDay) {
                $len = intdiv($mins, 60) . ' hr' . (intdiv($mins, 60) === 1 ? '' : 's') . ($mins % 60 ? ' ' . ($mins % 60) . ' min' : '');
            } else {
                $nDays = (int) round((strtotime(date('Y-m-d', $te)) - strtotime(date('Y-m-d', $ts))) / 86400) + 1;
                $len   = \App\Models\FacilityModel::isRoom((string) ($b['facility_type'] ?? '')) ? ($nDays - 1) . ' ' . ($nDays - 1 === 1 ? 'night' : 'nights') : $nDays . ' days';
            }
            $addons = ! empty($b['addons']) ? (json_decode($b['addons'], true) ?: []) : [];

            // A stay of several days is one row: its days are listed under "When", actions cover every day.
            $stayDays = $b['_days']; $stayN = $b['_n'];
            $hasPending  = (bool) array_filter($stayDays, static fn ($d) => $d['status'] === 'pending');
            $hasApproved = (bool) array_filter($stayDays, static fn ($d) => $d['status'] === 'approved' && strtotime($d['end_at']) > time());
            if ($stayN > 1) {
                $first = strtotime($stayDays[0]['start_at']); $last = strtotime($stayDays[$stayN - 1]['start_at']);
                $len   = $stayN . ' days · ' . intdiv($mins, 60) . ' hr' . (intdiv($mins, 60) === 1 ? '' : 's') . '/day';
            }
        ?>
        <tr>
            <td class="bk-cust">
                <div class="bk-person">
                    <span class="bk-avatar" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr(trim($b['customer_name']), 0, 1))) ?></span>
                    <div><div class="cell-title"><?= esc($b['customer_name']) ?></div><div class="cell-sub"><?= esc($b['customer_email']) ?></div></div>
                </div>
                <?php if (trim((string) $b['purpose']) !== ''): ?>
                <div class="bk-note-wrap">
                    <button type="button" class="btn btn-sm btn-act btn-act-ghost bk-note-btn"
                            data-note-name="<?= esc($b['customer_name'], 'attr') ?>"
                            data-note-facility="<?= esc($b['facility_name'], 'attr') ?>"
                            data-note-text="<?= esc($b['purpose'], 'attr') ?>"><i class="bi bi-sticky"></i> Note(s)</button>
                </div>
                <?php endif; ?>
            </td>
            <td class="bk-fac">
                <div class="cell-title"><?= esc($b['facility_name']) ?></div>
                <?php
                    $perHead = \App\Models\FacilityModel::isPerHead((string) ($b['facility_type'] ?? ''));
                    $addLabels = array_values(array_filter(array_column($addons, 'label')));
                ?>
                <?php if ($perHead): ?>
                    <div class="bk-chips"><span class="bk-chip"><i class="bi bi-people"></i><?= (int) $b['guests'] ?> <?= (int) $b['guests'] === 1 ? 'person' : 'people' ?></span></div>
                <?php endif; ?>
                <?php if ($addLabels): ?>
                <div class="bk-addons">
                    <div class="bk-addons-title">Add-ons</div>
                    <ul>
                        <?php foreach ($addLabels as $lbl): ?><li><i class="bi bi-plus-circle"></i><?= esc($lbl) ?></li><?php endforeach; ?>
                    </ul>
                    <div class="bk-addons-total">₱<?= number_format((float) $b['addons_total'], 2) ?></div>
                </div>
                <?php endif; ?>
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
                        <div class="bk-when-day"><?= $stayN > 1 ? esc(date('D, M j', $first) . ' → ' . date('D, M j', $last)) : esc(date('l', $ts)) . ($sameDay ? '' : ' → ' . esc(date('D, M j', $te))) ?></div>
                        <div class="bk-when-time"><i class="bi bi-clock"></i><?= esc(date('g:i A', $ts)) ?> – <?= esc(date('g:i A', $te)) ?></div>
                        <div class="bk-when-tags">
                            <span class="bk-tag"><i class="bi bi-hourglass-split"></i><?= esc($len) ?></span>
                            <span class="bk-tag <?= $rel[1] ?>"><i class="bi <?= $rel[2] ?>"></i><?= esc($rel[0]) ?></span>
                        </div>
                        <?php if ($stayN > 1): ?>
                        <details class="bk-days">
                            <summary>Show all <?= (int) $stayN ?> days</summary>
                            <ul>
                                <?php foreach ($stayDays as $d): ?>
                                    <li class="is-<?= esc($d['status'], 'attr') ?>"><span><?= esc(date('D, M j', strtotime($d['start_at']))) ?></span><em><?= esc(ucfirst($d['status'])) ?></em>
                                        <?php if ($d['status'] === 'approved' && strtotime($d['end_at']) > time()): ?>
                                            <form method="post" action="<?= site_url('manage/bookings/' . (int) $d['id'] . '/cancel') ?>" data-confirm="Cancel <?= esc(date('D, M j', strtotime($d['start_at'])), 'attr') ?> only? The other days stay booked." data-confirm-title="Cancel this day?" data-confirm-ok="Yes, cancel" data-confirm-cancel="Keep it" data-confirm-icon="bi-calendar-x">
                                                <?= csrf_field() ?><input type="hidden" name="single" value="1"><button class="btn btn-sm btn-outline-danger py-0">Cancel day</button>
                                            </form>
                                        <?php endif; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
            <td class="bk-status">
                <?php if ($stayN > 1): ?>
                    <?php
                        $cnt = array_count_values(array_column($stayDays, 'status'));
                        $parts = [];
                        foreach (['approved' => 'approved', 'pending' => 'pending', 'rejected' => 'rejected', 'cancelled' => 'cancelled'] as $k => $lbl) { if (! empty($cnt[$k])) { $parts[] = $cnt[$k] . ' ' . $lbl; } }
                    ?>
                    <?= status_badge(count($cnt) === 1 ? (string) array_key_first($cnt) : ($hasPending ? 'pending' : $b['status'])) ?>
                    <?php if (count($cnt) > 1): ?><div class="cell-sub mt-1"><?= esc(implode(' · ', $parts)) ?></div><?php endif; ?>
                <?php elseif ($b['status'] === 'approved'): ?>
                    <span class="pill pill-approved"><?= esc(approval_label($b)) ?></span>
                    <?php if (! empty($b['arrived_at'])): ?><span class="pill pill-arrived">Arrived <?= esc(date('g:i A', strtotime($b['arrived_at']))) ?></span>
                    <?php elseif (arrival_state($b)[0] === 'missed'): ?><span class="pill pill-missed">Did not arrive</span><?php endif; ?>
                <?php else: ?><?= status_badge($b['status']) ?><?php endif; ?>
                <?php if ($b['admin_remarks']): ?><div class="bk-remark"><i class="bi bi-chat-left-text"></i><span><?= esc($b['admin_remarks']) ?></span></div><?php endif; ?>
            </td>
            <td>
              <div class="bk-actions">
                <?php if ($hasPending): ?>
                    <a class="btn btn-sm btn-act btn-act-edit" href="<?= site_url('dashboard') ?>"><i class="bi bi-inbox"></i> Review on Dashboard</a>
                <?php elseif ($hasApproved): ?>
                    <div class="btn-row">
                        <button type="button" class="btn btn-sm btn-act btn-act-brass bk-reason-btn"
                                data-action="<?= site_url('manage/bookings/' . (int) $b['id'] . '/cancel') ?>"
                                data-title="Cancel this booking?" data-ok="Cancel booking" data-icon="bi-calendar-x" data-tone="brass"
                                data-text="<?= esc('Cancel the approved booking of ' . $b['customer_name'] . ' for ' . $b['facility_name'] . ($stayN > 1 ? ' (every day still to come)' : '') . '. The customer will be notified.', 'attr') ?>"><i class="bi bi-calendar-x"></i> Cancel booking</button>
                    </div>
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
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<!-- Reason pop-up (Reject / Cancel booking) -->
<div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content confirm-modal" id="reasonForm">
            <?= csrf_field() ?>
            <div class="confirm-head">
                <span class="confirm-icon" id="reasonIcon"><i class="bi bi-x-circle"></i></span>
                <h2 class="confirm-title" id="reasonTitle">Reason</h2>
            </div>
            <div class="confirm-body text-start">
                <p id="reasonText" class="text-center mb-3"></p>
                <label for="reasonInput" class="form-label fw-semibold">Reason <span class="text-muted fw-normal">(optional)</span></label>
                <textarea id="reasonInput" name="admin_remarks" class="form-control" rows="3" maxlength="255" placeholder="Tell the customer why"></textarea>
                <div class="form-text">The customer sees this reason in their notification.</div>
            </div>
            <div class="confirm-foot">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button>
                <button type="submit" class="btn btn-danger" id="reasonOk">Confirm</button>
            </div>
        </form>
    </div>
</div>

<!-- Customer note pop-up -->
<div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content confirm-modal">
            <div class="confirm-head">
                <span class="confirm-icon tone-brass"><i class="bi bi-sticky"></i></span>
                <h2 class="confirm-title" id="noteTitle">Customer note</h2>
                <div class="note-sub" id="noteSub"></div>
            </div>
            <div class="confirm-body"><p id="noteText" class="note-text"></p></div>
            <div class="confirm-foot"><button type="button" class="btn btn-gold" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>
<script>
(function () {
    if (!window.bootstrap) return;
    var reasonEl = document.getElementById('reasonModal'), noteEl = document.getElementById('noteModal');
    var reason = bootstrap.Modal.getOrCreateInstance(reasonEl), note = bootstrap.Modal.getOrCreateInstance(noteEl);
    var form = document.getElementById('reasonForm'), input = document.getElementById('reasonInput');

    document.addEventListener('click', function (e) {
        var r = e.target.closest('.bk-reason-btn');
        if (r) {
            var d = r.dataset, tone = d.tone || 'danger';
            form.action = d.action;
            input.value = '';
            document.getElementById('reasonTitle').textContent = d.title;
            document.getElementById('reasonText').textContent = d.text;
            var ic = document.getElementById('reasonIcon');
            ic.className = 'confirm-icon tone-' + tone;
            ic.innerHTML = '<i class="bi ' + d.icon + '"></i>';
            var ok = document.getElementById('reasonOk');
            ok.textContent = d.ok;
            ok.className = 'btn ' + (tone === 'danger' ? 'btn-danger' : 'btn-gold');
            reason.show();
            return;
        }
        var n = e.target.closest('.bk-note-btn');
        if (n) {
            document.getElementById('noteSub').textContent = n.dataset.noteName + ' · ' + n.dataset.noteFacility;
            document.getElementById('noteText').textContent = n.dataset.noteText;
            note.show();
        }
    });
    reasonEl.addEventListener('shown.bs.modal', function () { input.focus(); });
    form.addEventListener('submit', function () { document.getElementById('reasonOk').disabled = true; });
})();
</script>
<?= $this->endSection() ?>
