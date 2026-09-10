document.addEventListener('DOMContentLoaded', () => {
	// Candidate Search & Category Filters
	const searchInput = document.getElementById('candidate-search');
	const categoryButtons = document.querySelectorAll('.category-filter');
	const categorySections = document.querySelectorAll('.category-section');
	const emptySearch = document.getElementById('empty-search');
	let activeCategory = 'all';

	function applyFilters() {
		if (!emptySearch) return;
		const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
		let visibleCandidates = 0;

		categorySections.forEach((section) => {
			const sectionCategory = (section.dataset.categorySection || '').toLowerCase();
			const categoryMatches = activeCategory === 'all' || sectionCategory === activeCategory;
			let sectionVisibleCandidates = 0;

			section.querySelectorAll('.candidate-card').forEach((candidate) => {
				const searchData = (candidate.dataset.candidateSearch || '').toLowerCase();
				const candidateMatches = query === '' || searchData.includes(query);
				const isVisible = categoryMatches && candidateMatches;
				
				candidate.classList.toggle('hidden', !isVisible);
				if (isVisible) sectionVisibleCandidates += 1;
			});

			const countElem = section.querySelector('.category-count');
			if (countElem) {
				countElem.textContent = `${sectionVisibleCandidates} candidat${sectionVisibleCandidates > 1 ? 's' : ''}`;
			}
			
			section.classList.toggle('hidden', !categoryMatches || sectionVisibleCandidates === 0);
			visibleCandidates += sectionVisibleCandidates;
		});

		emptySearch.classList.toggle('hidden', visibleCandidates !== 0);
	}

	categoryButtons.forEach((button) => {
		button.addEventListener('click', () => {
			activeCategory = (button.dataset.category || '').toLowerCase();
			categoryButtons.forEach((item) => {
				const isActive = item === button;
				item.classList.toggle('bg-emerald-600', isActive);
				item.classList.toggle('text-white', isActive);
				item.classList.toggle('border', !isActive);
				item.classList.toggle('border-slate-800', !isActive);
				item.classList.toggle('bg-slate-900', !isActive);
				item.classList.toggle('text-slate-400', !isActive);
			});
			applyFilters();
		});
	});

	if (searchInput) {
		searchInput.addEventListener('input', applyFilters);
	}

	// Vote Modal & Transaction Workflow
	const voteModal = document.getElementById('vote-modal');
	const voteForm = document.getElementById('vote-form');
	const voteCandidateName = document.getElementById('vote-candidate-name');
	const voteCandidateInitial = document.getElementById('vote-candidate-initial');
	const voteCandidateNumber = document.getElementById('vote-candidate-number');
	const voteError = document.getElementById('vote-error');
	const voteSuccess = document.getElementById('vote-success');
	const voteFields = document.getElementById('vote-fields');
	const voteLoading = document.getElementById('vote-loading');
	const voteUssd = document.getElementById('vote-ussd');
	const voteOrderSummary = document.getElementById('vote-order-summary');
	const submitVote = document.getElementById('submit-vote');
	const customVotes = document.getElementById('custom-votes');
	const customVotesPanel = document.getElementById('custom-votes-panel');
	const recommendedPackages = document.getElementById('recommended-packages');
	const toggleCustomVotes = document.getElementById('toggle-custom-votes');
	const voteSuccessMessage = document.getElementById('vote-success-message');
	const voteReference = document.getElementById('vote-reference');
	const voteSuccessPrice = document.getElementById('vote-success-price');
	const voteSuccessVoter = document.getElementById('vote-success-voter');
	const voteSuccessVotes = document.getElementById('vote-success-votes');
	const ussdPhone = document.getElementById('ussd-phone');
	const ussdPrice = document.getElementById('ussd-price');
	const ussdProvider = document.getElementById('ussd-provider');
	const ussdVotes = document.getElementById('ussd-votes');
	const voterName = document.getElementById('voter-name');
	const voterPhone = document.getElementById('voter-phone');
	
	const campaignId = Number(document.body.dataset.campaignId || 0);

	let selectedCandidateId = null;
	let selectedCandidateNumberVal = null;
	let selectedVotes = 10;
	let selectedPrice = 900;
	let selectedPayment = 'MOMO';
	let isCustomVotes = false;
	let voteCompleted = false;

	function closeVoteModal() {
		if (!voteModal) return;
		const shouldRefresh = voteCompleted;
		voteCompleted = false;
		voteModal.classList.add('hidden');
		voteModal.classList.remove('flex');
		if (voteError) voteError.classList.add('hidden');
		if (voteSuccess) voteSuccess.classList.add('hidden');
		if (voteFields) voteFields.classList.remove('hidden');
		if (voteLoading) voteLoading.classList.add('hidden');
		if (voteUssd) voteUssd.classList.add('hidden');
		if (voteOrderSummary) voteOrderSummary.classList.remove('hidden');
		if (submitVote) submitVote.classList.remove('hidden');
		if (shouldRefresh) window.location.reload();
	}

	function showVoteStep(step) {
		if (voteFields) voteFields.classList.toggle('hidden', step !== 'fields');
		if (voteLoading) voteLoading.classList.toggle('hidden', step !== 'loading');
		if (voteUssd) voteUssd.classList.toggle('hidden', step !== 'ussd');
		if (voteSuccess) voteSuccess.classList.toggle('hidden', step !== 'success');
		if (voteOrderSummary) voteOrderSummary.classList.toggle('hidden', step !== 'fields');
		if (submitVote) submitVote.classList.toggle('hidden', step !== 'fields');
	}

	function openVoteModal(button) {
		selectedCandidateId = button.dataset.candidateId;
		selectedCandidateNumberVal = button.dataset.candidateNumber;
		const name = button.dataset.candidateName || '';

		if (voteCandidateName) voteCandidateName.textContent = `N° ${selectedCandidateNumberVal} - ${name}`;
		if (voteCandidateInitial) voteCandidateInitial.textContent = name.charAt(0).toUpperCase();
		if (voteCandidateNumber) voteCandidateNumber.textContent = selectedCandidateNumberVal;

		if (voteModal) {
			voteModal.classList.remove('hidden');
			voteModal.classList.add('flex');
		}
		if (customVotes) customVotes.value = selectedVotes;
	}

	document.querySelectorAll('.vote-button').forEach((button) => {
		button.addEventListener('click', () => openVoteModal(button));
	});

	document.querySelectorAll('.vote-quantity').forEach((button) => {
		button.addEventListener('click', () => {
			selectedVotes = Number(button.dataset.votes);
			selectedPrice = Number(button.dataset.price);
			if (customVotes) customVotes.value = selectedVotes;
			
			const formattedPrice = `${selectedPrice.toLocaleString('fr-FR')} FCFA`;
			document.getElementById('vote-total-price').textContent = formattedPrice;
			document.getElementById('submit-price').textContent = formattedPrice;
			document.getElementById('submit-votes').textContent = selectedVotes;

			document.querySelectorAll('.vote-quantity').forEach((item) => {
				item.classList.remove('selected', 'border-emerald-500', 'bg-emerald-600/20', 'ring-2', 'ring-emerald-500/30');
			});
			button.classList.add('selected', 'border-emerald-500', 'bg-emerald-600/20', 'ring-2', 'ring-emerald-500/30');
		});
	});

	if (toggleCustomVotes) {
		toggleCustomVotes.addEventListener('click', () => {
			isCustomVotes = !isCustomVotes;
			if (customVotesPanel) customVotesPanel.classList.toggle('hidden', !isCustomVotes);
			if (recommendedPackages) recommendedPackages.classList.toggle('hidden', isCustomVotes);
			toggleCustomVotes.textContent = isCustomVotes ? 'Packs Recommandés' : 'Personnaliser le nombre';
		});
	}

	if (customVotes) {
		customVotes.addEventListener('input', () => {
			selectedVotes = Math.max(1, Math.min(500, Number(customVotes.value) || 1));
			selectedPrice = Math.round(selectedVotes * 100 * (selectedVotes >= 50 ? 0.8 : selectedVotes >= 20 ? 0.85 : selectedVotes >= 10 ? 0.9 : 1));
			customVotes.value = selectedVotes;
			
			const formattedPrice = `${selectedPrice.toLocaleString('fr-FR')} FCFA`;
			document.getElementById('custom-votes-label').textContent = selectedVotes;
			document.getElementById('vote-total-price').textContent = formattedPrice;
			document.getElementById('submit-price').textContent = formattedPrice;
			document.getElementById('submit-votes').textContent = selectedVotes;
		});
	}

	document.querySelectorAll('.payment-method').forEach((button) => {
		button.addEventListener('click', () => {
			selectedPayment = button.dataset.payment;
			document.querySelectorAll('.payment-method').forEach((item) => item.classList.remove('selected', 'ring-2', 'ring-amber-500/40'));
			button.classList.add('selected', 'ring-2', 'ring-amber-500/40');
		});
	});

	document.getElementById('close-vote-modal')?.addEventListener('click', closeVoteModal);
	document.getElementById('vote-backdrop')?.addEventListener('click', closeVoteModal);

	if (voteForm) {
		voteForm.addEventListener('submit', (event) => {
			event.preventDefault();
			if (voteError) voteError.classList.add('hidden');
			
			if (!voterPhone || voterPhone.value.trim().length < 8) {
				if (voteError) {
					voteError.textContent = 'Veuillez renseigner un numéro Mobile Money valide.';
					voteError.classList.remove('hidden');
				}
				return;
			}

			showVoteStep('loading');
			setTimeout(() => {
				showVoteStep('ussd');
				if (ussdPhone) ussdPhone.textContent = voterPhone.value;
				if (ussdPrice) ussdPrice.textContent = `${selectedPrice.toLocaleString('fr-FR')} FCFA`;
				if (ussdProvider) ussdProvider.textContent = `POPUP ${selectedPayment}`;
				if (ussdVotes) ussdVotes.textContent = selectedVotes;
			}, 700);
		});
	}

	document.getElementById('edit-vote')?.addEventListener('click', () => {
		showVoteStep('fields');
	});

	document.getElementById('confirm-ussd')?.addEventListener('click', async () => {
		showVoteStep('loading');

		try {
			const response = await fetch(`/campaigns/${campaignId}/votes`, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					candidate_id: selectedCandidateId,
					vote_count: selectedVotes,
					payment_method: selectedPayment
				})
			});
			const result = await response.json();

			if (!response.ok || !result.success) {
				throw new Error(result.message || "Le vote n'a pas pu être enregistré.");
			}

			showVoteStep('success');
			voteCompleted = true;
			const voterLabel = (voterName && voterName.value.trim()) || 'Votant anonyme';
			if (voteSuccessVoter) voteSuccessVoter.textContent = voterLabel;
			if (voteSuccessMessage) {
				voteSuccessMessage.textContent = `${voterLabel}, ${result.votes_added} vote${result.votes_added > 1 ? 's' : ''} enregistré${result.votes_added > 1 ? 's' : ''} pour ${voteCandidateName.textContent}.`;
			}
			if (voteReference) voteReference.textContent = `IVT-${Math.floor(10000 + Math.random() * 90000)}`;
			if (voteSuccessVotes) voteSuccessVotes.textContent = `+${result.votes_added} Votes`;
			if (voteSuccessPrice) voteSuccessPrice.textContent = `${selectedPrice.toLocaleString('fr-FR')} FCFA`;
		} catch (error) {
			showVoteStep('ussd');
			if (voteError) {
				voteError.textContent = error instanceof Error ? error.message : "Le vote n'a pas pu être enregistré.";
				voteError.classList.remove('hidden');
			}
		}
	});

	document.getElementById('finish-vote')?.addEventListener('click', closeVoteModal);

	// Share candidate link
	document.querySelectorAll('.copy-link-button').forEach((button) => {
		button.addEventListener('click', async () => {
			const shareUrl = `${window.location.origin}/campaigns/${campaignId}?candidate=${encodeURIComponent(button.dataset.candidateId || '')}`;
			try {
				await navigator.clipboard.writeText(shareUrl);
				const original = button.textContent;
				button.textContent = '✓';
				setTimeout(() => { button.textContent = original; }, 1500);
			} catch {
				window.prompt('Copiez ce lien :', shareUrl);
			}
		});
	});
});