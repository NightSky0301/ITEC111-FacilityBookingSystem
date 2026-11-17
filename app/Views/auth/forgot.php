<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>
<?php $sent = session()->getFlashdata('sent'); ?>
<h1>Forgot password?</h1>
<?php if ($sent): ?>
    <div class="forgot-done" role="status">
        <i class="bi bi-envelope-check"></i>
        <div>
            <strong>Check your email</strong>
            <p>If an account exists for <strong><?= esc($sent) ?></strong>, we sent a link to reset the password. It works once and expires in 30 minutes.</p>
        </div>
    </div>
    <a href="<?= site_url('login') ?>" class="btn btn-primary w-100 py-2">Back to log in</a>
<?php else: ?>
    <p class="lead-in">Enter your account email and we will send you a link to choose a new password.</p>
    <form method="post" action="<?= site_url('forgot-password') ?>">
        <?= csrf_field() ?>
        <div class="mb-4">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= esc(old('email')) ?>" autocomplete="email" required autofocus>
        </div>
        <button class="btn btn-primary w-100 py-2">Send reset link</button>
    </form>
    <p class="mt-4 mb-0 text-muted"><a href="<?= site_url('login') ?>"><i class="bi bi-arrow-left"></i> Back to log in</a></p>
<?php endif; ?>
<?= $this->endSection() ?>
