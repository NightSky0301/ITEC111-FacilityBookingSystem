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

/* ---------- Card pager: [data-pager="N"] shows N cards per page and slides between pages ---------- */
function initPagers() {
    document.querySelectorAll('[data-pager]').forEach(function (grid) {
        const per = Math.max(1, parseInt(grid.dataset.pager, 10) || 4);
        const cards = Array.from(grid.children);
        if (cards.length <= per) return;                       // one page: nothing to flip

        const pages = [];
        for (let i = 0; i < cards.length; i += per) {
            const page = document.createElement('div');
            page.className = 'sess-grid pager-page' + (grid.dataset.cols ? ' cols-' + grid.dataset.cols : '');
            cards.slice(i, i + per).forEach(c => page.appendChild(c));
            pages.push(page);
        }
        const start = parseInt(grid.dataset.pagerStart, 10) || 0;
        const root = document.createElement('div'); root.className = 'pager';
        const view = document.createElement('div'); view.className = 'pager-viewport';
        const track = document.createElement('div'); track.className = 'pager-track';
        pages.forEach(p => track.appendChild(p)); view.appendChild(track);

        const bar = document.createElement('div'); bar.className = 'pager-bar';
        const prev = document.createElement('button'); prev.type = 'button'; prev.className = 'pager-btn'; prev.setAttribute('aria-label', 'Previous page'); prev.innerHTML = '<i class="bi bi-chevron-left"></i>';
        const next = document.createElement('button'); next.type = 'button'; next.className = 'pager-btn'; next.setAttribute('aria-label', 'Next page'); next.innerHTML = '<i class="bi bi-chevron-right"></i>';
        const label = document.createElement('span'); label.className = 'pager-label'; label.setAttribute('aria-live', 'polite');
        const dots = document.createElement('span'); dots.className = 'pager-dots';
        pages.forEach((_, i) => {
            const d = document.createElement('button'); d.type = 'button'; d.className = 'pager-dot'; d.setAttribute('aria-label', 'Page ' + (i + 1));
            d.addEventListener('click', () => go(i)); dots.appendChild(d);
        });
        bar.append(prev, dots, label, next);
        root.append(view, bar);
        grid.replaceWith(root);

        let cur = 0;
        function go(n) {
            cur = Math.max(0, Math.min(pages.length - 1, n));
            root.dataset.cur = cur;
            track.style.transform = 'translateX(' + (-100 * cur) + '%)';
            pages.forEach((p, i) => { p.toggleAttribute('inert', i !== cur); p.setAttribute('aria-hidden', i !== cur ? 'true' : 'false'); });
            Array.from(dots.children).forEach((d, i) => d.classList.toggle('is-on', i === cur));
            label.textContent = 'Page ' + (cur + 1) + ' of ' + pages.length;
            prev.disabled = cur === 0; next.disabled = cur === pages.length - 1;
            view.style.height = pages[cur].offsetHeight + 'px';
        }
        prev.addEventListener('click', () => go(cur - 1));
        next.addEventListener('click', () => go(cur + 1));
        window.addEventListener('resize', () => go(cur));
        // Swipe on touch screens.
        let x0 = null;
        view.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; }, { passive: true });
        view.addEventListener('touchend', e => { if (x0 === null) return; const dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 50) go(cur + (dx < 0 ? 1 : -1)); x0 = null; });
        go(start);
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(() => go(cur));
        window.addEventListener('load', () => go(cur));
    });
}
initPagers();
document.addEventListener('live:swap', initPagers);

/* ---------- Live updates: new bookings, approvals and notifications show up without a refresh ---------- */
(function () {
    const url = document.body.dataset.liveUrl;
    if (!url) return;                                  // guests have nothing to poll
    let stamp = document.body.dataset.liveStamp || '';
    let seenNote = null;                               // newest notification already shown (set on first poll)
    let timer = null;

    // Do not rebuild the page under someone's fingers.
    function busy() {
        if (document.querySelector('.modal.show, .dd.open')) return true;
        const a = document.activeElement;
        return !!(a && /^(INPUT|TEXTAREA|SELECT)$/.test(a.tagName) && a.closest('[data-live]'));
    }

    function toast(title, text) {
        let box = document.getElementById('liveToasts');
        if (!box) { box = document.createElement('div'); box.id = 'liveToasts'; box.className = 'live-toasts'; box.setAttribute('role', 'status'); box.setAttribute('aria-live', 'polite'); document.body.appendChild(box); }
        const t = document.createElement('div'); t.className = 'live-toast';
        t.innerHTML = '<i class="bi bi-bell-fill"></i><div><strong></strong><span></span></div>';
        t.querySelector('strong').textContent = title; t.querySelector('span').textContent = text || '';
        t.addEventListener('click', () => t.remove());
        box.appendChild(t);
        setTimeout(() => { t.classList.add('is-out'); setTimeout(() => t.remove(), 300); }, 7000);
    }

    function badge(n) {
        const bell = document.querySelector('[data-notif-open]'); if (!bell) return;
        let dot = bell.querySelector('.dot-count');
        if (n > 0) { if (!dot) { dot = document.createElement('span'); dot.className = 'dot-count'; bell.appendChild(dot); } dot.textContent = n > 99 ? '99+' : n; }
        else if (dot) dot.remove();
    }

    async function refreshPage() {
        if (document.querySelector('[data-live-reload]')) { location.reload(); return true; }
        const regions = document.querySelectorAll('[data-live]');
        if (!regions.length) return true;
        const res = await fetch(location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
        if (!res.ok || /\/login/.test(res.url)) return false;
        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
        regions.forEach(function (r) {
            const fresh = r.id && doc.getElementById(r.id);
            if (!fresh) return;
            const pager = r.querySelector('.pager');             // keep the page the person was on
            const cur = pager ? pager.dataset.cur : null;
            r.innerHTML = fresh.innerHTML;
            const grid = r.querySelector('[data-pager]');
            if (grid && cur !== null) grid.dataset.pagerStart = cur;
        });
        document.dispatchEvent(new CustomEvent('live:swap'));
        return true;
    }

    async function poll() {
        clearTimeout(timer);
        if (!document.hidden) {
            try {
                const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
                if (res.ok && (res.headers.get('content-type') || '').indexOf('json') !== -1) {
                    const d = await res.json();
                    badge(d.unread);
                    if (d.latest) {
                        if (seenNote !== null && d.latest.id > seenNote) toast(d.latest.title, d.latest.message);
                        seenNote = Math.max(seenNote || 0, d.latest.id);
                    } else if (seenNote === null) { seenNote = 0; }
                    if (d.stamp && d.stamp !== stamp && !busy()) {
                        if (await refreshPage()) { stamp = d.stamp; document.dispatchEvent(new CustomEvent('live:changed')); }
                    }
                }
            } catch (e) { /* offline for a moment: try again */ }
        }
        timer = setTimeout(poll, 4000);
    }

    document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
    poll();
})();

/* ---------- Date picker: <input type="date" data-datepicker min="YYYY-MM-DD"> becomes a green/brass calendar pop-up ---------- */
(function () {
    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const DOW = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
    const pad = n => String(n).padStart(2, '0');
    const iso = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    const parse = s => { const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s || ''); return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null; };
    const nice = d => d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    let openDp = null;

    document.querySelectorAll('input[data-datepicker]').forEach(function (input) {
        const min = parse(input.min), max = parse(input.max);
        let value = parse(input.value) || new Date();
        let view = new Date(value.getFullYear(), value.getMonth(), 1);

        const root = document.createElement('div'); root.className = 'dp';
        const btn = document.createElement('button'); btn.type = 'button'; btn.className = 'dp-btn'; btn.setAttribute('aria-haspopup', 'dialog'); btn.setAttribute('aria-expanded', 'false');
        if (input.id) { btn.id = input.id; input.removeAttribute('id'); }
        btn.innerHTML = '<i class="bi bi-calendar-event" aria-hidden="true"></i><span class="dp-text"></span><i class="bi bi-chevron-down dp-chev" aria-hidden="true"></i>';
        const pop = document.createElement('div'); pop.className = 'dp-pop'; pop.setAttribute('role', 'dialog'); pop.setAttribute('aria-label', 'Choose a date'); pop.hidden = true;

        input.type = 'hidden';
        input.parentNode.insertBefore(root, input);
        root.append(input, btn, pop);

        function text() { btn.querySelector('.dp-text').textContent = nice(value); input.value = iso(value); }
        function inRange(d) { return (!min || d >= min) && (!max || d <= max); }

        function draw(focusIso) {
            const y = view.getFullYear(), m = view.getMonth(), first = new Date(y, m, 1).getDay(), days = new Date(y, m + 1, 0).getDate();
            const today = new Date(); today.setHours(0, 0, 0, 0);
            let h = '<div class="dp-head"><button type="button" class="dp-nav" data-step="-1" aria-label="Previous month"><i class="bi bi-chevron-left"></i></button>'
                + '<div class="dp-title" aria-live="polite">' + MONTHS[m] + ' ' + y + '</div>'
                + '<button type="button" class="dp-nav" data-step="1" aria-label="Next month"><i class="bi bi-chevron-right"></i></button></div><div class="dp-grid">';
            DOW.forEach(d => { h += '<span class="dp-dow">' + d + '</span>'; });
            for (let k = 0; k < first; k++) h += '<span></span>';
            for (let d = 1; d <= days; d++) {
                const dt = new Date(y, m, d), ok = inRange(dt), cls = ['dp-day'];
                if (+dt === +today) cls.push('is-today');
                if (+dt === +value) cls.push('is-sel');
                h += '<button type="button" class="' + cls.join(' ') + '" data-d="' + iso(dt) + '"' + (ok ? '' : ' disabled') + (+dt === +value ? ' aria-pressed="true"' : '') + '>' + d + '</button>';
            }
            h += '</div><div class="dp-foot"><button type="button" class="dp-today">Today</button></div>';
            pop.innerHTML = h;
            const prev = pop.querySelector('[data-step="-1"]');
            if (min && new Date(y, m, 1) <= new Date(min.getFullYear(), min.getMonth(), 1)) prev.disabled = true;
            const tb = pop.querySelector('.dp-today'); if (!inRange(today)) tb.disabled = true;
            if (focusIso) { const f = pop.querySelector('[data-d="' + focusIso + '"]:not(:disabled)') || pop.querySelector('.dp-day:not(:disabled)'); if (f) f.focus(); }
        }

        function close(refocus) {
            if (pop.hidden) return;
            pop.hidden = true; root.classList.remove('is-open'); btn.setAttribute('aria-expanded', 'false');
            if (openDp === close) openDp = null;
            if (refocus) btn.focus();
        }
        function open() {
            if (openDp) openDp(false);
            view = new Date(value.getFullYear(), value.getMonth(), 1);
            draw(); pop.hidden = false; root.classList.add('is-open'); btn.setAttribute('aria-expanded', 'true'); openDp = close;
        }
        function pick(d) { value = d; text(); input.dispatchEvent(new Event('change', { bubbles: true })); close(true); }

        btn.addEventListener('click', () => (pop.hidden ? open() : close(false)));
        pop.addEventListener('click', function (e) {
            const nav = e.target.closest('.dp-nav'), day = e.target.closest('.dp-day'), td = e.target.closest('.dp-today');
            if (nav && !nav.disabled) { view = new Date(view.getFullYear(), view.getMonth() + +nav.dataset.step, 1); draw(); const n = pop.querySelector('[data-step="' + nav.dataset.step + '"]'); if (n && !n.disabled) n.focus(); }
            else if (day && !day.disabled) pick(parse(day.dataset.d));
            else if (td && !td.disabled) pick(new Date());
        });
        pop.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { e.stopPropagation(); close(true); return; }
            const day = e.target.closest && e.target.closest('.dp-day'); if (!day) return;
            const step = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[e.key]; if (!step) return;
            e.preventDefault();
            const d = parse(day.dataset.d); d.setDate(d.getDate() + step);
            if (!inRange(d)) return;
            if (d.getMonth() !== view.getMonth() || d.getFullYear() !== view.getFullYear()) { view = new Date(d.getFullYear(), d.getMonth(), 1); draw(iso(d)); }
            else { const t = pop.querySelector('[data-d="' + iso(d) + '"]'); if (t) t.focus(); }
        });
        text();
    });

    // Clicking the month arrows re-draws the pop-up, so the clicked arrow is no longer in the page when this runs.
    // Such a click came from inside the picker and must not close it.
    document.addEventListener('click', function (e) { if (openDp && e.target.isConnected && !e.target.closest('.dp')) openDp(false); });
})();
