document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('notification-list');
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]));

    async function request(path, options = {}) {
        const response = await fetch(path, {headers: {'Accept': 'application/json', ...(options.headers || {})}, ...options});
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Erreur de notification.');
        return data;
    }

    async function load() {
        try {
            const data = await request('/api/notifications');
            const notifications = data.data || [];
            list.innerHTML = notifications.length ? notifications.map((item) => `
                <article class="p-5 ${item.read_at ? '' : 'bg-primary/5'}">
                    <div class="flex items-start justify-between gap-4">
                        <div><h2 class="font-bold">${escapeHtml(item.title)}</h2><p class="mt-1 whitespace-pre-wrap text-sm text-fore-secondary">${escapeHtml(item.message)}</p><time class="mt-2 block text-xs text-fore-secondary">${escapeHtml(item.created_at)}</time></div>
                        ${item.read_at ? '' : `<button class="mark-read whitespace-nowrap text-xs font-bold text-primary" data-id="${Number(item.notification_id)}">Marquer lu</button>`}
                    </div>
                </article>`).join('') : '<p class="p-5 text-sm text-fore-secondary">Aucune notification pour le moment.</p>';
            list.querySelectorAll('.mark-read').forEach((button) => button.addEventListener('click', async () => {
                await request(`/api/notifications/${button.dataset.id}/read`, {method: 'POST'});
                await load();
                window.dispatchEvent(new Event('ivote-notifications-updated'));
            }));
        } catch (error) { list.innerHTML = `<p class="p-5 text-sm text-red-500">${escapeHtml(error.message)}</p>`; }
    }

    document.getElementById('mark-all-read').addEventListener('click', async () => {
        await request('/api/notifications/read-all', {method: 'POST'});
        await load();
        window.dispatchEvent(new Event('ivote-notifications-updated'));
    });
    load();
});
