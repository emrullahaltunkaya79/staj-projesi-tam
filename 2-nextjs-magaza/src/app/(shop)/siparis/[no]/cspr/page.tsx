import Link from "next/link";
import { siparisErisimi } from "@/lib/oturum";
import { CsprPanel } from "./CsprPanel";

export const metadata = { title: "CSPR ile Öde — HepsiBirarada", referrer: "no-referrer" as const };

export default async function CsprOdemeSayfasi({
  params,
  searchParams,
}: {
  params: Promise<{ no: string }>;
  searchParams: Promise<{ k?: string }>;
}) {
  const { no } = await params;
  const { k } = await searchParams;
  const { siparis: s, izinli } = await siparisErisimi(no, k ?? null);

  if (!s || !izinli) {
    return (
      <div className="py-20 text-center">
        <p className="text-sm text-black/50">{s ? "Bu siparişin ödeme sayfasına erişim yetkiniz yok." : "Sipariş bulunamadı."}</p>
        <Link href="/siparis-sorgula" className="mt-3 inline-block text-sm font-bold underline underline-offset-4">Sipariş sorgula →</Link>
      </div>
    );
  }

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-lg">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[15px] font-black uppercase tracking-wide text-black sm:text-[17px]">CSPR ile Öde</h1>
          <p className="mt-2 font-mono text-sm text-black/50">{s.order_no} · {new Intl.NumberFormat("tr-TR").format(s.total)} ₺</p>
        </div>
        <CsprPanel orderNo={s.order_no} erisim={k ?? null} />
      </div>
    </div>
  );
}
