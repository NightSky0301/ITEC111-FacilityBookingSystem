<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $readOnly = ! empty($readOnly);   // staff: same table, no add / edit / archive
    $cats = [
        'activities'    => ['Sports Activities', 'bi-trophy'],
        'hotels'        => ['Hotels & Suites', 'bi-building'],
        'fitness'       => ['Fitness & Wellbeing', 'bi-heart-pulse'],
        'entertainment' => ['Entertainment', 'bi-controller'],
    ];
    $count = array_fill_keys(array_keys($cats), 0);
    $activeN = 0;
    foreach ($facilities as $f) {
        $count[\App\Models\FacilityModel::sectionOf($f)]++;
        if (($f['status'] ?? '') === 'active') { $activeN++; }
    }
?>
<?php if (! $readOnly): ?>
<div class="page-head is-bare">
    <div class="page-actions">
        <a href="<?= site_url('admin/addons') ?>" class="btn btn-head"><i class="bi bi-plus-circle"></i> Manage add-ons</a>
        <a href="<?= site_url('admin/facilities/new') ?>" class="btn btn-head is-solid"><i class="bi bi-plus-lg"></i> Add facility</a>
    </div>
</div>
<?php endif; ?>

<section class="tbl-panel fy" id="live-facilities" data-live data-fy>
    <div class="sess-head">
        <div>
            <h2>All facilities</h2>
            <div class="sess-sub"><span data-fy-count><?= count($facilities) ?></span> <?= count($facilities) === 1 ? 'facility' : 'facilities' ?> · <?= $activeN ?> active</div>
        </div>
        <label class="fy-search"><i class="bi bi-search"></i><input type="search" placeholder="Search facilities" data-fy-q aria-label="Search facilities" autocomplete="off"></label>
    </div>

    <div class="aq-filters fy-filters" role="tablist" aria-label="Filter by category">
        <button type="button" class="aq-pill is-on" data-cat="all">All <b><?= count($facilities) ?></b></button>
        <?php foreach ($cats as $key => [$label, $icon]): ?>
            <button type="button" class="aq-pill" data-cat="<?= $key ?>"><i class="bi <?= $icon ?>"></i> <?= esc($label) ?> <b><?= $count[$key] ?></b></button>
        <?php endforeach; ?>
    </div>

<?php if (empty($facilities)): ?>
    <div class="empty"><i class="bi bi-geo-alt"></i><strong>No facilities yet</strong>Add your first court, such as basketball, volleyball or pickleball.</div>
<?php else: ?>
    <div class="table-responsive">
    <table class="table data-table table-hover tbl-brass fy-table">
        <thead><tr><th>Facility</th><th>Type</th><th>Capacity</th><th>Price</th><th>Status</th><th class="text-end"><?= $readOnly ? 'Schedule' : 'Actions' ?></th></tr></thead>
        <tbody>
        <?php foreach ($facilities as $f):
            $sec    = \App\Models\FacilityModel::sectionOf($f);
            $isRoom = $sec === 'hotels';
            $head   = \App\Models\FacilityModel::isPerHead($f);
            $unit   = $isRoom ? 'per night' : ($head ? 'per person / hour' : 'per hour');
            $typeNm = $types[$f['type']] ?? ucfirst($f['type']);
        ?>
            <tr data-cat="<?= $sec ?>" data-name="<?= esc(mb_strtolower($f['name'] . ' ' . $typeNm . ' ' . $cats[$sec][0]), 'attr') ?>" class="<?= ($f['status'] ?? '') === 'active' ? '' : 'is-off' ?>">
                <td>
                    <div class="fy-who">
                        <img src="<?= facility_image($f) ?>" alt="" class="fy-thumb" loading="lazy">
                        <div>
                            <div class="cell-title"><?= esc($f['name']) ?></div>
                            <div class="cell-sub"><i class="bi <?= $cats[$sec][1] ?>"></i> <?= esc($cats[$sec][0]) ?></div>
                        </div>
                    </div>
                </td>
                <td><span class="type-chip"><?= facility_icon_html($f['type']) ?><?= esc($typeNm) ?></span></td>
                <td><span class="fy-cap"><i class="bi bi-people"></i> <?= (int) $f['capacity'] ?></span></td>
                <td><div class="fy-price">₱<?= number_format((float) $f['price_per_hour'], 0) ?></div><div class="cell-sub"><?= $unit ?></div></td>
                <td><?= status_badge($f['status']) ?></td>
                <td class="text-end text-nowrap">
                    <a href="<?= site_url('calendar') . '?facility_id=' . (int) $f['id'] ?>" class="btn btn-sm btn-act btn-act-ghost"><i class="bi bi-calendar3"></i> Schedule</a>
<?php if (! $readOnly): ?>
                    <a href="<?= site_url('admin/facilities/' . (int) $f['id'] . '/edit') ?>" class="btn btn-sm btn-act btn-act-edit"><i class="bi bi-pencil-square"></i> Edit</a>
<?php endif; ?>
<?php if (! $readOnly): ?>
                    <form method="post" action="<?= site_url('admin/facilities/' . (int) $f['id'] . '/archive') ?>" class="d-inline" data-confirm="<?= esc($f['name'] . ' will be moved to the Archive. Booking history is kept and you can restore it any time.', 'attr') ?>" data-confirm-title="Archive this facility?" data-confirm-ok="Archive" data-confirm-icon="bi-archive">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-act btn-act-ghost-red"><i class="bi bi-archive"></i> Archive</button>
                    </form>
<?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <div class="empty fy-none" hidden><i class="bi bi-search"></i><strong>No facilities match</strong>Try another category or search.</div>
<?php endif; ?>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const KEY = 'fyCat';
    let q = '';
    function cat() { try { return sessionStorage.getItem(KEY) || 'all'; } catch (e) { return 'all'; } }
    function apply() {
        const root = document.querySelector('[data-fy]'); if (!root) return;
        let c = cat();
        if (!root.querySelector('.fy-filters [data-cat="' + c + '"]')) c = 'all';
        root.querySelectorAll('.fy-filters .aq-pill').forEach(b => b.classList.toggle('is-on', b.dataset.cat === c));
        let n = 0;
        root.querySelectorAll('.fy-table tbody tr').forEach(r => {
            const show = (c === 'all' || r.dataset.cat === c) && (!q || r.dataset.name.indexOf(q) !== -1);
            r.hidden = !show; if (show) n++;
        });
        const cnt = root.querySelector('[data-fy-count]'); if (cnt) cnt.textContent = n;
        const none = root.querySelector('.fy-none'), tbl = root.querySelector('.table-responsive');
        if (none) none.hidden = n !== 0; if (tbl) tbl.hidden = n === 0;
    }
    document.addEventListener('click', e => {
        const b = e.target.closest('.fy-filters .aq-pill'); if (!b) return;
        try { sessionStorage.setItem(KEY, b.dataset.cat); } catch (x) {}
        apply();
    });
    document.addEventListener('input', e => {
        if (!e.target.matches('[data-fy-q]')) return;
        q = e.target.value.trim().toLowerCase(); apply();
    });
    document.addEventListener('live:changed', () => setTimeout(() => {
        const i = document.querySelector('[data-fy-q]'); if (i && q) i.value = q;
        apply();
    }, 0));
    apply();
})();
</script>
<?= $this->endSection() ?>
