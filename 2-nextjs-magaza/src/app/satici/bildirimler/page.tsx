"use client";

import { useEffect, useState } from "react";
import { Mail, BellRing, CheckCircle2, Clock } from "lucide-react";

type BultenKaydi = { email: string; subscribed_at: string };
type StokKaydi = { email: string; urun: string; gonderildi: boolean; tarih: string };

const tarih = (v: string) => new Date(v).toLocaleString("tr-TR", { dateStyle: "medium", timeStyle: "short" });

export default function BildirimlerSayfasi() {
  const [bulten, setBulten] = useState<BultenKaydi[]>([]);
  const [stok, setStok] = useState<StokKaydi[]>([]);
  const [yukleniyor, setYukleniyor] = useState(true);

  useEffect(() => {
    fetch("/api/bildirimler")
      .then((r) => r.json())
      .then((d) => {
        setBulten(d.data?.bulten ?? []);
        setStok(d.data?.stok ?? []);
      })
      .catch(() => {})
      .finally(() => setYukleniyor(false));
  }, []);

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-black text-slate-900">Bildirimler</h1>
        <p className="mt-1 text-sm text-slate-400">Bülten aboneleri ve stok bildirimi kayıtları.</p>
      </div>

      {yukleniyor ? (
        <p className="text-sm text-slate-400">Yükleniyor…</p>
      ) : (
        <>
          {/* Stok bildirimleri */}
          <section>
            <h2 className="mb-3 flex items-center gap-2 text-[15px] font-black text-slate-900">
              <BellRing className="h-4.5 w-4.5 text-slate-400" /> Stok Bildirimleri ({stok.length})
            </h2>
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
              {stok.length === 0 ? (
                <p className="p-6 text-sm text-slate-400">Henüz kayıt yok.</p>
              ) : (
                <table className="w-full text-left text-sm">
                  <thead className="border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-wide text-slate-400">
                    <tr>
                      <th className="px-4 py-2.5 font-bold">E-posta</th>
                      <th className="px-4 py-2.5 font-bold">Ürün</th>
                      <th className="px-4 py-2.5 font-bold">Kayıt</th>
                      <th className="px-4 py-2.5 font-bold">Durum</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {stok.map((k, i) => (
                      <tr key={`${k.email}-${i}`}>
                        <td className="px-4 py-2.5 font-medium text-slate-700">{k.email}</td>
                        <td className="px-4 py-2.5 text-slate-600">{k.urun}</td>
                        <td className="px-4 py-2.5 text-slate-400">{tarih(k.tarih)}</td>
                        <td className="px-4 py-2.5">
                          {k.gonderildi ? (
                            <span className="flex items-center gap-1 text-emerald-600">
                              <CheckCircle2 className="h-3.5 w-3.5" /> Gönderildi
                            </span>
                          ) : (
                            <span className="flex items-center gap-1 text-amber-600">
                              <Clock className="h-3.5 w-3.5" /> Bekliyor
                            </span>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          </section>

          {/* Bülten aboneleri */}
          <section>
            <h2 className="mb-3 flex items-center gap-2 text-[15px] font-black text-slate-900">
              <Mail className="h-4.5 w-4.5 text-slate-400" /> Bülten Aboneleri ({bulten.length})
            </h2>
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
              {bulten.length === 0 ? (
                <p className="p-6 text-sm text-slate-400">Henüz abone yok.</p>
              ) : (
                <table className="w-full text-left text-sm">
                  <thead className="border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-wide text-slate-400">
                    <tr>
                      <th className="px-4 py-2.5 font-bold">E-posta</th>
                      <th className="px-4 py-2.5 font-bold">Abonelik Tarihi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {bulten.map((k) => (
                      <tr key={k.email}>
                        <td className="px-4 py-2.5 font-medium text-slate-700">{k.email}</td>
                        <td className="px-4 py-2.5 text-slate-400">{tarih(k.subscribed_at)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          </section>
        </>
      )}
    </div>
  );
}
