// Deliberadamente sin caché: los datos y fotos requieren conexión y sesión.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', event => {
  if (event.request.mode === 'navigate') event.respondWith(fetch(event.request).catch(() => new Response('<!doctype html><html lang="es"><meta name="viewport" content="width=device-width"><title>Sin conexión · Mi armario</title><body><h1>Necesitas conexión</h1><p>Conéctate a internet y vuelve a abrir Mi armario. No se han guardado cambios sin conexión.</p><button onclick="location.reload()">Volver a intentar</button></body></html>', {headers:{'Content-Type':'text/html; charset=utf-8','Cache-Control':'no-store'}})));
});
