"use client";

import { useState, useRef, useEffect } from "react";
import { MessageCircle, X, Send, Bot } from "lucide-react";

type Mesaj = {
  kimden: "bot" | "ben";
  metin: string;
  saat: string;
};

const OTOMATIK_YANITLAR = [
  { eslesen: ["kargo", "teslim", "ne zaman"], yanit: "📦 Kargo bilgisi: 14:00'a kadar verilen siparişler aynı gün çıkar. Kargo süresi 1-3 iş günü. 2.000₺ üzeri ücretsiz!" },
  { eslesen: ["iade", "geri", "iptal"], yanit: "↩️ İade: 14 gün koşulsuz iade hakkınız var. Sipariş sayfanızdan 'İptal Et' ile bekleyen siparişi hemen iptal edebilirsiniz." },
  { eslesen: ["cspr", "kripto", "casper"], yanit: "⛓️ CSPR ile ödeme: Sipariş verdikten sonra 'CSPR ile Öde' butonuna tıklayın, QR kodu Casper Wallet ile okutun. Ödeme zincirde görüldüğünde otomatik onaylanır." },
  { eslesen: ["kupon", "indirim", "kampanya"], yanit: "🎁 Aktif kuponlar: ILKSIPARIS (%15, min 200₺), KAMPANYA10 (%10, min 500₺), FREEKARGO (ücretsiz kargo), MEBULA50 (50₺, min 400₺)" },
  { eslegen: ["fiyat", "pahalı", "ucuz"], yanit: "💰 Tüm fiyatlar KDV dahildir. Toplu alımlarda özel fiyat için bize yazın: merhaba@hepsibirarada.test" },
  { eslesen: ["stok", "var mı", "tükendi"], yanit: "📦 Stok bilgisi ürün sayfasında canlı gösterilir. 'Son X adet' rozetini görüyorsanız acele edin — stok gerçek zamanlı güncellenir!" },
];

export function LiveChat() {
  const [acik, setAcik] = useState(false);
  const [mesajlar, setMesajlar] = useState<Mesaj[]>([
    {
      kimden: "bot",
      metin: "Merhaba! 👋 Ben HepsiBirarada asistanıyım. Size nasıl yardımcı olabilirim?",
      saat: new Date().toLocaleTimeString("tr-TR", { hour: "2-digit", minute: "2-digit" }),
    },
  ]);
  const [yazi, setYazi] = useState("");
  const [yaziyor, setYaziyor] = useState(false);
  const scrollRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (scrollRef.current) {
      scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
    }
  }, [mesajlar, yaziyor]);

  const gonder = (e: React.FormEvent) => {
    e.preventDefault();
    if (!yazi.trim()) return;

    const simdi = new Date().toLocaleTimeString("tr-TR", { hour: "2-digit", minute: "2-digit" });
    setMesajlar((p) => [...p, { kimden: "ben", metin: yazi.trim(), saat: simdi }]);
    const soru = yazi.trim().toLocaleLowerCase("tr");
    setYazi("");
    setYaziyor(true);

    setTimeout(() => {
      const eslesen = OTOMATIK_YANITLAR.find((r: { eslesen?: string[]; yanit: string }) =>
        (r.eslesen ?? []).some((k: string) => soru.includes(k)),
      );
      const yanit =
        eslesen?.yanit ??
        "Teşekkürler! Sorunuzu ekibimize ilettim. Daha hızlı yanıt için: merhaba@hepsibirarada.test 📧";
      setMesajlar((p) => [
        ...p,
        { kimden: "bot", metin: yanit, saat: new Date().toLocaleTimeString("tr-TR", { hour: "2-digit", minute: "2-digit" }) },
      ]);
      setYaziyor(false);
    }, 800 + Math.random() * 600);
  };

  return (
    <>
      {/* Köpük butonu */}
      <button
        type="button"
        onClick={() => setAcik(!acik)}
        aria-label="Destek"
        className={`fixed bottom-5 right-5 z-[55] flex h-14 w-14 items-center justify-center rounded-full shadow-2xl transition-all duration-300 ${
          acik
            ? "rotate-90 bg-slate-700 text-white"
            : "bg-indigo-600 text-white hover:scale-110 hover:bg-indigo-500"
        }`}
      >
        {acik ? <X className="h-5 w-5" /> : <MessageCircle className="h-6 w-6" />}
        {!acik && (
          <span className="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-400 text-[9px] font-black text-emerald-900">
            ●
          </span>
        )}
      </button>

      {/* Sohbet penceresi */}
      <div
        className={`fixed bottom-[84px] right-5 z-[55] w-[calc(100vw-2.5rem)] max-w-sm overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl transition-all duration-300 ${
          acik ? "translate-y-0 scale-100 opacity-100" : "pointer-events-none translate-y-4 scale-95 opacity-0"
        }`}
      >
        {/* Başlık */}
        <div className="flex items-center gap-3 bg-gradient-to-r from-indigo-600 to-purple-600 px-4 py-3">
          <span className="flex h-9 w-9 items-center justify-center rounded-full bg-white/20 backdrop-blur">
            <Bot className="h-5 w-5 text-white" />
          </span>
          <div>
            <p className="text-sm font-bold text-white">HepsiBirarada Destek</p>
            <p className="flex items-center gap-1 text-[11px] text-indigo-200">
              <span className="h-1.5 w-1.5 rounded-full bg-emerald-400" /> Çevrimiçi
            </p>
          </div>
        </div>

        {/* Mesajlar */}
        <div ref={scrollRef} className="max-h-[320px] space-y-3 overflow-y-auto bg-slate-50 p-4">
          {mesajlar.map((m, i) => (
            <div key={i} className={`flex ${m.kimden === "ben" ? "justify-end" : "justify-start"}`}>
              <div
                className={`max-w-[80%] rounded-2xl px-3.5 py-2.5 text-[13px] leading-relaxed shadow-sm ${
                  m.kimden === "ben"
                    ? "rounded-br-sm bg-indigo-600 text-white"
                    : "rounded-bl-sm border border-slate-100 bg-white text-slate-700"
                }`}
              >
                {m.metin}
                <span className={`mt-1 block text-[9px] ${m.kimden === "ben" ? "text-indigo-200" : "text-slate-300"}`}>
                  {m.saat}
                </span>
              </div>
            </div>
          ))}

          {yaziyor && (
            <div className="flex justify-start">
              <div className="rounded-2xl rounded-bl-sm border border-slate-100 bg-white px-4 py-3 shadow-sm">
                <div className="flex gap-1">
                  <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-300" style={{ animationDelay: "0ms" }} />
                  <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-300" style={{ animationDelay: "100ms" }} />
                  <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-300" style={{ animationDelay: "200ms" }} />
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Hızlı sorular */}
        {mesajlar.length <= 1 && (
          <div className="flex flex-wrap gap-1.5 border-t border-slate-100 bg-white px-3 py-2">
            {["Kargo ne zaman?", "İade nasıl?", "Kuponlar", "CSPR nedir?"].map((q) => (
              <button
                key={q}
                type="button"
                onClick={() => { setYazi(q); }}
                className="rounded-full border border-slate-200 px-3 py-1 text-[11px] font-bold text-slate-500 transition hover:border-indigo-400 hover:text-indigo-600"
              >
                {q}
              </button>
            ))}
          </div>
        )}

        {/* Giriş */}
        <form onSubmit={gonder} className="flex items-center gap-2 border-t border-slate-100 bg-white p-3">
          <input
            value={yazi}
            onChange={(e) => setYazi(e.target.value)}
            placeholder="Mesajınızı yazın…"
            className="h-10 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm focus:border-indigo-500 focus:bg-white focus:outline-none"
          />
          <button
            type="submit"
            disabled={!yazi.trim()}
            aria-label="Gönder"
            className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white transition hover:bg-indigo-500 disabled:opacity-30"
          >
            <Send className="h-4 w-4" />
          </button>
        </form>
      </div>
    </>
  );
}
