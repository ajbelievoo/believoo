const vapidPublicKey = document.querySelector('meta[name="vapid-key"]')?.content || '';

async function subscribePush() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
    try {
        const reg = await navigator.serviceWorker.register('/sw.js');
        await navigator.serviceWorker.ready;
        const existing = await reg.pushManager.getSubscription();
        if (existing) return;
        const sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: vapidPublicKey
        });
        await fetch('/push/subscribe', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
            body: JSON.stringify({subscription: sub.toJSON(), _token: document.querySelector('meta[name="csrf-token"]').content})
        });
    } catch (e) { console.error('[Push]', e); }
}

document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('enablePush');
    if (btn) btn.addEventListener('click', subscribePush);
    if (Notification.permission === 'default') setTimeout(subscribePush, 3000);
});
