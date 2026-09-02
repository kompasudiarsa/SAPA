self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open('laravel-pwa-v1').then((cache) => {
            return cache.addAll([
                '/',
                '/css/app.css',
                '/js/app.js'
            ]);
        })
    );
});

self.addEventListener('fetch', (e) => {
    e.respondWith(
        caches.match(e.request).then((response) => {
            return response || fetch(e.request);
        })
    );
});
