const CACHE_NAME = "hba-v4";
const OFFLINE_URL = "/offline.html";

// Kurulumda yalnızca küçük, değişmeyen varlıklar önbelleğe alınır.
// "/" önbelleğe ALINMAZ — sayfa içeriği değişken, network-first servis edilir.
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll([OFFLINE_URL, "/manifest.json"]))
  );
  self.skipWaiting();
});

// Eski önbellekleri (hba-v1'deki dev HTML dahil) temizle
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((names) => {
      return Promise.all(
        names.filter((n) => n !== CACHE_NAME).map((n) => caches.delete(n))
      );
    })
  );
  self.clients.claim();
});

// Fetch: statik cache-first, sayfa ve API network-first, offline fallback
self.addEventListener("fetch", (event) => {
  const { request } = event;

  // Sadece GET istekleri
  if (request.method !== "GET") return;

  const url = new URL(request.url);

  // API istekleri: network-first, cache fallback
  if (url.pathname.startsWith("/api/")) {
    event.respondWith(
      fetch(request)
        .then((res) => {
          const copy = res.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          return res;
        })
        .catch(() => caches.match(request))
    );
    return;
  }

  // Sayfa istekleri: network-first (her zaman taze HTML), offline'da cache → offline.html
  if (request.mode === "navigate") {
    event.respondWith(
      fetch(request)
        .then((res) => {
          const copy = res.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          return res;
        })
        .catch(() =>
          caches.match(request).then((cached) => cached || caches.match(OFFLINE_URL))
        )
    );
    return;
  }

  // Statik varlıklar: stale-while-revalidate (anında önbellek, arka planda güncelleme —
  // dev'de hash'siz chunk adları değiştiğinde bir sonraki yüklemede taze sürüm gelir)
  if (url.origin === location.origin) {
    event.respondWith(
      caches.match(request).then((cached) => {
        const agdan = fetch(request).then((res) => {
          if (res.ok) {
            const copy = res.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          }
          return res;
        }).catch(() => cached);
        return cached || agdan;
      })
    );
  }
});
