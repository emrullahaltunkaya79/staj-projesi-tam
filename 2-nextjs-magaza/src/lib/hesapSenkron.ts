/**
 * Favori/sepet hesap senkronu: tarayıcı listesi ile hesaptaki liste birleştirilir.
 * E-posta doğrulanmamışsa sunucu listesi yüklenmez (sipariş eşleşmesi e-postayla olduğu için
 * doğrulanmamış hesaplar sahiplik iddia edemez) — yalnız yerel liste kullanılır.
 */

export async function oturumlaSenkron<T extends { id: number }>(
  alan: "favoriler" | "sepet",
  yerel: T[],
  yaz: (liste: T[]) => void,
  _kullanici: unknown,
  emailDogrulanmis: boolean | null,
): Promise<void> {
  if (emailDogrulanmis === false) return; // doğrulanmamış hesap: yalnız yerel liste

  try {
    const yanit = await fetch("/api/hesap/durum");
    if (!yanit.ok) return;
    const sonuc = await yanit.json();
    const sunucu: T[] = sonuc.data?.[alan] ?? [];

    const harita = new Map<number, T>();
    for (const oge of [...sunucu, ...yerel]) harita.set(oge.id, oge);
    const birlesik = [...harita.values()];

    // Sıralama: sunucudaki sıra önce, sonra tarayıcıdaki yeniler
    birlesik.sort((a, b) => {
      const ai = sunucu.findIndex((s) => s.id === a.id);
      const bi = sunucu.findIndex((s) => s.id === b.id);
      if (ai !== -1 && bi !== -1) return ai - bi;
      if (ai !== -1) return -1;
      if (bi !== -1) return 1;
      return yerel.findIndex((s) => s.id === a.id) - yerel.findIndex((s) => s.id === b.id);
    });

    if (JSON.stringify(birlesik) !== JSON.stringify(yerel)) {
      yaz(birlesik);
      await fetch("/api/hesap/durum", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ [alan]: birlesik }),
      }).catch(() => {});
    }
  } catch {}
}
