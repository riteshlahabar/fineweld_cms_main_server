<!-- Firebase Web Push -->

<script src="https://www.gstatic.com/firebasejs/10.7.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging-compat.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    console.log("🔥 Firebase Script Loaded");

    const firebaseConfig = {
        apiKey: "AIzaSyCRWQi3pWKS4_Hup6MPtfKDuE8iligVVsg",
        authDomain: "fineweld-engineer-542bf.firebaseapp.com",
        projectId: "fineweld-engineer-542bf",
        messagingSenderId: "464748064826",
        appId: "1:464748064826:web:c3cb651c55e3ec994fc503"
    };

    firebase.initializeApp(firebaseConfig);
    const messaging = firebase.messaging();

    navigator.serviceWorker.register('/firebase-messaging-sw.js')
        .then(() => console.log("✅ Service Worker Registered"));

    const enableBtn = document.getElementById('enableNotifications');

    if (!enableBtn) {
        console.log("❌ Button not found");
        return;
    }

    // If already enabled
    if (Notification.permission === "granted") {
        enableBtn.classList.remove('btn-primary');
        enableBtn.classList.add('btn-success');
        enableBtn.innerText = "Already Enabled";
        enableBtn.disabled = true;
    }

    enableBtn.addEventListener('click', function () {

        Notification.requestPermission().then(permission => {

            if (permission === "granted") {

                messaging.getToken({
                    vapidKey: "BA7OhpLul-QbtKjQWuwZNbhS2UBkyDJOdg8oW9Tjj3C6N_Nqo5I0CLknES1gv7smsDmYq7KoY-oeBf6hEXpbjmQ"
                }).then(token => {

                    if (token) {

                        fetch('/save-admin-token', {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({ token: token })
                        });

                        enableBtn.classList.remove('btn-primary');
                        enableBtn.classList.add('btn-success');
                        enableBtn.innerText = "Already Enabled";
                        enableBtn.disabled = true;
                    }

                });

            } else {
                enableBtn.innerText = "Permission Denied";
                enableBtn.classList.remove('btn-primary');
                enableBtn.classList.add('btn-danger');
            }

        });

    });

    messaging.onMessage(function(payload) {
        new Notification(payload.notification.title, {
            body: payload.notification.body
        });
    });

});
</script>