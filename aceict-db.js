// ============================================================
// AceICT — Offline Database (IndexedDB wrapper)
// Phases 2-5: local cache + mutation queue + sync
//
// Usage:
//   await AceDB.cacheGet('/tests')          → cached API response
//   await AceDB.cachePut('/tests', data)    → save API response
//   await AceDB.enqueue(entry)              → queue a mutation
//   await AceDB.getQueue()                  → all pending mutations
//   await AceDB.dequeue(qid)               → remove after successful sync
//   await AceDB.setMeta('lastSync', ts)    → store timestamps / flags
//   await AceDB.getMeta('lastSync')        → retrieve them
// ============================================================

const AceDB = (() => {
  const DB_NAME    = 'aceict-offline';
  const DB_VERSION = 1;

  // All IndexedDB object stores
  const STORES = {
    api_cache:  { keyPath: 'key' },           // GET responses keyed by URL
    sync_queue: { keyPath: 'qid', autoIncrement: true }, // pending mutations
    meta:       { keyPath: 'key' },           // misc k/v (lastSync, etc.)
  };

  let _db = null;

  // ── Open (singleton) ────────────────────────────────────────
  function open() {
    if (_db) return Promise.resolve(_db);
    return new Promise((resolve, reject) => {
      const req = indexedDB.open(DB_NAME, DB_VERSION);

      req.onupgradeneeded = e => {
        const d = e.target.result;
        Object.entries(STORES).forEach(([name, opts]) => {
          if (!d.objectStoreNames.contains(name)) {
            d.createObjectStore(name, opts);
          }
        });
      };

      req.onsuccess = e => { _db = e.target.result; resolve(_db); };
      req.onerror   = e => reject(e.target.error);
    });
  }

  // ── Generic helpers ──────────────────────────────────────────
  async function _put(store, item) {
    const d = await open();
    return new Promise((resolve, reject) => {
      const tx = d.transaction(store, 'readwrite');
      tx.objectStore(store).put(item);
      tx.oncomplete = () => resolve();
      tx.onerror    = e => reject(e.target.error);
    });
  }

  async function _get(store, key) {
    const d = await open();
    return new Promise((resolve, reject) => {
      const req = d.transaction(store, 'readonly').objectStore(store).get(key);
      req.onsuccess = e => resolve(e.target.result ?? null);
      req.onerror   = e => reject(e.target.error);
    });
  }

  async function _getAll(store) {
    const d = await open();
    return new Promise((resolve, reject) => {
      const req = d.transaction(store, 'readonly').objectStore(store).getAll();
      req.onsuccess = e => resolve(e.target.result);
      req.onerror   = e => reject(e.target.error);
    });
  }

  async function _del(store, key) {
    const d = await open();
    return new Promise((resolve, reject) => {
      const tx = d.transaction(store, 'readwrite');
      tx.objectStore(store).delete(key);
      tx.oncomplete = () => resolve();
      tx.onerror    = e => reject(e.target.error);
    });
  }

  // ── API cache (GET responses) ────────────────────────────────
  async function cachePut(key, data) {
    return _put('api_cache', { key, data, cached_at: Date.now() });
  }

  async function cacheGet(key) {
    const row = await _get('api_cache', key);
    return row ? { data: row.data, cached_at: row.cached_at } : null;
  }

  async function cacheDelete(key) {
    return _del('api_cache', key);
  }

  async function cacheClear() {
    const d = await open();
    return new Promise((resolve, reject) => {
      const tx = d.transaction('api_cache', 'readwrite');
      tx.objectStore('api_cache').clear();
      tx.oncomplete = resolve;
      tx.onerror    = e => reject(e.target.error);
    });
  }

  // ── Sync queue (offline mutations) ──────────────────────────
  // entry shape: { method, endpoint, data, description }
  async function enqueue(entry) {
    return _put('sync_queue', { ...entry, queued_at: Date.now() });
  }

  async function getQueue() {
    return _getAll('sync_queue');
  }

  async function dequeue(qid) {
    return _del('sync_queue', qid);
  }

  async function clearQueue() {
    const d = await open();
    return new Promise((resolve, reject) => {
      const tx = d.transaction('sync_queue', 'readwrite');
      tx.objectStore('sync_queue').clear();
      tx.oncomplete = resolve;
      tx.onerror    = e => reject(e.target.error);
    });
  }

  // ── Metadata ─────────────────────────────────────────────────
  async function setMeta(key, value) {
    return _put('meta', { key, value, updated_at: Date.now() });
  }

  async function getMeta(key) {
    const row = await _get('meta', key);
    return row?.value ?? null;
  }

  // ── Queue count (for badge) ──────────────────────────────────
  async function queueCount() {
    const queue = await getQueue().catch(() => []);
    return queue.length;
  }

  return {
    open,
    cachePut, cacheGet, cacheDelete, cacheClear,
    enqueue, getQueue, dequeue, clearQueue, queueCount,
    setMeta, getMeta,
  };
})();
