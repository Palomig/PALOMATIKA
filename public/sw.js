// public/sw.js
const CACHE_NAME = 'palomatika-v2';

// App shell — cached on install
const SHELL_URLS = [
  '/js/alpine.min.js',
  '/offline.html',
];

self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(SHELL_URLS))
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;

  // Only handle GET requests
  if (request.method !== 'GET') return;

  // Never cache API calls or auth routes
  const url = new URL(request.url);
  if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/auth/')) return;

  event.respondWith(
    fetch(request)
      .then(response => {
        // Cache JS/CSS/fonts
        if (response.ok && (
          url.pathname.endsWith('.js') ||
          url.pathname.endsWith('.css') ||
          url.pathname.includes('/fonts/')
        )) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
        }
        return response;
      })
      .catch(() => {
        // Offline fallback for HTML navigation
        if (request.headers.get('accept')?.includes('text/html')) {
          return caches.match('/offline.html');
        }
      })
  );
});

// ─── Push Notifications ──────────────────────────────────────────────────────

self.addEventListener('push', (event) => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (e) {
    data = { title: 'Урок', body: event.data ? event.data.text() : '' };
  }

  const title   = data.title || 'Урок';
  const options = {
    body:               data.body || '',
    icon:               '/icons/apple-touch-icon.png',
    badge:              '/icons/badge-72.png',
    data:               { url: data.url || '/lessons' },
    requireInteraction: true,
    vibrate:            [200, 100, 200],
    tag:                data.tag || 'lesson-notify',
    renotify:           true,
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = event.notification.data?.url || '/lessons';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
      // If there is already an open window on the same origin, focus it and navigate
      for (const w of windows) {
        if (w.url.startsWith(self.registration.scope)) {
          w.focus();
          return w.navigate(url);
        }
      }
      // Otherwise open a new window
      return clients.openWindow(url);
    })
  );
});
