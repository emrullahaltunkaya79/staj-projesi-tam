import { OdemeButonu } from "./OdemeButonu";
import { IptalButonu } from "./IptalButonu";
import { IadeFormu } from "./IadeFormu";
import { Konfeti } from "@/components/Konfeti";
import { OdemeAdimlari } from "@/components/OdemeAdimlari";
import Link from "next/link";
import { siparisErisimi } from "@/lib/oturum";
import { KolayUyelik } from "@/components/KolayUyelik";

const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

export const metadata = {
  title: "Sipariş — HepsiBirarada",
  // Bağlantıdaki erişim anahtarı dış sitelere (kargo takibi vb.) referrer ile sızmasın
  referrer: "no-referrer" as const,
  robots: { index: false, follow: false },
};

const ADIMLAR: Record<string, string> = {
  pending: "Bekliyor",
  paid: "Ödendi",
  preparing: "Hazırlanıyor",
  shipped: "Kargoda",
  delivered: "Teslim",
};
const IADE_DURUM: Record<string, string> = {
  return_requested: "İade Talebiniz Alındı",
  returned: "İade Tamamlandı — Para İadesi Yapıldı",
};
const ODEME_YONTEM: Record<string, string> = {
  cspr: "⛓️ Online (CSPR)",
  kapida: "🚚 Kapıda Ödeme",
  havale: "🏦 Havale / EFT",
};

export default async function SiparisSayfasi({
  params,
  searchParams,
}: {
  params: Promise<{ no: string }>;
  searchParams: Promise<{ yeni?: string; k?: string }>;
}) {
  const { no } = await params;
  const { yeni, k } = await searchParams;

  // Sipariş numarası sıralı (ORD-000001…) olduğu için tek başına yetmez:
  // erişim anahtarı, sahibi olunmuş oturum ya da satıcı girişi gerekir
  const { siparis: s, izinli } = await siparisErisimi(no, k ?? null);

  if (!s || !izinli) {
    return (
      <div className="px-5 pb-24 pt-16 sm:px-10">
        <div className="mx-auto max-w-md text-center">
          <p className="text-3xl">🔒</p>
          <h1 className="mt-3 text-[15px] font-black uppercase tracking-wide text-black sm:text-[17px]">
            {s ? "Bu siparişi görme yetkiniz yok" : "Sipariş bulunamadı"}
          </h1>
          <p className="mt-2 text-sm leading-relaxed text-black/55">
            {s
              ? "Sipariş sayfası yalnızca sipariş sahibine açıktır. Onay e-postasındaki bağlantıyı kullanın veya sipariş numarası + e-posta ile sorgulayın."
              : "Numarayı kontrol edin veya sipariş numarası + e-posta ile sorgulayın."}
          </p>
          <div className="mt-6 flex flex-wrap justify-center gap-3">
            <Link href="/siparis-sorgula" className="flex h-11 items-center rounded-full bg-black px-6 text-sm font-bold text-white transition hover:bg-black/80">
              Sipariş sorgula
            </Link>
            <Link href="/siparislerim" className="flex h-11 items-center rounded-full border border-black/15 px-6 text-sm font-bold text-black/70 transition hover:border-black hover:text-black">
              Siparişlerim
            </Link>
          </div>
        </div>
      </div>
    );
  }

  const adimlar = Object.keys(ADIMLAR);
  const aktifIndex = adimlar.indexOf(s.status);
  const yontem = s.payment_method ?? "cspr";
  const iptal = s.status === "cancelled";
  const iadeDurumu = IADE_DURUM[s.status];
  const iadeSon = s.iade_son_tarih ? new Date(s.iade_son_tarih) : null;
  const iadeKalanGun = iadeSon ? Math.ceil((iadeSon.getTime() - Date.now()) / 86_400_000) : null;
  const iadeSuresiDoldu = iadeKalanGun !== null && iadeKalanGun <= 0;
  // Sipariş satırları KDV hariç saklanır; müşteriye KDV dahil gösterilir
  const brut = (tutar: number) => Math.round(tutar * (100 + (s.tax_rate ?? 20))) / 100;
  const tarih = (d: Date) =>
    new Intl.DateTimeFormat("tr-TR", { day: "numeric", month: "long", timeZone: "Europe/Istanbul" }).format(d);

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-3xl">
        <Konfeti aktif={yeni === "1"} />
        {yeni === "1" && (
          <div className="mb-8 border-l-4 border-black bg-neutral-50 px-5 py-4">
            <p className="text-sm font-bold">Siparişiniz alındı — stoklar sizin adınıza rezerve edildi.</p>
            <p className="mt-1 text-xs text-black/50">
              {yontem === "cspr"
                ? "Ödeme için 30 dakikanız var. "
                : yontem === "kapida"
                  ? "Ücreti paketiniz eline ulaştığında kargo görevlisine ödeyeceksiniz. "
                  : "Havale/EFT bilgileri aşağıda — ödemeniz onaylandığında siparişiniz hazırlanacak. "}
              Sipariş no: <span className="font-mono font-bold">{s.order_no}</span>
            </p>
          </div>
        )}
        {yeni === "1" && <KolayUyelik orderNo={s.order_no} />}

        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[15px] font-black uppercase tracking-wide text-black sm:text-[17px]">Sipariş</h1>
          <div className="mt-2 flex flex-wrap items-center gap-3">
            <p className="font-mono text-sm text-black/50">{s.order_no}</p>
            <span className="rounded-full border border-black/15 px-3 py-1 text-[11px] font-bold text-black/70">
              💳 Ödeme: {ODEME_YONTEM[yontem] ?? s.odeme_yontemi ?? "Online (CSPR)"}
            </span>
          </div>
          <div className="mt-5"><OdemeAdimlari aktif={3} /></div>
        </div>

        {/* Teslimat ve fatura bilgileri */}
        {(s.customer_name || s.customer_phone || s.shipping_address) && (
          <div className="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="rounded-xl border border-black/10 p-4 text-[13px]">
              <p className="text-[10px] font-bold uppercase tracking-widest text-black/40">Teslimat</p>
              <p className="mt-1.5 font-semibold text-black">{s.customer_name}</p>
              {s.customer_phone && <p className="text-black/60">{s.customer_phone}</p>}
              {s.customer_email && <p className="text-black/60">{s.customer_email}</p>}
              {s.shipping_address && <p className="mt-1.5 leading-relaxed text-black/70">{s.shipping_address}</p>}
            </div>
            <div className="rounded-xl border border-black/10 p-4 text-[13px]">
              <p className="text-[10px] font-bold uppercase tracking-widest text-black/40">
                Fatura {s.fatura?.tipi === "kurumsal" ? "(Kurumsal)" : ""}
              </p>
              {s.fatura?.tipi === "kurumsal" ? (
                <>
                  <p className="mt-1.5 font-semibold text-black">{s.fatura.unvan}</p>
                  <p className="text-black/60">VD: {s.fatura.vergi_dairesi} · {s.fatura.vergi_no}</p>
                  <p className="mt-1.5 leading-relaxed text-black/70">{s.fatura.adres || s.shipping_address}</p>
                </>
              ) : (
                <p className="mt-1.5 leading-relaxed text-black/60">Bireysel fatura — teslimat adresine kesilir.</p>
              )}
            </div>
          </div>
        )}

        {iadeDurumu ? (
          <div className={`mt-8 border px-5 py-4 text-sm font-bold ${s.status === "returned" ? "border-black bg-neutral-50 text-black" : "border-amber-300 bg-amber-50 text-amber-700"}`}>
            ↩️ {iadeDurumu}{s.iade_talebi?.kismi ? " (kısmi iade)" : ""}
            {s.status === "return_requested" && (
              <p className="mt-1 text-xs font-medium text-amber-600/80">Talebiniz satıcı tarafından inceleniyor — onaylandığında ürün bedeli 3-5 iş günü içinde iade edilir.</p>
            )}
            {s.iade_talebi && (
              <div className="mt-3 border-t border-current/15 pt-3 text-xs font-medium opacity-90">
                <p>Neden: {s.iade_talebi.neden_etiketi}{s.iade_talebi.aciklama ? ` — “${s.iade_talebi.aciklama}”` : ""}</p>
                <ul className="mt-1 list-disc pl-4">
                  {s.iade_talebi.kalemler.map((kalem) => (
                    <li key={kalem.sira}>{kalem.product_name} × {kalem.quantity}</li>
                  ))}
                </ul>
                {s.iade_tutari != null && <p className="mt-1 font-bold">İade tutarı: {fmt(s.iade_tutari)} ₺</p>}
              </div>
            )}
          </div>
        ) : iptal ? (
          <div className="mt-8 border border-black/15 bg-neutral-50 px-5 py-4 text-sm font-bold text-black/60">
            Bu sipariş iptal edildi.
          </div>
        ) : (
          <div className="mt-10 flex items-start">
            {adimlar.map((adim, i) => {
              const tam = i < aktifIndex;
              const aktif = i === aktifIndex;
              return (
                <div key={adim} className="flex items-start">
                  {i > 0 && <div className={`mt-4 h-px w-10 flex-1 ${tam || aktif ? "bg-black" : "bg-black/15"} sm:w-16`} />}
                  <div className="flex w-14 flex-col items-center gap-1.5 sm:w-20">
                    <div className={`flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs font-bold ${aktif ? "border-black bg-black text-white" : tam ? "border-black bg-white text-black" : "border-black/15 bg-white text-black/30"}`}>
                      {tam ? "✓" : i + 1}
                    </div>
                    <span className={`text-center text-[10px] font-bold ${aktif ? "text-black" : tam ? "text-black/60" : "text-black/30"}`}>
                      {ADIMLAR[adim]}
                    </span>
                  </div>
                </div>
              );
            })}
          </div>
        )}

        <div className="mt-10 divide-y divide-black/10 border-y border-black/15">
          {s.items.map((kalem, i) => (
            <div key={i} className="flex items-baseline justify-between gap-4 py-4">
              <div>
                <p className="text-sm font-semibold">{kalem.product_name}</p>
                <p className="mt-0.5 text-xs text-black/40 tabular-nums">{kalem.quantity} adet × {fmt(brut(kalem.unit_price))} ₺</p>
              </div>
              <span className="text-sm font-bold tabular-nums">{fmt(brut(kalem.line_total))} ₺</span>
            </div>
          ))}
        </div>

        <div className="mt-6 space-y-1 border-t border-black/10 pt-4 text-[13px] text-black/60">
          {s.subtotal != null && <div className="flex justify-between"><span>KDV hariç tutar</span><span className="tabular-nums">{fmt(s.subtotal)} ₺</span></div>}
          {s.tax_amount != null && <div className="flex justify-between"><span>KDV (%{s.tax_rate})</span><span className="tabular-nums">{fmt(s.tax_amount)} ₺</span></div>}
          <div className="flex items-baseline justify-between pt-2">
            <span className="text-sm font-medium text-black/60">Toplam (KDV dahil)</span>
            <span className="text-3xl font-black tabular-nums text-black">{fmt(s.total)} ₺</span>
          </div>
        </div>

        <div className="mt-10 space-y-4">
          {s.status === "pending" && !iptal && (
            <div className="flex flex-col gap-3">
              {yontem === "havale" ? (
                <div className="rounded-xl border border-black/10 bg-[#f8f8f8] p-5 text-sm">
                  <p className="text-[13px] font-black uppercase tracking-wide text-black">🏦 Havale / EFT Bilgileri</p>
                  <dl className="mt-3 space-y-1.5 text-[13px]">
                    <div className="flex justify-between gap-4"><dt className="text-black/50">Hesap sahibi</dt><dd className="font-bold text-black">HepsiBirarada Ticaret A.Ş.</dd></div>
                    <div className="flex justify-between gap-4"><dt className="shrink-0 text-black/50">IBAN</dt><dd className="text-right font-mono font-bold text-black">TR33 0006 1005 1978 6457 8413 26</dd></div>
                    <div className="flex justify-between gap-4"><dt className="text-black/50">Açıklama</dt><dd className="font-mono font-bold text-black">{s.order_no}</dd></div>
                    <div className="flex justify-between gap-4"><dt className="text-black/50">Ödenecek tutar</dt><dd className="font-black text-black">{fmt(s.total)} ₺</dd></div>
                  </dl>
                  <p className="mt-3 text-xs leading-relaxed text-black/50">
                    Ödemeyi yaptıktan sonra satıcımız transferi onaylar; siparişiniz hemen ardından hazırlanmaya başlar.
                    Açıklama alanına sipariş numaranızı yazmanız onayı hızlandırır.
                  </p>
                </div>
              ) : yontem === "kapida" ? (
                <div className="border border-black/10 bg-neutral-50 px-5 py-4 text-sm">
                  <p className="font-bold text-black">🚚 Kapıda ödeme seçtiniz</p>
                  <p className="mt-1 text-[13px] leading-relaxed text-black/60">
                    Paketiniz kargoya teslim edilirken <b className="text-black">{fmt(s.total)} ₺</b> ücreti kargo görevlisine
                    nakit veya kredi kartıyla ödeyin. Siparişiniz şu an hazırlanmak üzere satıcıda.
                  </p>
                </div>
              ) : (
                <OdemeButonu orderNo={s.order_no} erisim={k ?? null} />
              )}
              <IptalButonu orderNo={s.order_no} erisim={k ?? null} />
            </div>
          )}
          {s.status !== "pending" && s.status !== "cancelled" && !iadeDurumu && (
            <div className="flex items-center gap-2 text-sm font-semibold text-black">
              <span className="h-2 w-2 rounded-full bg-emerald-500" />
              Ödendi — Fatura: {s.invoice_no ?? "—"}
            </div>
          )}
          {s.invoice_no && (
            <a href={`/api/orders/${s.order_no}/invoice-pdf${k ? `?k=${encodeURIComponent(k)}` : ""}`}
               className="inline-flex h-10 items-center justify-center rounded-full bg-black px-6 text-xs font-bold text-white transition hover:bg-black/80 active:scale-95">
              📄 Faturayı İndir (PDF)
            </a>
          )}
          {s.status === "delivered" && (
            iadeSuresiDoldu ? (
              <p className="border border-black/15 bg-neutral-50 px-4 py-3 text-xs leading-relaxed text-black/60">
                İade süresi {iadeSon && tarih(iadeSon)} tarihinde doldu. Kusurlu ürünlerde garanti haklarınız devam eder —{" "}
                <a href="/iade-degisim" className="font-bold text-black underline underline-offset-2">iade ve garanti koşulları</a>.
              </p>
            ) : (
              <div className="space-y-2">
                {iadeSon && (
                  <p className="text-xs text-black/60">
                    ↩️ İade hakkınız: <b className="text-black">{iadeKalanGun} gün</b> kaldı ({tarih(iadeSon)} tarihine kadar) ·{" "}
                    <a href="/iade-degisim" className="font-bold text-black underline underline-offset-2">İade koşulları</a>
                  </p>
                )}
                <IadeFormu orderNo={s.order_no} erisim={k ?? null} kalemler={s.items} />
              </div>
            )
          )}
          {s.tracking_no && (
            <div className="border-t border-black/15 pt-4 text-sm">
              <p className="text-xs font-bold uppercase tracking-wide text-black/40">Kargo</p>
              <p className="mt-1 font-mono font-bold">{s.tracking_no}</p>
              {s.tracking_url && (
                <a href={s.tracking_url} target="_blank" rel="noopener" className="mt-2 inline-block text-sm font-bold underline underline-offset-4">
                  Kargomu Takip Et →
                </a>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
