"use client";

import { useEffect, useState } from "react";
import { BellRing } from "lucide-react";
import { KalpButonu } from "./KalpButonu";
import { GorselGaleri } from "./GorselGaleri";
import { StokAciliyet } from "./StokAciliyet";
import { VaryasyonSecici, urunVaryasyonlari } from "./VaryasyonSecici";
import { BedenRehberi } from "./BedenRehberi";
import { SepeteEkleButonu } from "./SepeteEkleButonu";
import { indirimliFiyat } from "@/lib/backoffice";
import { GeriSayim } from "./GeriSayim";
import { TeslimatBilgisi } from "./TeslimatBilgisi";
import { KarsilastirEkle } from "./KarsilastirCubugu";
import { FiyatAlarmi } from "./FiyatAlarmi";
import { UrunPaylas } from "./UrunPaylas";
import { imgUrl } from "@/lib/img";
import { kdvDahil } from "@/lib/fiyat";

export function UrunDetayOzellikleri({
  urun,
  aciklama,
  ratingAvg,
  reviewCount,
}: {
  urun: {
    id: number;
    name: string;
    brand?: string | null;
    model?: string | null;
    specs?: { k: string; v: string }[] | null;
    code: string | null;
    barcode: string | null;
    category: string | null;
    video_url?: string | null;
    pre_order?: boolean;
    unit: string;
    sale_price: number;
    discount_percent?: number | null;
    final_price?: number;
    purchase_price?: number;
    stock_quantity: number;
    reserved_quantity: number;
    available_quantity: number;
    in_stock: boolean;
    image_url: string | null;
    image_count?: number;
    variants?: { id: number; name: string; type?: string; renk?: string | null; beden?: string | null; stock_quantity: number; in_stock: boolean }[];
  };
  aciklama: string;
  ratingAvg: number | null;
  reviewCount: number;
}) {
  const [secimler, setSecimler] = useState<Record<string, string>>({});
  const [seciliVaryantId, setSeciliVaryantId] = useState<number | null>(null);
  const [galeriSekmesi, setGaleriSekmesi] = useState<string>("foto");
  const [kombiRenk, setKombiRenk] = useState<string | null>(null);
  const [kombiBeden, setKombiBeden] = useState<string | null>(null);
  const indirim = urun.discount_percent ?? 0;
  const netFiyat = indirim > 0 ? indirimliFiyat(urun) : urun.sale_price;

  const gercekVaryantlar = urun.variants ?? [];
  const kombiMi = gercekVaryantlar[0]?.type === "kombi";
  const seciliVaryant = kombiMi
    ? gercekVaryantlar.find((v) => v.renk === kombiRenk && v.beden === kombiBeden) ?? null
    : gercekVaryantlar.find((v) => v.id === seciliVaryantId) ?? null;
  const varyasyonlar = urunVaryasyonlari(urun.name);
  const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

  const tumSecildi = gercekVaryantlar.length > 0
    ? seciliVaryant !== null && seciliVaryant.in_stock
    : varyasyonlar.every((v) => secimler[v.ad]);

  return (
    <>
      {/* Görsel galerisi */}
      <div className="relative">
        {urun.video_url && (
          <div className="mb-3 flex gap-2">
            {[["foto", "📷 Fotoğraflar"], ["video", "🎬 Video"]].map(function ([anahtar, etiket]) {
              const aktif = galeriSekmesi === anahtar;
              return (
                <button key={anahtar} type="button" onClick={() => setGaleriSekmesi(anahtar)}
                        className={`rounded-full border px-4 py-1.5 text-[12px] font-bold transition ${aktif ? "border-black bg-black text-white" : "border-black/15 text-black/60 hover:border-black/40"}`}>
                  {etiket}
                </button>
              );
            })}
          </div>
        )}
        {galeriSekmesi === "video" && urun.video_url ? (
          <div className="aspect-video w-full overflow-hidden rounded-2xl border border-black/10 bg-black">
            <iframe src={urun.video_url.replace("watch?v=", "embed/")} title="Ürün videosu"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowFullScreen className="h-full w-full" />
          </div>
        ) : (
          <>
            <GorselGaleri
              urunAd={urun.name}
              gorseller={Array.from({ length: Math.max(1, urun.image_count ?? 1) }, (_, i) => `/api/img/${urun.id}?v=${i}`)}
            />
            <div className="absolute right-4 top-4 z-10 rounded-full bg-white/80 p-2 shadow backdrop-blur">
              <KalpButonu
                urun={{ id: urun.id, ad: urun.name, fiyat: netFiyat, gorsel: imgUrl(urun.id, urun.image_url), kategori: urun.category }}
                boyut="buyuk"
              />
            </div>
          </>
        )}
      </div>

      {/* Bilgi kolonu */}
      <div className="flex flex-col">
        <h1 className="text-4xl font-black leading-tight tracking-[-0.02em] sm:text-5xl">{urun.name}</h1>
        <p className="mt-3 text-sm text-black/50">
          {(urun.brand || urun.model) && (
            <span className="mr-2 font-black uppercase tracking-widest text-[var(--marka)]">
              {urun.brand}{urun.brand && urun.model ? " · " : ""}{urun.model}
            </span>
          )}
          {urun.code ? `Kod: ${urun.code}` : ""}
          {urun.barcode ? ` · Barkod: ${urun.barcode}` : ""}
        </p>

        <div className="mt-4 flex items-center gap-2 text-[13px] text-black/40">
          {ratingAvg != null ? (
            <>
              <span className="flex text-amber-500">{"★".repeat(Math.round(ratingAvg))}<span className="text-black/15">{"★".repeat(5 - Math.round(ratingAvg))}</span></span>
              <span className="font-bold text-black">{ratingAvg.toFixed(1)}</span>
              <a href="#yorumlar" className="underline-offset-2 hover:underline">({reviewCount} değerlendirme)</a>
            </>
          ) : (
            <span>Henüz değerlendirme yok</span>
          )}
        </div>

        <div className="mt-8 flex flex-wrap items-baseline gap-3">
          <span className="text-3xl font-black tabular-nums text-black">{fmt(kdvDahil(netFiyat))} ₺</span>
          {indirim > 0 && (
            <>
              <span className="text-lg tabular-nums text-black/40 line-through">{fmt(kdvDahil(urun.sale_price))} ₺</span>
              <span className="rounded-full bg-red-600 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-white">%{indirim} indirim</span>
              <span className="flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-3 py-1 text-[11px] font-bold text-red-600">
                ⏳ bugün <GeriSayim kompakt /> kadar
              </span>
            </>
          )}
          <span className="text-[11px] font-medium text-black/40">KDV dahil</span>
        </div>

        <SosyalKanit urunId={urun.id} />
        <FiyatGecmisi urunId={urun.id} guncelFiyat={kdvDahil(netFiyat)} />
        <KampanyaRozeti urunId={urun.id} />

        <p className="mt-3 text-sm font-medium">
          {urun.in_stock ? (
            <span className="text-black">
              Stokta — {urun.available_quantity} {urun.unit} müsait
            </span>
          ) : (
            <span className="text-black/40">{urun.pre_order ? "Stokta yok — ön sipariş verilebilir" : "Tükendi"}</span>
          )}
        </p>

        {!urun.in_stock && urun.pre_order && (
          <div className="mt-3 max-w-md rounded-xl border border-black/10 bg-[#f4f6ff] px-4 py-3 text-[13px]">
            <p className="font-bold text-black">🛫 Ön sipariş açık</p>
            <p className="mt-0.5 leading-relaxed text-black/60">
              Stok girişinde ilk kargolar ön sipariş verdiklerinize göre gönderilir.
            </p>
          </div>
        )}

        {!urun.in_stock && !urun.pre_order && <StokBildirimi urunId={urun.id} />}

        {/* Stok aciliyet */}
        {urun.in_stock && urun.available_quantity <= 5 && (
          <StokAciliyet musaitAdet={urun.available_quantity} urunId={urun.id} />
        )}

        {/* Gerçek varyasyon seçici — düz (renk|beden) veya kombi (renk + beden iki aşamalı) */}
        {gercekVaryantlar.length > 0 && (
          kombiMi ? (
            <div className="mt-6 space-y-4">
              <div>
                <p className="mb-2 text-[10px] font-bold uppercase tracking-widest text-black/40">
                  Renk <span className="text-red-400">*</span>
                </p>
                <div className="flex flex-wrap gap-2">
                  {[...new Set(gercekVaryantlar.map((v) => v.renk ?? ""))].filter(Boolean).map((renk) => {
                    const aktif = kombiRenk === renk;
                    return (
                      <button
                        key={renk}
                        type="button"
                        onClick={() => { setKombiRenk(renk); setKombiBeden(null); }}
                        className={`min-w-[64px] rounded-xl border-2 px-4 py-2 text-sm font-bold transition ${
                          aktif ? "border-black bg-black text-white" : "border-black/10 bg-white text-black/60 hover:border-black/30"
                        }`}
                      >
                        {renk}
                      </button>
                    );
                  })}
                </div>
              </div>
              <div>
                <p className="mb-2 text-[10px] font-bold uppercase tracking-widest text-black/40">
                  Beden <span className="text-red-400">*</span>
                </p>
                <div className="flex flex-wrap gap-2">
                  {kombiRenk
                    ? gercekVaryantlar.filter((v) => v.renk === kombiRenk).map((v) => (
                        <button
                          key={v.id}
                          type="button"
                          disabled={!v.in_stock}
                          onClick={() => setKombiBeden(v.beden ?? null)}
                          className={`min-w-[64px] rounded-xl border-2 px-4 py-2 text-sm font-bold transition ${
                            kombiBeden === v.beden
                              ? "border-black bg-black text-white"
                              : v.in_stock
                                ? "border-black/10 bg-white text-black/60 hover:border-black/30"
                                : "cursor-not-allowed border-black/5 bg-black/5 text-black/25 line-through"
                          }`}
                          title={v.in_stock ? `Stokta ${v.stock_quantity} adet` : "Tükendi"}
                        >
                          {v.beden}
                          {v.in_stock && <span className={`ml-1.5 text-[10px] ${kombiBeden === v.beden ? "text-white/70" : "text-black/30"}`}>{v.stock_quantity}</span>}
                        </button>
                      ))
                    : <p className="text-xs text-black/40">Önce renk seçin.</p>}
                </div>
                <div className="mt-2">
                  <BedenRehberi kategoriAd={urun.category} />
                </div>
              </div>
            </div>
          ) : (
            <div className="mt-6">
              <p className="mb-2 text-[10px] font-bold uppercase tracking-widest text-black/40">
                Seçenek <span className="text-red-400">*</span>
              </p>
              <div className="flex flex-wrap gap-2">
                {gercekVaryantlar.map((v) => {
                  const aktif = seciliVaryantId === v.id;
                  return (
                    <button
                      key={v.id}
                      type="button"
                      disabled={!v.in_stock}
                      onClick={() => setSeciliVaryantId(v.id)}
                      className={`min-w-[64px] rounded-xl border-2 px-4 py-2 text-sm font-bold transition ${
                        aktif
                          ? "border-black bg-black text-white"
                          : v.in_stock
                            ? "border-black/10 bg-white text-black/60 hover:border-black/30"
                            : "cursor-not-allowed border-black/5 bg-black/5 text-black/25 line-through"
                      }`}
                      title={v.in_stock ? `Stokta ${v.stock_quantity} adet` : "Tükendi"}
                    >
                      {v.name}
                      {v.in_stock && <span className={`ml-1.5 text-[10px] ${aktif ? "text-white/70" : "text-black/30"}`}>{v.stock_quantity}</span>}
                    </button>
                  );
                })}
              </div>
            </div>
          )
        )}

        {!kombiMi && gercekVaryantlar.length > 0 && !tumSecildi && (
          <p className="-mt-3 text-xs font-bold text-amber-500">
            Devam etmek için bir seçenek seçiniz
          </p>
        )}
        {kombiMi && !tumSecildi && (
          <p className="-mt-3 text-xs font-bold text-amber-500">
            Devam etmek için renk ve beden seçiniz
          </p>
        )}

        {/* Demo varyasyon seçici (DB varyantı olmayanlar için) */}
        {gercekVaryantlar.length === 0 && varyasyonlar.length > 0 && (
          <div className="mt-6">
            <VaryasyonSecici
              varyasyonlar={varyasyonlar}
              secimler={secimler}
              onSec={(ad, deger) => setSecimler((p) => ({ ...p, [ad]: deger }))}
            />
            {!tumSecildi && (
              <p className="mt-2 text-xs font-bold text-amber-500">
                Devam etmek için {varyasyonlar.filter((v) => !secimler[v.ad]).map((v) => v.ad).join(" ve ")} seçiniz
              </p>
            )}
          </div>
        )}

        {/* Teknik özellikler */}
        {urun.specs && urun.specs.length > 0 && (
          <div className="mt-10">
            <div className="flex items-baseline justify-between border-b-2 border-black pb-2">
              <h2 className="text-base font-black uppercase tracking-wide">Teknik Özellikler</h2>
              <span className="text-[11px] font-medium text-black/35">{urun.specs.length} madde</span>
            </div>
            <dl className="divide-y divide-black/10 border-b border-black/10">
              {urun.specs.map((s, i) => (
                <div key={i} className="grid grid-cols-[110px_1fr] gap-3 py-2.5 text-[13px] sm:grid-cols-[150px_1fr]">
                  <dt className="font-bold text-black/45">{s.k}</dt>
                  <dd className="font-medium text-black/85">{s.v}</dd>
                </div>
              ))}
            </dl>
          </div>
        )}

        {/* Sepete ekle */}
        <div className="mt-8">
          {tumSecildi ? (
            <SepeteEkleButonu
              urun={{
                id: urun.id, ad: urun.name, fiyat: netFiyat, gorsel: imgUrl(urun.id, urun.image_url), birim: urun.unit,
                ...(seciliVaryant ? { secenek: seciliVaryant.name, varyantId: seciliVaryant.id } : {}),
              }}
              musait={gercekVaryantlar.length > 0 ? (seciliVaryant?.stock_quantity ?? 0) : urun.pre_order && !urun.in_stock ? 99 : urun.available_quantity}
            />
          ) : (
            <button disabled className="h-12 w-full max-w-xs cursor-not-allowed bg-black/10 text-sm font-bold text-black/30">
              Önce {gercekVaryantlar.length > 0
                ? (kombiMi ? "renk ve beden" : gercekVaryantlar[0]?.type === "beden" ? "bir beden" : "bir seçenek")
                : varyasyonlar.filter((v) => !secimler[v.ad]).map((v) => v.ad).join(" ve ")} seçiniz
            </button>
          )}
        </div>

        <TeslimatBilgisi fiyat={kdvDahil(netFiyat)} stokta={urun.in_stock} onSiparis={Boolean(urun.pre_order)} />

        {/* Karşılaştır + fiyat alarmı */}
        <KarsilastirEkle urun={{ id: urun.id, ad: urun.name, kategori: urun.category, fiyat: urun.final_price ?? urun.sale_price, marka: urun.brand ?? null, puan: ratingAvg, stokta: urun.in_stock, gorsel: urun.image_url }} />
        <FiyatAlarmi urunId={urun.id} />

        {/* Paylaş */}
        <UrunPaylas urunAd={urun.name} />

        {/* Açıklama */}
        <p className="mt-6 max-w-md text-[15px] leading-relaxed text-black/70">{aciklama}</p>
      </div>
    </>
  );
}

/** Tükenen üründe e-posta ile stok bildirimi kaydı. */
function StokBildirimi({ urunId }: { urunId: number }) {
  const [eposta, setEposta] = useState("");
  const [durum, setDurum] = useState<"hazir" | "gonderiliyor" | "tamam" | "hata">("hazir");
  const [mesaj, setMesaj] = useState("");

  const gonder = async (e: React.FormEvent) => {
    e.preventDefault();
    setDurum("gonderiliyor");
    try {
      const yanit = await fetch("/api/stock-alert", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email: eposta, product_id: urunId }),
      });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.hata ?? "Kayıt oluşturulamadı.");
      setMesaj(sonuc.message ?? "Kaydınız alındı.");
      setDurum("tamam");
    } catch (err) {
      setMesaj(err instanceof Error ? err.message : "Kayıt oluşturulamadı.");
      setDurum("hata");
    }
  };

  return (
    <div className="mt-4 rounded-2xl border border-black/10 bg-black/[.03] p-4">
      <p className="flex items-center gap-2 text-[13px] font-bold text-black">
        <BellRing className="h-4 w-4 text-[var(--marka)]" /> Stok gelince haber ver
      </p>
      {durum === "tamam" ? (
        <p className="mt-2 text-[12px] font-medium text-emerald-600">{mesaj}</p>
      ) : (
        <form onSubmit={gonder} className="mt-2.5 flex gap-2">
          <input
            type="email"
            required
            value={eposta}
            onChange={(e) => setEposta(e.target.value)}
            placeholder="e-posta adresiniz"
            className="h-10 flex-1 border border-black/15 bg-white px-3 text-[13px] focus:border-black focus:outline-none"
          />
          <button
            type="submit"
            disabled={durum === "gonderiliyor"}
            className="h-10 shrink-0 rounded-full bg-[var(--marka)] px-4 text-[12px] font-bold text-white transition hover:opacity-90 disabled:opacity-50"
          >
            {durum === "gonderiliyor" ? "…" : "Haberdar Et"}
          </button>
        </form>
      )}
      {durum === "hata" && <p className="mt-1.5 text-[12px] font-medium text-red-600">{mesaj}</p>}
    </div>
  );
}

/** Paket kampanya rozeti — ürün için geçerli aktif kampanyayı gösterir. */
function KampanyaRozeti({ urunId }: { urunId: number }) {
  const [kampanya, setKampanya] = useState<{ ad: string; tur: string; al_adet: number; ode_adet: number; esik: number; urun_id: number | null } | null>(null);

  useEffect(() => {
    fetch("/api/kampanyalar")
      .then((r) => (r.ok ? r.json() : null))
      .then((d) => {
        const liste: { ad: string; tur: string; al_adet: number; ode_adet: number; esik: number; urun_id: number | null }[] = d?.data ?? [];
        const eslesen = liste.find((k) => k.urun_id === urunId) ?? liste.find((k) => k.urun_id === null);
        setKampanya(eslesen ?? null);
      })
      .catch(() => {});
  }, [urunId]);

  if (!kampanya) return null;
  const metin =
    kampanya.tur === "x_al_y_ode"
      ? `🎁 ${kampanya.al_adet} Al ${kampanya.ode_adet} Öde — ${kampanya.al_adet}. ürün bedava!`
      : `${new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(Number(kampanya.esik))} ₺ üstü sepette bu ürün yarı fiyat`;
  return (
    <p className="mt-2 inline-flex max-w-md items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-[12px] font-bold text-amber-800">
      {metin}
    </p>
  );
}

/** "Şu an X kişi görüntülüyor" — ürüne özgü sabit taban + dakikalık hafif dalga (sosyal kanıt). */
function SosyalKanit({ urunId }: { urunId: number }) {
  const [kisi, setKisi] = useState<number | null>(null);
  const [aylikAdet, setAylikAdet] = useState<number | null>(null);

  useEffect(() => {
    const hesapla = () => ((urunId * 97) % 13) + 4 + ((Math.floor(Date.now() / 60_000) + urunId) % 9);
    setKisi(hesapla());
    const zegerek = setInterval(() => setKisi(hesapla()), 45_000);
    // Gerçek satış verisi — arka ofis siparişlerinden
    fetch(`/api/products/${urunId}/satis`)
      .then((r) => (r.ok ? r.json() : null))
      .then((d) => setAylikAdet(Number(d?.data?.aylik_adet ?? 0)))
      .catch(() => setAylikAdet(null));
    return () => clearInterval(zegerek);
  }, [urunId]);

  if (kisi == null) return null;
  return (
    <div className="mt-2 space-y-1">
      <p className="flex items-center gap-2 text-[12px] font-semibold text-emerald-700">
        <span className="relative flex h-2 w-2">
          <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75" />
          <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
        </span>
        Şu an {kisi} kişi bu ürünü görüntülüyor
      </p>
      {aylikAdet != null && aylikAdet > 0 && (
        <p className="text-[12px] font-semibold text-black/50">🔥 Bu ay {aylikAdet} adet satıldı</p>
      )}
    </div>
  );
}

/** Son 30 gün fiyat mini grafiği — panelde kesilen fiyat değişiklikleriyle beslenir. */
function FiyatGecmisi({ urunId, guncelFiyat }: { urunId: number; guncelFiyat: number }) {
  const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);
  const [veri, setVeri] = useState<{ fiyat: number; tarih: string }[] | null>(null);

  useEffect(() => {
    fetch(`/api/products/${urunId}/fiyat-gecmisi`)
      .then((r) => (r.ok ? r.json() : null))
      .then((d) => setVeri(d?.data ?? null))
      .catch(() => setVeri(null));
  }, [urunId]);

  if (!veri || veri.length < 2) return null;

  const degerler = veri.map((v) => v.fiyat);
  const min = Math.min(...degerler);
  const enDusukBugun = guncelFiyat <= min + 0.01;
  const minY = Math.min(min, guncelFiyat);
  const maksY = Math.max(Math.max(...degerler), guncelFiyat);
  const yay = (v: number) => 36 - ((v - minY) / Math.max(maksY - minY, 0.01)) * 32;
  const puanlar = [...degerler, guncelFiyat]
    .map((f, i) => `${(i / degerler.length) * 100},${yay(f)}`)
    .join(" ");

  return (
    <div className="mt-4 max-w-md rounded-xl border border-black/10 bg-[#fafafa] px-4 py-3">
      <div className="flex flex-wrap items-center justify-between gap-2 text-[11px] font-bold uppercase tracking-widest text-black/40">
        <span>Son 30 gün · fiyat geçmişi</span>
        {enDusukBugun && (
          <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-black normal-case text-emerald-700">
            ✓ 30 günün en düşüğü
          </span>
        )}
      </div>
      <svg viewBox="0 0 100 38" preserveAspectRatio="none" className="mt-2 h-12 w-full text-[var(--marka)]" role="img" aria-label="Son 30 gün fiyat grafiği">
        <polyline points={puanlar} fill="none" stroke="currentColor" strokeWidth="1.4" vectorEffect="non-scaling-stroke" />
        <circle cx="100" cy={yay(guncelFiyat)} r="1.8" className="fill-black" />
      </svg>
      <div className="mt-1 flex justify-between text-[11px] text-black/45">
        <span>En düşük {fmt(min)} ₺</span>
        <span className="font-bold text-black">Bugün {fmt(guncelFiyat)} ₺</span>
      </div>
    </div>
  );
}
