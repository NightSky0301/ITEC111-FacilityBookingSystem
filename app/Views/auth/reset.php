<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>
<?php if (empty($token)): ?>
    <h1>Link not valid</h1>
    <p class="lead-in">This reset link has expired or was already used. Request a new one.</p>
    <a href="<?= site_url('forgot-password') ?>" class="btn btn-primary w-100 py-2">Request a new link</a>
<?php else: ?>
<h1>Choose a new password</h1>
<p class="lead-in">Pick a strong password you have not used before.</p>
<form method="post" action="<?= site_url('reset-password/' . $token) ?>" id="regForm" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="password">New password</label>
        <div class="pw-field">
            <input type="password" id="password" name="password" class="form-control" minlength="8" autocomplete="new-password" required>
            <button type="button" class="pw-eye" data-target="password" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
        </div>
        <div class="pw-meter" aria-hidden="true"><span id="pwBar" class="pw-bar"></span></div>
        <div class="pw-meter-text" id="pwMeterText" aria-live="polite">Strength: <strong id="pwLabel">Enter a password</strong></div>
        <ul class="pw-rules" id="pwRules">
            <li data-rule="len"><i class="bi bi-circle"></i> At least 8 characters</li>
            <li data-rule="upper"><i class="bi bi-circle"></i> One uppercase letter (A-Z)</li>
            <li data-rule="letter"><i class="bi bi-circle"></i> One letter (a-z)</li>
            <li data-rule="num"><i class="bi bi-circle"></i> One number (0-9)</li>
            <li data-rule="special"><i class="bi bi-circle"></i> One special character (! @ # $ %)</li>
        </ul>
    </div>
    <div class="mb-4">
        <label class="form-label" for="password_confirm">Confirm new password</label>
        <div class="pw-field">
            <input type="password" id="password_confirm" name="password_confirm" class="form-control" autocomplete="new-password" required>
            <button type="button" class="pw-eye" data-target="password_confirm" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
        </div>
        <div class="pw-match" id="pwMatch" aria-live="polite"></div>
    </div>
    <button class="btn btn-primary w-100 py-2" id="regSubmit">Save new password</button>
</form>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/register.js') ?>"></script>
<?= $this->endSection() ?>
