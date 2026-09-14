const CACHE = 'bconnect-v1';
const ASSETS = [
    '/',
    '/build/assets/app-CRfNc6w3.js',
    '/build/assets/app-v7Zw7Qz3.css',
    '/build/assets/lottie-DlXGThtN.js',
];

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then(c => c.addAll(ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
    e.waitUntil(self.clients.claim());
});

self.addEventListener('push', (e) => {
    const data = e.data.json();
    e.waitUntil(self.registration.showNotification(data.title || 'B-CONNECT', {
        body: data.body,
        icon: data.icon || '/favicon.ico',
        badge: data.badge || '/favicon.ico',
        data: data,
        actions: data.actions || []
    }));
});

self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    e.waitUntil(clients.openWindow(e.notification.data.url || '/'));
});
