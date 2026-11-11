"use client";

import { useEffect } from "react";

/** Beklenmeyen çalışma zamanı hatası sınırı — kullanıcıya dostça yeniden deneme fırsatı verir. */
export default function HataSiniri({ error, reset }: { error: Error & { digest?: string }; reset: () => void }) {
  useEffect(() => {
    // Konsolda teşhis için kaydet (üretimde izleme servisine iletilebilir)
    console.error(error);
  }, [error]);

  return (
    <div className="flex min-h-[60vh] items-center justify-center px-5">
      <div className="max-w-md text-center">
        <p className="text-4xl">😵</p>
        <h1 className="mt-4 text-[15px] font-black uppercase tracking-wide text-black sm:text-[17px]">
          Bir şeyler ters gitti
        </h1>
        <p className="mt-2 text-sm leading-relaxed text-black/55">
          Sayfa yüklenirken beklenmeyen bir hata oluştu. Bağlantınızı kontrol edip tekrar deneyin;
          sorun sürerse sepetteki ürünleriniz kaybolmadan ana sayfaya dönebilirsiniz.
        </p>
        {error.digest && <p className="mt-3 font-mono text-[11px] text-black/30">Hata kodu: {error.digest}</p>}
        <div className="mt-6 flex flex-wrap justify-center gap-3">
          <button type="button" onClick={reset}
                  className="flex h-11 items-center rounded-full bg-black px-6 text-sm font-bold text-white transition hover:bg-black/80">
            Tekrar dene
          </button>
          <a href="/"
             className="flex h-11 items-center rounded-full border border-black/15 px-6 text-sm font-bold text-black/70 transition hover:border-black hover:text-black">
            Ana sayfa
          </a>
        </div>
      </div>
    </div>
  );
}
