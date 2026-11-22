<?php
/** Staff / admin dashboard: today's approved sessions as cards, each opens a check-in popup. */
$nowTs = time();
?>
<div id="live-sessions" data-live>
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
    <div class="sess-grid" data-pager="4">
        <?php foreach ($latest as $b):
            $s = strtotime($b['start_at']);
            $e = strtotime($b['end_at']);
            $arrived = ! empty($b['arrived_at']);
            [$wStart, $wEnd] = arrival_window($b);
            $state = arrival_state($b, $nowTs);
            $state = [$state[1], $state[2]];
            $perHead = \App\Models\FacilityModel::isPerHead((string) ($b['facility_type'] ?? ''));
            $img = facility_image(['type' => $b['facility_type'] ?? 'other', 'name' => $b['facility_name'] ?? '', 'image' => $b['facility_image'] ?? null]);
        ?>
        <button type="button" class="sess-card <?= $state[1] ?>"
            data-bs-toggle="modal" data-bs-target="#sessModal"
            data-id="<?= (int) $b['id'] ?>"
            data-customer="<?= esc($b['customer_name'], 'attr') ?>"
            data-court="<?= esc($b['facility_name'], 'attr') ?>"
            data-start="<?= $s ?>" data-end="<?= $e ?>"
            data-wend="<?= $wEnd ?>"
            data-arrival="<?= esc(date('g:i A', $wStart) . ' – ' . date('g:i A', $wEnd), 'attr') ?>"
            data-range="<?= esc(fmt_range($b['start_at'], $b['end_at']), 'attr') ?>"
            data-people="<?= $perHead ? (int) $b['guests'] . ' ' . ((int) $b['guests'] === 1 ? 'person' : 'people') : '' ?>"
            data-arrived="<?= $arrived ? esc(date('g:i A', strtotime($b['arrived_at'])), 'attr') : '' ?>"
            data-img="<?= esc($img, 'attr') ?>">
            <span class="sess-photo" style="background-image:url('<?= esc($img, 'attr') ?>')">
                <span class="sess-state" data-state><?= $state[0] ?></span>
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
</div>

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
                    <div><dt>Arrival window</dt><dd id="sessArrival">–</dd></div>
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

    // Same rules as the server (arrival_state in booking_helper.php): the confirm button works only inside the arrival window.
    function stateOf(d) {
        const t = Math.floor(now() / 60000) * 60000, s = +d.start * 1000, w = +d.wend * 1000, e = +d.end * 1000;
        if (d.arrived) return now() >= e ? ['finished', 'Arrived · Finished', 'is-arrived is-done'] : ['arrived', 'Arrived', 'is-arrived'];
        if (t < s) return ['upcoming', 'Upcoming', 'is-soon'];
        if (now() < w) return ['open', 'Waiting for arrival', 'is-due'];
        return ['missed', 'Did not arrive', 'is-missed'];
    }

    function paintCards() {
        document.querySelectorAll('.sess-card[data-start]').forEach(function (c) {
            const st = stateOf(c.dataset);
            c.className = 'sess-card ' + st[2];
            const el = c.querySelector('[data-state]'); if (el) el.textContent = st[1];
        });
    }

    function refresh() {
        paintCards();
        if (!cur) return;
        const st = stateOf(cur), btn = $('sessBtn'), hint = $('sessHint');
        $('sessState').textContent = st[0] === 'open' || st[0] === 'upcoming' ? 'Session today' : st[1];
        btn.textContent = 'Confirm Arrival';
        btn.disabled = st[0] !== 'open';
        if (st[0] === 'arrived' || st[0] === 'finished') {
            btn.textContent = 'Arrival confirmed';
            hint.textContent = 'Checked in at ' + cur.arrived + '.' + (st[0] === 'finished' ? ' The session has finished.' : '');
        } else if (st[0] === 'upcoming') {
            const mins = Math.ceil((cur.start * 1000 - now()) / 60000);
            hint.textContent = 'Arrival can be confirmed from ' + cur.arrival + ' (opens in ' + mins + (mins === 1 ? ' minute' : ' minutes') + ').';
        } else if (st[0] === 'open') {
            hint.textContent = 'Arrival window is open (' + cur.arrival + '). Confirm when the customer is here.';
        } else {
            hint.textContent = 'The arrival window (' + cur.arrival + ') has passed. This booking is marked as Did not arrive.';
        }
    }

    document.getElementById('sessModal').addEventListener('show.bs.modal', function (ev) {
        const d = ev.relatedTarget.dataset;
        cur = { id: d.id, start: +d.start, end: +d.end, wend: +d.wend, arrival: d.arrival, arrived: d.arrived };
        $('sessTitle').textContent = d.court;
        $('sessCustomer').textContent = d.customer;
        $('sessCourt').textContent = d.court;
        $('sessArrival').textContent = d.arrival;
        $('sessRange').textContent = d.range;
        $('sessPeople').textContent = d.people;
        $('sessPeopleRow').hidden = !d.people;
        $('sessForm').action = arriveUrl + '/' + d.id + '/arrive';
        refresh();
    });
    paintCards();
    setInterval(refresh, 1000);
})();
</script>
