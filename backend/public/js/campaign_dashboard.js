document.addEventListener('DOMContentLoaded', () => {
    const campaignId = document.body.dataset.campaignId;
    const candidatesCount = parseInt(document.body.dataset.candidatesCount || '0', 10);

    document.querySelectorAll('.copy-campaign-link').forEach((button) => {
        button.addEventListener('click', async () => {
            const campaignUrl = `${window.location.origin}${button.dataset.campaignUrl}`;
            try {
                await navigator.clipboard.writeText(campaignUrl);
                const label = button.querySelector('span') || button;
                const originalText = label.textContent;
                label.textContent = 'Lien copié !';
                window.setTimeout(() => {
                    label.textContent = originalText;
                }, 2000);
            } catch {
                window.prompt('Copiez le lien de la campagne :', campaignUrl);
            }
        });
    });

    // --- 1. Gestion des dates minimales (+5 minutes) ---
    const getMinDateTime = () => {
        const minDate = new Date(Date.now() + 5 * 60 * 1000);
        const y = minDate.getFullYear();
        const m = String(minDate.getMonth() + 1).padStart(2, '0');
        const d = String(minDate.getDate()).padStart(2, '0');
        const h = String(minDate.getHours()).padStart(2, '0');
        const min = String(minDate.getMinutes()).padStart(2, '0');
        return `${y}-${m}-${d}T${h}:${min}`;
    };

    const editDateInput = document.getElementById('edit_date_cloture');
    const relaunchDateInput = document.getElementById('relaunch_date_cloture');
    if (editDateInput) editDateInput.min = getMinDateTime();
    if (relaunchDateInput) relaunchDateInput.min = getMinDateTime();

    // --- 2. Navigation des onglets ---
    window.switchTab = function (tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.classList.remove('active', 'border-b-2', 'border-primary', 'text-primary');
            el.classList.add('text-fore-secondary');
        });

        const targetTab = document.getElementById('tab-' + tabId);
        if (targetTab) targetTab.classList.remove('hidden');

        const activeBtn = document.getElementById('tab-btn-' + tabId);
        if (activeBtn) {
            activeBtn.classList.add('active', 'border-b-2', 'border-primary', 'text-primary');
            activeBtn.classList.remove('text-fore-secondary');
        }
    };

    // --- 3. Gestion des modales ---
    window.toggleModal = function (modalId, show) {
        const modal = document.getElementById(modalId);
        if (modal) {
            if (show) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            } else {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }
    };

    // --- 4. Catégorie personnalisée ---
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

    // --- 5. Recherche et filtre des transactions ---
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

// --- 6. Gestion du Recadrage Générique avec ratio dynamique ---
    const genericCropModal = document.getElementById('genericCropModal');
    const genericCropTarget = document.getElementById('genericCropTarget');
    const genericCropTitle = document.getElementById('genericCropTitle');
    const btnApplyGenericCrop = document.getElementById('btnApplyGenericCrop');
    const btnCancelGenericCrop = document.getElementById('btnCancelGenericCrop');

    let activeCropper = null;
    let onCropDoneCallback = null;

    function openCropper(file, aspectRatio, titleText, onDone) {
        onCropDoneCallback = onDone;
        if (genericCropTitle) genericCropTitle.textContent = titleText || "Recadrer la photo";
        
        const reader = new FileReader();
        reader.onload = (e) => {
            genericCropTarget.src = e.target.result;
            genericCropModal.classList.remove('hidden');
            if (activeCropper) activeCropper.destroy();

            activeCropper = new Cropper(genericCropTarget, {
                aspectRatio: aspectRatio,
                viewMode: 2,
                autoCropArea: 1,
                responsive: true
            });
        };
        reader.readAsDataURL(file);
    }

    btnCancelGenericCrop.addEventListener('click', () => {
        genericCropModal.classList.add('hidden');
        if (activeCropper) activeCropper.destroy();
        activeCropper = null;
    });

    btnApplyGenericCrop.addEventListener('click', () => {
        if (!activeCropper) return;
        activeCropper.getCroppedCanvas({
            maxWidth: 1600,
            maxHeight: 1200,
            imageSmoothingQuality: 'high'
        }).toBlob((blob) => {
            if (onCropDoneCallback) onCropDoneCallback(blob);
            genericCropModal.classList.add('hidden');
            activeCropper.destroy();
            activeCropper = null;
        }, 'image/webp', 0.88);
    });

    // --- 7. Upload Photo Candidat (Ratio 3:4 Portrait) ---
    let candidateCroppedBlob = null;
    const candidateInput = document.getElementById('candidateImageInput');
    const candidatePlaceholder = document.getElementById('candidateUploadPlaceholder');
    const candidatePreviewContainer = document.getElementById('candidatePreviewContainer');
    const candidatePreview = document.getElementById('candidateImagePreview');
    const btnRemoveCandidateImage = document.getElementById('btnRemoveCandidateImage');

    candidatePlaceholder.addEventListener('click', () => candidateInput.click());
    candidateInput.addEventListener('change', (e) => {
        if (e.target.files[0]) {
            openCropper(e.target.files[0], 3 / 4, "Recadrer la photo du candidat (Portrait 3:4)", (blob) => {
                candidateCroppedBlob = blob;
                candidatePreview.src = URL.createObjectURL(blob);
                candidatePlaceholder.classList.add('hidden');
                candidatePreviewContainer.classList.remove('hidden');
            });
        }
    });

    btnRemoveCandidateImage.addEventListener('click', () => {
        candidateCroppedBlob = null;
        candidateInput.value = '';
        candidatePreview.src = '';
        candidatePreviewContainer.classList.add('hidden');
        candidatePlaceholder.classList.remove('hidden');
    });

    // --- 8. Upload Photo Campagne (Ratio 16:9 Paysage) ---
    let campaignCroppedBlob = null;
    const campaignInput = document.getElementById('campaignImageInput');
    const campaignPlaceholder = document.getElementById('campaignUploadPlaceholder');
    const campaignPreviewContainer = document.getElementById('campaignPreviewContainer');
    const campaignPreview = document.getElementById('campaignImagePreview');
    const btnRemoveCampaignImage = document.getElementById('btnRemoveCampaignImage');

    campaignPlaceholder.addEventListener('click', () => campaignInput.click());
    campaignInput.addEventListener('change', (e) => {
        if (e.target.files[0]) {
            openCropper(e.target.files[0], 16 / 9, "Recadrer l'affiche (Paysage 16:9)", (blob) => {
                campaignCroppedBlob = blob;
                campaignPreview.src = URL.createObjectURL(blob);
                campaignPlaceholder.classList.add('hidden');
                campaignPreviewContainer.classList.remove('hidden');
            });
        }
    });

    btnRemoveCampaignImage.addEventListener('click', () => {
        campaignCroppedBlob = null;
        campaignInput.value = '';
        campaignPreview.src = '';
        campaignPreviewContainer.classList.add('hidden');
        campaignPlaceholder.classList.remove('hidden');
    });

    // --- 9. Statut : Publier (avec vérification 0 candidat) / Clôturer ---
    window.updateCampaignAction = async function (action) {
        if (action === 'publish' && candidatesCount === 0) {
            alert("Action impossible : Vous devez ajouter au moins un candidat avant de publier la campagne.");
            return;
        }

        const label = action === 'publish' ? 'passer en direct' : 'clôturer la campagne';
        if (!confirm(`Confirmez-vous vouloir ${label} ?`)) return;

        try {
            const res = await fetch(`/api/campaigns/${campaignId}/status`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json' 
                },
                body: JSON.stringify({ action: action })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                location.reload();
            } else {
                alert(data.message || 'Erreur lors du changement de statut');
            }
        } catch (err) {
            alert('Une erreur réseau est survenue.');
        }
    };

    // --- 10. Envoi Candidat avec barre de progression ---
    const formCandidate = document.getElementById('formCandidate');
    if (formCandidate) {
        formCandidate.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('submitCandidateBtn');
            const progressContainer = document.getElementById('candidateProgressContainer');
            const progressBar = document.getElementById('candidateProgressBar');
            const uploadPercent = document.getElementById('candidateUploadPercent');
            const uploadStatus = document.getElementById('candidateUploadStatus');

            submitBtn.disabled = true;
            progressContainer.classList.remove('hidden');
            progressBar.style.width = '0%';
            uploadPercent.textContent = '0%';
            uploadStatus.textContent = 'Envoi...';

            const formData = new FormData(formCandidate);
            if (candidateCroppedBlob) {
                formData.set('image', candidateCroppedBlob, 'candidate.webp');
            } else {
                formData.delete('image');
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', `/api/campaigns/${campaignId}/candidates`, true);
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.onprogress = (evt) => {
                if (evt.lengthComputable) {
                    const percent = Math.round((evt.loaded / evt.total) * 100);
                    progressBar.style.width = `${percent}%`;
                    uploadPercent.textContent = `${percent}%`;
                }
            };

            xhr.onload = () => {
                submitBtn.disabled = false;
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (xhr.status >= 200 && xhr.status < 300 && data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Erreur lors de l\'ajout du candidat');
                        progressContainer.classList.add('hidden');
                    }
                } catch {
                    alert('Erreur de réponse du serveur.');
                    progressContainer.classList.add('hidden');
                }
            };

            xhr.onerror = () => {
                submitBtn.disabled = false;
                progressContainer.classList.add('hidden');
                alert('Erreur réseau.');
            };

            xhr.send(formData);
        });
    }

    // --- 11. Envoi Photo Campagne avec barre de progression ---
    const formPhoto = document.getElementById('formPhoto');
    if (formPhoto) {
        formPhoto.addEventListener('submit', (e) => {
            e.preventDefault();
            if (!campaignCroppedBlob) {
                alert("Veuillez sélectionner et recadrer une image.");
                return;
            }

            const submitBtn = document.getElementById('submitCampaignPhotoBtn');
            const progressContainer = document.getElementById('campaignProgressContainer');
            const progressBar = document.getElementById('campaignProgressBar');
            const uploadPercent = document.getElementById('campaignUploadPercent');
            const uploadStatus = document.getElementById('campaignUploadStatus');

            submitBtn.disabled = true;
            progressContainer.classList.remove('hidden');
            progressBar.style.width = '0%';
            uploadPercent.textContent = '0%';
            uploadStatus.textContent = 'Téléversement...';

            const formData = new FormData();
            formData.set('campaign_image', campaignCroppedBlob, 'banner.webp');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', `/api/campaigns/${campaignId}/update-photo`, true);
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.onprogress = (evt) => {
                if (evt.lengthComputable) {
                    const percent = Math.round((evt.loaded / evt.total) * 100);
                    progressBar.style.width = `${percent}%`;
                    uploadPercent.textContent = `${percent}%`;
                }
            };

            xhr.onload = () => {
                submitBtn.disabled = false;
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (xhr.status >= 200 && xhr.status < 300 && data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Erreur lors de la mise à jour');
                        progressContainer.classList.add('hidden');
                    }
                } catch {
                    alert('Erreur serveur.');
                    progressContainer.classList.add('hidden');
                }
            };

            xhr.onerror = () => {
                submitBtn.disabled = false;
                progressContainer.classList.add('hidden');
                alert('Erreur réseau.');
            };

            xhr.send(formData);
        });
    }

    // --- 12. Modifier la campagne avec validation date future ---
    const formEditCampaign = document.getElementById('formEditCampaign');
    if (formEditCampaign) {
        formEditCampaign.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (editDateInput && editDateInput.value) {
                const selectedTime = new Date(editDateInput.value).getTime();
                const minTime = Date.now() + (5 * 60 * 1000) - 1000;
                if (selectedTime < minTime) {
                    alert("La date de clôture doit être située au moins 5 minutes dans le futur.");
                    return;
                }
            }

            const submitBtn = formEditCampaign.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            const formData = new FormData(formEditCampaign);
            const payload = Object.fromEntries(formData.entries());

            try {
                const res = await fetch(`/api/campaigns/${campaignId}/update`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Erreur lors de la modification');
                }
            } catch (err) {
                alert('Une erreur réseau est survenue.');
            } finally {
                submitBtn.disabled = false;
            }
        });
    }

    async function sendDraftRequest(url, method, payload) {
        const response = await fetch(url, {
            method,
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: payload ? JSON.stringify(payload) : undefined
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Action impossible.');
        return data;
    }

    document.querySelectorAll('.candidate-edit').forEach((button) => button.addEventListener('click', async () => {
        const name = window.prompt('Nom du candidat :', button.dataset.name);
        if (name === null || !name.trim()) return;
        const number = Number(window.prompt('Numéro du candidat :', button.dataset.number));
        if (!Number.isInteger(number) || number < 1) { window.alert('Numéro invalide.'); return; }
        const ageValue = window.prompt('Âge :', button.dataset.age === '0' ? '' : button.dataset.age);
        if (ageValue === null) return;
        const theme = window.prompt('Thème :', button.dataset.theme);
        if (theme === null) return;
        const description = window.prompt('Description :', button.dataset.description);
        if (description === null) return;
        const bio = window.prompt('Biographie :', button.dataset.bio);
        if (bio === null) return;
        const categoryOptions = [...document.querySelectorAll('#categorySelect option')]
            .filter((option) => option.value && option.value !== 'new')
            .map((option) => `${option.value} = ${option.textContent.trim()}`)
            .join('\n');
        const categoryChoice = window.prompt(`ID de catégorie (laisser vide pour aucune) :\n${categoryOptions}`, button.dataset.categoryId === '0' ? '' : button.dataset.categoryId);
        if (categoryChoice === null) return;
        const categoryId = categoryChoice.trim() ? Number(categoryChoice) : null;
        if (categoryId !== null && !Number.isInteger(categoryId)) { window.alert('ID de catégorie invalide.'); return; }
        try {
            await sendDraftRequest(`/api/candidates/${button.dataset.candidateId}`, 'PUT', {
                name: name.trim(), candidate_number: number,
                age: ageValue.trim() ? Number(ageValue) : null,
                theme, description, bio,
                category_id: categoryId
            });
            location.reload();
        } catch (error) { window.alert(error.message); }
    }));

    document.querySelectorAll('.candidate-delete').forEach((button) => button.addEventListener('click', async () => {
        if (!window.confirm(`Supprimer le candidat « ${button.dataset.name} » ?`)) return;
        try {
            await sendDraftRequest(`/api/candidates/${button.dataset.candidateId}`, 'DELETE');
            location.reload();
        } catch (error) { window.alert(error.message); }
    }));

    const addCategoryButton = document.getElementById('addCategoryBtn');
    if (addCategoryButton) addCategoryButton.addEventListener('click', async () => {
        const name = window.prompt('Nom de la nouvelle catégorie :');
        if (!name || !name.trim()) return;
        try {
            await sendDraftRequest(`/api/campaigns/${campaignId}/categories`, 'POST', {name: name.trim()});
            location.reload();
        } catch (error) { window.alert(error.message); }
    });

    document.querySelectorAll('.category-rename').forEach((button) => button.addEventListener('click', async () => {
        const name = window.prompt('Nouveau nom de catégorie :', button.dataset.categoryName);
        if (!name || !name.trim()) return;
        try {
            await sendDraftRequest(`/api/categories/${button.dataset.categoryId}`, 'PUT', {name: name.trim()});
            location.reload();
        } catch (error) { window.alert(error.message); }
    }));

    document.querySelectorAll('.category-delete').forEach((button) => button.addEventListener('click', async () => {
        if (!window.confirm('Supprimer cette catégorie vide ?')) return;
        try {
            await sendDraftRequest(`/api/categories/${button.dataset.categoryId}`, 'DELETE');
            location.reload();
        } catch (error) { window.alert(error.message); }
    }));

    // --- 13. Relancer la campagne avec validation date future ---
    const formRelaunch = document.getElementById('formRelaunch');
    if (formRelaunch) {
        formRelaunch.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (relaunchDateInput && relaunchDateInput.value) {
                const selectedTime = new Date(relaunchDateInput.value).getTime();
                const minTime = Date.now() + (5 * 60 * 1000) - 1000;
                if (selectedTime < minTime) {
                    alert("La nouvelle date de clôture doit être située au moins 5 minutes dans le futur.");
                    return;
                }
            }

            const submitBtn = formRelaunch.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            const formData = new FormData(formRelaunch);
            const payload = Object.fromEntries(formData.entries());

            try {
                const res = await fetch(`/api/campaigns/${campaignId}/relaunch`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Erreur lors de la relance');
                }
            } catch (err) {
                alert('Une erreur réseau est survenue.');
            } finally {
                submitBtn.disabled = false;
            }
        });
    }

    // --- 14. Demande de retrait ---
    const formPayout = document.getElementById('formPayout');
    if (formPayout) {
        formPayout.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = formPayout.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            const formData = new FormData(formPayout);
            const payload = Object.fromEntries(formData.entries());

            try {
                const res = await fetch(`/api/campaigns/${campaignId}/payouts`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert('Demande de retrait initiée avec succès.');
                    location.reload();
                } else {
                    alert(data.message || 'Erreur lors de la demande de retrait');
                }
            } catch (err) {
                alert('Une erreur réseau est survenue.');
            } finally {
                submitBtn.disabled = false;
            }
        });
    }
});