import Link from "next/link";
import { SiparisSorgulaFormu } from "@/components/SiparisSorgulaFormu";

export const metadata = { title: "Sipariş Sorgula — HepsiBirarada", robots: { index: false } };

export default async function SiparisSorgulaSayfasi({ searchParams }: { searchParams: Promise<{ no?: string }> }) {
  const { no } = await searchParams;

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[15px] font-black uppercase tracking-wide text-black sm:text-[17px]">Sipariş Sorgula</h1>
          <p className="mt-2 text-sm text-black/50">
            Üye olmadan verdiğiniz siparişi, sipariş numarası ve siparişte kullandığınız e-posta ile görüntüleyin.
          </p>
        </div>
        <div className="mt-8">
          <SiparisSorgulaFormu varsayilanNo={(no ?? "").toUpperCase()} />
        </div>
        <p className="mt-6 text-center text-[12px] text-black/45">
          Hesabınız var mı? <Link href="/siparislerim" className="font-bold text-black underline underline-offset-2">Siparişlerim</Link> sayfasından tüm siparişlerinizi görün.
        </p>
      </div>
    </div>
  );
}
