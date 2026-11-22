// Light / dark mode. The choice is saved in this browser; until one is made the device setting is used.
(function () {
    const KEY = 'athletix-theme';
    const root = document.documentElement;
    const media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function stored() { try { const v = localStorage.getItem(KEY); return v === 'dark' || v === 'light' ? v : null; } catch (e) { return null; } }
    function apply(theme) {
        root.setAttribute('data-theme', theme);
        root.setAttribute('data-bs-theme', theme);
        document.querySelectorAll('[data-theme-toggle]').forEach(function (b) { b.setAttribute('aria-checked', theme === 'dark' ? 'true' : 'false'); });
    }

    apply(root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-theme-toggle]');
        if (!btn) return;
        // (the account menu stays open: it closes only on outside clicks)
        const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem(KEY, next); } catch (err) { /* private window: the choice lasts for this page only */ }
        apply(next);
    });

    if (media && media.addEventListener) {
        media.addEventListener('change', function (e) { if (!stored()) apply(e.matches ? 'dark' : 'light'); });
    }
})();
