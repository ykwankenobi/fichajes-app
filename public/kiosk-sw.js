const kioskCacheName = 'work-time-kiosk-v1';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

// Kiosk pages contain personal attendance data, so they are always requested
// from the server and never kept for offline use.
self.addEventListener('fetch', (event) => {
    event.respondWith(fetch(event.request));
});
