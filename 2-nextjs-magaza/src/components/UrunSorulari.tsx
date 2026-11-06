"use client";

import { useEffect, useState } from "react";
import { MessageCircleQuestion } from "lucide-react";
import { auth } from "./AuthProvider";

type Soru = { id: number; ad: string; soru: string; cevap: string; tarih: string };

/** Ürün soru-cevap: müşteri sorar, satıcı yanıtlar; yalnız yanıtlananlar yayınlanır. */
export function UrunSorulari({ urunId }: { urunId: number }) {
  const { kullanici } = auth();
  const [sorular, setSorular] = useState<Soru[]>([]);
  const [formAcik, setFormAcik] = useState(false);
  const [ad, setAd] = useState("");
  const [eposta, setEposta] = useState("");
  const [soru, setSoru] = useState("");
  const [durum, setDurum] = useState<"hazir" | "gonderiliyor" | "tamam" | "hata">("hazir");
  const [mesaj, setMesaj] = useState("");

  useEffect(() => {
    fetch(`/api/products/${urunId}/sorular`)
      .then((r) => r.json())
      .then((d) => setSorular(d.data ?? []))
      .catch(() => {});
  }, [urunId]);

  useEffect(() => {
    if (kullanici) {
      setAd((a) => a || kullanici.name);
      setEposta((e) => e || kullanici.email);
    }
  }, [kullanici]);

  const gonder = async (e: React.FormEvent) => {
    e.preventDefault();
    setDurum("gonderiliyor");
    try {
      const yanit = await fetch(`/api/products/${urunId}/sorular`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ad, email: eposta, soru }),
      });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.hata ?? "Soru gönderilemedi.");
      setMesaj(sonuc.message);
      setDurum("tamam");
      setSoru("");
    } catch (err) {
      setMesaj(err instanceof Error ? err.message : "Soru gönderilemedi.");
      setDurum("hata");
    }
  };

  return (
    <section id="sorular" className="scroll-mt-40 px-5 pt-12 sm:px-10">
      <div className="mx-auto max-w-7xl border-t border-black/10 pt-8">
        <div className="flex flex-wrap items-baseline justify-between gap-3">
          <h2 className="text-[15px] font-black uppercase tracking-wide text-black">
            Satıcıya Sor <span className="text-black/35">({sorular.length})</span>
          </h2>
          {!formAcik && durum !== "tamam" && (
            <button type="button" onClick={() => setFormAcik(true)}
                    className="flex items-center gap-1.5 rounded-full border border-black/15 px-4 py-2 text-[12px] font-bold text-black/70 transition hover:border-black hover:text-black">
              <MessageCircleQuestion className="h-4 w-4" /> Soru sor
            </button>
          )}
        </div>

        {durum === "tamam" && (
          <p className="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-[13px] font-semibold text-emerald-800">✓ {mesaj}</p>
        )}

        {formAcik && durum !== "tamam" && (
          <form onSubmit={gonder} className="mt-4 max-w-2xl space-y-3 rounded-xl border border-black/10 p-4">
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <input required minLength={2} value={ad} onChange={(e) => setAd(e.target.value)} placeholder="Adınız" aria-label="Adınız"
                     className="h-10 border border-black/15 bg-white px-3 text-[13px] focus:border-black focus:outline-none" />
              <input required type="email" value={eposta} onChange={(e) => setEposta(e.target.value)} placeholder="E-posta (yanıt için, yayınlanmaz)"
                     aria-label="E-posta" className="h-10 border border-black/15 bg-white px-3 text-[13px] focus:border-black focus:outline-none" />
            </div>
            <textarea required minLength={10} maxLength={1000} rows={3} value={soru} onChange={(e) => setSoru(e.target.value)}
                      placeholder="Ürünle ilgili sorunuzu yazın (ör. Mac ile uyumlu mu?)" aria-label="Sorunuz"
                      className="w-full border border-black/15 bg-white px-3 py-2 text-[13px] focus:border-black focus:outline-none" />
            {durum === "hata" && <p className="text-[12px] font-medium text-red-600">{mesaj}</p>}
            <div className="flex items-center gap-3">
              <button type="submit" disabled={durum === "gonderiliyor"}
                      className="h-10 rounded-full bg-black px-5 text-[12px] font-bold text-white transition hover:bg-black/80 disabled:opacity-50">
                {durum === "gonderiliyor" ? "Gönderiliyor…" : "Soruyu gönder"}
              </button>
              <span className="text-[11px] text-black/40">Yanıtlandığında e-posta ile bildirilir ve burada yayınlanır.</span>
            </div>
          </form>
        )}

        {sorular.length === 0 ? (
          !formAcik && durum !== "tamam" && (
            <p className="mt-4 text-[13px] text-black/45">Bu ürün hakkında henüz soru yok. Merak ettiğinizi satıcıya sorun.</p>
          )
        ) : (
          <div className="mt-5 divide-y divide-black/10 border-y border-black/10">
            {sorular.map((s) => (
              <div key={s.id} className="py-4 text-[13px]">
                <p className="font-semibold text-black">
                  <span className="mr-1.5 text-black/35">S:</span>{s.soru}
                </p>
                <p className="mt-1.5 leading-relaxed text-black/70">
                  <span className="mr-1.5 font-bold text-[var(--marka)]">C:</span>{s.cevap}
                </p>
                <p className="mt-1.5 text-[11px] text-black/35">
                  {s.ad} · {new Date(s.tarih).toLocaleDateString("tr-TR", { day: "numeric", month: "long", year: "numeric" })} · Satıcı yanıtladı
                </p>
              </div>
            ))}
          </div>
        )}
      </div>
    </section>
  );
}
