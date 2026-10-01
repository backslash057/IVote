document.addEventListener('DOMContentLoaded', () => {
    const api = async (path, options = {}) => {
        const response = await fetch(path, {
            ...options,
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', ...(options.headers || {})}
        });
        const data = await response.json();
        if (!response.ok || data.success === false) throw new Error(data.message || 'Action impossible.');
        return data;
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'}[char]));
    const reason = (message) => {
        const value = window.prompt(message);
        return value && value.trim() ? value.trim() : null;
    };

    async function loadOverview() {
        const data = await api('/api/admin/overview');
        Object.entries(data.data || {}).forEach(([key, value]) => {
            const element = document.querySelector(`[data-stat="${key}"]`);
            if (element) element.textContent = Number(value).toLocaleString('fr-FR');
        });
    }

    async function loadUsers() {
        const container = document.getElementById('admin-users');
        const data = await api('/api/admin/users');
        const users = (data.data || []).filter((user) => user.role === 'organizer');
        const recipient = document.getElementById('announcement-recipient');
        if (recipient) {
            const activeUsers = users.filter((user) => user.status === 'active');
            recipient.innerHTML = '<option value="">Choisir un organisateur</option>' + activeUsers.map((user) => `<option value="${user.user_id}">${escapeHtml(user.name)} · ${escapeHtml(user.email)}</option>`).join('');
        }
        container.innerHTML = users.length ? users.map((user) => `
            <div class="p-4 flex items-center justify-between gap-4">
                <div><p class="font-bold text-sm">${escapeHtml(user.name)}</p><p class="text-xs text-fore-secondary">${escapeHtml(user.email)}</p></div>
                <button class="admin-action ${user.status === 'active' ? 'text-red-500' : 'text-green-600'}" data-user-id="${user.user_id}" data-user-status="${user.status}">${user.status === 'active' ? 'Suspendre' : 'Réactiver'}</button>
            </div>`).join('') : '<p class="p-4 text-sm text-fore-secondary">Aucun organisateur.</p>';

        container.querySelectorAll('[data-user-id]').forEach((button) => button.addEventListener('click', async () => {
            const nextStatus = button.dataset.userStatus === 'active' ? 'suspended' : 'active';
            const message = nextStatus === 'active' ? 'Motif de réactivation :' : 'Motif de suspension :';
            const explanation = reason(message);
            if (!explanation) return;
            try {
                await api(`/api/admin/users/${button.dataset.userId}/status`, {method: 'POST', body: JSON.stringify({status: nextStatus, reason: explanation})});
                await Promise.all([loadOverview(), loadUsers()]);
            } catch (error) { window.alert(error.message); }
        }));
    }

    async function loadCampaigns() {
        const container = document.getElementById('admin-campaigns');
        const data = await api('/api/admin/campaigns');
        const labels = {draft: 'Brouillon', pending_review: 'En validation', published: 'En ligne', suspended: 'Suspendue', closed: 'Clôturée', archived: 'Archivée'};
        container.innerHTML = (data.data || []).map((campaign) => `
            <div class="p-4 flex items-center justify-between gap-4">
                <div><p class="font-bold text-sm">${escapeHtml(campaign.title)}</p><p class="text-xs text-fore-secondary">${escapeHtml(campaign.organizer_name)} · ${labels[campaign.status] || escapeHtml(campaign.status)}${campaign.suspension_reason ? ` · Motif : ${escapeHtml(campaign.suspension_reason)}` : ''}</p></div>
                ${campaign.status === 'published' ? `<button class="admin-action text-red-500" data-campaign-id="${campaign.campaign_id}">Suspendre</button>` : campaign.status === 'suspended' ? `<button class="admin-action text-green-600" data-restore-id="${campaign.campaign_id}">Restaurer</button>` : ''}
            </div>`).join('') || '<p class="p-4 text-sm text-fore-secondary">Aucune campagne.</p>';

        container.querySelectorAll('[data-campaign-id], [data-restore-id]').forEach((button) => button.addEventListener('click', async () => {
            const action = button.dataset.restoreId ? 'restore' : 'suspend';
            const explanation = action === 'restore' ? null : reason('Motif de suspension :');
            if (!explanation) return;
            try {
                await api(`/api/admin/campaigns/${button.dataset.campaignId || button.dataset.restoreId}/moderate`, {method: 'POST', body: JSON.stringify({action, reason: explanation})});
                await Promise.all([loadOverview(), loadCampaigns()]);
            } catch (error) { window.alert(error.message); }
        }));
    }

    async function loadPayouts() {
        const container = document.getElementById('admin-payouts');
        const data = await api('/api/admin/payouts');
        const payouts = data.data || [];
        const labels = {pending: 'En attente', approved: 'Approuvé · versement à effectuer', rejected: 'Refusé', paid: 'Payé', completed: 'Payé', under_review: 'À décider', cancelled: 'Annulé'};
        container.innerHTML = payouts.length ? `<table class="w-full text-left text-xs"><thead><tr class="border-b border-bordercustom text-fore-secondary"><th class="p-4">Organisateur</th><th class="p-4">Campagne</th><th class="p-4">Montant</th><th class="p-4">Statut</th><th class="p-4">Motif / action</th></tr></thead><tbody>${payouts.map((payout) => `<tr class="border-b border-bordercustom"><td class="p-4">${escapeHtml(payout.organizer_name)}</td><td class="p-4">${escapeHtml(payout.title)}</td><td class="p-4 font-bold">${Number(payout.amount_fcfa).toLocaleString('fr-FR')} FCFA</td><td class="p-4">${labels[payout.status] || escapeHtml(payout.status)}</td><td class="p-4">${['pending', 'under_review'].includes(payout.status) ? `<button class="payout-decision text-green-600 font-bold mr-3" data-action="approve" data-payout-id="${payout.payout_id}">Valider</button><button class="payout-decision text-red-500 font-bold" data-action="reject" data-payout-id="${payout.payout_id}">Refuser</button>` : payout.status === 'rejected' ? `<span class="text-red-500">${escapeHtml(payout.rejection_reason || 'Motif non renseigné')}</span>` : '<span class="text-fore-secondary">Décision définitive</span>'}</td></tr>`).join('')}</tbody></table>` : '<p class="p-4 text-sm text-fore-secondary">Aucun retrait.</p>';
        container.querySelectorAll('.payout-decision').forEach((button) => button.addEventListener('click', async () => {
            const action = button.dataset.action;
            const explanation = action === 'reject' ? reason('Motif du refus (obligatoire) :') : null;
            if (action === 'reject' && !explanation) return;
            const row = button.closest('tr');
            row.querySelectorAll('button').forEach((item) => item.disabled = true);
            try {
                await api(`/api/admin/payouts/${button.dataset.payoutId}/review`, {method: 'POST', body: JSON.stringify({action, ...(explanation ? {reason: explanation} : {})})});
                await Promise.all([loadOverview(), loadPayouts()]);
            } catch (error) { window.alert(error.message); row.querySelectorAll('button').forEach((item) => item.disabled = false); }
        }));
    }

    async function refreshAll() {
        try { await Promise.all([loadOverview(), loadUsers(), loadCampaigns(), loadPayouts()]); } catch (error) { window.alert(error.message); }
    }
    document.querySelectorAll('[data-refresh]').forEach((button) => button.addEventListener('click', refreshAll));

    const broadcastToggle = document.getElementById('announcement-broadcast');
    const recipientSelect = document.getElementById('announcement-recipient');
    if (broadcastToggle && recipientSelect) {
        broadcastToggle.addEventListener('change', () => {
            recipientSelect.classList.toggle('hidden', broadcastToggle.checked);
            recipientSelect.required = !broadcastToggle.checked;
        });
    }

    const announcementForm = document.getElementById('admin-announcement-form');
    if (announcementForm) announcementForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submit = announcementForm.querySelector('[type="submit"]');
        submit.disabled = true;
        try {
            const broadcast = broadcastToggle.checked;
            const result = await api('/api/admin/notifications', {
                method: 'POST',
                body: JSON.stringify({
                    title: document.getElementById('announcement-title').value.trim(),
                    message: document.getElementById('announcement-message').value.trim(),
                    broadcast,
                    ...(!broadcast ? {recipient_id: Number(recipientSelect.value)} : {})
                })
            });
            window.alert(`${result.message} Destinataires : ${result.recipient_count}.`);
            announcementForm.reset();
            broadcastToggle.dispatchEvent(new Event('change'));
        } catch (error) { window.alert(error.message); }
        finally { submit.disabled = false; }
    });
    refreshAll();
});
