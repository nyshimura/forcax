const CACHE_NAME = 'forca-store-v2';

self.addEventListener('install', (e) => {
    self.skipWaiting();
    e.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll([
            '/forca/',
            '/forca/index.php',
            '/forca/manifest.json',
            '/forca/icon.svg',
            'https://cdn.tailwindcss.com',
            'https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js'
        ])),
    );
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keyList) => {
            return Promise.all(keyList.map((key) => {
                if (key !== CACHE_NAME) {
                    return caches.delete(key);
                }
            }));
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (e) => {
    if (e.request.method !== 'GET') return;
    
    // For API calls, don't cache at all
    if (e.request.url.includes('/api/')) {
        e.respondWith(fetch(e.request));
        return;
    }

    // Network-first strategy
    e.respondWith(
        fetch(e.request).then((response) => {
            const resClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
                cache.put(e.request, resClone);
            });
            return response;
        }).catch(() => {
            return caches.match(e.request);
        })
    );
});
