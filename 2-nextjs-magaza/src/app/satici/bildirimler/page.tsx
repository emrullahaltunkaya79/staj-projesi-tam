"use client";

import { useEffect, useState } from "react";
import { Mail, BellRing, CheckCircle2, Clock, TrendingDown, MessageCircleQuestion, Send, Trash2 } from "lucide-react";

type BultenKaydi = { email: string; subscribed_at: string };
type StokKaydi = { email: string; urun: string; gonderildi: boolean; tarih: string };
type FiyatKaydi = { email: string; urun: string; takip_fiyati: number; gonderildi: boolean; tarih: string };
type SoruKaydi = { id: number; ad: string; email: string; urun_adi: string; soru: string; cevap: string | null; created_at: string };
type MesajKaydi = { id: number; ad: string; eposta: string; konu: string | null; mesaj: string; created_at: string };

const tarih = (v: string) => new Date(v).toLocaleString("tr-TR", { dateStyle: "medium", timeStyle: "short" });

export default function BildirimlerSayfasi() {
  const [bulten, setBulten] = useState<BultenKaydi[]>([]);
  const [stok, setStok] = useState<StokKaydi[]>([]);
  const [fiyat, setFiyat] = useState<FiyatKaydi[]>([]);
  const [sorular, setSorular] = useState<SoruKaydi[]>([]);
  const [cevaplar, setCevaplar] = useState<Record<number, string>>({});
  const [mesajlar, setMesajlar] = useState<MesajKaydi[]>([]);
  const [yukleniyor, setYukleniyor] = useState(true);

  const yukle = () => {
    fetch("/api/bildirimler")
      .then((r) => r.json())
      .then((d) => {
        setBulten(d.data?.bulten ?? []);
        setStok(d.data?.stok ?? []);
        setFiyat(d.data?.fiyat ?? []);
      })
      .catch(() => {});
    fetch("/api/sorular")
      .then((r) => r.json())
      .then((d) => setSorular(d.data ?? []))
      .catch(() => {});
    fetch("/api/iletisim")
      .then((r) => r.json())
      .then((d) => setMesajlar(d.data ?? []))
      .catch(() => {})
      .finally(() => setYukleniyor(false));
  };

  useEffect(() => { yukle(); }, []);

  const cevapla = async (id: number) => {
    const cevap = cevaplar[id]?.trim();
    if (!cevap) return;
    await fetch(`/api/sorular/${id}/cevap`, {
      method: "PATCH",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ cevap }),
    });
    yukle();
  };

  const soruSil = async (id: number) => {
    if (!window.confirm("Bu soruyu silmek istediğinize emin misiniz?")) return;
    await fetch(`/api/sorular/${id}`, { method: "DELETE" });
    yukle();
  };

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-black text-slate-900">Bildirimler</h1>
        <p className="mt-1 text-sm text-slate-400">Bülten, stok/fiyat alarmları, ürün soruları ve iletişim mesajları.</p>
      </div>

      {yukleniyor ? (
        <p className="text-sm text-slate-400">Yükleniyor…</p>
      ) : (
        <>
          {/* Ürün soruları */}
          <section>
            <h2 className="mb-3 flex items-center gap-2 text-[15px] font-black text-slate-900">
              <MessageCircleQuestion className="h-4.5 w-4.5 text-slate-400" /> Ürün Soruları ({sorular.filter((s) => !s.cevap).length} yanıt bekliyor)
            </h2>
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
              {sorular.length === 0 ? (
                <p className="p-6 text-sm text-slate-400">Henüz soru yok.</p>
              ) : (
                <div className="divide-y divide-slate-100">
                  {sorular.map((s) => (
                    <div key={s.id} className={`px-4 py-3 ${s.cevap ? "opacity-60" : ""}`}>
                      <div className="flex flex-wrap items-center justify-between gap-2">
                        <p className="text-sm font-bold text-slate-800">
                          {s.ad} <span className="ml-1 font-normal text-slate-400">· {s.email}</span>
                        </p>
                        <span className="text-[11px] text-slate-400">{tarih(s.created_at)}</span>
                      </div>
                      <p className="mt-0.5 text-[11px] font-bold uppercase tracking-wide text-slate-400">{s.urun_adi}</p>
                      <p className="mt-1 text-sm text-slate-700">{s.soru}</p>
                      {s.cevap ? (
                        <p className="mt-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                          <b>Cevap:</b> {s.cevap}
                        </p>
                      ) : (
                        <div className="mt-2 flex gap-2">
                          <input value={cevaplar[s.id] ?? ""} onChange={(e) => setCevaplar((c) => ({ ...c, [s.id]: e.target.value }))}
                                 placeholder="Cevabınız (müşteriye e-posta ile gider)"
                                 className="h-9 flex-1 rounded-lg border border-slate-200 px-3 text-sm focus:border-slate-900 focus:outline-none" />
                          <button type="button" onClick={() => cevapla(s.id)} disabled={!cevaplar[s.id]?.trim()}
                                  className="flex h-9 items-center gap-1.5 rounded-lg bg-slate-900 px-3 text-xs font-bold text-white transition hover:bg-slate-700 disabled:opacity-40">
                            <Send className="h-3.5 w-3.5" /> Yanıtla
                          </button>
                          <button type="button" onClick={() => soruSil(s.id)} aria-label="Soruyu sil"
                                  className="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-400 transition hover:border-red-300 hover:text-red-600">
                            <Trash2 className="h-4 w-4" />
                          </button>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </div>
          </section>

          {/* Fiyat alarmları */}
          <section>
            <h2 className="mb-3 flex items-center gap-2 text-[15px] font-black text-slate-900">
              <TrendingDown className="h-4.5 w-4.5 text-slate-400" /> Fiyat Alarmları ({fiyat.length})
            </h2>
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
              {fiyat.length === 0 ? (
                <p className="p-6 text-sm text-slate-400">Henüz kayıt yok.</p>
              ) : (
                <table className="w-full text-left text-sm">
                  <thead className="border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-wide text-slate-400">
                    <tr>
                      <th className="px-4 py-2.5 font-bold">E-posta</th>
                      <th className="px-4 py-2.5 font-bold">Ürün</th>
                      <th className="px-4 py-2.5 font-bold">Takip Fiyatı</th>
                      <th className="px-4 py-2.5 font-bold">Kayıt</th>
                      <th className="px-4 py-2.5 font-bold">Durum</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {fiyat.map((k, i) => (
                      <tr key={`${k.email}-${i}`}>
                        <td className="px-4 py-2.5 font-medium text-slate-700">{k.email}</td>
                        <td className="px-4 py-2.5 text-slate-600">{k.urun}</td>
                        <td className="px-4 py-2.5 tabular-nums text-slate-600">{k.takip_fiyati} ₺</td>
                        <td className="px-4 py-2.5 text-slate-400">{tarih(k.tarih)}</td>
                        <td className="px-4 py-2.5">
                          {k.gonderildi ? (
                            <span className="flex items-center gap-1 text-emerald-600"><CheckCircle2 className="h-3.5 w-3.5" /> Gönderildi</span>
                          ) : (
                            <span className="flex items-center gap-1 text-amber-600"><Clock className="h-3.5 w-3.5" /> Bekliyor</span>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          </section>

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

          {/* İletişim mesajları */}
          <section>
            <h2 className="mb-3 flex items-center gap-2 text-[15px] font-black text-slate-900">
              <Mail className="h-4.5 w-4.5 text-slate-400" /> İletişim Mesajları ({mesajlar.length})
            </h2>
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
              {mesajlar.length === 0 ? (
                <p className="p-6 text-sm text-slate-400">Henüz mesaj yok.</p>
              ) : (
                <div className="divide-y divide-slate-100">
                  {mesajlar.map((m) => (
                    <div key={m.id} className="px-4 py-3">
                      <div className="flex flex-wrap items-center justify-between gap-2">
                        <p className="text-sm font-bold text-slate-800">
                          {m.ad} <span className="ml-1 font-normal text-slate-400">· {m.eposta}</span>
                        </p>
                        <span className="text-[11px] text-slate-400">{new Date(m.created_at).toLocaleDateString("tr-TR")}</span>
                      </div>
                      {m.konu && <p className="text-[11px] font-bold text-slate-500">Konu: {m.konu}</p>}
                      <p className="mt-1 text-sm text-slate-600">{m.mesaj}</p>
                    </div>
                  ))}
                </div>
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
