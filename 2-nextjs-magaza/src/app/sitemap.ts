import type { MetadataRoute } from "next";
import { urunler } from "@/lib/backoffice";

const BASE_URL = "http://127.0.0.1:3001";

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const statikSayfalar = [
    { url: "/", priority: 1, changeFrequency: "daily" as const },
    { url: "/flas-indirimler", priority: 0.9, changeFrequency: "daily" as const },
    { url: "/outlet", priority: 0.8, changeFrequency: "daily" as const },
    { url: "/cok-satanlar", priority: 0.8, changeFrequency: "daily" as const },
    { url: "/son-kalan", priority: 0.6, changeFrequency: "daily" as const },
    { url: "/son-firsat", priority: 0.7, changeFrequency: "daily" as const },
    { url: "/sezon", priority: 0.7, changeFrequency: "weekly" as const },
    { url: "/hakkinda", priority: 0.5, changeFrequency: "monthly" as const },
    { url: "/iletisim", priority: 0.5, changeFrequency: "monthly" as const },
    { url: "/sss", priority: 0.6, changeFrequency: "monthly" as const },
    { url: "/favoriler", priority: 0.3, changeFrequency: "weekly" as const },
    { url: "/siparislerim", priority: 0.4, changeFrequency: "daily" as const },
    { url: "/sepet", priority: 0.4, changeFrequency: "weekly" as const },
  ];

  const kategoriler = [
    "Bilgisayar", "Elektronik", "Kırtasiye", "Ofis",
    "Ev & Yaşam", "Oyun & Eğlence", "Spor & Outdoor", "Kişisel Bakım", "Müzik",
  ].map((k) => ({
    url: `${BASE_URL}/kategori/${encodeURIComponent(k)}`,
    priority: 0.8,
    changeFrequency: "daily" as const,
  }));

  // En çok ürünü olan 30 markanın sayfası
  let markaSayfalari: MetadataRoute.Sitemap = [];
  try {
    const tumu = await urunler();
    const sayilar = tumu.reduce<Record<string, number>>((m, u) => {
      if (u.brand) m[u.brand] = (m[u.brand] ?? 0) + 1;
      return m;
    }, {});
    markaSayfalari = Object.entries(sayilar)
      .sort((a, b) => b[1] - a[1])
      .slice(0, 30)
      .map(([marka]) => ({
        url: `${BASE_URL}/marka/${encodeURIComponent(marka)}`,
        priority: 0.6,
        changeFrequency: "weekly" as const,
      }));
  } catch {}

  return [...statikSayfalar, ...kategoriler, ...markaSayfalari].map((s) => ({
    url: s.url.startsWith("http") ? s.url : `${BASE_URL}${s.url}`,
    priority: s.priority,
    changeFrequency: s.changeFrequency,
    lastModified: new Date(),
  }));
}
