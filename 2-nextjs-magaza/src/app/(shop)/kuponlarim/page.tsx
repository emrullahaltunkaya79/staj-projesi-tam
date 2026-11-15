"use client";

import { useEffect, useState } from "react";
import Link from "next/link";

type AktifKupon = {
  code: string;
  name: string;
  etiket: string;
  min_order_total: number;
  son_gun: string | null;
};

/** Müşterinin kullanabileceği güncel kuponlar + tek tıkla kod kopyalama. */
export default function KuponlarimSayfasi() {
  const [kuponlar, setKuponlar] = useState<AktifKupon[]>([]);
  const [yukleniyor, setYukleniyor] = useState(true);
  const [kopyalanan, setKopyalanan] = useState<string | null>(null);

  useEffect(() => {
    fetch("/api/aktif-kuponlar")
      .then((r) => r.json())
      .then((d) => setKuponlar(d.data ?? []))
      .catch(() => {})
      .finally(() => setYukleniyor(false));
  }, []);

  const kopyala = async (code: string) => {
    try {
      await navigator.clipboard.writeText(code);
      setKopyalanan(code);
      setTimeout(() => setKopyalanan(null), 2000);
    } catch {
      window.prompt("Kupon kodu:", code);
    }
  };

  const tarih = (d: string) =>
    new Intl.DateTimeFormat("tr-TR", { day: "numeric", month: "long", year: "numeric" }).format(new Date(d));

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-3xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[15px] font-black uppercase tracking-wide text-black sm:text-[17px]">Kuponlarım</h1>
          <p className="mt-2 text-sm text-black/50">
            Kullanabileceğiniz güncel kuponlar — kodu kopyalayıp ödeme adımındaki “Kupon kodu” alanına yapıştırın.
          </p>
        </div>

        {yukleniyor ? (
          <p className="py-12 text-center text-sm text-black/40">Yükleniyor…</p>
        ) : kuponlar.length === 0 ? (
          <div className="py-12 text-center">
            <p className="text-3xl">🎟️</p>
            <p className="mt-3 text-sm text-black/50">
              Şu an kullanılabilir kupon yok. Yeni kampanyaları kaçırmamak için bültenimize abone olabilirsiniz.
            </p>
            <Link href="/" className="mt-6 inline-flex h-11 items-center rounded-full bg-black px-6 text-sm font-bold text-white transition hover:bg-black/80">
              Alışverişe başla
            </Link>
          </div>
        ) : (
          <div className="mt-8 space-y-4">
            {kuponlar.map((k) => (
              <div key={k.code} className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-dashed border-black/25 bg-[#fffdf5] p-5">
                <div>
                  <p className="font-mono text-lg font-black tracking-wider text-black">{k.code}</p>
                  <p className="mt-0.5 text-sm font-bold text-[var(--marka)]">{k.etiket}</p>
                  <p className="mt-1 text-xs text-black/50">
                    {k.name}
                    {k.min_order_total > 0 ? ` · min. ${k.min_order_total} ₺ sepet` : ""}
                    {k.son_gun ? ` · ${tarih(k.son_gun)} tarihine kadar` : ""}
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => kopyala(k.code)}
                  className="h-10 shrink-0 rounded-full bg-black px-5 text-xs font-bold text-white transition hover:bg-black/80 active:scale-95"
                >
                  {kopyalanan === k.code ? "✓ Kopyalandı" : "Kodu Kopyala"}
                </button>
              </div>
            ))}
            <p className="pt-2 text-xs text-black/40">
              İpucu: Kuponu kopyaladıktan sonra <Link href="/sepet" className="font-bold underline underline-offset-2">sepete</Link> gidip ödeme adımında kodu girin —
              indirim otomatik yansır.
            </p>
          </div>
        )}
      </div>
    </div>
  );
}
