const CACHE_NAME = 'credit-ledger-v1';
const OFFLINE_URLS = [
    '/',
    '/login',
    '/admin/customers',
    '/admin',
    '/manifest.json'
];

// Install event - cache core pages
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Caching offline pages');
                return cache.addAll(OFFLINE_URLS);
            })
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    // Skip API calls - they need internet
    if (event.request.url.includes('/api/')) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                return response || fetch(event.request)
                    .then((fetchResponse) => {
                        return caches.open(CACHE_NAME)
                            .then((cache) => {
                                cache.put(event.request, fetchResponse.clone());
                                return fetchResponse;
                            });
                    });
            })
            .catch(() => {
                // Return offline page for navigation requests
                if (event.request.mode === 'navigate') {
                    return caches.match('/');
                }
            })
    );
});

// Background sync for offline credit data
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-credits') {
        event.waitUntil(syncCredits());
    }
});

async function syncCredits() {
    // Get pending credits from IndexedDB and sync to server
    console.log('Syncing offline credits...');
}