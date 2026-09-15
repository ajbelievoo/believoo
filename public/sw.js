const CACHE_NAME = 'believoo-v1';
const PRECACHE_ASSETS = [
    '/',
    '/images/icon-192x192.png',
    '/images/icon-512x512.png',
    '/css/app.css',
    '/js/app.js',
];

const OFFLINE_PAGE = '/offline';

// Install: pre-cache shell assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_ASSETS))
            .then(() => self.skipWaiting())
            .catch(() => self.skipWaiting())
    );
});

// Activate: clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

// Fetch: stale-while-revalidate for navigations, network-first for dynamic requests
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests and external origins
    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Navigation requests: network-first with offline fallback
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    return response;
                })
                .catch(() =>
                    caches.match(request).then((cached) =>
                        cached || caches.match(OFFLINE_PAGE) || new Response('Offline', {
                            status: 503,
                            headers: { 'Content-Type': 'text/plain' },
                        })
                    )
                )
        );
        return;
    }

    // Static assets: stale-while-revalidate
    if (url.pathname.match(/\.(css|js|png|jpg|jpeg|webp|svg|ico|woff|woff2|ttf|eot)$/)) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const fetchPromise = fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const clone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    }
                    return networkResponse;
                }).catch(() => cached);

                return cached || fetchPromise;
            })
        );
        return;
    }

    // API / dynamic: network only
    event.respondWith(fetch(request).catch(() => caches.match(request)));
});
