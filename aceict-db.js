// ============================================================
// AceICT — Offline Database v2 (IndexedDB wrapper)
// NOTE: This file is a reference copy.
//       The live version is inlined in index.html.
//
// Stores:
//   api_cache          — GET response cache keyed by URL
//   sync_queue         — pending offline mutations (auto-drained on reconnect)
//   meta               — misc k/v  (lastSync, lastWarmup, …)
//   offline_questions  — pre-cached questions for offline practice / testing
// ============================================================

const AceDB = (() => {
  const DB_NAME    = 'aceict-offline';
  const DB_VERSION = 2;

  let _db = null;

  function open() {
    if (_db) return Promise.resolve(_db);
    return new Promise((resolve, reject) => {
      const req = indexedDB.open(DB_NAME, DB_VERSION);
      req.onupgradeneeded = e => {
        const d = e.target.result;
        if (!d.objectStoreNames.contains('api_cache'))
          d.createObjectStore('api_cache', { keyPath: 'key' });
        if (!d.objectStoreNames.contains('sync_queue'))
          d.createObjectStore('sync_queue', { keyPath: 'qid', autoIncrement: true });
        if (!d.objectStoreNames.contains('meta'))
          d.createObjectStore('meta', { keyPath: 'key' });
        if (!d.objectStoreNames.contains('offline_questions'))
          d.createObjectStore('offline_questions', { keyPath: 'id' });
      };
      req.onsuccess = e => { _db = e.target.result; resolve(_db); };
      req.onerror   = e => reject(e.target.error);
    });
  }

  async function _put(store, item) {
    const d = await open();
    return new Promise((res, rej) => {
      const tx = d.transaction(store, 'readwrite');
      tx.objectStore(store).put(item);
      tx.oncomplete = () => res();
      tx.onerror    = e => rej(e.target.error);
    });
  }

  async function _get(store, key) {
    const d = await open();
    return new Promise((res, rej) => {
      const req = d.transaction(store, 'readonly').objectStore(store).get(key);
      req.onsuccess = e => res(e.target.result ?? null);
      req.onerror   = e => rej(e.target.error);
    });
  }

  async function _getAll(store) {
    const d = await open();
    return new Promise((res, rej) => {
      const req = d.transaction(store, 'readonly').objectStore(store).getAll();
      req.onsuccess = e => res(e.target.result);
      req.onerror   = e => rej(e.target.error);
    });
  }

  async function _del(store, key) {
    const d = await open();
    return new Promise((res, rej) => {
      const tx = d.transaction(store, 'readwrite');
      tx.objectStore(store).delete(key);
      tx.oncomplete = () => res();
      tx.onerror    = e => rej(e.target.error);
    });
  }

  async function _clear(store) {
    const d = await open();
    return new Promise((res, rej) => {
      const tx = d.transaction(store, 'readwrite');
      tx.objectStore(store).clear();
      tx.oncomplete = res;
      tx.onerror    = e => rej(e.target.error);
    });
  }

  async function _count(store) {
    const d = await open();
    return new Promise((res, rej) => {
      const req = d.transaction(store, 'readonly').objectStore(store).count();
      req.onsuccess = e => res(e.target.result);
      req.onerror   = () => rej(0);
    });
  }

  // ── API cache ────────────────────────────────────────────────
  const cachePut    = (key, data) => _put('api_cache', { key, data, cached_at: Date.now() });
  const cacheDelete = key         => _del('api_cache', key);
  const cacheClear  = ()          => _clear('api_cache');

  async function cacheGet(key, maxAgeMs = null) {
    const r = await _get('api_cache', key);
    if (!r) return null;
    if (maxAgeMs && Date.now() - r.cached_at > maxAgeMs) return null;
    return { data: r.data, cached_at: r.cached_at };
  }

  // ── Sync queue ───────────────────────────────────────────────
  const enqueue    = entry => _put('sync_queue', { ...entry, queued_at: Date.now() });
  const getQueue   = ()    => _getAll('sync_queue');
  const dequeue    = qid   => _del('sync_queue', qid);
  const clearQueue = ()    => _clear('sync_queue');
  const queueCount = ()    => _count('sync_queue').catch(() => 0);

  // ── Offline questions store ──────────────────────────────────
  const questionsPutAll = async qs => { for (const q of qs) await _put('offline_questions', { ...q, cached_at: Date.now() }); };
  const questionsGetAll = ()        => _getAll('offline_questions');
  const questionsClear  = ()        => _clear('offline_questions');
  const questionsCount  = ()        => _count('offline_questions').catch(() => 0);

  // ── Metadata ─────────────────────────────────────────────────
  const setMeta = (key, value) => _put('meta', { key, value, updated_at: Date.now() });
  const getMeta = async key    => { const r = await _get('meta', key); return r?.value ?? null; };

  // ── Stats for sync details panel ─────────────────────────────
  async function getCacheStats() {
    const [apiCount, qCount, qsCount, lastSync, lastWarmup] = await Promise.all([
      _count('api_cache').catch(() => 0),
      queueCount(),
      questionsCount(),
      getMeta('lastSync'),
      getMeta('lastWarmup'),
    ]);
    return { apiCount, queueCount: qCount, questionsCount: qsCount, lastSync, lastWarmup };
  }

  return {
    open,
    cachePut, cacheGet, cacheDelete, cacheClear,
    enqueue, getQueue, dequeue, clearQueue, queueCount,
    questionsPutAll, questionsGetAll, questionsClear, questionsCount,
    setMeta, getMeta,
    getCacheStats,
  };
})();
