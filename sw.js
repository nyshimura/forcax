const CACHE_NAME = 'forca-store-v3';

self.addEventListener('install', (e) => {
    self.skipWaiting();
    e.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll([
            './',
            './index.php',
            './manifest.json',
            './icon.svg'
        ])).catch(() => {})
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

    // For API calls, never cache; return clean JSON if offline
    if (e.request.url.includes('/api/')) {
        e.respondWith(
            fetch(e.request).catch(() => new Response(
                JSON.stringify({ error: 'Sem conexão com a internet.' }),
                { status: 503, headers: { 'Content-Type': 'application/json' } }
            ))
        );
        return;
    }

    // Network-first strategy
    e.respondWith(
        fetch(e.request).then((response) => {
            if (response && response.status === 200 && response.type === 'basic') {
                const resClone = response.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(e.request, resClone);
                });
            }
            return response;
        }).catch(() => {
            return caches.match(e.request);
        })
    );
});
