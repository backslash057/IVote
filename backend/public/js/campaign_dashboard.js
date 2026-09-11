document.addEventListener('DOMContentLoaded', () => {
    const campaignId = document.body.dataset.campaignId;

    // 1. Navigation des onglets
    window.switchTab = function (tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.classList.remove('active', 'border-b-2', 'border-emerald-500', 'text-white');
            el.classList.add('text-slate-400');
        });

        const targetTab = document.getElementById('tab-' + tabId);
        if (targetTab) targetTab.classList.remove('hidden');

        const activeBtn = document.getElementById('tab-btn-' + tabId);
        if (activeBtn) {
            activeBtn.classList.add('active', 'border-b-2', 'border-emerald-500', 'text-white');
            activeBtn.classList.remove('text-slate-400');
        }
    };

    // 2. Modales
    window.toggleModal = function (modalId, show) {
        const modal = document.getElementById(modalId);
        if (modal) {
            if (show) modal.classList.remove('hidden');
            else modal.classList.add('hidden');
        }
    };

    // 3. Catégorie personnalisée
    const categorySelect = document.getElementById('categorySelect');
    if (categorySelect) {
        categorySelect.addEventListener('change', (e) => {
            const wrapper = document.getElementById('newCategoryWrapper');
            const input = document.getElementById('newCategoryName');
            if (e.target.value === 'new') {
                wrapper.classList.remove('hidden');
                input.required = true;
                input.focus();
            } else {
                wrapper.classList.add('hidden');
                input.required = false;
            }
        });
    }

    // 4. Recherche et filtre des transactions
    const txSearch = document.getElementById('txSearch');
    const paymentFilter = document.getElementById('paymentFilter');

    function filterTransactions() {
        const query = txSearch ? txSearch.value.toLowerCase() : '';
        const method = paymentFilter ? paymentFilter.value : 'all';
        const rows = document.querySelectorAll('.tx-row');

        rows.forEach(row => {
            const matchSearch = row.dataset.search.includes(query);
            const matchMethod = method === 'all' || row.dataset.method === method;
            row.style.display = (matchSearch && matchMethod) ? '' : 'none';
        });
    }

    if (txSearch) txSearch.addEventListener('keyup', filterTransactions);
    if (paymentFilter) paymentFilter.addEventListener('change', filterTransactions);

    // 5. Statut : Publier / Clôturer
    window.updateCampaignAction = async function (action) {
        const label = action === 'publish' ? 'passer en direct' : 'clôturer la campagne';
        if (!confirm(`Confirmez-vous vouloir ${label} ?`)) return;

        try {
            const res = await fetch(`/campaigns/${campaignId}/status`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: action })
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Erreur lors du changement de statut');
            }
        } catch (err) {
            alert('Une erreur réseau est survenue.');
        }
    };

    // 6. Ajouter un candidat
    const formCandidate = document.getElementById('formCandidate');
    if (formCandidate) {
        formCandidate.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formCandidate);

            try {
                const res = await fetch(`/campaigns/${campaignId}/candidates`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Erreur lors de l\'ajout du candidat');
                }
            } catch (err) {
                alert('Une erreur réseau est survenue.');
            }
        });
    }

    // 7. Modifier la photo de couverture
    const formPhoto = document.getElementById('formPhoto');
    if (formPhoto) {
        formPhoto.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formPhoto);

            try {
                const res = await fetch(`/campaigns/${campaignId}/update-photo`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Erreur lors de la mise à jour de la photo');
                }
            } catch (err) {
                alert('Une erreur réseau est survenue.');
            }
        });
    }

    // 8. Modifier la campagne
    const formEditCampaign = document.getElementById('formEditCampaign');
    if (formEditCampaign) {
        formEditCampaign.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formEditCampaign);
            const payload = Object.fromEntries(formData.entries());

            try {
                const res = await fetch(`/campaigns/${campaignId}/update`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Erreur lors de la modification');
                }
            } catch (err) {
                alert('Une erreur réseau est survenue.');
            }
        });
    }

    // 9. Relancer la campagne
    const formRelaunch = document.getElementById('formRelaunch');
    if (formRelaunch) {
        formRelaunch.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formRelaunch);
            const payload = Object.fromEntries(formData.entries());

            try {
                const res = await fetch(`/campaigns/${campaignId}/relaunch`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Erreur lors de la relance');
                }
            } catch (err) {
                alert('Une erreur réseau est survenue.');
            }
        });
    }

    // 10. Demande de retrait
    const formPayout = document.getElementById('formPayout');
    if (formPayout) {
        formPayout.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formPayout);
            const payload = Object.fromEntries(formData.entries());

            try {
                const res = await fetch(`/campaigns/${campaignId}/payouts`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    alert('Demande de retrait envoyée.');
                    location.reload();
                } else {
                    alert(data.message || 'Erreur lors de la demande de retrait');
                }
            } catch (err) {
                alert('Une erreur réseau est survenue.');
            }
        });
    }
});