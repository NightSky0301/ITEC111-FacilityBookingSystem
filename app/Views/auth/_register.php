<h1>Create an account</h1>
<p class="lead-in">Register to reserve courts and other facilities.</p>
<form method="post" action="<?= site_url('register') ?>" id="regForm" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3 mb-3 name-row">
        <div class="col-12 col-md-4">
            <label class="form-label" for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name" class="form-control name-part" value="<?= esc($mode === 'register' ? old('first_name') : '') ?>" autocomplete="given-name" maxlength="50" pattern="[^0-9]+" title="Letters only. Numbers are not allowed." required>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label" for="middle_name">Middle name</label>
            <input type="text" id="middle_name" name="middle_name" class="form-control name-part" value="<?= esc($mode === 'register' ? old('middle_name') : '') ?>" autocomplete="additional-name" maxlength="50" pattern="[^0-9]*" title="Letters only. Numbers are not allowed.">
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label" for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name" class="form-control name-part" value="<?= esc($mode === 'register' ? old('last_name') : '') ?>" autocomplete="family-name" maxlength="50" pattern="[^0-9]+" title="Letters only. Numbers are not allowed." required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="form-control" value="<?= esc($mode === 'register' ? old('email') : '') ?>" autocomplete="email" required>
    </div>
    <div class="mb-3">
        <label class="form-label" for="phone">Phone <span class="text-muted fw-normal">(optional)</span></label>
        <input type="tel" id="phone" name="phone" class="form-control" value="<?= esc($mode === 'register' ? old('phone') : '') ?>" autocomplete="tel" inputmode="numeric" maxlength="15" pattern="[0-9]{7,15}" title="Digits only, 7 to 15 numbers.">
    </div>
    <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <div class="pw-field">
            <input type="password" id="password" name="password" class="form-control" minlength="8" autocomplete="new-password" required aria-describedby="pwMeterText pwRules">
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
        <label class="form-label" for="password_confirm">Confirm password</label>
        <div class="pw-field">
            <input type="password" id="password_confirm" name="password_confirm" class="form-control" autocomplete="new-password" required>
            <button type="button" class="pw-eye" data-target="password_confirm" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
        </div>
        <div class="pw-match" id="pwMatch" aria-live="polite"></div>
    </div>
    <button class="btn btn-primary w-100 py-2" id="regSubmit">Create account</button>
</form>
