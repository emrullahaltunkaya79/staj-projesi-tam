"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { GuvenKartlari } from "@/components/GuvenKartlari";
import { sepet } from "@/components/CartProvider";
import { auth } from "@/components/AuthProvider";
import { KargoSecici, UCRETSIZ_KARGO_LIMITI } from "@/components/KargoSecici";

const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

export default function OdemeSayfasi() {
  const { ogeler, araToplam, kdv, genelToplam, temizle } = sepet();
  const { kullanici } = auth();
  const router = useRouter();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [address, setAddress] = useState("");
  const [taxRate, setTaxRate] = useState("20");
  const [gonderiliyor, setGonderiliyor] = useState(false);
  const [hata, setHata] = useState<string | null>(null);

  // Kupon state
  const [kuponKodu, setKuponKodu] = useState("");
  const [kuponYukleniyor, setKuponYukleniyor] = useState(false);
  const [kupon, setKupon] = useState<{ code: string; name: string; discount: number; type: string; type_label: string } | null>(null);
  const [kuponMesaj, setKuponMesaj] = useState<string | null>(null);

  // Kargo
  const [kargoBolge, setKargoBolge] = useState("");
  const [kargoFiyat, setKargoFiyat] = useState(0);
  const ucretsizKargo = genelToplam >= UCRETSIZ_KARGO_LIMITI || kupon?.type === "free_shipping";
  const toplamKargo = ucretsizKargo ? 0 : kargoFiyat;

  // Girişli kullanıcıysa bilgilerini ön-doldur
  useEffect(() => {
    if (kullanici) {
      setName(kullanici.name);
      setEmail(kullanici.email);
    }
  }, [kullanici?.id]);

  const kuponUygula = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!kuponKodu.trim()) return;
    setKuponYukleniyor(true);
    setKuponMesaj(null);
    setKupon(null);

    try {
      const r = await fetch("/api/coupons/validate", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ code: kuponKodu.trim(), cart_total: genelToplam }),
      });
      const d = await r.json();
      if (d.valid && d.data) {
        setKupon(d.data);
        setKuponMesaj(d.message);
      } else {
        setKuponMesaj(d.message || "Kupon geçersiz.");
      }
    } catch {
      setKuponMesaj("Kupon doğrulanamadı.");
    } finally {
      setKuponYukleniyor(false);
    }
  };

  const indirimTutari = kupon ? kupon.discount : 0;
  const odenecekTutar = Math.max(0, genelToplam - indirimTutari) + toplamKargo;

  // Giriş yapmış müşterinin bilgilerini ön doldur
  useEffect(() => {
    if (kullanici) {
      setName((prev) => prev || kullanici.name);
      setEmail((prev) => prev || kullanici.email);
    }
  }, [kullanici]);

  const gonder = async (e: React.FormEvent) => {
    e.preventDefault();
    setHata(null);

    if (ogeler.length === 0) {
      setHata("Sepetiniz boş.");
      return;
    }

    setGonderiliyor(true);
    try {
      const yanit = await fetch("/api/orders", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name,
          email,
          address,
          taxRate: Number(taxRate),
          items: ogeler.map((o) => ({ product_id: o.id, quantity: o.adet })),
          externalRef: `NEXT-${Math.random().toString(36).slice(2, 10).toUpperCase()}`,
        }),
      });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.hata ?? "Sipariş oluşturulamadı.");

      temizle();
      router.push(`/siparis/${sonuc.orderNo}?yeni=1`);
    } catch (err) {
      setHata(err instanceof Error ? err.message : "Beklenmeyen hata.");
      setGonderiliyor(false);
    }
  };

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[56px] font-black leading-none tracking-[-0.03em] sm:text-[84px]">Ödeme</h1>
        </div>

        <form onSubmit={gonder} className="mt-10 grid grid-cols-1 gap-12 lg:grid-cols-3">
          <div className="space-y-6 lg:col-span-2">
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
              <div>
                <label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-black/50">Ad Soyad *</label>
                <input required minLength={3} value={name} onChange={(e) => setName(e.target.value)} placeholder="Ayşe Yılmaz"
                       className="h-12 w-full border border-black/20 bg-white px-4 text-sm focus:border-black focus:outline-none" />
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-black/50">E-posta *</label>
                <input required type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="ayse@ornek.com"
                       className="h-12 w-full border border-black/20 bg-white px-4 text-sm focus:border-black focus:outline-none" />
              </div>
              <div className="sm:col-span-2">
                <label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-black/50">Teslim Adresi *</label>
                <textarea required minLength={10} rows={3} value={address} onChange={(e) => setAddress(e.target.value)} placeholder="Mahalle, cadde, no, ilçe / il"
                          className="w-full border border-black/20 bg-white px-4 py-3 text-sm focus:border-black focus:outline-none" />
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-black/50">KDV Oranı</label>
                <select value={taxRate} onChange={(e) => setTaxRate(e.target.value)}
                        className="h-12 w-full border border-black/20 bg-white px-3 text-sm focus:border-black focus:outline-none">
                  <option value="20">%20 (Standart)</option>
                  <option value="10">%10 (Orta)</option>
                  <option value="1">%1 (Düşük)</option>
                  <option value="0">KDV Yok</option>
                </select>
              </div>
            </div>

            {hata && (
              <div className="border-l-4 border-red-600 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{hata}</div>
            )}

            {/* Kargo seçimi */}
            <div className="mt-6">
              <KargoSecici
                toplamTutar={genelToplam}
                secilen={kargoBolge}
                onSec={(id, fiyat) => { setKargoBolge(id); setKargoFiyat(fiyat); }}
              />
            </div>

            {/* Kupon kodu */}
            <div className="mt-6 rounded-xl border border-black/10 bg-[#f8f8f8] p-4">
              <p className="text-[10px] font-bold uppercase tracking-widest text-black/40">İndirim Kuponu</p>
              {kupon ? (
                <div className="mt-2 flex items-center justify-between">
                  <div>
                    <p className="text-sm font-bold text-emerald-600">✓ {kupon.name}</p>
                    <p className="text-xs text-black/40">{kupon.type_label}</p>
                  </div>
                  <button type="button" onClick={() => { setKupon(null); setKuponKodu(""); setKuponMesaj(null); }}
                          className="text-xs font-bold text-red-500 underline underline-offset-2">
                    Kaldır
                  </button>
                </div>
              ) : (
                <form onSubmit={kuponUygula} className="mt-2 flex gap-2">
                  <input value={kuponKodu} onChange={(e) => setKuponKodu(e.target.value.toUpperCase())}
                         placeholder="KUPON KODU"
                         className="h-10 flex-1 border border-black/15 bg-white px-3 font-mono text-xs font-bold uppercase focus:border-black focus:outline-none" />
                  <button type="submit" disabled={kuponYukleniyor || !kuponKodu.trim()}
                          className="h-10 shrink-0 rounded-full bg-black px-5 text-xs font-bold text-white transition hover:bg-black/80 disabled:opacity-40">
                    {kuponYukleniyor ? "…" : "Uygula"}
                  </button>
                </form>
              )}
              {kuponMesaj && !kupon && (
                <p className="mt-1.5 text-xs font-semibold text-red-500">{kuponMesaj}</p>
              )}
            </div>
          </div>

          <aside className="h-fit border-t border-black/20 pt-6 lg:border-t-0 lg:border-l lg:pl-8 lg:pt-0">
            <div className="space-y-2 text-xs">
              {ogeler.map((o) => (
                <div key={o.id} className="flex justify-between text-black/60">
                  <span className="min-w-0 truncate">{o.adet} × {o.ad}</span>
                  <span className="shrink-0 font-semibold tabular-nums text-black">{fmt(o.fiyat * o.adet)} ₺</span>
                </div>
              ))}
            </div>
            <div className="mt-5 space-y-3 border-t border-black/15 pt-5 text-sm">
              <div className="flex justify-between text-black/60"><span>Ara Toplam</span><span className="font-semibold text-black tabular-nums">{fmt(araToplam)} ₺</span></div>
              <div className="flex justify-between text-black/60"><span>KDV (%{taxRate})</span><span className="font-semibold text-black tabular-nums">{fmt(kdv)} ₺</span></div>
              {kupon && kupon.discount > 0 && (
                <div className="flex justify-between text-emerald-600">
                  <span>İndirim ({kupon.code})</span>
                  <span className="font-bold tabular-nums">−{fmt(kupon.discount)} ₺</span>
                </div>
              )}
              {kupon && kupon.type === "free_shipping" && (
                <div className="flex justify-between text-emerald-600"><span>Kupon</span><span className="font-bold">Ücretsiz Kargo</span></div>
              )}
              {toplamKargo > 0 && (
                <div className="flex justify-between text-black/60">
                  <span>Kargo</span>
                  <span className="font-semibold text-black tabular-nums">{fmt(toplamKargo)} ₺</span>
                </div>
              )}
              {ucretsizKargo && kargoBolge && (
                <div className="flex justify-between text-emerald-600">
                  <span>Kargo</span>
                  <span className="font-bold">Ücretsiz</span>
                </div>
              )}
              <div className="flex justify-between border-t border-black/15 pt-4 text-xl font-black tabular-nums">{fmt(odenecekTutar)} ₺</div>
            </div>
            <button type="submit" disabled={gonderiliyor}
                    className="mt-8 h-12 w-full rounded-full bg-black text-sm font-bold text-white transition hover:bg-black/80 active:scale-95 disabled:opacity-50">
              {gonderiliyor ? "Gönderiliyor…" : "Siparişi Onayla — Stoğu Rezerve Et"}
            </button>
          </aside>
        </form>

        <div className="mt-12">
          <GuvenKartlari kompakt />
        </div>
      </div>
    </div>
  );
}
