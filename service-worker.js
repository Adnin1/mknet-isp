// MK Net service worker - caches static assets so the site's shell loads
// even with no connection, and shows a friendly offline page for pages
// that need a live network request (e.g. checkout, dashboard data).
const CACHE_NAME = 'mknet-v1';
const OFFLINE_URL = '/mknet/offline.html';

const PRECACHE_URLS = [
  OFFLINE_URL,
  "/assets/style.css",
  "/assets/images/logo.png",
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS)).catch(() => {})
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  // Only handle same-origin GET navigation/asset requests; never intercept
  // POST (forms, checkout, payments must always hit the real network).
  if (event.request.method !== 'GET') return;

  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).catch(() => caches.match(OFFLINE_URL))
    );
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cached) => {
      return cached || fetch(event.request).then((response) => {
        // Cache successful static asset responses for next time.
        if (response.ok && event.request.url.match(/\.(css|js|png|jpg|jpeg|svg|woff2?)$/)) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
        }
        return response;
      }).catch(() => cached);
    })
  );
});
