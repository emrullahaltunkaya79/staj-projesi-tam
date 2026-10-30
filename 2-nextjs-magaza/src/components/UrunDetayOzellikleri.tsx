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
  };
}) {
  const [secimler, setSecimler] = useState<Record<string, string>>({});
  const indirim = urun.discount_percent ?? 0;
  const netFiyat = indirim > 0 ? indirimliFiyat(urun) : urun.sale_price;
  const varyasyonlar = urunVaryasyonlari(urun.name);
  const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

  const tumSecildi = varyasyonlar.every((v) => secimler[v.ad]);

  return (
    <>
      {/* Görsel galerisi */}
      <div className="relative">
        <GorselGaleri anaGorsel={imgUrl(urun.id, urun.image_url)} urunAd={urun.name} />
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

        {/* Varyasyon seçici */}
        {varyasyonlar.length > 0 && (
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
              urun={{ id: urun.id, ad: urun.name, fiyat: netFiyat, gorsel: imgUrl(urun.id, urun.image_url), birim: urun.unit }}
              musait={urun.available_quantity}
            />
          ) : (
            <button disabled className="h-12 w-full max-w-xs cursor-not-allowed bg-black/10 text-sm font-bold text-black/30">
              Önce {varyasyonlar.filter((v) => !secimler[v.ad]).map((v) => v.ad).join(" ve ")} seçiniz
            </button>
          )}
        </div>
      </div>
    </>
  );
}
