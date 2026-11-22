// Search box on the home page: suggestions while you type (activities, courts, the gym, hotels, rooms).
(function () {
    const script = document.currentScript;
    const url = script ? script.dataset.suggest : '';
    const form = document.querySelector('.hx-search');
    if (!form || !url) return;
    const input = form.querySelector('#hxQ'), list = form.querySelector('#hxList'), clear = form.querySelector('.hx-clear');
    const date = () => { const d = form.querySelector('#hxDate'); return d ? d.value : ''; };
    let items = [], active = -1, timer = null, ctl = null, lastQ = null;

    function esc(s) { return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
    function mark(text, q) {
        const words = q.trim().split(/\s+/).filter(Boolean).map(w => w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
        const safe = esc(text);
        return words.length ? safe.replace(new RegExp('(' + words.map(esc).join('|') + ')', 'gi'), '<mark>$1</mark>') : safe;
    }
    function open() { list.hidden = false; input.setAttribute('aria-expanded', 'true'); }
    function close() { list.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); active = -1; }
    function setActive(n) {
        const rows = list.querySelectorAll('[role=option]');
        if (!rows.length) return;
        active = (n + rows.length) % rows.length;
        rows.forEach((r, i) => { r.classList.toggle('is-on', i === active); r.setAttribute('aria-selected', i === active ? 'true' : 'false'); });
        input.setAttribute('aria-activedescendant', rows[active].id);
        rows[active].scrollIntoView({ block: 'nearest' });
    }
    function render(data, q) {
        items = data.items || [];
        let html = '';
        if (!items.length) {
            html = '<li class="hx-none" role="presentation"><i class="bi bi-search"></i> No match for “' + esc(q) + '”. Press Enter to search everything.</li>';
        } else {
            if (!q.trim()) html += '<li class="hx-head" role="presentation">Start with</li>';
            items.forEach((it, i) => {
                html += '<li role="option" id="hxo' + i + '" aria-selected="false" data-i="' + i + '"><span class="hx-ico"><i class="bi ' + esc(it.icon) + '"></i></span>' +
                    '<span class="hx-txt"><b>' + mark(it.title, q) + '</b><small>' + esc(it.sub) + '</small></span><em>' + esc(it.kind) + '</em></li>';
            });
            if (q.trim()) html += '<li class="hx-all" role="presentation" data-all="1"><i class="bi bi-search"></i> See all results for “' + esc(q.trim()) + '”' + (data.more ? ' <span>+' + data.more + ' more</span>' : '') + '</li>';
        }
        list.innerHTML = html; active = -1; open();
    }
    function fetchNow() {
        const q = input.value; if (q === lastQ && !list.hidden) return; lastQ = q;
        if (ctl) ctl.abort(); ctl = window.AbortController ? new AbortController() : null;
        fetch(url + '?q=' + encodeURIComponent(q) + '&date=' + encodeURIComponent(date()), { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: ctl ? ctl.signal : undefined })
            .then(r => r.ok ? r.json() : null).then(d => { if (d && input.value === q) render(d, q); }).catch(() => {});
    }
    function go(i) { if (items[i]) window.location.href = items[i].url; }

    input.addEventListener('input', () => { clear.hidden = !input.value; clearTimeout(timer); timer = setTimeout(fetchNow, 130); });
    input.addEventListener('focus', () => { lastQ = null; fetchNow(); });
    input.addEventListener('keydown', e => {
        if (e.key === 'ArrowDown') { e.preventDefault(); if (list.hidden) { lastQ = null; fetchNow(); } else setActive(active + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); if (!list.hidden) setActive(active - 1); }
        else if (e.key === 'Escape') { close(); }
        else if (e.key === 'Enter' && !list.hidden && active >= 0) { e.preventDefault(); go(active); }
    });
    list.addEventListener('mousedown', e => e.preventDefault());          // keep focus in the box while clicking
    list.addEventListener('click', e => {
        const li = e.target.closest('li'); if (!li) return;
        if (li.dataset.all) { form.submit(); return; }
        if (li.dataset.i !== undefined) go(+li.dataset.i);
    });
    list.addEventListener('mousemove', e => { const li = e.target.closest('[role=option]'); if (li) { const n = +li.dataset.i; if (n !== active) setActive(n); } });
    clear.addEventListener('click', () => { input.value = ''; clear.hidden = true; input.focus(); lastQ = null; fetchNow(); });
    document.addEventListener('click', e => { if (!form.querySelector('.hx-q').contains(e.target)) close(); });
    form.addEventListener('submit', () => close());
})();
