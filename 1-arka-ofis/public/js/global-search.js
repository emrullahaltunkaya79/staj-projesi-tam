/**
 * Üst bardaki global arama: cariler, ürünler ve faturalarda arar.
 * /ara endpoint'ine gecikmeli istek atıp açılır sonuç listesi gösterir.
 */
(function () {
    const input = document.getElementById('global-search');
    const results = document.getElementById('global-search-results');
    if (!input || !results) return;

    let timer = null;
    let sequence = 0;

    const escapeHtml = (text) => {
        const el = document.createElement('span');
        el.textContent = text;
        return el.innerHTML;
    };

    const renderGroup = (title, items) => {
        if (!items || items.length === 0) return '';
        const rows = items.map((item) => `
            <a href="${item.url}" class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-indigo-50">
                <span class="truncate text-sm font-medium text-slate-700">${escapeHtml(item.title)}</span>
                <span class="shrink-0 text-[11px] text-slate-400">${escapeHtml(item.subtitle)}</span>
            </a>`).join('');
        return `<div class="px-4 pb-1 pt-2.5 text-[10px] font-bold uppercase tracking-widest text-slate-400">${title}</div>${rows}`;
    };

    const closeResults = () => {
        results.classList.remove('fade-in');
        setTimeout(() => results.classList.add('hidden'), 150);
    };

    const search = async () => {
        const q = input.value.trim();
        if (q.length < 2) {
            closeResults();
            return;
        }

            const current = ++sequence;
            try {
                const res = await fetch(`/ara?q=${encodeURIComponent(q)}`, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });
                if (current !== sequence) return;

                const data = await res.json();
                const html = renderGroup('Cariler', data.cariler)
                    + renderGroup('Ürünler', data.urunler)
                    + renderGroup('Faturalar', data.faturalar);

                if (html) {
                    results.innerHTML = html;
                    results.classList.remove('hidden');
                    // Bir frame bekle ki CSS transition tetiklensin
                    requestAnimationFrame(() => results.classList.add('fade-in'));
                } else {
                    results.innerHTML = '<div class="px-4 py-3 text-sm text-slate-400">Sonuç bulunamadı.</div>';
                    results.classList.remove('hidden');
                    requestAnimationFrame(() => results.classList.add('fade-in'));
                }
            } catch (e) {
                closeResults();
            }
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(search, 250);
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeResults();
    });

    document.addEventListener('click', (e) => {
        if (!results.contains(e.target) && e.target !== input) {
            closeResults();
        }
    });
})();
