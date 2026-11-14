document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('createCampaignForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnSpinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');
    const errorBox = document.getElementById('formErrorMessage');
    const successBox = document.getElementById('formSuccessMessage');

    const dateClotureInput = document.getElementById('date_cloture');

    // Éléments du widget Image & Crop
    const imageInput = document.getElementById('imageInput');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const previewContainer = document.getElementById('previewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const btnChangeImage = document.getElementById('btnChangeImage');
    const btnRemoveImage = document.getElementById('btnRemoveImage');

    const cropModal = document.getElementById('cropModal');
    const cropImageTarget = document.getElementById('cropImageTarget');
    const btnApplyCrop = document.getElementById('btnApplyCrop');
    const btnCancelCrop = document.getElementById('btnCancelCrop');

    // Barre de progression
    const uploadProgressContainer = document.getElementById('uploadProgressContainer');
    const uploadProgressBar = document.getElementById('uploadProgressBar');
    const uploadPercent = document.getElementById('uploadPercent');
    const uploadStatusText = document.getElementById('uploadStatusText');

    let cropper = null;
    let croppedBlob = null;

    if (!form) return;

    // --- 1. Gestion de la date minimale (+5 minutes) ---
    const updateMinDate = () => {
        const minDate = new Date(Date.now() + 5 * 60 * 1000);
        // Format YYYY-MM-DDTHH:MM local
        const year = minDate.getFullYear();
        const month = String(minDate.getMonth() + 1).padStart(2, '0');
        const day = String(minDate.getDate()).padStart(2, '0');
        const hours = String(minDate.getHours()).padStart(2, '0');
        const minutes = String(minDate.getMinutes()).padStart(2, '0');
        dateClotureInput.min = `${year}-${month}-${day}T${hours}:${minutes}`;
    };
    updateMinDate();

    // --- 2. Gestion du Recadrage et de l'Image ---
    const openFileSelector = () => imageInput.click();
    uploadPlaceholder.addEventListener('click', openFileSelector);
    btnChangeImage.addEventListener('click', openFileSelector);

    btnRemoveImage.addEventListener('click', () => {
        croppedBlob = null;
        imageInput.value = '';
        imagePreview.src = '';
        previewContainer.classList.add('hidden');
        uploadPlaceholder.classList.remove('hidden');
    });

    imageInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            alert('Veuillez sélectionner un fichier image valide.');
            return;
        }

        const reader = new FileReader();
        reader.onload = (ev) => {
            cropImageTarget.src = ev.target.result;
            cropModal.classList.remove('hidden');

            if (cropper) {
                cropper.destroy();
            }

            cropper = new Cropper(cropImageTarget, {
              aspectRatio: 16 / 9,
              viewMode: 2,
              autoCropArea: 1,
              responsive: true
          });
        };
        reader.readAsDataURL(file);
    });

    btnCancelCrop.addEventListener('click', () => {
        cropModal.classList.add('hidden');
        imageInput.value = '';
        if (cropper) cropper.destroy();
    });

    btnApplyCrop.addEventListener('click', () => {
        if (!cropper) return;

        cropper.getCroppedCanvas({
            maxWidth: 1600,
            maxHeight: 900,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        }).toBlob((blob) => {
            croppedBlob = blob;
            imagePreview.src = URL.createObjectURL(blob);
            uploadPlaceholder.classList.add('hidden');
            previewContainer.classList.remove('hidden');

            cropModal.classList.add('hidden');
            cropper.destroy();
            cropper = null;
        }, 'image/webp', 0.88);
    });

    // --- 3. Soumission avec Suivi de Progression (XHR) ---
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        errorBox.classList.add('hidden');
        successBox.classList.add('hidden');
        errorBox.textContent = '';
        successBox.textContent = '';

        // Validation frontend de la date
        if (dateClotureInput.value) {
            const selectedTime = new Date(dateClotureInput.value).getTime();
            const minTime = Date.now() + (5 * 60 * 1000) - 1000;
            if (selectedTime < minTime) {
                errorBox.textContent = "La date de clôture doit être située au moins 5 minutes dans le futur.";
                errorBox.classList.remove('hidden');
                return;
            }
        }

        const formData = new FormData(form);

        // Remplacer le fichier brut par le blob recadré
        if (croppedBlob) {
            formData.set('image', croppedBlob, 'banner.webp');
        } else {
            formData.delete('image');
        }

        submitBtn.disabled = true;
        btnSpinner.classList.remove('hidden');
        btnText.textContent = 'Envoi en cours...';

        uploadProgressContainer.classList.remove('hidden');
        uploadProgressBar.style.width = '0%';
        uploadPercent.textContent = '0%';
        uploadStatusText.textContent = 'Téléversement des données et de l\'image...';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/campaigns', true);
        xhr.setRequestHeader('Accept', 'application/json');

        // Barre de progression réelle
        xhr.upload.onprogress = (event) => {
            if (event.lengthComputable) {
                const percentComplete = Math.round((event.loaded / event.total) * 100);
                uploadProgressBar.style.width = `${percentComplete}%`;
                uploadPercent.textContent = `${percentComplete}%`;
                if (percentComplete >= 100) {
                    uploadStatusText.textContent = 'Traitement sur le serveur...';
                }
            }
        };

        xhr.onload = () => {
            submitBtn.disabled = false;
            btnSpinner.classList.add('hidden');
            btnText.textContent = 'Créer la Campagne';

            try {
                const result = JSON.parse(xhr.responseText);

                if (xhr.status >= 200 && xhr.status < 300 && result.success && result.campaign_id && result.campaign_code) {
                    uploadStatusText.textContent = 'Téléversement terminé avec succès !';
                    successBox.textContent = result.message || 'Campagne créée avec succès ! Redirection...';
                    successBox.classList.remove('hidden');

                    setTimeout(() => {
                        window.location.href = `/campaigns/${encodeURIComponent(result.campaign_code)}`;
                    }, 1000);
                } else {
                    throw new Error(result.message || 'Une erreur est survenue lors de la création.');
                }
            } catch (err) {
                errorBox.textContent = err.message || 'Une erreur inattendue est survenue.';
                errorBox.classList.remove('hidden');
                uploadProgressContainer.classList.add('hidden');
            }
        };

        xhr.onerror = () => {
            submitBtn.disabled = false;
            btnSpinner.classList.add('hidden');
            btnText.textContent = 'Créer la Campagne';
            uploadProgressContainer.classList.add('hidden');
            errorBox.textContent = 'Erreur réseau ou perte de connexion lors de l\'envoi.';
            errorBox.classList.remove('hidden');
        };

        xhr.send(formData);
    });
});