<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="tb-row">
    <form method="get" class="tb-filter">
        <select name="role" class="form-select" data-dd aria-label="Filter by role" onchange="this.form.submit()">
            <option value="">All roles</option>
            <?php foreach ($roles as $key => $label): ?>
                <option value="<?= esc($key) ?>" <?= $role === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <label class="tb-search"><i class="bi bi-search" aria-hidden="true"></i><input type="search" placeholder="Search name, email, phone, role..." data-smart-search="#live-users" data-smart-item="tbody tr" aria-label="Search users" autocomplete="off" spellcheck="false"></label>
    <a href="<?= site_url('admin/users/new') ?>" class="btn btn-primary tb-add"><i class="bi bi-plus-lg"></i> Add user</a>
</div>

<section class="tbl-panel" id="live-users" data-live>
    <div class="sess-head">
        <div>
            <h2>All accounts</h2>
            <div class="sess-sub"><span data-smart-count><?= count($users) ?></span> <?= count($users) === 1 ? 'account' : 'accounts' ?><?= $role ? ' · ' . esc($roles[$role] ?? $role) : '' ?></div>
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
                    <?php if ($status === 'active'): ?>
                        <span class="d-inline-block" tabindex="0" title="Deactivate this account first. Only deactivated accounts can be archived.">
                            <button type="button" class="btn btn-sm btn-act btn-act-red" disabled aria-disabled="true"><i class="bi bi-lock"></i> Archive</button>
                        </span>
                    <?php else: ?>
                    <form method="post" action="<?= site_url('admin/users/' . (int) $u['id'] . '/archive') ?>" class="d-inline"
                          data-confirm="<?= esc($u['name'] . ' will be moved to the Archive and can no longer log in. You can restore the account from the Archive.', 'attr') ?>" data-confirm-title="Archive this account?" data-confirm-ok="Archive" data-confirm-icon="bi-archive">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-act btn-act-red"><i class="bi bi-archive"></i> Archive</button>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<div class="empty" data-smart-empty hidden><i class="bi bi-search"></i><strong>No match</strong>No account matches your search.</div>
<?php endif; ?>
</section>
<?= $this->endSection() ?>
