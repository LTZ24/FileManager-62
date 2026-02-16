// Bump this when deploying asset changes to avoid stale caches.
const CACHE_VERSION = 'v3.0.2';
const CACHE_NAME = `filemanager-62-${CACHE_VERSION}`;

// Auto-detect base path from SW location.
// Hosting (domain root): '/'   —   Localhost (subfolder): '/filemanager.smkn62.sch.id/'
const BASE_PATH = new URL('./', self.location).pathname;

const OFFLINE_URL = BASE_PATH + 'offline.html';

const STATIC_CACHE_URLS = [
    'offline.html',
    'manifest.json',
    'assets/css/style.css',
    'assets/css/ajax.css',
    'assets/js/main.js',
    'assets/js/ajax.js',
    'assets/js/upload-manager.js',
    'assets/js/table-pagination.js',
    'assets/js/session-keepalive.js',
    'assets/js/pwa.js',
    'assets/images/smk62.png',
    'assets/images/icons/favicon-16x16.png',
    'assets/images/icons/favicon-32x32.png',
    'assets/images/icons/apple-touch-icon.png',
    'assets/images/icons/icon-72x72.png',
    'assets/images/icons/icon-96x96.png',
    'assets/images/icons/icon-128x128.png',
    'assets/images/icons/icon-144x144.png',
    'assets/images/icons/icon-152x152.png',
    'assets/images/icons/icon-192x192.png',
    'assets/images/icons/icon-384x384.png',
    'assets/images/icons/icon-512x512.png',
    'assets/images/icons/icon-192x192-maskable.png',
    'assets/images/icons/icon-512x512-maskable.png'
].map(p => BASE_PATH + p);

// CDN assets cached separately (may fail due to CORS/network)
const CDN_CACHE_URLS = [
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
];

const DYNAMIC_CACHE_URLS = [
    'pages/files/',
    'pages/files/upload',
    'pages/links/',
    'pages/forms/',
    'pages/settings',
    'pages/category/kesiswaan',
    'pages/category/kurikulum',
    'pages/category/sapras-humas',
    'pages/category/tata-usaha'
].map(p => BASE_PATH + p);

self.addEventListener('install', (event) => {
    console.log('[SW] Installing service worker...', CACHE_VERSION);
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(async (cache) => {
                console.log('[SW] Caching static assets');
                // Cache each URL individually so one failure doesn't block all
                const results = await Promise.allSettled(
                    STATIC_CACHE_URLS.map(url =>
                        cache.add(url).catch(err => {
                            console.warn('[SW] Failed to cache:', url, err.message);
                        })
                    )
                );
                // CDN assets: best-effort, don't block install
                await Promise.allSettled(
                    CDN_CACHE_URLS.map(url =>
                        cache.add(url).catch(err => {
                            console.warn('[SW] CDN cache skipped:', url, err.message);
                        })
                    )
                );
                console.log('[SW] Static assets cached');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('[SW] Cache open failed:', error);
            })
    );
});

self.addEventListener('activate', (event) => {
    console.log('[SW] Activating service worker...', CACHE_VERSION);
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('[SW] Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('[SW] Service worker activated');
                return self.clients.claim();
            })
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Don't handle non-http(s) requests (e.g. chrome-extension://)
    if (url.protocol !== 'http:' && url.protocol !== 'https:') {
        return;
    }

    // Only handle same-origin requests
    if (url.origin !== location.origin) {
        return;
    }
    
    if (request.method !== 'GET') {
        return;
    }

    // Strip BASE_PATH to get the app-relative path for routing decisions
    const pathname = url.pathname;
    const rel = pathname.startsWith(BASE_PATH)
        ? pathname.slice(BASE_PATH.length)
        : pathname;
    
    if (rel.startsWith('auth/')) {
        return;
    }
    
    if (rel.startsWith('includes/api') || rel.startsWith('api/')) {
        event.respondWith(networkFirst(request));
        return;
    }

    // Critical assets should prefer network so UI/JS updates take effect.
    const criticalAssets = new Set([
        'assets/js/main.js',
        'assets/js/ajax.js',
        'assets/js/pwa.js',
        'assets/js/upload-manager.js',
        'assets/js/table-pagination.js',
        'assets/css/style.css',
        'assets/css/ajax.css'
    ]);

    if (criticalAssets.has(rel)) {
        event.respondWith(networkFirst(request));
        return;
    }
    
    // Pages now use clean URLs (no .php) via .htaccess rewrite.
    if (rel.endsWith('.php') || rel.startsWith('pages/')) {
        event.respondWith(networkFirst(request));
        return;
    }
    
    if (pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)$/)) {
        event.respondWith(cacheFirst(request));
        return;
    }
    
    event.respondWith(networkFirst(request));
});

async function cacheFirst(request) {
    try {
        const reqUrl = new URL(request.url);
        if (reqUrl.protocol !== 'http:' && reqUrl.protocol !== 'https:') {
            return fetch(request);
        }

        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            console.log('[SW] Cache hit:', request.url);
            return cachedResponse;
        }
        
        console.log('[SW] Cache miss, fetching:', request.url);
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            // Cache API doesn't support non-http(s) schemes
            if (reqUrl.origin === location.origin) {
                await cache.put(request, networkResponse.clone());
            }
        }
        
        return networkResponse;
        
    } catch (error) {
        console.error('[SW] Cache-first failed:', error);
        
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        return await caches.match(OFFLINE_URL);
    }
}

async function networkFirst(request) {
    try {
        const reqUrl = new URL(request.url);
        if (reqUrl.protocol !== 'http:' && reqUrl.protocol !== 'https:') {
            return fetch(request);
        }

        console.log('[SW] Network first:', request.url);
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            if (reqUrl.origin === location.origin) {
                await cache.put(request, networkResponse.clone());
            }
        }
        
        return networkResponse;
        
    } catch (error) {
        console.log('[SW] Network failed, trying cache:', request.url);
        
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            console.log('[SW] Serving from cache:', request.url);
            return cachedResponse;
        }
        
        console.log('[SW] No cache available, serving offline page');
        return await caches.match(OFFLINE_URL);
    }
}

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        console.log('[SW] Received SKIP_WAITING message');
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CACHE_URLS') {
        console.log('[SW] Received CACHE_URLS message');
        event.waitUntil(
            caches.open(CACHE_NAME)
                .then((cache) => {
                    return cache.addAll(event.data.urls);
                })
        );
    }
    
    if (event.data && event.data.type === 'CLEAR_CACHE') {
        console.log('[SW] Received CLEAR_CACHE message');
        event.waitUntil(
            caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => caches.delete(cacheName))
                );
            })
        );
    }
});

self.addEventListener('sync', (event) => {
    console.log('[SW] Background sync:', event.tag);
    
    if (event.tag === 'sync-data') {
        event.waitUntil(syncData());
    }
});

async function syncData() {
    try {
        console.log('[SW] Syncing data...');
        
    } catch (error) {
        console.error('[SW] Sync failed:', error);
    }
}

self.addEventListener('push', (event) => {
    console.log('[SW] Push notification received');
    
    const options = {
        body: event.data ? event.data.text() : 'Notifikasi baru dari FileManager SMKN62',
        icon: BASE_PATH + 'assets/images/icons/icon-192x192.png',
        badge: BASE_PATH + 'assets/images/icons/icon-72x72.png',
        vibrate: [200, 100, 200],
        tag: 'db-guru-notification',
        requireInteraction: false
    };
    
    event.waitUntil(
        self.registration.showNotification('FileManager SMKN62', options)
    );
});

self.addEventListener('notificationclick', (event) => {
    console.log('[SW] Notification clicked');
    
    event.notification.close();
    
    event.waitUntil(
        clients.openWindow(BASE_PATH)
    );
});
