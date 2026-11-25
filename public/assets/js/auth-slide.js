// Login <-> Create account: no page reload, the two halves swap sides in 3D.
(function () {
    const stage = document.getElementById('authStage');
    if (!stage) return;
    const TITLES = { login: 'Log in', register: 'Create an account' };
    const brand = 'Athletix';
    let busy = false;

    function modeNow() { return stage.classList.contains('is-register') ? 'register' : 'login'; }

    function setMode(mode, push) {
        if (mode === modeNow() || busy) return;
        busy = true;
        stage.classList.remove('to-login', 'to-register');
        void stage.offsetWidth;                     // restart the animation if it was just played
        stage.classList.add('is-moving', mode === 'register' ? 'to-register' : 'to-login');
        stage.classList.toggle('is-register', mode === 'register');
        const panel = stage.querySelector('.auth-panel');
        if (panel) panel.scrollTop = 0;
        stage.querySelectorAll('[data-for]').forEach(el => { el.inert = el.dataset.for !== mode; });
        document.title = TITLES[mode] + ' · ' + brand;
        if (push) history.pushState({ mode: mode }, '', mode === 'register' ? stage.dataset.registerUrl : stage.dataset.loginUrl);
        stage.querySelectorAll('.flash').forEach(f => f.remove());

        setTimeout(function () {
            stage.classList.remove('is-moving', 'to-login', 'to-register');
            busy = false;
            const first = stage.querySelector('.auth-box[data-for="' + mode + '"] input:not([type=hidden])');
            if (first) first.focus({ preventScroll: true });
        }, 880);
    }

    // Back: on Create an account it returns to Log in (the screen you came from); on Log in it leaves to the home page.
    document.addEventListener('click', function (e) {
        const back = e.target.closest('.auth-back');
        if (!back || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0 || modeNow() !== 'register') return;
        e.preventDefault();
        setMode('login', true);
    });

    document.addEventListener('click', function (e) {
        const a = e.target.closest('[data-auth-switch]');
        if (!a || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
        e.preventDefault();
        setMode(a.dataset.authSwitch, true);
    });

    history.replaceState({ mode: modeNow() }, '');
    window.addEventListener('popstate', function (e) {
        const m = (e.state && e.state.mode) || (location.pathname.replace(/\/+$/, '').endsWith('register') ? 'register' : 'login');
        setMode(m, false);
    });

    const first = stage.querySelector('.auth-box[data-for="' + modeNow() + '"] input:not([type=hidden])');
    if (first && modeNow() === 'login') first.focus({ preventScroll: true });
})();
