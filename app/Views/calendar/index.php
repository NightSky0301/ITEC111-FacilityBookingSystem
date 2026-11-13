<?= $this->extend('layouts/main') ?>
<?= $this->section('head') ?>
<script src="<?= base_url('assets/vendor/fullcalendar.min.js') ?>"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="page-head">
    <div>
        <h1>Calendar</h1>
        <p><?= is_admin() ? 'All requests and approved bookings.' : 'Click a free day to start a booking request.' ?></p>
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
    <span><i style="background:#a93d36"></i> Booked</span>
    <span><i style="background:#c28a1c"></i> Waiting for approval</span>
    <?php if (! is_admin()): ?><span><i style="background:#0e3b3f"></i> Your booking</span><?php endif; ?>
</div>

<div class="panel"><div class="panel-body"><div id="calendar"></div></div></div>

<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="evTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="evTitle"></h5><button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body" id="evBody"></div>
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

    const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: small ? 'listWeek' : 'dayGridMonth',
        height: 'auto',
        nowIndicator: true,
        dayMaxEvents: 3,
        headerToolbar: small
            ? { left: 'prev,next', center: 'title', right: 'today' }
            : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
        buttonText: { today: 'Today', month: 'Month', week: 'Week', day: 'Day', list: 'List' },
        eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
        events: function (info, success, failure) {
            const url = new URL('<?= site_url('calendar/events') ?>', window.location.origin);
            url.searchParams.set('start', info.startStr);
            url.searchParams.set('end', info.endStr);
            if (filter.value) url.searchParams.set('facility_id', filter.value);
            fetch(url, { credentials: 'same-origin' }).then(r => r.json()).then(success).catch(failure);
        },
        eventClick: function (info) {
            const p = info.event.extendedProps;
            const fmt = d => d ? d.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' }) : '';
            document.getElementById('evTitle').textContent = info.event.title;
            let html = '<dl class="detail-list">' +
                '<dt>Facility</dt><dd>' + esc(p.facility) + '</dd>' +
                '<dt>When</dt><dd>' + esc(fmt(info.event.start)) + ' to ' + esc(fmt(info.event.end)) + '</dd>' +
                '<dt>Players</dt><dd>' + esc(p.guests) + '</dd>' +
                '<dt>Status</dt><dd>' + esc(p.status.charAt(0).toUpperCase() + p.status.slice(1)) + '</dd>';
            if (p.purpose) html += '<dt>Purpose</dt><dd>' + esc(p.purpose) + '</dd>';
            document.getElementById('evBody').innerHTML = html + '</dl>';
            modal.show();
        },
        <?php if (! is_admin()): ?>
        dateClick: function (info) {
            const qs = new URLSearchParams();
            if (filter.value) qs.set('facility_id', filter.value);
            qs.set('start', info.dateStr.length === 10 ? info.dateStr + 'T09:00' : info.dateStr.substring(0, 16));
            window.location.href = '<?= site_url('bookings/new') ?>?' + qs.toString();
        },
        <?php endif; ?>
    });
    calendar.render();
    filter.addEventListener('change', () => calendar.refetchEvents());
});
</script>
<?= $this->endSection() ?>
