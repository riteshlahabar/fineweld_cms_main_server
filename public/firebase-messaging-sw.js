importScripts('https://www.gstatic.com/firebasejs/10.7.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging-compat.js');

self.addEventListener('install', function(event) {
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    event.waitUntil(self.clients.claim());
});

firebase.initializeApp({
    apiKey: "AIzaSyCRWQi3pWKS4_Hup6MPtfKDuE8iligVVsg",
    authDomain: "fineweld-engineer-542bf.firebaseapp.com",
    projectId: "fineweld-engineer-542bf",
    storageBucket: "fineweld-engineer-542bf.firebasestorage.app",
    messagingSenderId: "464748064826",
    appId: "1:464748064826:web:c3cb651c55e3ec994fc503"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function(payload) {
    self.registration.showNotification(
        payload.notification.title,
        {
            body: payload.notification.body,
        }
    );
});