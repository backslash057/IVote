<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/campaignController.php';

$campaignIdValue = $_GET['campaign_id'] ?? $_GET['id'] ?? null;
$campaignId = filter_var($campaignIdValue, FILTER_VALIDATE_INT, [
	'options' => ['min_range' => 1]
]);

$campaign = $campaignId ? (new CampaignController())->getCampaignDetails($campaignId) : null;

if (!$campaign) {
	http_response_code(404);
}

$escape = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$isActive = $campaign && ($campaign['date_cloture'] === null || strtotime($campaign['date_cloture']) >= time());
$categories = $campaign['categories'] ?? [];
$totalVotes = (int) ($campaign['totalVotes'] ?? 0);
$candidateCount = (int) ($campaign['candidateCount'] ?? 0);
?>
<!DOCTYPE html>
<html lang="fr" class="bg-slate-950 text-slate-100">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= $campaign ? $escape($campaign['title']) : 'Campagne introuvable' ?> - IVote</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen flex-col bg-slate-950 text-slate-100 antialiased" data-campaign-id="<?= (int) ($campaign['campaign_id'] ?? 0) ?>">

	<!-- Navigation Header -->
	<header class="fixed top-0 left-0 right-0 z-40 border-b border-slate-800 bg-slate-950/90 backdrop-blur-xl transition-all">
		<div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
			<div class="flex items-center gap-3 sm:gap-6">
				<a href="/" class="group flex items-center gap-2.5 text-left">
					<div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-md shadow-emerald-600/30 transition-transform group-hover:scale-105">
						<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
						</svg>
					</div>
					<div>
						<span class="font-display flex items-center gap-1.5 text-base font-bold tracking-tight text-white">IVote</span>
						<span class="hidden text-[10px] text-slate-400 sm:block">Vote & Paiement Mobile Money</span>
					</div>
				</a>
			</div>
			<div class="flex items-center gap-3">
				<a href="/campaigns" class="text-xs font-semibold text-slate-300 hover:text-white transition-colors">Campagnes</a>
				<a href="/login" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-emerald-500">Espace Organisateur</a>
			</div>
		</div>
	</header>

	<!-- Main Page Content -->
	<main class="mx-auto w-full max-w-7xl flex-1 space-y-12 px-4 pb-16 pt-24 sm:px-6 lg:px-8">
		<?php if (!$campaign): ?>
			<section class="flex min-h-[60vh] items-center justify-center text-center">
				<div>
					<p class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Campagne introuvable</p>
					<h1 class="mt-3 text-3xl font-bold text-white">Cette campagne n'existe pas.</h1>
					<p class="mt-2 text-sm text-slate-400">L'identifiant demandé ne correspond à aucune campagne enregistrée.</p>
					<a href="/campaigns" class="mt-6 inline-flex rounded-xl bg-emerald-600 px-5 py-3 text-xs font-bold text-white hover:bg-emerald-500">Retour aux campagnes</a>
				</div>
			</section>
		<?php else: ?>
			<!-- Campaign Hero Section -->
			<section class="relative overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl">
				<div class="h-64 w-full bg-slate-800 sm:h-80">
					<?php if (!empty($campaign['image_url'])): ?>
						<img src="<?= $escape($campaign['image_url']) ?>" alt="<?= $escape($campaign['title']) ?>" class="h-full w-full object-cover opacity-70">
					<?php else: ?>
						<div class="flex h-full items-center justify-center text-sm text-slate-500">Aucune image disponible</div>
					<?php endif; ?>
				</div>
				<div class="border-t border-slate-800 bg-slate-950/90 p-6 sm:p-10">
					<div class="flex flex-col justify-between gap-8 lg:flex-row lg:items-end">
						<div class="max-w-3xl space-y-3">
							<span class="inline-flex rounded-full px-3 py-1 text-[11px] font-bold <?= $isActive ? 'bg-emerald-600 text-white' : 'bg-slate-700 text-slate-200' ?>">
								<?= $isActive ? '• En direct' : 'Campagne terminée' ?>
							</span>
							<h1 class="text-3xl font-extrabold leading-tight text-white sm:text-5xl"><?= $escape($campaign['title']) ?></h1>
							<p class="text-sm leading-relaxed text-slate-300 sm:text-base"><?= $escape($campaign['description']) ?></p>
							<div class="flex flex-wrap gap-4 pt-2 text-xs text-slate-300">
								<span>Organisé par : <strong class="text-emerald-400"><?= $escape($campaign['organizer_name'] ?? 'Organisateur IVote') ?></strong></span>
								<?php if (!empty($campaign['date_cloture'])): ?>
									<span>Clôture : <strong class="text-white"><?= $escape(date('d/m/Y à H:i', strtotime($campaign['date_cloture']))) ?></strong></span>
								<?php endif; ?>
							</div>
						</div>
						<div class="grid grid-cols-2 gap-3 text-center">
							<div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
								<div class="font-mono text-xl font-bold text-emerald-400"><?= number_format($totalVotes) ?></div>
								<div class="text-[10px] uppercase text-slate-400">Votes</div>
							</div>
							<div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
								<div class="font-mono text-xl font-bold text-white"><?= $candidateCount ?></div>
								<div class="text-[10px] uppercase text-slate-400">Candidats</div>
							</div>
						</div>
					</div>
				</div>
			</section>

			<!-- Filter Bar -->
			<section class="space-y-4">
				<div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
					<div class="flex gap-2 overflow-x-auto pb-2">
						<button type="button" data-category="all" class="category-filter whitespace-nowrap rounded-2xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white">Toutes les catégories</button>
						<?php foreach ($categories as $category): ?>
							<button type="button" data-category="<?= $escape($category['name']) ?>" class="category-filter whitespace-nowrap rounded-2xl border border-slate-800 bg-slate-900 px-4 py-2.5 text-xs font-bold text-slate-400 hover:text-white">
								<?= $escape($category['name']) ?> (<?= count($category['candidates']) ?>)
							</button>
						<?php endforeach; ?>
					</div>
					<div class="relative w-full md:w-80">
						<input id="candidate-search" type="search" placeholder="Rechercher un candidat..." class="w-full rounded-2xl border border-slate-800 bg-slate-900 px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none">
					</div>
				</div>
			</section>

			<!-- Candidate Categories Section -->
			<section id="categories-list" class="space-y-12">
				<?php foreach ($categories as $category): ?>
					<div class="category-section space-y-6" data-category-section="<?= $escape($category['name']) ?>">
						<div class="flex items-center justify-between border-b border-slate-800 pb-4">
							<h2 class="text-xl font-bold text-white sm:text-2xl">Catégorie : <?= $escape($category['name']) ?></h2>
							<span class="category-count text-xs font-semibold text-slate-400"><?= count($category['candidates']) ?> candidat<?= count($category['candidates']) > 1 ? 's' : '' ?></span>
						</div>
						<?php if (empty($category['candidates'])): ?>
							<div class="rounded-2xl border border-slate-800 bg-slate-950/60 py-8 text-center text-xs text-slate-500">Aucun candidat enregistré dans cette catégorie.</div>
						<?php else: ?>
							<div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
								<?php foreach ($category['candidates'] as $candidate): ?>
									<article class="candidate-card group relative flex flex-col overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-xl transition-all duration-300 hover:border-emerald-500/40" data-candidate-id="<?= (int) $candidate['id'] ?>" data-candidate-name="<?= $escape($candidate['name']) ?>" data-candidate-search="<?= $escape(implode(' ', [$candidate['name'], $candidate['theme'], $candidate['description'], $candidate['bio']])) ?>">
										<div class="relative flex h-64 w-full items-center justify-center overflow-hidden bg-white sm:h-72">
											<span class="text-5xl font-black text-slate-200"><?= $escape(strtoupper(substr($candidate['name'], 0, 1))) ?></span>
											<div class="absolute left-3 top-3 flex items-center gap-1.5 rounded-full border border-slate-700 bg-slate-950/90 px-3 py-1 text-xs font-bold text-white shadow-md">
												<span class="h-2 w-2 rounded-full bg-emerald-400"></span>
												N° <?= (int) $candidate['number'] ?>
											</div>
										</div>

										<div class="flex flex-1 flex-col justify-between space-y-4 p-5">
											<div>
												<div class="flex items-center justify-between gap-3">
													<h3 class="font-display text-lg font-bold text-white transition-colors group-hover:text-emerald-400"><?= $escape($candidate['name']) ?></h3>
													<?php if ($candidate['age'] !== null): ?><span class="text-xs font-medium text-slate-400"><?= (int) $candidate['age'] ?> ans</span><?php endif; ?>
												</div>
												<p class="mt-0.5 text-xs text-slate-400"><?= $escape($candidate['theme']) ?></p>
												<?php if (!empty($candidate['description'])): ?><p class="mt-2.5 line-clamp-2 text-xs leading-relaxed text-slate-400"><?= $escape($candidate['description']) ?></p><?php endif; ?>
												<?php if (!empty($candidate['bio'])): ?><p class="mt-2.5 line-clamp-3 rounded-xl border border-slate-800 bg-slate-950 p-2.5 text-xs italic leading-relaxed text-slate-300">&quot;<?= $escape($candidate['bio']) ?>&quot;</p><?php endif; ?>
											</div>

											<div class="space-y-1.5 border-t border-slate-800 pt-2">
												<div class="flex items-center justify-between text-xs">
													<span class="font-medium text-slate-400">Suffrages obtenus</span>
													<span class="font-mono font-bold text-emerald-400"><?= number_format($candidate['votes']) ?> votes (<?= $candidate['percentage'] ?>%)</span>
												</div>
												<div class="h-2 w-full overflow-hidden rounded-full border border-slate-800 bg-slate-950">
													<div class="h-full rounded-full bg-emerald-500" style="width: <?= min(100, (float) $candidate['percentage']) ?>%"></div>
												</div>
											</div>

											<div class="flex items-center gap-2 pt-2">
												<button type="button" class="vote-button flex-1 rounded-xl px-4 py-2.5 text-xs font-bold shadow-md transition-all <?= $isActive ? 'cursor-pointer bg-emerald-600 text-white shadow-emerald-600/30 hover:bg-emerald-500' : 'cursor-not-allowed bg-slate-700 text-slate-400 shadow-none' ?>" data-candidate-id="<?= (int) $candidate['id'] ?>" data-candidate-name="<?= $escape($candidate['name']) ?>" data-candidate-number="<?= (int) $candidate['number'] ?>" <?= !$isActive ? 'disabled' : '' ?>><?= $isActive ? 'Voter' : 'Votes clôturés' ?></button>
												<button type="button" class="copy-link-button rounded-xl border border-slate-800 bg-slate-950 p-2.5 text-slate-300 transition-colors hover:bg-slate-800 hover:text-emerald-400" data-candidate-id="<?= (int) $candidate['id'] ?>" data-candidate-name="<?= $escape($candidate['name']) ?>" title="Copier le lien du candidat">⧉</button>
											</div>
										</div>
									</article>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
				<div id="empty-search" class="hidden rounded-3xl border border-slate-800 bg-slate-900 px-6 py-16 text-center">
					<h2 class="text-base font-bold text-white">Aucun candidat trouvé</h2>
					<p class="mt-2 text-xs text-slate-400">Modifiez votre recherche ou réinitialisez le filtre.</p>
				</div>
			</section>

			<!-- 3 Simple Steps Guide -->
			<section class="rounded-3xl border border-slate-800 bg-slate-900 p-6 sm:p-8">
				<div class="mx-auto mb-8 max-w-xl text-center">
					<h3 class="font-display text-lg font-bold text-white">
						Comment voter en 3 étapes simples ?
					</h3>
					<p class="mt-1 text-xs text-slate-400">
						Aucun compte requis. Paiement direct et instantané via votre compte Mobile Money.
					</p>
				</div>

				<div class="grid grid-cols-1 gap-6 md:grid-cols-3">
					<div class="space-y-2 rounded-2xl border border-slate-800 bg-slate-950 p-4">
						<div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600/20 text-sm font-bold text-emerald-400">
							1
						</div>
						<h4 class="text-sm font-bold text-white">Choisissez votre Candidat</h4>
						<p class="text-xs leading-relaxed text-slate-400">
							Consultez le profil de votre candidat favori et sélectionnez le pack de votes de votre choix (1x, 5x, 10x, 50x...).
						</p>
					</div>
					<div class="space-y-2 rounded-2xl border border-slate-800 bg-slate-950 p-4">
						<div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600/20 text-sm font-bold text-emerald-400">
							2
						</div>
						<h4 class="text-sm font-bold text-white">Validez par Mobile Money</h4>
						<p class="text-xs leading-relaxed text-slate-400">
							Renseignez votre numéro Orange Money ou MTN MoMo et confirmez le push USSD sur votre téléphone avec votre code secret.
						</p>
					</div>
					<div class="space-y-2 rounded-2xl border border-slate-800 bg-slate-950 p-4">
						<div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600/20 text-sm font-bold text-emerald-400">
							3
						</div>
						<h4 class="text-sm font-bold text-white">Suffrages Comptabilisés</h4>
						<p class="text-xs leading-relaxed text-slate-400">
							Vos votes sont instantanément crédités au compteur officiel avec reçu numérique vérifiable.
						</p>
					</div>
				</div>
			</section>

			<!-- Call to Action for Organizers -->
			<section class="flex flex-col items-start justify-between gap-6 rounded-3xl border border-slate-800 bg-slate-900 p-6 sm:flex-row sm:items-center sm:p-8">
				<div class="flex items-start gap-4">
					<div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600/15 text-emerald-400">
						<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
					</div>
					<div>
						<h2 class="text-base font-bold text-white">Vous organisez un concours ou une élection ?</h2>
						<p class="mt-1 max-w-xl text-xs leading-relaxed text-slate-400">Créez votre campagne de vote monétisée et suivez les paiements en temps réel.</p>
					</div>
				</div>
				<a href="/login.php" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-xs font-bold text-white transition-colors hover:bg-emerald-500">
					Espace organisateur 
					<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
				</a>
			</section>
		<?php endif; ?>
	</main>

	<!-- Vote Modal -->
	<div id="vote-modal" class="fixed inset-0 z-50 hidden items-end justify-center p-0 sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="vote-modal-title">
		<div id="vote-backdrop" class="fixed inset-0 bg-slate-950/85 backdrop-blur-md"></div>
		<div class="relative z-10 max-h-[92vh] w-full max-w-xl overflow-y-auto rounded-t-3xl border border-slate-700/60 bg-slate-900 p-5 text-slate-100 shadow-2xl shadow-emerald-950/60 transition-all duration-300 sm:rounded-3xl sm:p-7">
			<div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-slate-700 sm:hidden"></div>
			<div class="mb-4 flex items-center justify-between border-b border-slate-800 pb-4">
				<div class="flex items-center gap-3">
					<div class="relative flex h-12 w-12 items-center justify-center rounded-full border-2 border-emerald-500 bg-white text-lg font-black text-slate-200 shadow-md">
						<span id="vote-candidate-initial">?</span>
						<span class="absolute -bottom-1 -right-1 rounded-full border border-slate-900 bg-emerald-600 px-1.5 py-0.5 text-[10px] font-bold text-white">#<span id="vote-candidate-number"></span></span>
					</div>
					<div>
						<div class="flex items-center gap-1 text-xs font-medium uppercase tracking-wide text-emerald-400"><svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4m9-2 1.8 5.4L21 10l-5.2 1.9L14 17l-1.8-5.1L7 10l5.2-1.6L14 3zM6 16v5m-2.5-2.5h5" /></svg> Espace Vote Public Sécurisé</div>
						<h2 id="vote-modal-title" class="text-base font-bold text-white sm:text-lg">Voter pour <span id="vote-candidate-name"></span></h2>
					</div>
				</div>
				<button type="button" id="close-vote-modal" class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-slate-800/80 text-slate-400 transition-colors hover:bg-slate-700 hover:text-white" aria-label="Fermer">×</button>
			</div>

			<form id="vote-form" class="mt-5 space-y-6">
				<div id="vote-fields" class="space-y-6">
					<div>
						<label class="mb-3 flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-slate-300">
							<span class="flex items-center gap-1.5"><svg class="h-3.5 w-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="6" stroke-width="2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 13.5 7 22l5-3 5 3-1.5-8.5" /></svg> 1. Choisissez votre Pack</span>
							<button type="button" id="toggle-custom-votes" class="cursor-pointer text-xs font-medium normal-case text-emerald-400 underline hover:text-emerald-300">Personnaliser le nombre</button>
						</label>
						<div id="recommended-packages" class="grid grid-cols-3 gap-2 sm:gap-2.5">
							<?php foreach ([[1, 100, 0, ''], [5, 500, 0, ''], [10, 900, 10, 'Populaire'], [25, 2125, 15, ''], [50, 4000, 20, 'Top Offre'], [100, 7500, 25, '']] as $package): ?>
								<button type="button" class="vote-quantity relative min-h-[78px] cursor-pointer rounded-xl border <?= $package[0] === 10 ? 'selected border-emerald-500 bg-emerald-600/20 ring-2 ring-emerald-500/30' : 'border-slate-700/60 bg-slate-800/50' ?> p-2.5 text-left text-white transition-all hover:border-slate-600 hover:bg-slate-800 sm:p-3" data-votes="<?= $package[0] ?>" data-price="<?= $package[1] ?>">
									<?php if ($package[3]): ?><span class="absolute -top-2.5 right-2 rounded-full bg-<?= $package[3] === 'Populaire' ? 'amber' : 'emerald' ?>-500 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-slate-950"><?= $package[3] ?></span><?php endif; ?>
									<span class="block font-mono text-base font-extrabold sm:text-lg"><?= $package[0] ?></span>
									<span class="text-[10px] text-slate-400"><?= $package[0] === 1 ? 'Vote' : 'Votes' ?></span>
									<span class="mt-1 block text-xs font-bold text-emerald-300"><?= number_format($package[1], 0, ',', ' ') ?> FCFA</span>
									<?php if ($package[2]): ?><span class="mt-0.5 block text-[10px] font-semibold text-emerald-400">Économisez <?= $package[2] ?>%</span><?php endif; ?>
								</button>
							<?php endforeach; ?>
						</div>
						<div id="custom-votes-panel" class="mt-3 hidden space-y-3 rounded-xl border border-slate-700 bg-slate-800/60 p-4">
							<div class="flex items-center justify-between"><span class="text-sm text-slate-300">Quantité personnalisée</span><span class="font-mono text-xl font-bold text-emerald-400"><span id="custom-votes-label">10</span> Votes</span></div>
							<input id="custom-votes" type="range" min="1" max="500" step="5" value="10" class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-slate-700 accent-emerald-500">
							<div class="flex justify-between text-xs text-slate-400"><span>1 Vote (100 FCFA)</span><span>250 Votes</span><span>500 Votes</span></div>
						</div>
					</div>

					<div>
						<label class="mb-2.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-300"><svg class="h-3.5 w-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="7" y="2" width="10" height="20" rx="2" stroke-width="2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 18h2" /></svg> 2. Mode de paiement</label>
						<div class="grid grid-cols-2 gap-2">
							<button type="button" class="payment-method selected flex cursor-pointer items-center gap-2.5 rounded-xl border border-amber-400 bg-amber-500/20 p-2.5 text-left text-amber-300 ring-2 ring-amber-500/40" data-payment="MOMO"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-400 text-[10px] font-bold text-slate-950">MTN</span><span><strong class="block truncate text-xs text-slate-100">MTN MoMo</strong><span class="font-mono text-[10px] text-slate-400">*126#</span></span></button>
							<button type="button" class="payment-method flex cursor-pointer items-center gap-2.5 rounded-xl border border-orange-500/30 bg-orange-500/10 p-2.5 text-left text-orange-300 hover:border-orange-400" data-payment="OM"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-orange-500 text-[10px] font-bold text-white">OM</span><span><strong class="block truncate text-xs text-slate-100">Orange Money</strong><span class="font-mono text-[10px] text-slate-400">#150#</span></span></button>
						</div>
					</div>

					<div class="space-y-2">
						<label class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-300"><svg class="h-3.5 w-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3 4 6v5c0 5 3.4 8.5 8 10 4.6-1.5 8-5 8-10V6l-8-3z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 12 2 2 4-4" /></svg> 3. Validation Mobile Money</label>
						<div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
							<input id="voter-name" type="text" placeholder="Votre nom ou pseudo (optionnel)" class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-emerald-500 focus:outline-none">
							<input id="voter-phone" type="tel" required placeholder="N° Mobile Money (ex: 699001122)" class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-emerald-500 focus:outline-none">
						</div>
						<input id="cheer-message" type="text" placeholder="Message d'encouragement (optionnel)" class="w-full rounded-xl border border-slate-700/80 bg-slate-800/50 px-3.5 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-emerald-500 focus:outline-none">
					</div>
				</div>

				<p id="vote-error" class="hidden flex items-center gap-2 rounded-xl border border-rose-500/30 bg-rose-500/15 p-2.5 text-xs text-rose-300"></p>
				
				<div id="vote-loading" class="hidden space-y-3 py-6 text-center">
					<div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-emerald-500/30 border-t-emerald-400"></div>
					<p class="text-sm font-semibold text-white">Génération de la session Mobile Money...</p>
					<p class="mx-auto max-w-sm text-xs text-slate-300">Veuillez patienter pendant l'envoi de l'invite USSD sur votre mobile.</p>
				</div>

				<div id="vote-ussd" class="hidden space-y-6 py-6 text-center">
					<div class="relative mx-auto h-16 w-16">
						<div class="absolute inset-0 animate-ping rounded-full border-4 border-emerald-500/20"></div>
						<div class="relative flex h-16 w-16 items-center justify-center rounded-full border-2 border-emerald-500 bg-emerald-600/20"><svg class="h-8 w-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2" stroke-width="2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 18h6" /></svg></div>
					</div>
					<div>
						<div class="mb-2 inline-flex items-center gap-1.5 rounded-full border border-amber-500/30 bg-amber-500/15 px-3 py-1 text-xs font-semibold text-amber-300">Invite USSD Envoyée</div>
						<h4 class="text-lg font-bold text-white">Consultez votre téléphone !</h4>
						<p class="mx-auto mt-1.5 max-w-sm text-xs text-slate-300">Entrez votre code secret PIN Mobile Money pour confirmer <strong id="ussd-price" class="text-emerald-400"></strong>.</p>
					</div>
					<div class="mx-auto max-w-xs space-y-1.5 rounded-2xl border border-slate-700/80 bg-slate-950 p-3.5 text-left shadow-inner">
						<div class="flex items-center justify-between text-[10px] text-slate-400"><span id="ussd-provider" class="font-semibold uppercase text-emerald-400"></span><span>À l'instant</span></div>
						<div class="text-xs font-mono text-slate-200">Autoriser le débit pour <span id="ussd-votes"></span> votes ?</div>
						<div class="text-[11px] font-mono text-amber-400">Entrez votre code secret PIN...</div>
					</div>
					<div class="space-y-2 pt-2">
						<button type="button" id="confirm-ussd" class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/30 hover:bg-emerald-500">Simuler PIN Validé (Confirmer)</button>
						<button type="button" id="edit-vote" class="cursor-pointer text-xs text-slate-400 hover:text-slate-200">Modifier le numéro ou le mode de paiement</button>
					</div>
				</div>

				<div id="vote-success" class="hidden space-y-4 py-5 text-center">
					<div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-emerald-500 bg-emerald-500/20 text-emerald-400"><svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke-width="2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 12 2.5 2.5L16 9" /></svg></div>
					<div>
						<span class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Paiement Validé & Enregistré</span>
						<h3 class="mt-1 text-2xl font-bold text-white">Merci <span id="vote-success-voter">Cher Votant</span> !</h3>
						<p id="vote-success-message" class="mt-1 text-xs text-slate-300"></p>
					</div>
					<div class="rounded-xl border border-slate-800 bg-slate-950 p-4 text-left text-xs font-mono">
						<div class="flex justify-between text-slate-400"><span>Référence Transaction</span><span id="vote-reference" class="text-slate-200"></span></div>
						<div class="mt-2 flex justify-between text-slate-400"><span>Suffrages Ajoutés</span><span id="vote-success-votes" class="text-emerald-400"></span></div>
						<div class="mt-2 flex justify-between border-t border-slate-800/80 pt-2 font-bold text-slate-300"><span>Montant Débité</span><span id="vote-success-price" class="text-sm text-emerald-400"></span></div>
					</div>
					<button type="button" id="finish-vote" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-500">Fermer</button>
				</div>

				<div id="vote-order-summary" class="border-t border-slate-800 pt-3">
					<div class="mb-3 flex items-center justify-between text-xs sm:text-sm"><span class="text-slate-400">Total à payer</span><span id="vote-total-price" class="font-mono text-lg font-bold text-emerald-400 sm:text-xl">900 FCFA</span></div>
					<button id="submit-vote" type="submit" class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/30 transition hover:bg-emerald-500 sm:text-base"><span class="text-amber-300">⚡</span> Payer <span id="submit-price">900 FCFA</span> &amp; Valider <span id="submit-votes">10</span> Votes</button>
					<div class="mt-2 flex items-center justify-center gap-2 text-[11px] text-slate-400"><svg class="h-3 w-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2" stroke-width="2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V8a4 4 0 0 1 8 0v3" /></svg> Transaction chiffrée SSL • Prise en compte immédiate</div>
				</div>
			</form>
		</div>
	</div>

	<!-- Footer -->
	<footer class="mt-auto border-t border-slate-800 bg-slate-900/60 pt-12 pb-8 text-slate-400">
		<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
			<div class="grid grid-cols-1 gap-8 md:grid-cols-4">
				<div class="space-y-4 md:col-span-1">
					<div class="font-display flex items-center gap-2 text-xl font-bold text-white">
						<span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-white">
							<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
						</span>
						IVote
					</div>
					<p class="text-xs leading-relaxed text-slate-400">
						La plateforme moderne pour vos concours, élections et votes en ligne sécurisés et en temps réel.
					</p>
				</div>

				<div>
					<h3 class="text-xs font-semibold uppercase tracking-wider text-white">Navigation</h3>
					<ul class="mt-4 space-y-2 text-xs">
						<li><a href="/" class="transition-colors hover:text-emerald-400">Accueil</a></li>
						<li><a href="/campaigns" class="transition-colors hover:text-emerald-400">Explorer les campagnes</a></li>
						<li><a href="/login.php" class="transition-colors hover:text-emerald-400">Espace organisateur</a></li>
					</ul>
				</div>

				<div>
					<h3 class="text-xs font-semibold uppercase tracking-wider text-white">Légal</h3>
					<ul class="mt-4 space-y-2 text-xs">
						<li><a href="#" class="transition-colors hover:text-emerald-400">Conditions d'utilisation</a></li>
						<li><a href="#" class="transition-colors hover:text-emerald-400">Politique de confidentialité</a></li>
						<li><a href="#" class="transition-colors hover:text-emerald-400">Mentions légales</a></li>
					</ul>
				</div>

				<div>
					<h3 class="text-xs font-semibold uppercase tracking-wider text-white">Support</h3>
					<ul class="mt-4 space-y-2 text-xs">
						<li><a href="#" class="transition-colors hover:text-emerald-400">Centre d'aide</a></li>
						<li><a href="#" class="transition-colors hover:text-emerald-400">Contact</a></li>
						<li><span class="text-emerald-400">support@ivote.com</span></li>
					</ul>
				</div>
			</div>

			<div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-slate-800/80 pt-6 text-xs text-slate-500 sm:flex-row">
				<p>&copy; <?= date('Y') ?> IVote. Tous droits réservés.</p>
				<p>Votez en toute sécurité.</p>
			</div>
		</div>
	</footer>

	<!-- Separate JavaScript File -->
	<script src="/js/campaign-view.js" defer></script>
</body>
</html>