// Registration form: name without numbers, phone digits only, password eye toggles, strength meter and rules.
(function () {
    const $ = id => document.getElementById(id);
    const name = $('name'), phone = $('phone'), pw = $('password'), pc = $('password_confirm');
    if (!pw) return;

    if (name) name.addEventListener('input', () => { name.value = name.value.replace(/[0-9]/g, ''); });
    if (phone) phone.addEventListener('input', () => { phone.value = phone.value.replace(/[^0-9]/g, ''); });

    const rules = {
        len:     v => v.length >= 8,
        upper:   v => /[A-Z]/.test(v),
        letter:  v => /[a-z]/.test(v),
        num:     v => /[0-9]/.test(v),
        special: v => /[^A-Za-z0-9]/.test(v),
    };
    const levels = [
        { t: 'Enter a password', c: '' },
        { t: 'Very weak', c: 'lv1' }, { t: 'Weak', c: 'lv2' }, { t: 'Fair', c: 'lv3' }, { t: 'Good', c: 'lv4' }, { t: 'Strong', c: 'lv5' },
    ];

    function matchCheck() {
        const m = $('pwMatch');
        if (!pc.value) { m.textContent = ''; m.className = 'pw-match'; return false; }
        const same = pc.value === pw.value;
        m.textContent = same ? 'Passwords match' : 'Passwords do not match yet';
        m.className = 'pw-match ' + (same ? 'ok' : 'bad');
        return same;
    }

    function check() {
        const v = pw.value;
        let score = 0;
        for (const k in rules) {
            const ok = rules[k](v);
            if (ok) score++;
            const li = document.querySelector('[data-rule="' + k + '"]');
            li.classList.toggle('ok', ok);
            li.firstElementChild.className = 'bi ' + (ok ? 'bi-check-circle-fill' : 'bi-circle');
        }
        const lv = v ? levels[Math.max(1, score)] : levels[0];
        $('pwBar').className = 'pw-bar ' + lv.c;
        $('pwBar').style.width = (v ? Math.max(1, score) * 20 : 0) + '%';
        $('pwLabel').textContent = lv.t;
        $('pwLabel').className = lv.c;
        matchCheck();
        return score === 5;
    }

    pw.addEventListener('input', check);
    pc.addEventListener('input', matchCheck);

    const form = $('regForm');
    const optionalPw = form.dataset.optionalPassword === '1';   // Edit user: leave empty to keep the current password
    if (phone) phone.value = phone.value.replace(/[^0-9]/g, '');

    // The account cannot be saved until every rule is met.
    $('regForm').addEventListener('submit', function (e) {
        let msg = '';
        if (name && (!name.value.trim() || name.value.trim().length < 2)) msg = 'Enter your full name (letters only, no numbers).';
        else if (phone && phone.value && !/^[0-9]{7,15}$/.test(phone.value)) msg = 'Phone must be 7 to 15 digits.';
        else if (optionalPw && !pw.value && !pc.value) msg = '';
        else if (!check()) msg = 'Your password must meet every requirement listed below it.';
        else if (!matchCheck()) msg = 'The two passwords do not match.';
        if (msg) {
            e.preventDefault();
            let box = $('regError');
            if (!box) {
                box = document.createElement('div');
                box.id = 'regError'; box.className = 'flash flash-bad'; box.setAttribute('role', 'alert');
                box.innerHTML = '<i class="bi bi-exclamation-triangle"></i><div class="flash-text"></div>';
                this.prepend(box);
            }
            box.querySelector('.flash-text').textContent = msg;
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
    check();
})();
