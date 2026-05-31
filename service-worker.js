// ============================================================
// AceICT — Service Worker (Phase 1: App Shell Caching)
// Strategy:
//   App shell (HTML)  → Cache-First  (loads instantly offline)
//   API calls         → Network-First (fresh data, cache fallback)
//   Google Fonts      → Cache-First  (load once, keep forever)
// ============================================================

const SHELL_CACHE = 'aceict-shell-v1';
const API_CACHE   = 'aceict-api-v1';
const FONT_CACHE  = 'aceict-fonts-v1';

const SHELL_ASSETS = [
  './',
  './index.html',
];

// ── Install: cache the app shell immediately ─────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(SHELL_CACHE)
      .then(cache => cache.addAll(SHELL_ASSETS))
      .then(() => self.skipWaiting())   // activate immediately, no waiting
  );
});

// ── Activate: delete caches from previous versions ───────────
self.addEventListener('activate', event => {
  const current = new Set([SHELL_CACHE, API_CACHE, FONT_CACHE]);
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(
        keys.filter(k => !current.has(k)).map(k => caches.delete(k))
      ))
      .then(() => self.clients.claim())   // take control of all open tabs
  );
});

// ── Fetch: route each request to the right strategy ──────────
self.addEventListener('fetch', event => {
  const req = event.request;
  const url = new URL(req.url);

  // Only handle GET — let POST/PATCH/DELETE pass through untouched
  if (req.method !== 'GET') return;

  // Google Fonts (CSS + actual font files): Cache-First
  if (url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') {
    event.respondWith(cacheFirst(req, FONT_CACHE));
    return;
  }

  // API calls (/api/v1/...): Network-First with offline JSON fallback
  if (url.pathname.includes('/api/')) {
    event.respondWith(networkFirst(req, API_CACHE));
    return;
  }

  // Everything else (app shell, index.html): Cache-First
  event.respondWith(cacheFirst(req, SHELL_CACHE));
});

// ── Cache-First strategy ─────────────────────────────────────
// Serves from cache instantly; updates cache in background on success.
async function cacheFirst(req, cacheName) {
  const cached = await caches.match(req);
  if (cached) {
    // Refresh cache in background without blocking the response
    fetch(req).then(fresh => {
      if (fresh && fresh.ok) {
        caches.open(cacheName).then(c => c.put(req, fresh));
      }
    }).catch(() => {});
    return cached;
  }
  // Not cached yet — fetch, cache, return
  try {
    const fresh = await fetch(req);
    if (fresh && fresh.ok) {
      const cache = await caches.open(cacheName);
      cache.put(req, fresh.clone());
    }
    return fresh;
  } catch {
    // Truly offline and not cached — return the app shell so the app still boots
    return caches.match('./index.html');
  }
}

// ── Network-First strategy ───────────────────────────────────
// Tries network; on failure serves cached response or an offline JSON stub.
async function networkFirst(req, cacheName) {
  try {
    const fresh = await fetch(req);
    if (fresh && fresh.ok) {
      const cache = await caches.open(cacheName);
      cache.put(req, fresh.clone());
    }
    return fresh;
  } catch {
    const cached = await caches.match(req);
    if (cached) return cached;
    // Return a structured offline response the app can detect and handle
    return new Response(
      JSON.stringify({
        success: false,
        error:   'You are offline. Data will load when you reconnect.',
        offline: true,
      }),
      {
        status:  503,
        headers: { 'Content-Type': 'application/json' },
      }
    );
  }
}

// ── Background sync message from the app ─────────────────────
// Lets the app tell the SW to clear the API cache after a write
// so the next read gets fresh data.
self.addEventListener('message', event => {
  if (event.data?.type === 'CLEAR_API_CACHE') {
    caches.delete(API_CACHE).then(() => {
      caches.open(API_CACHE); // re-create empty
    });
  }
  if (event.data?.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
