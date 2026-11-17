<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $tabs = [
        'facilities' => ['Facilities', 'bi-grid-3x3-gap', count($facilities)],
        'users'      => ['Accounts',   'bi-people',       count($users)],
        'bookings'   => ['Bookings',   'bi-calendar-check', count($bookings)],
    ];
    $count = $tabs[$tab][2];
    $restore = static function (string $what, int $id, string $name): string {
        return '<form method="post" action="' . site_url('admin/archive/' . $what . '/' . $id . '/restore') . '" class="d-inline" '
            . 'data-confirm="' . esc($name . ' will be put back and become available again.', 'attr') . '" data-confirm-title="Restore this item?" '
            . 'data-confirm-ok="Restore" data-confirm-tone="brass" data-confirm-icon="bi-arrow-counterclockwise">'
            . csrf_field() . '<button class="btn btn-sm btn-gold"><i class="bi bi-arrow-counterclockwise me-1"></i>Restore</button></form>';
    };
?>
<div class="page-head">
    <div>
        <h1>Archive</h1>
        <p>Deleted facilities, accounts and bookings are kept here. Restore anything you removed by mistake.</p>
    </div>
</div>

<nav class="arc-tabs" aria-label="Archive sections">
    <?php foreach ($tabs as $key => [$label, $icon, $n]): ?>
        <a href="<?= site_url('admin/archive?tab=' . $key) ?>" class="arc-tab <?= $tab === $key ? 'active' : '' ?>" <?= $tab === $key ? 'aria-current="page"' : '' ?>>
            <i class="bi <?= $icon ?>"></i> <?= $label ?> <span class="arc-count"><?= $n ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<section class="tbl-panel">
    <div class="sess-head">
        <div>
            <h2>Deleted <?= strtolower($tabs[$tab][0]) ?></h2>
            <div class="sess-sub"><?= $count ?> <?= $count === 1 ? 'item' : 'items' ?> in the archive</div>
        </div>
    </div>

<?php if ($count === 0): ?>
    <div class="empty"><i class="bi bi-archive"></i><strong>Nothing here</strong>Deleted <?= strtolower($tabs[$tab][0]) ?> will show up here so you can restore them.</div>
<?php elseif ($tab === 'facilities'): ?>
    <div class="table-responsive"><table class="table data-table table-hover tbl-brass">
        <thead><tr><th>Facility</th><th>Type</th><th>Capacity</th><th>Rate</th><th>Deleted</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($facilities as $f):
            $img = facility_image($f); ?>
            <tr>
                <td><div class="d-flex align-items-center gap-3"><img src="<?= $img ?>" alt="" class="table-thumb"><div class="cell-title"><?= esc($f['name']) ?></div></div></td>
                <td><span class="type-chip"><?= facility_icon_html($f['type']) ?><?= esc($types[$f['type']] ?? ucfirst($f['type'])) ?></span></td>
                <td><?= (int) $f['capacity'] ?></td>
                <td>₱<?= number_format((float) $f['price_per_hour'], 2) ?></td>
                <td><?= esc(date('M j, Y · g:i A', strtotime($f['deleted_at']))) ?></td>
                <td class="text-end"><?= $restore('facilities', (int) $f['id'], $f['name']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php elseif ($tab === 'users'): ?>
    <div class="table-responsive"><table class="table data-table table-hover tbl-brass">
        <thead><tr><th>Name</th><th>Role</th><th>Phone</th><th>Deleted</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><div class="cell-title"><?= esc($u['name']) ?></div><div class="cell-sub"><?= esc($u['email']) ?></div></td>
                <td><span class="role-tag role-<?= esc($u['role']) ?>"><?= esc($roles[$u['role']] ?? ucfirst($u['role'])) ?></span></td>
                <td><?= esc($u['phone']) ?: '<span class="text-muted">–</span>' ?></td>
                <td><?= esc(date('M j, Y · g:i A', strtotime($u['deleted_at']))) ?></td>
                <td class="text-end"><?= $restore('users', (int) $u['id'], $u['name']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php else: ?>
    <div class="table-responsive"><table class="table data-table table-hover tbl-brass">
        <thead><tr><th>Customer</th><th>Facility</th><th>When</th><th>Status</th><th>Deleted</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($bookings as $b): ?>
            <tr>
                <td><div class="cell-title"><?= esc($b['customer_name'] ?? 'Unknown') ?></div><div class="cell-sub"><?= esc($b['customer_email'] ?? '') ?></div></td>
                <td><div class="cell-title"><?= esc($b['facility_name'] ?? 'Unknown') ?></div></td>
                <td><?= esc(fmt_range($b['start_at'], $b['end_at'])) ?></td>
                <td><?= status_badge($b['status']) ?></td>
                <td><?= esc(date('M j, Y · g:i A', strtotime($b['deleted_at']))) ?></td>
                <td class="text-end"><?= $restore('bookings', (int) $b['id'], 'This booking') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
</section>
<?= $this->endSection() ?>
