// ============================================================
// AceICT — Service Worker v2
// Strategies:
//   App shell (HTML, manifest) → Cache-First (loads instantly offline)
//   API calls                  → Network-First with 6 s timeout
//   Google Fonts               → Cache-First (load once, keep forever)
//   Background Sync            → Relays drain-queue tag to open clients
// ============================================================

const SHELL_CACHE = 'aceict-shell-v2';
const API_CACHE   = 'aceict-api-v2';
const FONT_CACHE  = 'aceict-fonts-v1';   // fonts rarely change — keep forever

const SHELL_ASSETS = [
  './',
  './index.html',
  './manifest.json',
];

// ── Install: cache the app shell ─────────────────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(SHELL_CACHE)
      .then(cache => cache.addAll(SHELL_ASSETS))
      .then(() => self.skipWaiting())
  );
});

// ── Activate: remove stale caches ────────────────────────────
self.addEventListener('activate', event => {
  const keep = new Set([SHELL_CACHE, API_CACHE, FONT_CACHE]);
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(k => !keep.has(k)).map(k => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

// ── Fetch: route requests ─────────────────────────────────────
self.addEventListener('fetch', event => {
  const req = event.request;
  const url = new URL(req.url);

  // Only intercept GET
  if (req.method !== 'GET') return;

  // Google Fonts — Cache-First (virtually never changes)
  if (url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') {
    event.respondWith(cacheFirst(req, FONT_CACHE));
    return;
  }

  // API calls — Network-First with 6 s timeout, IndexedDB fallback handled by app
  if (url.pathname.includes('/api/')) {
    event.respondWith(networkFirst(req, API_CACHE, 6000));
    return;
  }

  // Navigation requests — Cache-First (app shell)
  if (req.mode === 'navigate') {
    event.respondWith(cacheFirst(req, SHELL_CACHE));
    return;
  }

  // Everything else — Cache-First
  event.respondWith(cacheFirst(req, SHELL_CACHE));
});

// ── Cache-First ───────────────────────────────────────────────
async function cacheFirst(req, cacheName) {
  const cached = await caches.match(req);
  if (cached) {
    // Background refresh without blocking response
    fetch(req).then(fresh => {
      if (fresh?.ok) caches.open(cacheName).then(c => c.put(req, fresh));
    }).catch(() => {});
    return cached;
  }
  try {
    const fresh = await fetch(req);
    if (fresh?.ok) {
      const cache = await caches.open(cacheName);
      cache.put(req, fresh.clone());
    }
    return fresh;
  } catch {
    // Offline + not cached — return app shell so app can boot
    const shell = await caches.match('./index.html');
    return shell || new Response('Offline', { status: 503 });
  }
}

// ── Network-First with timeout ────────────────────────────────
async function networkFirst(req, cacheName, timeoutMs = 6000) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);
  try {
    const fresh = await fetch(req, { signal: controller.signal });
    clearTimeout(timer);
    if (fresh?.ok) {
      const cache = await caches.open(cacheName);
      cache.put(req, fresh.clone());
    }
    return fresh;
  } catch {
    clearTimeout(timer);
    const cached = await caches.match(req);
    if (cached) return cached;
    return new Response(
      JSON.stringify({ success:false, error:'Offline — cached data will load from IndexedDB.', offline:true }),
      { status:503, headers:{ 'Content-Type':'application/json' } }
    );
  }
}

// ── Background Sync: relay to all open clients ────────────────
self.addEventListener('sync', event => {
  if (event.tag === 'aceict-drain-queue') {
    event.waitUntil(relayToClients({ type: 'DRAIN_QUEUE' }));
  }
  if (event.tag === 'aceict-refresh') {
    event.waitUntil(relayToClients({ type: 'RUN_SYNC' }));
  }
});

// ── Periodic Background Sync ──────────────────────────────────
self.addEventListener('periodicsync', event => {
  if (event.tag === 'aceict-periodic-sync') {
    event.waitUntil(relayToClients({ type: 'RUN_SYNC' }));
  }
});

async function relayToClients(msg) {
  const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: false });
  clients.forEach(c => c.postMessage(msg));
}

// ── Messages from the app ─────────────────────────────────────
self.addEventListener('message', event => {
  if (event.data?.type === 'CLEAR_API_CACHE') {
    caches.delete(API_CACHE).then(() => caches.open(API_CACHE));
  }
  if (event.data?.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
