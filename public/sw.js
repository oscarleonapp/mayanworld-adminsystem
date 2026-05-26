// Service Worker para Travel Agency MVP
const CACHE_NAME = 'travel-mvp-v2';

// Auto-detectar base path según el entorno
// En local: /travel-agency-mvp/public
// En producción: vacío o ruta según instalación
const getBasePath = () => {
  const path = self.location.pathname;
  // Si estamos en /travel-agency-mvp/public/sw.js, el base es /travel-agency-mvp/public
  // Si estamos en /sw.js, el base es vacío
  if (path.includes('/travel-agency-mvp/public/')) {
    return '/travel-agency-mvp/public';
  } else if (path.includes('/public/')) {
    return '/public';
  }
  return '';
};

const BASE_PATH = getBasePath();

const urlsToCache = [
  BASE_PATH + '/',
  BASE_PATH + '/assets/css/style.css',
  BASE_PATH + '/assets/js/main.js'
];

// Install event
self.addEventListener('install', event => {
  console.log('Service Worker: Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Service Worker: Caching files');
        return cache.addAll(urlsToCache).catch(err => {
          console.warn('Service Worker: Some files failed to cache', err);
        });
      })
      .then(() => self.skipWaiting())
  );
});

// Activate event
self.addEventListener('activate', event => {
  console.log('Service Worker: Activating...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            console.log('Service Worker: Clearing old cache');
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch event - Network First strategy
self.addEventListener('fetch', event => {
  // No cachear peticiones POST, PUT, DELETE (solo GET y HEAD)
  if (event.request.method !== 'GET' && event.request.method !== 'HEAD') {
    return;
  }

  // No cachear peticiones a dominios externos (placeholder, analytics, etc.)
  const url = new URL(event.request.url);
  if (url.origin !== location.origin) {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Solo cachear respuestas exitosas (200-299)
        if (response && response.status === 200 && response.type === 'basic') {
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, responseClone);
          });
        }
        return response;
      })
      .catch(() => {
        return caches.match(event.request);
      })
  );
});
