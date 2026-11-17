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


// Confirmation pop-up: <form data-confirm="Message" data-confirm-title="Title" data-confirm-ok="Button" data-confirm-tone="danger|brass" data-confirm-icon="bi-trash">
(function () {
    const modalEl = document.getElementById('confirmModal');
    if (!modalEl || !window.bootstrap) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const okBtn = document.getElementById('confirmOk');
    let pending = null;

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.matches || !form.matches('form[data-confirm]') || form.dataset.confirmed === '1') return;
        e.preventDefault();
        pending = { form: form, submitter: e.submitter || null };
        const d = form.dataset;
        const tone = d.confirmTone || 'danger';
        document.getElementById('confirmTitle').textContent = d.confirmTitle || 'Are you sure?';
        document.getElementById('confirmText').textContent = d.confirm;
        const icon = document.getElementById('confirmIcon');
        icon.className = 'confirm-icon tone-' + tone;
        icon.innerHTML = '<i class="bi ' + (d.confirmIcon || 'bi-exclamation-triangle') + '"></i>';
        okBtn.textContent = d.confirmOk || 'Confirm';
        okBtn.className = 'btn ' + (tone === 'danger' ? 'btn-danger' : 'btn-gold');
        document.getElementById('confirmCancel').textContent = d.confirmCancel || 'Cancel';
        modal.show();
    });

    okBtn.addEventListener('click', function () {
        if (!pending) return;
        const { form, submitter } = pending;
        pending = null;
        form.dataset.confirmed = '1';
        modal.hide();
        if (form.requestSubmit) form.requestSubmit(submitter || undefined); else form.submit();
    });

    modalEl.addEventListener('shown.bs.modal', () => document.getElementById('confirmCancel').focus());
    modalEl.addEventListener('hidden.bs.modal', () => { if (pending) pending.form.dataset.confirmed = ''; pending = null; });
})();


// Password eye buttons (login + register).
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.pw-eye');
    if (!btn) return;
    const input = document.getElementById(btn.dataset.target);
    if (!input) return;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    btn.firstElementChild.className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
});

// Custom dropdown: <select data-dd> is replaced by a styled green button + list. The real select stays in the page and still fires "change".
(function () {
    const selects = document.querySelectorAll('select[data-dd]');
    let openDd = null;
    function closeAll() { if (openDd) { openDd.classList.remove('open'); openDd.querySelector('.dd-btn').setAttribute('aria-expanded', 'false'); openDd = null; } }

    selects.forEach(function (sel) {
        const wrap = document.createElement('div');
        wrap.className = 'dd';
        const btn = document.createElement('button');
        btn.type = 'button'; btn.className = 'dd-btn'; btn.setAttribute('aria-haspopup', 'listbox'); btn.setAttribute('aria-expanded', 'false');
        const label = sel.getAttribute('aria-label') || (sel.id && document.querySelector('label[for="' + sel.id + '"]') ? document.querySelector('label[for="' + sel.id + '"]').textContent : '');
        if (label) btn.setAttribute('aria-label', label.trim());
        btn.innerHTML = '<span class="dd-text"></span><i class="bi bi-chevron-down dd-chev" aria-hidden="true"></i>';
        const list = document.createElement('ul');
        list.className = 'dd-menu'; list.setAttribute('role', 'listbox'); list.tabIndex = -1;

        Array.from(sel.options).forEach(function (o, i) {
            const li = document.createElement('li');
            li.setAttribute('role', 'option'); li.dataset.i = i; li.tabIndex = -1;
            li.innerHTML = '<span></span><i class="bi bi-check-lg dd-check" aria-hidden="true"></i>';
            li.firstChild.textContent = o.textContent;
            list.appendChild(li);
        });

        sel.classList.add('dd-native'); sel.tabIndex = -1; sel.setAttribute('aria-hidden', 'true');
        sel.parentNode.insertBefore(wrap, sel);
        wrap.append(btn, list, sel);

        function sync() {
            btn.querySelector('.dd-text').textContent = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].textContent : '';
            list.querySelectorAll('li').forEach(function (li) {
                const on = +li.dataset.i === sel.selectedIndex;
                li.classList.toggle('sel', on); li.setAttribute('aria-selected', on ? 'true' : 'false');
            });
        }
        function choose(i) {
            closeAll();
            if (i !== sel.selectedIndex) { sel.selectedIndex = i; sync(); sel.dispatchEvent(new Event('change', { bubbles: true })); }
            btn.focus();
        }
        function open() {
            closeAll(); wrap.classList.add('open'); btn.setAttribute('aria-expanded', 'true'); openDd = wrap;
            const cur = list.querySelector('li.sel') || list.firstElementChild; if (cur) cur.focus();
        }

        btn.addEventListener('click', function (e) { e.stopPropagation(); wrap.classList.contains('open') ? closeAll() : open(); });
        btn.addEventListener('keydown', function (e) { if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); open(); } });
        list.addEventListener('click', function (e) { const li = e.target.closest('li'); if (li) choose(+li.dataset.i); });
        list.addEventListener('keydown', function (e) {
            const items = Array.from(list.children), idx = items.indexOf(document.activeElement);
            if (e.key === 'ArrowDown') { e.preventDefault(); items[Math.min(items.length - 1, idx + 1)].focus(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); items[Math.max(0, idx - 1)].focus(); }
            else if (e.key === 'Home') { e.preventDefault(); items[0].focus(); }
            else if (e.key === 'End') { e.preventDefault(); items[items.length - 1].focus(); }
            else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); if (idx >= 0) choose(idx); }
            else if (e.key === 'Escape') { e.preventDefault(); closeAll(); btn.focus(); }
            else if (e.key === 'Tab') { closeAll(); }
        });
        sel.addEventListener('change', sync);
        sync();
    });

    document.addEventListener('click', closeAll);
})();
