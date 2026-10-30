import { siparis } from "@/lib/backoffice";
import { OdemeButonu } from "./OdemeButonu";
import { IptalButonu } from "./IptalButonu";
import { IadeButonu } from "./IadeButonu";
import { Konfeti } from "@/components/Konfeti";

const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);
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

export default async function SiparisSayfasi({
  params,
  searchParams,
}: {
  params: Promise<{ no: string }>;
  searchParams: Promise<{ yeni?: string }>;
}) {
  const { no } = await params;
  const { yeni } = await searchParams;
  const s = await siparis(no);

  if (!s) {
    return (
      <div className="flex min-h-[40vh] items-center justify-center px-5">
        <p className="text-sm text-black/50">Sipariş bulunamadı.</p>
      </div>
    );
  }

  const adimlar = Object.keys(ADIMLAR);
  const aktifIndex = adimlar.indexOf(s.status);
  const iptal = s.status === "cancelled";
  const iadeDurumu = IADE_DURUM[s.status];

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-3xl">
        <Konfeti aktif={yeni === "1"} />
        {yeni === "1" && (
          <div className="mb-8 border-l-4 border-black bg-neutral-50 px-5 py-4">
            <p className="text-sm font-bold">Siparişiniz alındı — stoklar sizin adınıza rezerve edildi.</p>
            <p className="mt-1 text-xs text-black/50">Ödeme için 30 dakikanız var. Sipariş no: <span className="font-mono font-bold">{s.order_no}</span></p>
          </div>
        )}

        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[40px] font-black leading-none tracking-[-0.03em] sm:text-[56px]">Sipariş</h1>
          <p className="mt-2 font-mono text-sm text-black/50">{s.order_no}</p>
        </div>

        {iadeDurumu ? (
          <div className={`mt-8 border px-5 py-4 text-sm font-bold ${s.status === "returned" ? "border-black bg-neutral-50 text-black" : "border-amber-300 bg-amber-50 text-amber-700"}`}>
            ↩️ {iadeDurumu}
            {s.status === "return_requested" && (
              <p className="mt-1 text-xs font-medium text-amber-600/80">Talebiniz satıcı tarafından inceleniyor — onaylandığında ürün bedeli 14 gün içinde iade edilir.</p>
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
          {s.items.map((k, i) => (
            <div key={i} className="flex items-baseline justify-between gap-4 py-4">
              <div>
                <p className="text-sm font-semibold">{k.product_name}</p>
                <p className="mt-0.5 text-xs text-black/40 tabular-nums">{k.quantity} adet × {fmt(k.unit_price)} ₺</p>
              </div>
              <span className="text-sm font-bold tabular-nums">{fmt(k.line_total)} ₺</span>
            </div>
          ))}
        </div>

        <div className="mt-6 flex items-baseline justify-between">
          <span className="text-sm font-medium text-black/60">Toplam</span>
          <span className="text-3xl font-black tabular-nums">{fmt(s.total)} ₺</span>
        </div>

        <div className="mt-10 space-y-4">
          {s.status === "pending" && !iptal && (
            <div className="flex flex-col gap-3">
              <OdemeButonu orderNo={s.order_no} />
              <IptalButonu orderNo={s.order_no} />
            </div>
          )}
          {s.status !== "pending" && s.status !== "cancelled" && !iadeDurumu && (
            <div className="flex items-center gap-2 text-sm font-semibold text-black">
              <span className="h-2 w-2 rounded-full bg-emerald-500" />
              Ödendi — Fatura: {s.invoice_no ?? "—"}
            </div>
          )}
          {s.invoice_no && (
            <a href={`/api/orders/${s.order_no}/invoice-pdf`}
               className="inline-flex h-10 items-center justify-center rounded-full bg-black px-6 text-xs font-bold text-white transition hover:bg-black/80 active:scale-95">
              📄 Faturayı İndir (PDF)
            </a>
          )}
          {s.status === "delivered" && (
            <IadeButonu orderNo={s.order_no} />
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
