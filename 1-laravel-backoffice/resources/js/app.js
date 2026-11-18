import './bootstrap';
import '@hotwired/turbo';

// ─── rAF yedeği: gömülü webview'da rAF hiç tetiklenmez ───
// Turbo render önceki kare beklediğinden görünümsüz/duruk ortamda gezinme
// kilitleniyordu; rAF 50 ms içinde tetiklenmezse timeout devreye girer.
const yerelRaf = window.requestAnimationFrame.bind(window);
window.requestAnimationFrame = (callback) => {
    let tetiklendi = false;
    const bir = (deger) => {
        if (tetiklendi) return;
        tetiklendi = true;
        clearTimeout(yedek);
        callback(deger);
    };
    const kimlik = yerelRaf(bir);
    const yedek = setTimeout(() => bir(performance.now()), 50);
    return kimlik;
};

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

window.Alpine = Alpine;
Alpine.plugin(collapse);

// ─── ERP Panel: dönem filtresi + canlı KPI verisi (Alpine) ───
Alpine.data('donemFiltre', () => ({
    acik: false,
    donem: 'ay',
    ozelAcik: false,
    ozelBas: '',
    ozelSon: '',
    hazirDonemler: [
        { deger: 'bugun', etiket: 'Bugün' },
        { deger: 'hafta', etiket: 'Bu Hafta' },
        { deger: 'ay', etiket: 'Bu Ay' },
        { deger: '30gun', etiket: 'Son 30 Gün' },
    ],
    get donemEtiketi() {
        const bulunan = this.hazirDonemler.find(d => d.deger === this.donem);
        return this.donem === 'ozel'
            ? (this.ozelBas && this.ozelSon ? `${this.ozelBas} – ${this.ozelSon}` : 'Özel Aralık')
            : (bulunan?.etiket ?? 'Bu Ay');
    },
    donemSec(deger) {
        this.donem = deger;
        this.acik = false;
        this.$dispatch('panel-donem', { donem: deger });
    },
    ozelUygula() {
        this.acik = false;
        this.$dispatch('panel-donem', { donem: 'ozel', bas: this.ozelBas, son: this.ozelSon });
    },
}));

Alpine.data('panelVerisi', () => ({
    k: window.__panelBaslangic ?? {},
    yukleniyor: false,

    init() {
        // Dönem filtresinden gelen isteği yakala: tüm kart verilerini yeniden al
        window.addEventListener('panel-donem', async (e) => {
            this.yukleniyor = true;
            try {
                const p = new URLSearchParams({ donem: e.detail.donem });
                if (e.detail.bas) p.set('bas', e.detail.bas);
                if (e.detail.son) p.set('son', e.detail.son);
                const yanit = await fetch(`/panel/veriler?${p}`, {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                });
                if (!yanit.ok) throw new Error('veriler');
                const sonuc = await yanit.json();
                this.k = sonuc.data;
                // Grafikleri yeni veriyle yeniden çiz
                window.dispatchEvent(new CustomEvent('panel-veriler', { detail: this.k }));
            } catch {
                // sessiz — eski değerler kalmaya devam eder
            } finally {
                this.yukleniyor = false;
            }
        });
    },

    get toplamGonderi() {
        const d = this.k.grafik?.durumDagilim;
        if (!d) return -1;
        return (d.hazirlaniyor ?? 0) + (d.kargoda ?? 0) + (d.teslim ?? 0) + (d.sorunlu ?? 0);
    },

    get urunGrafikBos() {
        return (this.k.grafik?.enCokSatan?.length ?? 0) === 0;
    },
}));

Alpine.start();
