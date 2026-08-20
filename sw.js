const CACHE_NAME = 'mknet-pwa-v1';
const ASSETS_TO_CACHE = [
  '/mknet/index.php',
  "/pages/packages.php",
  "/pages/paybill.php",
  "/pages/coverage.php",
  "/pages/network_status.php"
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(ASSETS_TO_CACHE).catch(() => {});
    })
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(response => {
      return response || fetch(event.request);
    }).catch(() => fetch(event.request))
  );
});
