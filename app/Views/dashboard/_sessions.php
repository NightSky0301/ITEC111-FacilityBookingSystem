<?php
/** Staff / admin dashboard: today's approved sessions as cards, each opens a check-in popup. */
$nowTs = time();
?>
<section class="sess-panel" aria-label="Sessions today">
    <div class="sess-head">
        <div>
            <h2>Session(s) today</h2>
            <div class="sess-sub"><?= esc(date('l, F j')) ?> · <?= count($latest) ?> approved <?= count($latest) === 1 ? 'booking' : 'bookings' ?></div>
        </div>
        <a href="<?= site_url('manage/bookings?status=approved') ?>" class="btn btn-gold btn-sm">View all <i class="bi bi-arrow-right ms-1"></i></a>
    </div>

    <?php if (empty($latest)): ?>
        <div class="empty">
            <i class="bi bi-calendar-check"></i>
            <strong>No sessions today</strong>
            Approved bookings for today will show up here.
        </div>
    <?php else: ?>
    <div class="sess-grid">
        <?php foreach ($latest as $b):
            $s = strtotime($b['start_at']);
            $e = strtotime($b['end_at']);
            $arrived = ! empty($b['arrived_at']);
            if ($arrived)           { $state = ['Arrived', 'is-arrived']; }
            elseif ($nowTs >= $e)   { $state = ['Finished', 'is-done']; }
            elseif ($nowTs >= $s)   { $state = ['Waiting for arrival', 'is-due']; }
            else                    { $state = ['Upcoming', 'is-soon']; }
            $perHead = \App\Models\FacilityModel::isPerHead((string) ($b['facility_type'] ?? ''));
            $img = facility_image(['type' => $b['facility_type'] ?? 'other', 'name' => $b['facility_name'] ?? '', 'image' => $b['facility_image'] ?? null]);
        ?>
        <button type="button" class="sess-card <?= $state[1] ?>"
            data-bs-toggle="modal" data-bs-target="#sessModal"
            data-id="<?= (int) $b['id'] ?>"
            data-customer="<?= esc($b['customer_name'], 'attr') ?>"
            data-court="<?= esc($b['facility_name'], 'attr') ?>"
            data-start="<?= $s ?>" data-end="<?= $e ?>"
            data-arrival="<?= esc(date('g:i A', $s), 'attr') ?>"
            data-range="<?= esc(fmt_range($b['start_at'], $b['end_at']), 'attr') ?>"
            data-people="<?= $perHead ? (int) $b['guests'] . ' ' . ((int) $b['guests'] === 1 ? 'person' : 'people') : '' ?>"
            data-arrived="<?= $arrived ? esc(date('g:i A', strtotime($b['arrived_at'])), 'attr') : '' ?>"
            data-img="<?= esc($img, 'attr') ?>">
            <span class="sess-photo" style="background-image:url('<?= esc($img, 'attr') ?>')">
                <span class="sess-state"><?= $state[0] ?></span>
            </span>
            <span class="sess-body">
                <span class="sess-time"><?= date('g:i A', $s) ?> – <?= date('g:i A', $e) ?></span>
                <span class="sess-court"><?= esc($b['facility_name']) ?></span>
                <span class="sess-who"><i class="bi bi-person-circle"></i> <?= esc($b['customer_name']) ?></span>
            </span>
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<div class="modal fade sess-modal" id="sessModal" tabindex="-1" aria-labelledby="sessTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="sess-kicker" id="sessState">Session</div>
                    <h2 class="modal-title" id="sessTitle">Session</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="sess-dl">
                    <div><dt>Customer</dt><dd id="sessCustomer">–</dd></div>
                    <div><dt>Court</dt><dd id="sessCourt">–</dd></div>
                    <div><dt>Arrival time</dt><dd id="sessArrival">–</dd></div>
                    <div><dt>Booked for</dt><dd id="sessRange">–</dd></div>
                    <div id="sessPeopleRow"><dt>People</dt><dd id="sessPeople">–</dd></div>
                </dl>
                <p class="sess-hint" id="sessHint" role="status"></p>
            </div>
            <div class="modal-footer">
                <form method="post" id="sessForm" class="w-100">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-gold w-100" id="sessBtn" disabled>Confirm Arrival</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // Use the server's clock so a wrong computer clock cannot unlock the button early.
    const skew = <?= $nowTs ?> * 1000 - Date.now();
    const now = () => Date.now() + skew;
    const $ = id => document.getElementById(id);
    const arriveUrl = '<?= site_url('manage/bookings') ?>';
    let cur = null;

    function refresh() {
        if (!cur) return;
        const t = Math.floor(now() / 60000) * 60000;   // to the minute, like the server
        const s = cur.start * 1000, e = cur.end * 1000;
        const btn = $('sessBtn'), hint = $('sessHint');
        if (cur.arrived) {
            btn.disabled = true; btn.textContent = 'Arrival confirmed'; hint.textContent = 'Checked in at ' + cur.arrived + '.';
        } else if (t < s) {
            const mins = Math.ceil((s - now()) / 60000);
            btn.disabled = true; btn.textContent = 'Confirm Arrival';
            hint.textContent = 'Available at ' + cur.arrival + ' (in ' + mins + (mins === 1 ? ' minute' : ' minutes') + ').';
        } else if (now() >= e) {
            btn.disabled = true; btn.textContent = 'Confirm Arrival'; hint.textContent = 'This session has finished.';
        } else {
            btn.disabled = false; btn.textContent = 'Confirm Arrival'; hint.textContent = 'The arrival time has come. Confirm when the customer is here.';
        }
    }

    document.getElementById('sessModal').addEventListener('show.bs.modal', function (ev) {
        const d = ev.relatedTarget.dataset;
        cur = { id: d.id, start: +d.start, end: +d.end, arrival: d.arrival, arrived: d.arrived };
        $('sessTitle').textContent = d.court;
        $('sessState').textContent = d.arrived ? 'Arrived' : 'Session today';
        $('sessCustomer').textContent = d.customer;
        $('sessCourt').textContent = d.court;
        $('sessArrival').textContent = d.arrival;
        $('sessRange').textContent = d.range;
        $('sessPeople').textContent = d.people;
        $('sessPeopleRow').hidden = !d.people;
        $('sessForm').action = arriveUrl + '/' + d.id + '/arrive';
        refresh();
    });
    setInterval(refresh, 1000);
})();
</script>
