document.addEventListener('DOMContentLoaded', async () => {
    const badge = document.getElementById('notification-count');
    if (!badge) return;
    const update = async () => {
        try {
            const response = await fetch('/api/notifications/unread-count', {headers: {'Accept': 'application/json'}});
            const data = await response.json();
            const count = Number(data.count || 0);
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.classList.toggle('hidden', count === 0 || !data.success);
        } catch (_) { badge.classList.add('hidden'); }
    };
    window.addEventListener('ivote-notifications-updated', update);
    await update();
});
