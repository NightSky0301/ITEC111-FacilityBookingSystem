<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $editing = ! empty($user); $isMe = $editing && (int) $user['id'] === (int) session()->get('user_id'); ?>
<div class="page-head">
    <div>
        <h1><?= $editing ? 'Edit user' : 'Add user' ?></h1>
        <p><?= $editing ? 'Leave the password empty to keep the current one.' : 'Create an account for a staff member, another admin, or a customer.' ?></p>
    </div>
</div>

<div class="row g-4">
<div class="col-lg-7">
<div class="panel"><div class="panel-body">
<form method="post" action="<?= $editing ? site_url('admin/users/' . (int) $user['id']) : site_url('admin/users') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="name">Full name</label>
        <input type="text" id="name" name="name" class="form-control" value="<?= esc(old('name', $user['name'] ?? '')) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label" for="email">Email (used to log in)</label>
        <input type="email" id="email" name="email" class="form-control" value="<?= esc(old('email', $user['email'] ?? '')) ?>" autocomplete="off" required>
    </div>
    <div class="row">
        <div class="col-sm-6 mb-3">
            <label class="form-label" for="phone">Phone <span class="text-muted fw-normal">(optional)</span></label>
            <input type="text" id="phone" name="phone" class="form-control" value="<?= esc(old('phone', $user['phone'] ?? '')) ?>">
        </div>
        <div class="col-sm-6 mb-3">
            <label class="form-label" for="role">Role</label>
            <select id="role" name="role" class="form-select" <?= $isMe ? 'disabled' : '' ?>>
                <?php foreach ($roles as $key => $label): ?>
                    <option value="<?= esc($key) ?>" <?= old('role', $user['role'] ?? 'staff') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($isMe): ?>
                <input type="hidden" name="role" value="<?= esc($user['role']) ?>">
                <div class="form-text">You cannot change your own role.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="mb-4">
        <label class="form-label" for="password"><?= $editing ? 'New password' : 'Password' ?></label>
        <input type="password" id="password" name="password" class="form-control" minlength="6" autocomplete="new-password" <?= $editing ? '' : 'required' ?>>
        <div class="form-text">At least 6 characters.</div>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary">Save user</button>
        <a href="<?= site_url('admin/users') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
</div></div>
</div>

<div class="col-lg-5">
<div class="panel"><div class="panel-body">
    <h2 class="h5">What each role can do</h2>
    <p class="mb-1 mt-3"><span class="role-tag role-staff">Staff</span></p>
    <ul class="perm-list">
        <li><i class="bi bi-check-lg"></i> Approve, reject and cancel bookings</li>
        <li><i class="bi bi-check-lg"></i> See the calendar and facilities</li>
        <li><i class="bi bi-check-lg"></i> Get notified of new requests</li>
        <li class="no"><i class="bi bi-x-lg"></i> Cannot edit facilities or prices</li>
        <li class="no"><i class="bi bi-x-lg"></i> Cannot manage users</li>
    </ul>
    <p class="mb-1 mt-4"><span class="role-tag role-admin">Admin</span></p>
    <ul class="perm-list">
        <li><i class="bi bi-check-lg"></i> Everything staff can do</li>
        <li><i class="bi bi-check-lg"></i> Add and edit facilities and prices</li>
        <li><i class="bi bi-check-lg"></i> Add, edit and deactivate users</li>
    </ul>
    <p class="mb-1 mt-4"><span class="role-tag role-customer">Customer</span></p>
    <ul class="perm-list">
        <li><i class="bi bi-check-lg"></i> Request and cancel their own bookings</li>
    </ul>
</div></div>
</div>
</div>
<?= $this->endSection() ?>
