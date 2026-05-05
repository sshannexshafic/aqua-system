// Service Worker for Offline Support
const CACHE_NAME = 'aqua-farm-v1';

// Files to cache for offline access
const urlsToCache = [
    '/',
    '/public/index.php',
    '/public/login.php',
    '/assets/css/style.css',
    '/assets/css/dashboard.css',
    '/assets/js/main.js',
    '/assets/js/offline.js',
    '/public/offline.html'
];

// Role-specific pages to cache
const farmerPages = [
    '/farmer/dashboard.php',
    '/farmer/monitoring/realtime.php',
    '/farmer/fish_health/health_tracker.php'
];

const adminPages = [
    '/admin/dashboard.php',
    '/admin/marketplace/manage_listings.php'
];

// Install event - cache files
self.addEventListener('install', event => {
    console.log('Service Worker installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Caching app shell');
                return cache.addAll(urlsToCache);
            })
            .then(() => {
                console.log('Cache added successfully');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('Cache failed:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('Service Worker activating...');
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            console.log('Service Worker activated');
            return self.clients.claim();
        })
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
    console.log('Fetching:', event.request.url);
    
    // Skip non-GET requests and API calls
    if (event.request.method !== 'GET' || event.request.url.includes('/api/')) {
        event.respondWith(fetch(event.request));
        return;
    }
    
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Cache hit - return response
                if (response) {
                    console.log('Serving from cache:', event.request.url);
                    return response;
                }
                
                // Clone the request
                const fetchRequest = event.request.clone();
                
                // Make network request
                return fetch(fetchRequest).then(response => {
                    // Check if valid response
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }
                    
                    // Clone the response
                    const responseToCache = response.clone();
                    
                    // Cache the new response
                    caches.open(CACHE_NAME)
                        .then(cache => {
                            cache.put(event.request, responseToCache);
                            console.log('Cached new resource:', event.request.url);
                        });
                    
                    return response;
                }).catch(error => {
                    console.log('Network request failed, serving offline page');
                    // If both cache and network fail, show offline page
                    if (event.request.url.includes('.php') && !event.request.url.includes('login')) {
                        return caches.match('/public/offline.html');
                    }
                    return new Response('You are offline. Please check your connection.');
                });
            })
    );
});

// Background sync for offline data
self.addEventListener('sync', event => {
    console.log('Background sync event:', event.tag);
    if (event.tag === 'sync-farm-data') {
        event.waitUntil(syncFarmData());
    }
});

function syncFarmData() {
    return fetch('/api/sync.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'sync',
            timestamp: new Date().toISOString()
        })
    }).then(response => response.json())
      .then(data => {
          console.log('Sync completed:', data);
      });
}

// Push notifications
self.addEventListener('push', event => {
    console.log('Push notification received');
    const options = {
        body: event.data.text(),
        icon: '/assets/images/icon-192x192.png',
        badge: '/assets/images/badge.png',
        vibrate: [200, 100, 200],
        actions: [
            { action: 'view', title: 'View Details' },
            { action: 'dismiss', title: 'Dismiss' }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification('Aquaculture Alert', options)
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
    console.log('Notification clicked:', event.action);
    event.notification.close();
    
    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});