<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="page-head">
    <div>
        <h1>Users</h1>
        <p>Staff handle day-to-day bookings when the owner is away. Customers register on their own.</p>
    </div>
    <div class="page-actions"><a href="<?= site_url('admin/users/new') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add user</a></div>
</div>

<form method="get" class="filter-bar">
    <select name="role" class="form-select" data-dd aria-label="Filter by role" onchange="this.form.submit()">
        <option value="">All roles</option>
        <?php foreach ($roles as $key => $label): ?>
            <option value="<?= esc($key) ?>" <?= $role === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<section class="tbl-panel" id="live-users" data-live>
    <div class="sess-head">
        <div>
            <h2>All accounts</h2>
            <div class="sess-sub"><?= count($users) ?> <?= count($users) === 1 ? 'account' : 'accounts' ?><?= $role ? ' · ' . esc($roles[$role] ?? $role) : '' ?></div>
        </div>
    </div>
<?php if (empty($users)): ?>
    <div class="empty"><i class="bi bi-people"></i><strong>No users found</strong>Try a different filter.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table data-table table-hover tbl-brass">
    <thead><tr><th>Name</th><th>Role</th><th>Phone</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): $status = $u['status'] ?? 'active'; $isMe = (int) $u['id'] === $meId; ?>
        <tr>
            <td>
                <div class="cell-title"><?= esc($u['name']) ?><?= $isMe ? ' <span class="text-muted fw-normal">(you)</span>' : '' ?></div>
                <div class="cell-sub"><?= esc($u['email']) ?></div>
            </td>
            <td><span class="role-tag role-<?= esc($u['role']) ?>"><?= esc($roles[$u['role']] ?? ucfirst($u['role'])) ?></span></td>
            <td><?= esc($u['phone']) ?: '<span class="text-muted">–</span>' ?></td>
            <td><?= status_badge($status) ?></td>
            <td class="text-end text-nowrap">
                <a href="<?= site_url('admin/users/' . (int) $u['id'] . '/edit') ?>" class="btn btn-sm btn-act btn-act-edit"><i class="bi bi-pencil-square"></i> Edit</a>
                <?php if (! $isMe): ?>
                    <form method="post" action="<?= site_url('admin/users/' . (int) $u['id'] . '/toggle') ?>" class="d-inline"
                          <?= $status === 'active' ? 'data-confirm="' . esc('They will be logged out and cannot log in until you reactivate ' . $u['name'] . '\'s account.', 'attr') . '" data-confirm-title="Deactivate this account?" data-confirm-ok="Deactivate" data-confirm-icon="bi-person-slash"' : '' ?>>
                        <?= csrf_field() ?>
                        <?php if ($status === 'active'): ?>
                            <button class="btn btn-sm btn-act btn-act-brass"><i class="bi bi-person-slash"></i> Deactivate</button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-act btn-act-ghost"><i class="bi bi-person-check"></i> Reactivate</button>
                        <?php endif; ?>
                    </form>
                    <form method="post" action="<?= site_url('admin/users/' . (int) $u['id'] . '/archive') ?>" class="d-inline"
                          data-confirm="<?= esc($u['name'] . ' will be moved to the Archive and can no longer log in. You can restore the account from the Archive.', 'attr') ?>" data-confirm-title="Archive this account?" data-confirm-ok="Archive" data-confirm-icon="bi-archive">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-act btn-act-red"><i class="bi bi-archive"></i> Archive</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
</section>
<?= $this->endSection() ?>
