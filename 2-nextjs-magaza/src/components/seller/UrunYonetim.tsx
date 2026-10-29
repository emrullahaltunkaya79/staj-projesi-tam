"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Plus, X, Pencil, Trash2, Loader2, Image as ImageIcon } from "lucide-react";

import type { SellerUrun } from "@/lib/seller";

export function UrunYonetim({ urunler, kategoriler }: { urunler: SellerUrun[]; kategoriler: string[] }) {
  const router = useRouter();
  const [modalAcik, setModalAcik] = useState(false);
  const [duzenlenen, setDuzenlenen] = useState<SellerUrun | null>(null);
  const [yukleniyor, setYukleniyor] = useState(false);
  const [hata, setHata] = useState<string | null>(null);

  // Form state
  const [ad, setAd] = useState("");
  const [kategori, setKategori] = useState("");
  const [birim, setBirim] = useState("adet");
  const [alisFiyat, setAlisFiyat] = useState("");
  const [satisFiyat, setSatisFiyat] = useState("");
  const [stok, setStok] = useState("");
  const [kod, setKod] = useState("");

  const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

  const ac = (u?: SellerUrun) => {
    if (u) {
      setDuzenlenen(u);
      setAd(u.name); setKategori(u.category ?? ""); setBirim(u.unit);
      setAlisFiyat(String(u.purchase_price)); setSatisFiyat(String(u.sale_price));
      setStok(String(u.stock_quantity)); setKod(u.code ?? "");
    } else {
      setDuzenlenen(null);
      setAd(""); setKategori(""); setBirim("adet");
      setAlisFiyat(""); setSatisFiyat(""); setStok(""); setKod("");
    }
    setHata(null);
    setModalAcik(true);
  };

  const kaydet = async (e: React.FormEvent) => {
    e.preventDefault();
    setYukleniyor(true); setHata(null);

    const govde = JSON.stringify({
      name: ad, unit: birim,
      purchase_price: Number(alisFiyat), sale_price: Number(satisFiyat),
      stock_quantity: Number(stok), code: kod || null,
    });

    try {
      const url = duzenlenen ? `/api/satici/products/${duzenlenen.id}` : "/api/satici/products";
      const method = duzenlenen ? "PUT" : "POST";
      const r = await fetch(url, { method, headers: { "Content-Type": "application/json" }, body: govde });
      const d = await r.json();
      if (!r.ok) throw new Error(d.message || d.errors?.name?.[0] || "Kaydedilemedi.");
      setModalAcik(false);
      router.refresh();
    } catch (err) {
      setHata(err instanceof Error ? err.message : "Beklenmeyen hata.");
    } finally {
      setYukleniyor(false);
    }
  };

  const sil = async (id: number, ad: string) => {
    if (!confirm(`"${ad}" ürününü silmek istediğinize emin misiniz?`)) return;
    const r = await fetch(`/api/satici/products/${id}`, { method: "DELETE" });
    if (r.ok) router.refresh();
  };

  const gorselYukle = async (id: number, files: FileList | null) => {
    if (!files || files.length === 0) return;
    const fd = new FormData();
    Array.from(files).forEach((f) => fd.append("images[]", f));
    const r = await fetch(`/api/satici/products/${id}/images`, { method: "POST", body: fd });
    if (r.ok) router.refresh();
    else alert("Görsel yüklenemedi. Dosya boyutu maks 4 MB olmalı.");
  };

  return (
    <>
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-black tracking-tight text-slate-900">Ürünler</h1>
          <p className="mt-1 text-sm text-slate-400">{urunler.length} ürün · ekleyin, düzenleyin, silin</p>
        </div>
        <button type="button" onClick={() => ac()}
                className="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-500 active:scale-95">
          <Plus className="h-4 w-4" /> Yeni Ürün
        </button>
      </div>

      <div className="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[720px] text-left text-sm">
            <thead>
              <tr className="border-b border-slate-100 bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-400">
                <th className="px-5 py-3">Ürün</th>
                <th className="px-3 py-3">Kategori</th>
                <th className="px-3 py-3 text-right">Satış</th>
                <th className="px-3 py-3 text-right">Stok</th>
                <th className="px-5 py-3 text-right">İşlem</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50">
              {urunler.map((u) => (
                <tr key={u.id} className="transition hover:bg-slate-50">
                  <td className="px-5 py-3">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-100">
                        {u.image_url ? (
                          // eslint-disable-next-line @next/next/no-img-element
                          <img src={u.image_url} alt="" className="h-full w-full object-contain p-1" />
                        ) : (
                          <span className="text-lg font-black text-slate-300">{u.name.charAt(0)}</span>
                        )}
                      </div>
                      <div>
                        <p className="font-bold text-slate-800">{u.name}</p>
                        {u.code && <p className="font-mono text-xs text-slate-400">{u.code}</p>}
                      </div>
                    </div>
                  </td>
                  <td className="px-3 py-3 text-slate-500">{u.category ?? "—"}</td>
                  <td className="px-3 py-3 text-right font-bold tabular-nums text-slate-800">{fmt(u.sale_price)} ₺</td>
                  <td className="px-3 py-3 text-right">
                    <span className={`rounded-full px-2.5 py-1 text-[11px] font-bold ${
                      u.in_stock ? "bg-emerald-50 text-emerald-600" : "bg-red-50 text-red-500"
                    }`}>{u.stock_quantity}</span>
                  </td>
                  <td className="px-5 py-3">
                    <div className="flex items-center justify-end gap-1">
                      <label title="Görsel Yükle" className="cursor-pointer rounded-lg p-2 text-slate-400 transition hover:bg-emerald-50 hover:text-emerald-600">
                        <ImageIcon className="h-4 w-4" />
                        <input type="file" accept="image/*" multiple hidden
                               onChange={(e) => gorselYukle(u.id, e.target.files)} />
                      </label>
                      <button type="button" onClick={() => ac(u)} title="Düzenle"
                              className="rounded-lg p-2 text-slate-400 transition hover:bg-indigo-50 hover:text-indigo-600">
                        <Pencil className="h-4 w-4" />
                      </button>
                      <button type="button" onClick={() => sil(u.id, u.name)} title="Sil"
                              className="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                        <Trash2 className="h-4 w-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal */}
      {modalAcik && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div onClick={() => setModalAcik(false)} className="absolute inset-0 bg-black/40 backdrop-blur-sm" />
          <div className="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
            <button type="button" onClick={() => setModalAcik(false)} aria-label="Kapat"
                    className="absolute right-4 top-4 text-slate-300 transition hover:text-slate-600">
              <X className="h-5 w-5" />
            </button>
            <h2 className="text-lg font-black text-slate-900">{duzenlenen ? "Ürünü Düzenle" : "Yeni Ürün"}</h2>

            <form onSubmit={kaydet} className="mt-5 grid grid-cols-2 gap-4">
              <div className="col-span-2">
                <label className="mb-1 block text-xs font-bold text-slate-500">Ürün Adı *</label>
                <input required value={ad} onChange={(e) => setAd(e.target.value)}
                       className="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none" />
              </div>
              <div>
                <label className="mb-1 block text-xs font-bold text-slate-500">Kategori</label>
                <select value={kategori} onChange={(e) => setKategori(e.target.value)}
                        className="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                  <option value="">Seçiniz</option>
                  {kategoriler.map((k) => <option key={k} value={k}>{k}</option>)}
                </select>
              </div>
              <div>
                <label className="mb-1 block text-xs font-bold text-slate-500">Birim *</label>
                <select value={birim} onChange={(e) => setBirim(e.target.value)}
                        className="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                  {["adet", "kutu", "paket", "set", "takım", "kg", "lt", "metre"].map((b) => <option key={b}>{b}</option>)}
                </select>
              </div>
              <div>
                <label className="mb-1 block text-xs font-bold text-slate-500">Alış Fiyatı (₺) *</label>
                <input required type="number" step="0.01" min="0" value={alisFiyat} onChange={(e) => setAlisFiyat(e.target.value)}
                       className="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none" />
              </div>
              <div>
                <label className="mb-1 block text-xs font-bold text-slate-500">Satış Fiyatı (₺) *</label>
                <input required type="number" step="0.01" min="0" value={satisFiyat} onChange={(e) => setSatisFiyat(e.target.value)}
                       className="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none" />
              </div>
              <div>
                <label className="mb-1 block text-xs font-bold text-slate-500">Stok *</label>
                <input required type="number" min="0" value={stok} onChange={(e) => setStok(e.target.value)}
                       className="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none" />
              </div>
              <div>
                <label className="mb-1 block text-xs font-bold text-slate-500">Ürün Kodu</label>
                <input value={kod} onChange={(e) => setKod(e.target.value)} placeholder="URN-001"
                       className="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none" />
              </div>

              {hata && <p className="col-span-2 text-sm font-semibold text-red-600">{hata}</p>}

              <div className="col-span-2 mt-2 flex gap-3">
                <button type="submit" disabled={yukleniyor}
                        className="flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-indigo-600 text-sm font-bold text-white transition hover:bg-indigo-500 active:scale-95 disabled:opacity-50">
                  {yukleniyor ? <Loader2 className="h-4 w-4 animate-spin" /> : duzenlenen ? "Güncelle" : "Ürünü Oluştur"}
                </button>
                <button type="button" onClick={() => setModalAcik(false)}
                        className="h-11 rounded-xl border border-slate-200 px-5 text-sm font-bold text-slate-500 transition hover:border-slate-400">
                  İptal
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  );
}
