import { siparis } from "@/lib/backoffice";
import { CsprPanel } from "./CsprPanel";

export default async function CsprOdemeSayfasi({ params }: { params: Promise<{ no: string }> }) {
  const { no } = await params;
  const s = await siparis(no);

  if (!s) {
    return <div className="py-20 text-center text-sm text-black/50">Sipariş bulunamadı.</div>;
  }

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-lg">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[40px] font-black leading-none tracking-[-0.03em] sm:text-[56px]">CSPR ile Öde</h1>
          <p className="mt-2 font-mono text-sm text-black/50">{s.order_no} · {new Intl.NumberFormat("tr-TR").format(s.total)} ₺</p>
        </div>
        <CsprPanel orderNo={s.order_no} />
      </div>
    </div>
  );
}
