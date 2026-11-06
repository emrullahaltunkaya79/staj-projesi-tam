import { NextResponse, type NextRequest } from "next/server";
import { OTURUM_CEREZI, tokenKullanicisi, saticiMi } from "@/lib/kimlik";

/**
 * Satıcı paneli (sayfalar + API'ler) mağazanın ana token'ıyla arka ofise gider; çağıran kişi burada
 * doğrulanır: yalnızca satıcı (yönetici/personel) oturumu geçebilir. Sayfalar sunucuda render edildiği
 * için kontrol tarayıcıya bırakılamaz — aksi halde veriler HTML'e gömülü gider.
 */
export async function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // İletişim formu herkese açık; mesaj listesi satıcıya özel
  if (pathname === "/api/iletisim" && request.method === "POST") {
    return NextResponse.next();
  }

  const kullanici = await tokenKullanicisi(request.cookies.get(OTURUM_CEREZI)?.value);
  if (saticiMi(kullanici)) {
    return NextResponse.next();
  }

  if (pathname.startsWith("/satici")) {
    return NextResponse.rewrite(new URL("/satici-giris", request.url));
  }

  return NextResponse.json({ hata: "Bu işlem için satıcı girişi gerekli." }, { status: 401 });
}

export const config = {
  matcher: [
    "/satici/:path*",
    "/api/satici/:path*",
    "/api/stats/:path*",
    "/api/kuponlar/:path*",
    "/api/yorumlar/:path*",
    "/api/sorular/:path*",
    "/api/bildirimler",
    "/api/iletisim",
  ],
};
