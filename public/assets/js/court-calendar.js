// Court availability page: picking a day or changing the month refreshes only the calendar and the
// sessions column (no full page reload). Any problem falls back to the normal page load.
(function () {
    const view = document.getElementById('courtView');
    if (!view || !window.fetch || !window.DOMParser) return;

    let ctrl = null;

    function isCalLink(a) {
        if (!a || !view.contains(a) || a.target || a.hasAttribute('download')) return false;
        return a.matches('a.cal-day, .cal-nav a');
    }

    function swap(doc) {
        const nView = doc.getElementById('courtView');
        if (!nView) return false;
        view.innerHTML = nView.innerHTML;
        const live = document.getElementById('courtLive'), nLive = doc.getElementById('courtLive');
        if (live && nLive) live.innerHTML = nLive.innerHTML;
        return true;
    }

    async function load(url, push) {
        if (ctrl) ctrl.abort();
        ctrl = new AbortController();
        view.classList.add('is-loading');
        view.setAttribute('aria-busy', 'true');
        try {
            const res = await fetch(url, { credentials: 'same-origin', signal: ctrl.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const final = new URL(res.url, location.href);
            // Redirected away (login, "court not available"...) or an error: do a normal page load instead.
            if (!res.ok || final.pathname !== new URL(url, location.href).pathname) { location.href = url; return; }
            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            if (!swap(doc)) { location.href = url; return; }
            if (push) history.pushState({ court: true }, '', url);
            const t = doc.querySelector('title'); if (t) document.title = t.textContent;
        } catch (err) {
            if (err.name === 'AbortError') return;
            location.href = url;
            return;
        }
        view.classList.remove('is-loading');
        view.removeAttribute('aria-busy');
    }

    document.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const a = e.target.closest('a');
        if (!isCalLink(a)) return;
        e.preventDefault();
        load(new URL(a.getAttribute('href'), location.href).href, true);
    });

    // Someone booked, approved or cancelled something: reload this court's day quietly.
    document.addEventListener('live:changed', function () { if (!document.hidden) load(location.href, false); });

    // Back / forward buttons re-load the matching day without a full reload too.
    history.replaceState({ court: true }, '', location.href);
    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.court) load(location.href, false);
    });
})();
