const CACHE_NAME = "hba-v1";
const OFFLINE_URL = "/offline.html";

// Ana sayfa + statik varlıkları önbelleğe al
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll([
        "/",
        OFFLINE_URL,
        "/manifest.json",
      ]);
    })
  );
  self.skipWaiting();
});

// Eski önbellekleri temizle
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

// Fetch: cache-first (statik), network-first (API), offline fallback
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

  // Sayfa istekleri: cache-first, network fallback, offline fallback
  if (request.mode === "navigate") {
    event.respondWith(
      caches.match(request).then((cached) => {
        return (
          cached ||
          fetch(request)
            .then((res) => {
              const copy = res.clone();
              caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
              return res;
            })
            .catch(() => caches.match(OFFLINE_URL))
        );
      })
    );
    return;
  }

  // Statik varlıklar (CSS, JS, resim): cache-first
  if (url.origin === location.origin) {
    event.respondWith(
      caches.match(request).then((cached) => {
        return (
          cached ||
          fetch(request).then((res) => {
            if (res.ok) {
              const copy = res.clone();
              caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
            }
            return res;
          })
        );
      })
    );
  }
});
