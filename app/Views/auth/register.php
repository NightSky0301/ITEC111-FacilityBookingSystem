<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>
<h1>Create an account</h1>
<p class="lead-in">Register to reserve courts and other facilities.</p>
<form method="post" action="<?= site_url('register') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="name">Full name</label>
        <input type="text" id="name" name="name" class="form-control" value="<?= esc(old('name')) ?>" autocomplete="name" required>
    </div>
    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="form-control" value="<?= esc(old('email')) ?>" autocomplete="email" required>
    </div>
    <div class="mb-3">
        <label class="form-label" for="phone">Phone <span class="text-muted fw-normal">(optional)</span></label>
        <input type="text" id="phone" name="phone" class="form-control" value="<?= esc(old('phone')) ?>" autocomplete="tel">
    </div>
    <div class="row">
        <div class="col-sm-6 mb-3">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" minlength="6" autocomplete="new-password" required>
        </div>
        <div class="col-sm-6 mb-4">
            <label class="form-label" for="password_confirm">Confirm password</label>
            <input type="password" id="password_confirm" name="password_confirm" class="form-control" autocomplete="new-password" required>
        </div>
    </div>
    <button class="btn btn-primary w-100 py-2">Create account</button>
</form>
<p class="mt-4 mb-0 text-muted">Already registered? <a href="<?= site_url('login') ?>">Log in</a></p>
<?= $this->endSection() ?>
