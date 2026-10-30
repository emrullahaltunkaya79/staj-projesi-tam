"use client";

import { useState } from "react";
import { KalpButonu } from "./KalpButonu";
import { GorselGaleri } from "./GorselGaleri";
import { StokAciliyet } from "./StokAciliyet";
import { VaryasyonSecici, urunVaryasyonlari } from "./VaryasyonSecici";
import { SepeteEkleButonu } from "./SepeteEkleButonu";
import { indirimliFiyat } from "@/lib/backoffice";
import { imgUrl } from "@/lib/img";

export function UrunDetayOzellikleri({
  urun,
}: {
  urun: {
    id: number;
    name: string;
    code: string | null;
    barcode: string | null;
    category: string | null;
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
    variants?: { id: number; name: string; stock_quantity: number; in_stock: boolean }[];
  };
}) {
  const [secimler, setSecimler] = useState<Record<string, string>>({});
  const [seciliVaryantId, setSeciliVaryantId] = useState<number | null>(null);
  const indirim = urun.discount_percent ?? 0;
  const netFiyat = indirim > 0 ? indirimliFiyat(urun) : urun.sale_price;

  const gercekVaryantlar = urun.variants ?? [];
  const seciliVaryant = gercekVaryantlar.find((v) => v.id === seciliVaryantId) ?? null;
  const varyasyonlar = urunVaryasyonlari(urun.name);
  const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

  const tumSecildi = gercekVaryantlar.length > 0
    ? seciliVaryant !== null
    : varyasyonlar.every((v) => secimler[v.ad]);

  return (
    <>
      {/* Görsel galerisi */}
      <div className="relative">
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
      </div>

      {/* Bilgi kolonu */}
      <div className="flex flex-col">
        <h1 className="text-4xl font-black leading-tight tracking-[-0.02em] sm:text-5xl">{urun.name}</h1>
        <p className="mt-3 text-sm text-black/50">
          {urun.code ? `Kod: ${urun.code}` : ""}
          {urun.barcode ? ` · Barkod: ${urun.barcode}` : ""}
        </p>

        <div className="mt-8 flex flex-wrap items-baseline gap-3">
          <span className="text-3xl font-black tabular-nums text-red-600">{fmt(netFiyat)} ₺</span>
          {indirim > 0 && (
            <>
              <span className="text-lg tabular-nums text-black/40 line-through">{fmt(urun.sale_price)} ₺</span>
              <span className="rounded-full bg-red-600 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-white">%{indirim} indirim</span>
            </>
          )}
        </div>

        <p className="mt-3 text-sm font-medium">
          {urun.in_stock ? (
            <span className="text-black">
              Stokta — {urun.available_quantity} {urun.unit} müsait
            </span>
          ) : (
            <span className="text-black/40">Tükendi</span>
          )}
        </p>

        {/* Stok aciliyet */}
        {urun.in_stock && urun.available_quantity <= 5 && (
          <StokAciliyet musaitAdet={urun.available_quantity} urunId={urun.id} />
        )}

        {/* Gerçek varyasyon seçici (DB stoklu) */}
        {gercekVaryantlar.length > 0 && (
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
            {!tumSecildi && (
              <p className="mt-2 text-xs font-bold text-amber-500">Devam etmek için bir seçenek seçiniz</p>
            )}
          </div>
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

        {/* Sepete ekle */}
        <div className="mt-8">
          {tumSecildi ? (
            <SepeteEkleButonu
              urun={{
                id: urun.id, ad: urun.name, fiyat: netFiyat, gorsel: imgUrl(urun.id, urun.image_url), birim: urun.unit,
                ...(seciliVaryant ? { secenek: seciliVaryant.name, varyantId: seciliVaryant.id } : {}),
              }}
              musait={gercekVaryantlar.length > 0 ? (seciliVaryant?.stock_quantity ?? 0) : urun.available_quantity}
            />
          ) : (
            <button disabled className="h-12 w-full max-w-xs cursor-not-allowed bg-black/10 text-sm font-bold text-black/30">
              Önce {gercekVaryantlar.length > 0 ? "bir seçenek" : varyasyonlar.filter((v) => !secimler[v.ad]).map((v) => v.ad).join(" ve ")} seçiniz
            </button>
          )}
        </div>
      </div>
    </>
  );
}
