const CACHE_NAME = 'academic-hub-v1';
const OFFLINE_URL = '/offline.html';

const STATIC_ASSETS = [
    OFFLINE_URL,
    '/manifest.json',
    '/favicon-32x32.png',
    '/android-chrome-192x192.png',
    '/android-chrome-512x512.png',
    '/apple-touch-icon.png'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (name !== CACHE_NAME) {
                        return caches.delete(name);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Hanya tangani metode GET
    if (request.method !== 'GET') {
        return;
    }

    // Untuk navigasi halaman (HTML): Network first, fallback ke offline.html
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(async () => {
                const cache = await caches.open(CACHE_NAME);
                const cachedOffline = await cache.match(OFFLINE_URL);
                return cachedOffline || Response.error();
            })
        );
        return;
    }

    // Untuk static assets (gambar, favicon, manifest): Cache first, fallback network
    if (
        request.destination === 'image' ||
        request.url.includes('/android-chrome') ||
        request.url.includes('/favicon') ||
        request.url.includes('/manifest.json')
    ) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then((response) => {
                    if (response.status === 200) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, responseClone));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Default: fetch jaringan langsung
    event.respondWith(fetch(request).catch(() => caches.match(request)));
});
