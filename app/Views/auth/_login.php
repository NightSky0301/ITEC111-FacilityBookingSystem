<h1>Log in</h1>
<p class="lead-in">Use your email and password to continue.</p>
<form method="post" action="<?= site_url('login') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="login_email">Email</label>
        <input type="email" id="login_email" name="email" class="form-control" value="<?= esc($mode === 'login' ? old('email') : '') ?>" autocomplete="email" required>
    </div>
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-baseline">
            <label class="form-label" for="login_password">Password</label>
            <a href="<?= site_url('forgot-password') ?>" class="small">Forgot password?</a>
        </div>
        <div class="pw-field">
            <input type="password" id="login_password" name="password" class="form-control" autocomplete="current-password" required>
            <button type="button" class="pw-eye" data-target="login_password" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
        </div>
    </div>
    <button class="btn btn-primary w-100 py-2">Log in</button>
</form>
<p class="mt-4 mb-0 text-muted">New here? <a href="<?= site_url('register') ?>" data-auth-switch="register">Create an account</a></p>
