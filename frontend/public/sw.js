// File: public/sw.js
// Service worker ini akan berjalan di background dan "mendengarkan" push notification

self.addEventListener('push', event => {
    console.log('[Service Worker] Push Received.');
    console.log(`[Service Worker] Push had this data: "${event.data.text()}"`);

    try {
        const data = event.data.json();
        const title = data.title || 'Notifikasi Baru';
        const options = {
            body: data.body || 'Anda memiliki pesan baru.',
            icon: '/taskora-icon.png', // Pastikan ikon ini ada di folder public
            badge: '/taskora-badge.png' // Opsional: Ikon kecil untuk notifikasi bar
        };

        event.waitUntil(self.registration.showNotification(title, options));
    } catch (e) {
        console.error('Error parsing push data:', e);
        // Fallback untuk data non-JSON
        const title = 'Notifikasi Baru';
        const options = { body: event.data.text() };
        event.waitUntil(self.registration.showNotification(title, options));
    }
});

self.addEventListener('notificationclick', event => {
  console.log('[Service Worker] Notification click Received.');
  event.notification.close();
  // Fokus ke window aplikasi jika sudah terbuka
  event.waitUntil(
    clients.matchAll({
      type: "window"
    }).then(clientList => {
      for (let i = 0; i < clientList.length; i++) {
        let client = clientList[i];
        if (client.url == '/' && 'focus' in client)
          return client.focus();
      }
      if (clients.openWindow)
        return clients.openWindow('/');
    })
  );
});

