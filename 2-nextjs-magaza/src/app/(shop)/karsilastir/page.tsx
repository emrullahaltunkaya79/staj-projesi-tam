"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { X, Star } from "lucide-react";
import { kdvDahil, tl } from "@/lib/fiyat";

type Urun = {
  id: number;
  ad: string;
  kategori: string | null;
  fiyat: number;
  marka: string | null;
  puan: number | null;
  stokta: boolean;
  gorsel: string | null;
};

const ANAHTAR = "hba-karsilastir";

type Depo = Record<string, Urun[]>;

function oku(): Depo {
  try {
    return JSON.parse(localStorage.getItem(ANAHTAR) ?? "{}");
  } catch {
    return {};
  }
}

export default function KarsilastirSayfasi() {
  const [depo, setDepo] = useState<Depo>({});

  useEffect(() => setDepo(oku()), []);

  const kategoriler = Object.entries(depo).filter(([, liste]) => liste.length > 0);

  const cikar = (kategori: string, id: number) => {
    const yeni = { ...depo, [kategori]: depo[kategori].filter((u) => u.id !== id) };
    setDepo(yeni);
    localStorage.setItem(ANAHTAR, JSON.stringify(yeni));
    window.dispatchEvent(new Event("hba-karsilastir"));
  };

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[15px] font-black uppercase tracking-wide text-black sm:text-[17px]">Ürün Karşılaştırma</h1>
          <p className="mt-2 text-sm text-black/50">Ürün sayfalarındaki "Karşılaştırmaya ekle" ile buraya ürün ekleyebilirsiniz (kategori başına en fazla 4).</p>
        </div>

        {kategoriler.length === 0 ? (
          <div className="py-20 text-center">
            <p className="text-sm text-black/50">Karşılaştırma listeniz boş.</p>
            <Link href="/" className="mt-4 inline-block text-sm font-bold underline underline-offset-4">Ürünlere göz at →</Link>
          </div>
        ) : (
          <div className="mt-8 space-y-12">
            {kategoriler.map(([kategori, liste]) => (
              <section key={kategori}>
                <h2 className="mb-4 text-[13px] font-black uppercase tracking-widest text-black">{kategori}</h2>
                <div className="overflow-x-auto">
                  <table className="w-full min-w-[640px] border-collapse text-left text-[13px]">
                    <thead>
                      <tr>
                        {liste.map((u) => (
                          <th key={u.id} className="w-1/4 border-b border-black/10 p-3 align-top">
                            <div className="relative">
                              <button type="button" onClick={() => cikar(kategori, u.id)} aria-label={`${u.ad} çıkar`}
                                      className="absolute right-0 top-0 z-10 rounded-full bg-white/80 p-1 text-black/40 transition hover:text-red-600">
                                <X className="h-4 w-4" />
                              </button>
                              <Link href={`/urun/${u.id}`} className="block">
                                <div className="flex aspect-square items-center justify-center bg-[#f4f4f4]">
                                  {u.gorsel ? (
                                    // eslint-disable-next-line @next/next/no-img-element
                                    <img src={u.gorsel} alt={u.ad} className="h-full w-full object-contain p-3" />
                                  ) : (
                                    <span className="text-4xl font-black text-black/10">{u.ad.charAt(0)}</span>
                                  )}
                                </div>
                                <p className="mt-2 text-[12px] font-semibold leading-snug text-black">{u.ad}</p>
                              </Link>
                            </div>
                          </th>
                        ))}
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-black/10">
                      <tr>
                        {liste.map((u) => (
                          <td key={u.id} className="p-3 align-top">
                            {u.marka && <p className="text-[10px] font-black uppercase tracking-widest text-black/40">{u.marka}</p>}
                            <p className="mt-1 text-[15px] font-black tabular-nums text-black">{tl(kdvDahil(u.fiyat))} ₺</p>
                            <p className={`mt-0.5 text-[11px] font-bold ${u.stokta ? "text-emerald-600" : "text-red-500"}`}>
                              {u.stokta ? "Stokta" : "Tükendi"}
                            </p>
                          </td>
                        ))}
                      </tr>
                      <tr>
                        {liste.map((u) => (
                          <td key={u.id} className="p-3">
                            {u.puan != null ? (
                              <span className="flex items-center gap-1 text-[12px] font-bold text-amber-500">
                                <Star className="h-3 w-3 fill-amber-400" /> {u.puan}
                              </span>
                            ) : (
                              <span className="text-[12px] text-black/35">Değerlendirme yok</span>
                            )}
                          </td>
                        ))}
                      </tr>
                      <tr>
                        {liste.map((u) => (
                          <td key={u.id} className="p-3">
                            <Link href={`/urun/${u.id}`}
                                  className="inline-flex h-9 items-center rounded-full bg-black px-4 text-[11px] font-bold text-white transition hover:bg-black/80">
                              Ürünü incele
                            </Link>
                          </td>
                        ))}
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
