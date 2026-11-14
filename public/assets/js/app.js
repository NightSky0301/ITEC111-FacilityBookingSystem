// Small helpers used across the site.
document.addEventListener('click', function (e) {
    // Close buttons on flash messages
    const close = e.target.closest('.flash-close');
    if (close) {
        const box = close.closest('.flash');
        if (box) box.remove();
    }
});

// Notifications pop-up: the bell opens it, the list is loaded fresh each time.
(function () {
    const modalEl = document.getElementById('notifModal');
    const bell = document.querySelector('[data-notif-open]');
    if (!modalEl || !bell || !window.bootstrap) return;

    const body = document.getElementById('notifBody');
    const feedUrl = modalEl.dataset.feed;
    const ajax = { 'X-Requested-With': 'XMLHttpRequest' };

    function setBadge(n) {
        let dot = bell.querySelector('.dot-count');
        if (n > 0) {
            if (!dot) { dot = document.createElement('span'); dot.className = 'dot-count'; bell.appendChild(dot); }
            dot.textContent = n > 99 ? '99+' : n;
        } else if (dot) {
            dot.remove();
        }
    }

    async function load() {
        body.innerHTML = '<div class="notif-loading"><span class="spinner-border spinner-border-sm" role="status"></span> Loading…</div>';
        try {
            const res = await fetch(feedUrl, { headers: ajax, credentials: 'same-origin' });
            if (!res.ok) throw new Error(res.status);
            body.innerHTML = await res.text();
            const feed = body.querySelector('#notifFeed');
            if (feed) setBadge(parseInt(feed.dataset.unread, 10) || 0);
            // The button that was clicked is gone after a reload: keep keyboard focus (and Esc) inside the pop-up.
            if (!modalEl.contains(document.activeElement)) modalEl.focus();
        } catch (e) {
            body.innerHTML = '<div class="empty"><i class="bi bi-exclamation-circle"></i><strong>Could not load notifications</strong><a href="' + bell.getAttribute('href') + '">Open the full page</a></div>';
        }
    }

    bell.addEventListener('click', function (e) {
        e.preventDefault();
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });
    modalEl.addEventListener('show.bs.modal', load);

    body.addEventListener('submit', async function (e) {
        const form = e.target.closest('form[data-notif-readall]');
        if (!form) return;
        e.preventDefault();
        try {
            await fetch(form.action, { method: 'POST', body: new FormData(form), headers: ajax, credentials: 'same-origin' });
        } catch (err) { /* the reload below shows the real state */ }
        load();
    });
})();
