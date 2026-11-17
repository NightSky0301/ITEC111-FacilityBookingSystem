<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>
<h1>Log in</h1>
<p class="lead-in">Use your email and password to continue.</p>
<form method="post" action="<?= site_url('login') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="form-control" value="<?= esc(old('email')) ?>" autocomplete="email" required autofocus>
    </div>
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-baseline">
            <label class="form-label" for="password">Password</label>
            <a href="<?= site_url('forgot-password') ?>" class="small">Forgot password?</a>
        </div>
        <div class="pw-field">
            <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" required>
            <button type="button" class="pw-eye" data-target="password" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
        </div>
    </div>
    <button class="btn btn-primary w-100 py-2">Log in</button>
</form>
<p class="mt-4 mb-0 text-muted">New here? <a href="<?= site_url('register') ?>">Create an account</a></p>
<?= $this->endSection() ?>
