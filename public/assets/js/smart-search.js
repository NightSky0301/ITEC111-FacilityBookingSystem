/*
 * Smart search shared by the tables and the calendar.
 *   <input data-smart-search="#tableOrListSelector" data-smart-item="tr[data-s]">  (see each page)
 *   window.smartMatch(query, text) -> true when every typed word matches the start of a word in text.
 * It ignores case and accents, and forgives a small typo (one wrong letter on words of 4+ letters, two on 7+).
 */
(function () {
    const norm = s => String(s == null ? '' : s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9₱\s]/g, ' ');
    function lev(a, b, max) {   // edit distance, gives up above max
        if (Math.abs(a.length - b.length) > max) return max + 1;
        let prev = Array.from({ length: b.length + 1 }, (_, i) => i);
        for (let i = 1; i <= a.length; i++) {
            const cur = [i]; let min = i;
            for (let j = 1; j <= b.length; j++) {
                cur[j] = Math.min(prev[j] + 1, cur[j - 1] + 1, prev[j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
                if (cur[j] < min) min = cur[j];
            }
            if (min > max) return max + 1;
            prev = cur;
        }
        return prev[b.length];
    }
    function wordHit(tok, words) {
        for (const w of words) if (w.startsWith(tok)) return true;
        if (tok.length < 4) return false;
        const max = tok.length >= 7 ? 2 : 1;
        for (const w of words) if (lev(tok, w.slice(0, tok.length), max) <= max) return true;
        return false;
    }
    window.smartMatch = function (query, text) {
        const toks = norm(query).split(/\s+/).filter(Boolean);
        if (!toks.length) return true;
        const words = norm(text).split(/\s+/).filter(Boolean);
        return toks.every(t => wordHit(t, words));
    };

    // Generic table/list filter. Pages that refresh themselves (data-live) replace their HTML, so this listens on the document,
    // remembers the typed text for the page, and applies it again after every refresh.
    const key = el => 'smart:' + location.pathname + location.search + ':' + (el.dataset.smartSearch || '');
    function apply(input) {
        const root = document.querySelector(input.dataset.smartSearch); if (!root) return;
        const sel = input.dataset.smartItem || 'tbody tr';
        let shown = 0, total = 0;
        root.querySelectorAll(sel).forEach(function (row) {
            total++;
            const ok = window.smartMatch(input.value, row.dataset.s || row.textContent);
            row.hidden = !ok; if (ok) shown++;
        });
        const none = root.querySelector('[data-smart-empty]'); if (none) none.hidden = shown > 0 || total === 0;
        const cnt = root.querySelector('[data-smart-count]'); if (cnt) cnt.textContent = shown;
    }
    function applyAll() {
        document.querySelectorAll('input[data-smart-search]').forEach(function (input) {
            let saved = ''; try { saved = sessionStorage.getItem(key(input)) || ''; } catch (e) {}
            if (input.value === '' && saved !== '' && !input.dataset.touched) input.value = saved;
            apply(input);
        });
    }
    document.addEventListener('input', function (e) {
        const input = e.target.closest && e.target.closest('input[data-smart-search]'); if (!input) return;
        input.dataset.touched = '1';
        try { sessionStorage.setItem(key(input), input.value); } catch (err) {}
        apply(input);
    });
    document.addEventListener('live:changed', function () { setTimeout(applyAll, 60); });
    applyAll();
})();
