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
    <select name="role" class="form-select" aria-label="Filter by role" onchange="this.form.submit()">
        <option value="">All roles</option>
        <?php foreach ($roles as $key => $label): ?>
            <option value="<?= esc($key) ?>" <?= $role === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<div class="panel">
<?php if (empty($users)): ?>
    <div class="empty"><i class="bi bi-people"></i><strong>No users found</strong>Try a different filter.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table data-table table-hover">
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
                <a href="<?= site_url('admin/users/' . (int) $u['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                <?php if (! $isMe): ?>
                    <form method="post" action="<?= site_url('admin/users/' . (int) $u['id'] . '/toggle') ?>" class="d-inline"
                          <?= $status === 'active' ? 'onsubmit="return confirm(\'Deactivate this account? They will be logged out and cannot log in until you reactivate it.\')"' : '' ?>>
                        <?= csrf_field() ?>
                        <?php if ($status === 'active'): ?>
                            <button class="btn btn-sm btn-outline-danger">Deactivate</button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary">Reactivate</button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
</div>
<?= $this->endSection() ?>
