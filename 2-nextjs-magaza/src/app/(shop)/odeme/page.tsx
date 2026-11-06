"use client";

import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { Truck } from "lucide-react";
import { GuvenKartlari } from "@/components/GuvenKartlari";
import { sepet } from "@/components/CartProvider";
import { auth } from "@/components/AuthProvider";
import { OdemeAdimlari } from "@/components/OdemeAdimlari";
import { AuthModal } from "@/components/AuthModal";
import { ILLER, ISTANBUL_ILCELERI, kargoBolgesi, telefonDuzelt } from "@/lib/adres";
import { KARGO_BOLGELERI, UCRETSIZ_KARGO_LIMITI, teslimSuresi } from "@/lib/kargo";
import { kdvDahil } from "@/lib/fiyat";

const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

type KayitliAdres = { id: number; baslik: string; ad_soyad: string; telefon: string; il: string; ilce: string; acik_adres: string };

const etiket = "mb-1.5 block text-xs font-bold uppercase tracking-wide text-black/50";
const alan = "h-12 w-full border border-black/20 bg-white px-4 text-sm focus:border-black focus:outline-none";

export default function OdemeSayfasi() {
  const { ogeler, kdv, genelToplam, temizle } = sepet();
  const { kullanici } = auth();
  const router = useRouter();

  // İletişim + teslimat
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [telefon, setTelefon] = useState("");
  const [il, setIl] = useState("");
  const [ilce, setIlce] = useState("");
  const [acikAdres, setAcikAdres] = useState("");

  // Fatura
  const [faturaTipi, setFaturaTipi] = useState<"bireysel" | "kurumsal">("bireysel");
  const [unvan, setUnvan] = useState("");
  const [vergiDairesi, setVergiDairesi] = useState("");
  const [vergiNo, setVergiNo] = useState("");
  const [faturaAyri, setFaturaAyri] = useState(false);
  const [faturaAdresi, setFaturaAdresi] = useState("");

  // Hesaba kayıtlı adresler (giriş yapmış müşteri)
  const [adresler, setAdresler] = useState<KayitliAdres[]>([]);
  const [adresiKaydet, setAdresiKaydet] = useState(false);

  const [gonderiliyor, setGonderiliyor] = useState(false);
  const [hata, setHata] = useState<string | null>(null);
  const [authAcik, setAuthAcik] = useState(false);

  // Kupon
  const [kuponKodu, setKuponKodu] = useState("");
  const [kuponYukleniyor, setKuponYukleniyor] = useState(false);
  const [kupon, setKupon] = useState<{ code: string; name: string; discount: number; type: string; type_label: string } | null>(null);
  const [kuponMesaj, setKuponMesaj] = useState<string | null>(null);

  // Kargo bölgesi teslimat adresinden belirlenir — ayrı seçilemez, adresle çelişemez
  const bolge = useMemo(() => KARGO_BOLGELERI.find((b) => b.id === kargoBolgesi(il, ilce)) ?? null, [il, ilce]);
  const ucretsizKargo = genelToplam >= UCRETSIZ_KARGO_LIMITI || kupon?.type === "free_shipping";
  const toplamKargo = ucretsizKargo || !bolge ? 0 : bolge.fiyat;
  const indirimTutari = kupon ? kupon.discount : 0;
  const odenecekTutar = Math.max(0, genelToplam - indirimTutari) + toplamKargo;

  useEffect(() => {
    if (!kullanici) return;
    setName((n) => n || kullanici.name);
    setEmail((e) => e || kullanici.email);
    if (kullanici.phone) setTelefon((t) => t || kullanici.phone!);
    fetch("/api/hesap/adresler")
      .then((r) => (r.ok ? r.json() : null))
      .then((d) => d && setAdresler(d.data ?? []))
      .catch(() => {});
  }, [kullanici?.id]); // eslint-disable-line react-hooks/exhaustive-deps

  const adresSec = (a: KayitliAdres) => {
    setName(a.ad_soyad);
    setTelefon(a.telefon);
    setIl(a.il);
    setIlce(a.ilce);
    setAcikAdres(a.acik_adres);
  };

  const kuponUygula = async () => {
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

  const gonder = async (e: React.FormEvent) => {
    e.preventDefault();
    setHata(null);

    if (ogeler.length === 0) return setHata("Sepetiniz boş.");
    const tel = telefonDuzelt(telefon);
    if (!tel) return setHata("Geçerli bir cep telefonu girin (05XX XXX XX XX). Kargo firması teslimat için arar.");
    if (!bolge) return setHata("Teslimat ili ve ilçesini seçin.");
    if (faturaTipi === "kurumsal" && !/^\d{10,11}$/.test(vergiNo)) {
      return setHata("Vergi numarası 10 (VKN) veya 11 (TCKN) haneli olmalıdır.");
    }

    const teslimatAdresi = `${acikAdres.trim()}, ${ilce.trim()} / ${il}`;

    setGonderiliyor(true);
    try {
      const yanit = await fetch("/api/orders", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name,
          email,
          phone: tel,
          address: teslimatAdresi,
          items: ogeler.map((o) => ({ product_id: o.id, quantity: o.adet, ...(o.varyantId ? { variant_id: o.varyantId } : {}) })),
          externalRef: `NEXT-${Math.random().toString(36).slice(2, 10).toUpperCase()}`,
          fatura: {
            tipi: faturaTipi,
            ...(faturaTipi === "kurumsal" ? { unvan, vergiDairesi, vergiNo } : {}),
            ...(faturaAyri && faturaAdresi.trim() ? { adres: faturaAdresi.trim() } : {}),
          },
        }),
      });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.hata ?? "Sipariş oluşturulamadı.");

      if (kullanici && adresiKaydet) {
        fetch("/api/hesap/adresler", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ baslik: `Adres ${adresler.length + 1}`, ad_soyad: name, telefon: tel, il, ilce, acik_adres: acikAdres.trim() }),
        }).catch(() => {});
      }

      // Misafir siparişi: sipariş sayfasındaki tek adımlı üyelik önerisi bu bilgileri kullanır
      if (!kullanici) {
        try {
          sessionStorage.setItem("hba-misafir-siparis", JSON.stringify({ orderNo: sonuc.orderNo, name, email }));
        } catch {}
      }

      temizle();
      router.push(`/siparis/${sonuc.orderNo}?yeni=1&k=${encodeURIComponent(sonuc.erisimAnahtari)}`);
    } catch (err) {
      setHata(err instanceof Error ? err.message : "Beklenmeyen hata.");
      setGonderiliyor(false);
    }
  };

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[15px] font-black uppercase tracking-wide text-black sm:text-[17px]">Ödeme</h1>
          <div className="mt-5"><OdemeAdimlari aktif={2} /></div>
        </div>

        <form onSubmit={gonder} className="mt-10 grid grid-cols-1 gap-12 lg:grid-cols-3">
          <div className="space-y-8 lg:col-span-2">
            {kullanici ? (
              <div className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                <span className="text-sm font-bold text-emerald-800">✓ {kullanici.name} hesabıyla sipariş veriyorsunuz</span>
                <span className="text-xs text-emerald-700/70">Bilgileriniz otomatik dolduruldu</span>
              </div>
            ) : (
              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div className="rounded-xl border-2 border-black p-4">
                  <p className="flex items-center justify-between gap-2 text-sm font-black text-black">
                    Misafir olarak devam et
                    <span className="rounded-full bg-black px-2 py-0.5 text-[10px] font-bold text-white">Seçili</span>
                  </p>
                  <p className="mt-1 text-xs leading-relaxed text-black/50">
                    Üyelik gerekmez. Siparişinizi sipariş numarası ve e-postanızla takip edebilirsiniz.
                  </p>
                </div>
                <button type="button" onClick={() => setAuthAcik(true)}
                        className="rounded-xl border-2 border-black/10 p-4 text-left transition hover:border-black/40">
                  <p className="text-sm font-black text-black">Giriş yap / Üye ol</p>
                  <p className="mt-1 text-xs leading-relaxed text-black/50">
                    Bilgileriniz ve kayıtlı adresleriniz otomatik dolsun, siparişleriniz hesabınızda listelensin.
                  </p>
                </button>
              </div>
            )}

            {/* 1. İletişim ve teslimat */}
            <section>
              <h2 className="mb-4 text-[13px] font-black uppercase tracking-widest text-black">1. Teslimat bilgileri</h2>

              {adresler.length > 0 && (
                <div className="mb-4 flex flex-wrap gap-2">
                  <span className="self-center text-[11px] font-bold text-black/40">Kayıtlı adreslerim:</span>
                  {adresler.map((a) => (
                    <button key={a.id} type="button" onClick={() => adresSec(a)} title={`${a.acik_adres}, ${a.ilce} / ${a.il}`}
                            className="rounded-full border border-black/15 px-3 py-1.5 text-[12px] font-bold text-black/70 transition hover:border-black hover:text-black">
                      📍 {a.baslik} · {a.ilce}/{a.il}
                    </button>
                  ))}
                </div>
              )}

              <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                  <label className={etiket}>Ad Soyad *</label>
                  <input required minLength={3} value={name} onChange={(e) => setName(e.target.value)} placeholder="Ayşe Yılmaz" className={alan} />
                </div>
                <div>
                  <label className={etiket}>E-posta *</label>
                  <input required type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="ayse@ornek.com" className={alan} />
                </div>
                <div>
                  <label className={etiket}>Cep Telefonu *</label>
                  <input required type="tel" inputMode="tel" value={telefon} onChange={(e) => setTelefon(e.target.value)}
                         onBlur={() => { const t = telefonDuzelt(telefon); if (t) setTelefon(t); }}
                         placeholder="0532 123 45 67" className={alan} />
                  <p className="mt-1 text-[11px] text-black/35">Kargo firması teslimatta bu numarayı arar.</p>
                </div>
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className={etiket} htmlFor="il">İl *</label>
                    <select id="il" required value={il} onChange={(e) => { setIl(e.target.value); setIlce(""); }}
                            className="h-12 w-full border border-black/20 bg-white px-3 text-sm focus:border-black focus:outline-none">
                      <option value="">Seçiniz</option>
                      {ILLER.map((ad) => <option key={ad} value={ad}>{ad}</option>)}
                    </select>
                  </div>
                  <div>
                    <label className={etiket} htmlFor="ilce">İlçe *</label>
                    {il === "İstanbul" ? (
                      <select id="ilce" required value={ilce} onChange={(e) => setIlce(e.target.value)}
                              className="h-12 w-full border border-black/20 bg-white px-3 text-sm focus:border-black focus:outline-none">
                        <option value="">Seçiniz</option>
                        {ISTANBUL_ILCELERI.map((ad) => <option key={ad} value={ad}>{ad}</option>)}
                      </select>
                    ) : (
                      <input id="ilce" required minLength={2} value={ilce} onChange={(e) => setIlce(e.target.value)}
                             placeholder="İlçe" disabled={!il} className={`${alan} disabled:bg-black/[.03]`} />
                    )}
                  </div>
                </div>
                <div className="sm:col-span-2">
                  <label className={etiket}>Açık Adres *</label>
                  <textarea required minLength={10} rows={2} value={acikAdres} onChange={(e) => setAcikAdres(e.target.value)}
                            placeholder="Mahalle, cadde, sokak, bina ve daire no"
                            className="w-full border border-black/20 bg-white px-4 py-3 text-sm focus:border-black focus:outline-none" />
                  {kullanici && (
                    <label className="mt-2 flex cursor-pointer items-center gap-2 text-[12px] font-semibold text-black/60">
                      <input type="checkbox" checked={adresiKaydet} onChange={(e) => setAdresiKaydet(e.target.checked)} className="h-4 w-4 accent-black" />
                      Bu adresi hesabıma kaydet
                    </label>
                  )}
                </div>
              </div>

              <div className="mt-4 flex items-center gap-3 rounded-xl border border-black/10 bg-[#f8f8f8] px-4 py-3 text-[13px]">
                <Truck className="h-4 w-4 shrink-0 text-black/50" />
                {bolge ? (
                  <span>
                    <b>Kargo: {bolge.ad}</b> — {ucretsizKargo ? <b className="text-emerald-700">Ücretsiz</b> : `${bolge.fiyat} ₺`} · tahmini {teslimSuresi(bolge.gun)}
                  </span>
                ) : (
                  <span className="text-black/45">Kargo ücreti ve süresi il/ilçe seçilince hesaplanır. {UCRETSIZ_KARGO_LIMITI.toLocaleString("tr-TR")} ₺ üzeri ücretsiz.</span>
                )}
              </div>
            </section>

            {/* 2. Fatura */}
            <section>
              <h2 className="mb-4 text-[13px] font-black uppercase tracking-widest text-black">2. Fatura bilgileri</h2>
              <div className="flex gap-2">
                {(["bireysel", "kurumsal"] as const).map((tip) => (
                  <button key={tip} type="button" onClick={() => setFaturaTipi(tip)}
                          className={`h-10 rounded-full border-2 px-5 text-[13px] font-bold transition ${
                            faturaTipi === tip ? "border-black bg-black text-white" : "border-black/15 text-black/60 hover:border-black/40"
                          }`}>
                    {tip === "bireysel" ? "Bireysel" : "Kurumsal (şirket adına)"}
                  </button>
                ))}
              </div>

              {faturaTipi === "kurumsal" && (
                <div className="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-3">
                  <div className="sm:col-span-3">
                    <label className={etiket}>Şirket Unvanı *</label>
                    <input required value={unvan} onChange={(e) => setUnvan(e.target.value)} placeholder="Örnek Teknoloji A.Ş." className={alan} />
                  </div>
                  <div className="sm:col-span-2">
                    <label className={etiket}>Vergi Dairesi *</label>
                    <input required value={vergiDairesi} onChange={(e) => setVergiDairesi(e.target.value)} placeholder="Kadıköy" className={alan} />
                  </div>
                  <div>
                    <label className={etiket}>Vergi / TC No *</label>
                    <input required inputMode="numeric" maxLength={11} value={vergiNo}
                           onChange={(e) => setVergiNo(e.target.value.replace(/\D/g, ""))} placeholder="10 veya 11 hane" className={alan} />
                  </div>
                </div>
              )}

              <label className="mt-4 flex cursor-pointer items-center gap-2 text-[13px] font-semibold text-black/70">
                <input type="checkbox" checked={!faturaAyri} onChange={(e) => setFaturaAyri(!e.target.checked)} className="h-4 w-4 accent-black" />
                Fatura adresim teslimat adresiyle aynı
              </label>
              {faturaAyri && (
                <div className="mt-3">
                  <label className={etiket}>Fatura Adresi *</label>
                  <textarea required minLength={10} rows={2} value={faturaAdresi} onChange={(e) => setFaturaAdresi(e.target.value)}
                            placeholder="Fatura kesilecek açık adres, ilçe / il"
                            className="w-full border border-black/20 bg-white px-4 py-3 text-sm focus:border-black focus:outline-none" />
                </div>
              )}
            </section>

            {hata && <div className="border-l-4 border-red-600 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{hata}</div>}

            {/* Kupon kodu */}
            <div className="rounded-xl border border-black/10 bg-[#f8f8f8] p-4">
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
                <div className="mt-2 flex gap-2">
                  <input value={kuponKodu} onChange={(e) => setKuponKodu(e.target.value.toUpperCase())}
                         onKeyDown={(e) => { if (e.key === "Enter") { e.preventDefault(); kuponUygula(); } }}
                         placeholder="KUPON KODU"
                         className="h-10 flex-1 border border-black/15 bg-white px-3 font-mono text-xs font-bold uppercase focus:border-black focus:outline-none" />
                  <button type="button" onClick={kuponUygula} disabled={kuponYukleniyor || !kuponKodu.trim()}
                          className="h-10 shrink-0 rounded-full bg-black px-5 text-xs font-bold text-white transition hover:bg-black/80 disabled:opacity-40">
                    {kuponYukleniyor ? "…" : "Uygula"}
                  </button>
                </div>
              )}
              {kuponMesaj && !kupon && <p className="mt-1.5 text-xs font-semibold text-red-500">{kuponMesaj}</p>}
            </div>
          </div>

          <aside className="h-fit border-t border-black/20 pt-6 lg:border-t-0 lg:border-l lg:pl-8 lg:pt-0">
            <div className="space-y-2 text-xs">
              {ogeler.map((o) => (
                <div key={`${o.id}-${o.varyantId ?? 0}`} className="flex justify-between gap-3 text-black/60">
                  <span className="min-w-0 truncate">{o.adet} × {o.ad}{o.secenek ? ` (${o.secenek})` : ""}</span>
                  <span className="shrink-0 font-semibold tabular-nums text-black">{fmt(kdvDahil(o.fiyat) * o.adet)} ₺</span>
                </div>
              ))}
            </div>
            <div className="mt-5 space-y-3 border-t border-black/15 pt-5 text-sm">
              <div className="flex justify-between text-black/60"><span>Ürünler (KDV dahil)</span><span className="font-semibold text-black tabular-nums">{fmt(genelToplam)} ₺</span></div>
              {kupon && kupon.discount > 0 && (
                <div className="flex justify-between text-emerald-600">
                  <span>İndirim ({kupon.code})</span>
                  <span className="font-bold tabular-nums">−{fmt(kupon.discount)} ₺</span>
                </div>
              )}
              <div className="flex justify-between text-black/60">
                <span>Kargo</span>
                <span className={`font-semibold tabular-nums ${ucretsizKargo ? "text-emerald-600" : "text-black"}`}>
                  {ucretsizKargo ? "Ücretsiz" : bolge ? `${fmt(toplamKargo)} ₺` : "—"}
                </span>
              </div>
              <div className="flex items-baseline justify-between border-t border-black/15 pt-4">
                <span className="text-sm font-bold text-black">Ödenecek</span>
                <span className="text-xl font-black tabular-nums">{fmt(odenecekTutar)} ₺</span>
              </div>
              <p className="text-right text-[11px] text-black/40">Fiyatlara %20 KDV dahildir ({fmt(kdv)} ₺)</p>
            </div>
            <button type="submit" disabled={gonderiliyor}
                    className="mt-8 h-12 w-full rounded-full bg-black text-sm font-bold text-white transition hover:bg-black/80 active:scale-95 disabled:opacity-50">
              {gonderiliyor ? "Gönderiliyor…" : "Siparişi Onayla — Stoğu Rezerve Et"}
            </button>
            <p className="mt-3 text-center text-[11px] leading-relaxed text-black/45">
              Siparişi onaylayarak{" "}
              <a href="/sozlesme/mesafeli-satis" target="_blank" className="font-semibold underline underline-offset-2 hover:text-black">Mesafeli Satış Sözleşmesi</a>
              {" "}ve{" "}
              <a href="/iade-degisim" target="_blank" className="font-semibold underline underline-offset-2 hover:text-black">İade Koşulları</a>
              &apos;nı kabul etmiş olursunuz.
            </p>
          </aside>
        </form>

        <div className="mt-12">
          <GuvenKartlari kompakt />
        </div>
      </div>
      <AuthModal acik={authAcik} kapat={() => setAuthAcik(false)} />
    </div>
  );
}
