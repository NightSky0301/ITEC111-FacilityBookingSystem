<?= $this->extend('layouts/main') ?>
<?= $this->section('head') ?>
<script src="<?= base_url('assets/vendor/fullcalendar.min.js') ?>"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="page-head">
    <div>
        <h1>Calendar</h1>
        <p><?= is_manager() ? 'All requests and approved bookings.' : 'Click a day to see your bookings on it.' ?></p>
    </div>
    <div class="page-actions">
        <label class="visually-hidden" for="facilityFilter">Facility</label>
        <select id="facilityFilter" class="form-select">
            <option value="">All facilities</option>
            <?php foreach ($facilities as $f): ?>
                <option value="<?= (int) $f['id'] ?>" <?= (int) service('request')->getGet('facility_id') === (int) $f['id'] ? 'selected' : '' ?>><?= esc($f['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="legend">
    <span><i style="background:#f6dedb;border-left:4px solid #a93d36"></i> Booked</span>
    <span><i style="background:#fbf1d9;border-left:4px solid #c28a1c"></i> Waiting for approval</span>
    <span><i style="background:#e3eeee;border-left:4px solid #0e3b3f"></i> <?= is_manager() ? 'Your own booking' : 'Your booking' ?></span>
</div>

<div class="panel cal-fc"><div class="panel-body"><div id="calendar"></div></div></div>

<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="evTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ev-dialog"><div class="modal-content ev-modal">
        <div class="ev-head">
            <span class="ev-ic"><i class="bi bi-calendar-event"></i></span>
            <div class="ev-head-text">
                <div class="ev-kicker" id="evKicker"></div>
                <h5 class="ev-title" id="evTitle"></h5>
            </div>
            <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="ev-body" id="evBody"></div>
        <div class="ev-foot d-none" id="evFoot">
            <a href="#" id="evBook" class="btn btn-sm btn-gold">Book this day</a>
        </div>
    </div></div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filter = document.getElementById('facilityFilter');
    const modal = new bootstrap.Modal(document.getElementById('eventModal'));
    const small = window.matchMedia('(max-width: 767px)').matches;
    const esc = s => { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };
    const params = new URLSearchParams(window.location.search);
    const wantDate = /^\d{4}-\d{2}-\d{2}$/.test(params.get('date') || '') ? params.get('date') : null;
    let wantBooking = params.get('booking');
    const peso = n => '₱' + Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const KICKER = { mine: 'Your booking', booked: 'Booked', pending: 'Waiting for approval' };

    // The booking popup, used when you click a booking and when you arrive from the dashboard.
    function showEvent(ev) {
        const p = ev.extendedProps, s = ev.start, e = ev.end;
        const day = s.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
        const t = d => d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        const hrs = p.hours + (p.hours === 1 ? ' hr' : ' hrs');
        const row = (icon, label, value) => '<div class="ev-row"><span class="ev-row-ic"><i class="bi ' + icon + '"></i></span><div><small>' + label + '</small><div>' + value + '</div></div></div>';
        document.getElementById('evKicker').textContent = p.customer ? 'Requested by ' + p.customer : (KICKER[p.kind] || '');
        document.getElementById('evTitle').textContent = p.facility;
        let html = row('bi-calendar3', 'Date', esc(day)) +
            row('bi-clock', 'Time', esc(t(s) + ' – ' + t(e)) + ' <span class="ev-muted">· ' + hrs + '</span>') +
            row('bi-flag', 'Status', '<span class="pill pill-' + esc(p.status) + '">' + esc(p.status.charAt(0).toUpperCase() + p.status.slice(1)) + '</span>');
        if (p.people) html += row('bi-people', 'People', esc(p.people + (p.people === 1 ? ' person' : ' people')));
        if (p.addons && p.addons.length) html += row('bi-plus-circle', 'Add-ons', esc(p.addons.join(', ')));
        if (p.purpose) html += row('bi-chat-left-text', 'Notes', esc(p.purpose));
        if (p.total !== null && p.total !== undefined) html += '<div class="ev-total"><span>Total payment</span><strong>' + peso(p.total) + '</strong></div>';
        document.getElementById('evBody').innerHTML = html;
        document.getElementById('evFoot').classList.add('d-none');
        modal.show();
    }

    // Clicking a day: show only the signed-in customer's own bookings on that day.
    const ymdOf = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    function showDay(dateStr, timeStr) {
        const day = dateStr.substring(0, 10);
        const list = calendar.getEvents().filter(e => e.extendedProps.kind !== 'booked' && ymdOf(e.start) === day).sort((a, b) => a.start - b.start);
        if (list.length === 1) { showEvent(list[0]); return; }
        const when = new Date(day + 'T00:00');
        const t = d => d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        document.getElementById('evTitle').textContent = when.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' });
        document.getElementById('evKicker').textContent = list.length ? 'Your bookings (' + list.length + ')' : 'Your bookings';
        let html;
        if (list.length) {
            html = list.map(e => '<button type="button" class="ev-item" data-id="' + esc(e.id) + '"><span><b>' + esc(e.extendedProps.facility) + '</b><small>' + esc(t(e.start) + ' – ' + t(e.end)) + '</small></span>' +
                '<span class="ev-item-end"><span class="pill pill-' + esc(e.extendedProps.status) + '">' + esc(e.extendedProps.status.charAt(0).toUpperCase() + e.extendedProps.status.slice(1)) + '</span>' +
                (e.extendedProps.total != null ? '<strong>' + peso(e.extendedProps.total) + '</strong>' : '') + '</span></button>').join('');
        } else {
            html = '<div class="ev-empty"><span class="ev-row-ic"><i class="bi bi-calendar-x"></i></span><div><b>No bookings on this day</b>You have nothing booked or waiting for approval.</div></div>';
        }
        document.getElementById('evBody').innerHTML = html;
        const book = document.getElementById('evBook'), today = ymdOf(new Date());
        if (day >= today) {
            const qs = new URLSearchParams();
            if (filter.value) qs.set('facility_id', filter.value);
            qs.set('start', day + 'T' + (timeStr && timeStr.length >= 5 ? timeStr.substring(0, 5) : '09:00'));
            book.href = '<?= site_url('bookings/new') ?>?' + qs.toString();
            document.getElementById('evFoot').classList.remove('d-none');
        } else { document.getElementById('evFoot').classList.add('d-none'); }
        modal.show();
    }
    document.getElementById('evBody').addEventListener('click', function (e) {
        const b = e.target.closest('.ev-item'); if (!b) return;
        const ev = calendar.getEventById(b.dataset.id); if (ev) showEvent(ev);
    });

    // Month view: instead of stacking a bar per booking, show one small count chip per kind.
    const CHIP = { mine: 'Yours', pending: 'Waiting', booked: 'Booked' };
    function paintChips() {
        const root = document.getElementById('calendar');
        root.querySelectorAll('.day-chips').forEach(n => n.remove());
        root.querySelectorAll('.has-ev').forEach(n => n.classList.remove('has-ev'));
        if (calendar.view.type !== 'dayGridMonth') return;
        const by = {};
        calendar.getEvents().forEach(e => {
            const d = ymdOf(e.start), k = e.extendedProps.kind;
            ((by[d] = by[d] || {})[k] = (by[d][k] || 0) + 1);
        });
        root.querySelectorAll('.fc-daygrid-day[data-date]').forEach(cell => {
            const c = by[cell.dataset.date]; if (!c) return;
            const frame = cell.querySelector('.fc-daygrid-day-frame'); if (!frame) return;
            cell.classList.add('has-ev');
            const box = document.createElement('div');
            box.className = 'day-chips';
            ['mine', 'pending', 'booked'].forEach(k => {
                if (!c[k]) return;
                const chip = document.createElement('span');
                chip.className = 'day-chip dc-' + k;
                chip.title = c[k] + ' ' + CHIP[k].toLowerCase();
                chip.innerHTML = '<b>' + c[k] + '</b><em>' + CHIP[k] + '</em>';
                box.appendChild(chip);
            });
            frame.appendChild(box);
        });
    }
    const repaint = () => setTimeout(paintChips, 0);

    const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: small ? 'listWeek' : 'dayGridMonth',
        initialDate: wantDate || undefined,
        height: 'auto',
        nowIndicator: true,
        dayMaxEvents: 3,
        headerToolbar: small
            ? { left: 'prev,next', center: 'title', right: 'today' }
            : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
        buttonText: { today: 'Today', month: 'Month', week: 'Week', day: 'Day', list: 'List' },
        eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short', omitZeroMinute: true },
        fixedWeekCount: false,
        events: function (info, success, failure) {
            const url = new URL('<?= site_url('calendar/events') ?>', window.location.origin);
            url.searchParams.set('start', info.startStr);
            url.searchParams.set('end', info.endStr);
            if (filter.value) url.searchParams.set('facility_id', filter.value);
            fetch(url, { credentials: 'same-origin' }).then(r => r.json()).then(success).catch(failure);
        },
        datesSet: repaint,
        windowResize: repaint,
        eventsSet: function () {
            repaint();
            // Arrived from the dashboard: open that booking's popup once it has loaded.
            if (!wantBooking) return;
            const ev = calendar.getEventById(wantBooking);
            if (ev) { wantBooking = null; showEvent(ev); }
        },
        eventClick: function (info) { info.jsEvent.preventDefault(); showEvent(info.event); },
        <?php if (! is_manager()): ?>
        dateClick: function (info) { showDay(info.dateStr, info.dateStr.length > 10 ? info.dateStr.substring(11, 16) : ''); },
        <?php else: ?>
        dateClick: function (info) { calendar.changeView('timeGridDay', info.dateStr.substring(0, 10)); },
        <?php endif; ?>
    });
    calendar.render();
    filter.addEventListener('change', () => calendar.refetchEvents());
});
</script>
<?= $this->endSection() ?>
