console.log('[SW] Service Worker script loading');

importScripts('https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js');

console.log('[SW] Firebase scripts imported');

firebase.initializeApp({
    apiKey:            "AIzaSyADdYLSu_oY1zihFmTGwX3KrPTm2Xl8P8Q",
    authDomain:        "pintoursindoormapping.firebaseapp.com",
    projectId:         "pintoursindoormapping",
    storageBucket:     "pintoursindoormapping.firebasestorage.app",
    messagingSenderId: "999155327407",
    appId:             "1:999155327407:web:ae304113f56707d54ecf5a",
});

console.log('[SW] Firebase initialized');

const messaging = firebase.messaging();

// ─── Relay helper ─────────────────────────────────────────────────────────────
// Posts a structured message to ALL controlled page clients so the debug panel
// can display service worker events in real time.
async function relayToClients(type, payload = null) {
    const clientList = await clients.matchAll({ type: 'window', includeUncontrolled: true });

    console.log(`[SW] Relaying ${type} to ${clientList.length} client(s)`, payload);

    for (const client of clientList) {
        client.postMessage({ type, payload });
    }
}

// ─── Background message ───────────────────────────────────────────────────────
messaging.onBackgroundMessage((payload) => {
    console.log('[SW] BACKGROUND_MESSAGE_RECEIVED', payload);

    // Relay to debug panel immediately (before showing notification)
    relayToClients('SW_BACKGROUND_MESSAGE', payload);

    const title = payload.notification?.title ?? 'Notification';
    const body  = payload.notification?.body  ?? '';
    const data  = payload.data ?? {};

    console.log('[SW] Showing notification', { title, body, data });

    return self.registration.showNotification(title, {
        body,
        icon:  '/favicon.ico',
        badge: '/favicon.ico',
        data,
    }).then(() => {
        console.log('[SW] Notification displayed successfully');
        relayToClients('SW_LOG', { step: 'NOTIFICATION_DISPLAYED', data: { title, body } });
    }).catch((err) => {
        console.error('[SW] Failed to show notification', err);
        relayToClients('SW_LOG', { step: 'NOTIFICATION_DISPLAY_ERROR', data: err.message });
    });
});

// ─── Notification click ───────────────────────────────────────────────────────
self.addEventListener('notificationclick', (event) => {
    console.log('[SW] Notification clicked', event.notification);

    const notificationData = {
        title: event.notification.title,
        body:  event.notification.body,
        data:  event.notification.data,
    };

    event.notification.close();

    event.waitUntil(
        relayToClients('SW_NOTIFICATION_CLICK', notificationData).then(() =>
            clients
                .matchAll({ type: 'window', includeUncontrolled: true })
                .then((clientList) => {
                    console.log('[SW] Active window clients:', clientList.length);

                    const focusable = clientList.find((c) => 'focus' in c);
                    if (focusable) return focusable.focus();

                    return clients.openWindow('/');
                })
        )
    );
});

// ─── Install / Activate ───────────────────────────────────────────────────────
self.addEventListener('install', () => {
    console.log('[SW] Install event — skipping waiting');
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    console.log('[SW] Activate event — claiming clients');
    event.waitUntil(clients.claim());
});

console.log('[SW] Service Worker ready');