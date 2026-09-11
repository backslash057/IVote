document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('createCampaignForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnSpinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');

    const errorBox = document.getElementById('formErrorMessage');
    const successBox = document.getElementById('formSuccessMessage');

    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        errorBox.classList.add('hidden');
        successBox.classList.add('hidden');

        errorBox.textContent = '';
        successBox.textContent = '';

        submitBtn.disabled = true;
        btnSpinner.classList.remove('hidden');
        btnText.textContent = 'Création en cours...';

        const formData = new FormData(form);

        try {
            const response = await fetch('/campaigns/new', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (response.ok && result.success && result.campaign_id) {
                successBox.textContent =
                    result.message || 'Campagne créée avec succès !';

                successBox.classList.remove('hidden');

                setTimeout(() => {
                    window.location.href =
                        `/campaigns/${result.campaign_id}/dashboard/`;
                }, 1000);
            } else {
                throw new Error(
                    result.message ||
                    'Une erreur est survenue lors de la création.'
                );
            }
        } catch (error) {
            errorBox.textContent =
                error instanceof SyntaxError
                    ? 'Une erreur est survenue lors de la création.'
                    : error.message;

            errorBox.classList.remove('hidden');
        } finally {
            submitBtn.disabled = false;
            btnSpinner.classList.add('hidden');
            btnText.textContent = 'Créer la Campagne';
        }
    });
});