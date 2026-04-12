const CACHE_VERSION = 'v3';
const STATIC_CACHE = `ihome-static-${CACHE_VERSION}`;
const RUNTIME_CACHE = `ihome-runtime-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';

const STATIC_ASSETS = [
    '/offline.html',
    '/manifest.json',
    '/favicon.ico',
    '/images/icon-192.png',
    '/images/icon-512.png',
    '/images/maskable-icon-192.png',
    '/images/maskable-icon-512.png',
    '/images/icon-192.svg',
    '/images/icon-512.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(STATIC_CACHE)
            .then((cache) => cache.addAll(STATIC_ASSETS.map((url) => new Request(url, { cache: 'reload' }))))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key.startsWith('ihome-') && ![STATIC_CACHE, RUNTIME_CACHE].includes(key))
                    .map((key) => caches.delete(key))
            )
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin || shouldBypassRequest(url)) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirstNavigation(request));
        return;
    }

    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(request));
    }
});

function shouldBypassRequest(url) {
    return url.pathname.startsWith('/livewire') || url.pathname.startsWith('/logout');
}

function isStaticAsset(url) {
    return (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/images/') ||
        url.pathname === '/manifest.json' ||
        url.pathname === '/favicon.ico' ||
        url.pathname === OFFLINE_URL
    );
}

async function networkFirstNavigation(request) {
    try {
        return await fetch(request);
    } catch (error) {
        return (await caches.match(OFFLINE_URL)) || new Response('غير متصل', { status: 503 });
    }
}

async function cacheFirst(request) {
    const cached = await caches.match(request);

    if (cached) {
        return cached;
    }

    try {
        const response = await fetch(request);

        if (response.ok) {
            const cache = await caches.open(RUNTIME_CACHE);
            await cache.put(request, response.clone());
        }

        return response;
    } catch (error) {
        return new Response('', { status: 503 });
    }
}
