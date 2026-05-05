/**
 * Minimal service worker — enables install / “Add to Home Screen” where required.
 * Network-first fetch (no offline shell). Extend with caches when needed.
 */
self.addEventListener('install', function (event) {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', function (event) {
  event.respondWith(fetch(event.request));
});
