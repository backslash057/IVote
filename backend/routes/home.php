<?php
require_once $_SERVER["DOCUMENT_ROOT"] . '/controllers/campaignController.php';

try {
    $campaignController = new CampaignController();
    $sortedCampaigns = $campaignController->getActivePopularCampaigns(3);
}
catch (PDOException $e) {
    error_log($e->getMessage());
    throw new Exception("Error loading page");
}
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-950 text-slate-100 scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IVote - Accueil</title>
    <!-- Ingestion de Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-full flex-col bg-slate-950 text-slate-100 antialiased">

    <!-- Navbar Component (navbar.php) -->
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
            <a href="/dashboard" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-emerald-500">Espace Organisateur</a>
		</div>
	</header>

    <!-- Zone de contenu principal -->
    <main class="flex-1 space-y-16 pt-24 pb-16">

        <!-- Section Hero -->
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 px-6 py-12 shadow-2xl sm:px-10 lg:px-16 lg:py-16">
                <div class="absolute right-0 top-0 h-full w-1/2 bg-emerald-500/5"></div>
                <div class="relative max-w-2xl">
                    <div class="mb-5 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                        Le vote qui vous rassemble
                    </div>
                    <h1 class="font-display text-4xl font-bold leading-tight text-white sm:text-6xl">Découvrez les campagnes qui font vibrer le public.</h1>
                    <p class="mt-5 max-w-xl text-sm leading-relaxed text-slate-300 sm:text-base">Votez pour vos favoris, soutenez les talents émergents et suivez les résultats en temps réel sur IVote.</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="campaigns" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-xs font-bold text-white shadow-lg shadow-emerald-600/20 transition-colors hover:bg-emerald-500">
                            Explorer les campagnes 
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                        <a href="/campaigns/new" class="inline-flex items-center gap-2 rounded-xl border border-slate-700 px-5 py-3 text-xs font-bold text-slate-200 transition-colors hover:border-emerald-500 hover:text-white">Organiser une campagne</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Campagnes Populaires (Affichée uniquement si $sortedCampaigns n'est pas vide) -->
        <?php if (!empty($sortedCampaigns)): ?>
            <section id="campaigns" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-400">À l'affiche</p>
                        <h2 class="mt-2 font-display text-3xl font-bold text-white">Campagnes populaires</h2>
                        <p class="mt-2 text-sm text-slate-400">Les votes les plus suivis du moment.</p>
                    </div>
                    <span class="hidden items-center gap-2 text-xs text-slate-500 sm:flex">
                        <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Classement en direct
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($sortedCampaigns as $campaign): ?>
                        <?php
                            $campaignId = (int) ($campaign['campaign_id'] ?? 0);
                            $imageUrl = $campaign['image_url'] ?? 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=1400&auto=format&fit=crop&q=80';
                            $category = $campaign['categories'] ?? 'Campagne de vote';
                            $organization = $campaign['organizer_name'] ?? 'Organisateur IVote';
                            $description = $campaign['description'] ?? 'Participez à cette campagne de vote.';
                            $candidateCount = (int) ($campaign['candidate_count'] ?? 0);
                        ?>
                        <article class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-lg transition-all hover:border-slate-700">
                            <div class="relative h-48 w-full bg-slate-950">
                                <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($campaign['title'], ENT_QUOTES, 'UTF-8') ?>" class="h-full w-full object-cover opacity-85">
                                <div class="absolute inset-x-3 top-3 flex items-center justify-between">
                                    <span class="rounded-full bg-emerald-600 px-2.5 py-1 text-[11px] font-bold text-white">• En Direct</span>
                                </div>
                            </div>

                            <div class="flex flex-1 flex-col justify-between space-y-4 p-5">
                                <div class="space-y-2">
                                    <div class="text-xs text-emerald-400"><?= htmlspecialchars($organization, ENT_QUOTES, 'UTF-8') ?></div>
                                    <h3 class="line-clamp-1 text-lg font-bold text-white"><?= htmlspecialchars($campaign['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <p class="line-clamp-2 text-xs text-slate-400"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
                                </div>

                                <div class="space-y-3 border-t border-slate-800/80 pt-3">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-medium text-slate-300">
                                            <?= $candidateCount ?> candidat<?= $candidateCount > 1 ? 's' : '' ?>
                                        </span>
                                        <span class="font-mono font-semibold text-slate-300">
                                            🗳️ <?= number_format($campaign['totalVotes'] ?? 0) ?> votes
                                        </span>
                                    </div>
                                    <a href="/campaigns/<?= $campaignId ?>" class="block w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-center text-xs font-bold text-white transition hover:bg-emerald-500">
                                        Accéder au Vote en Direct →
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Section Comment ça marche -->
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6 sm:p-8">
                <div class="mx-auto mb-8 max-w-xl text-center">
                    <h3 class="font-display text-lg font-bold text-white">
                        Comment voter en 3 étapes simples ?
                    </h3>
                    <p class="mt-1 text-xs text-slate-400">
                        Aucun compte requis. Paiement direct et instantané via votre compte Mobile Money.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <!-- Étape 1 -->
                    <div class="space-y-2 rounded-2xl border border-slate-800 bg-slate-950 p-4">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600/20 text-sm font-bold text-emerald-400">
                            1
                        </div>
                        <h4 class="text-sm font-bold text-white">Choisissez votre Candidat</h4>
                        <p class="text-xs leading-relaxed text-slate-400">
                            Consultez le profil de votre candidat favori et sélectionnez le pack de votes de votre choix (1x, 5x, 10x, 50x...).
                        </p>
                    </div>

                    <!-- Étape 2 -->
                    <div class="space-y-2 rounded-2xl border border-slate-800 bg-slate-950 p-4">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600/20 text-sm font-bold text-emerald-400">
                            2
                        </div>
                        <h4 class="text-sm font-bold text-white">Validez par Mobile Money</h4>
                        <p class="text-xs leading-relaxed text-slate-400">
                            Renseignez votre numéro Orange Money ou MTN MoMo et confirmez le push USSD sur votre téléphone avec votre code secret.
                        </p>
                    </div>

                    <!-- Étape 3 -->
                    <div class="space-y-2 rounded-2xl border border-slate-800 bg-slate-950 p-4">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600/20 text-sm font-bold text-emerald-400">
                            3
                        </div>
                        <h4 class="text-sm font-bold text-white">Suffrages Comptabilisés</h4>
                        <p class="text-xs leading-relaxed text-slate-400">
                            Vos votes sont instantanément crédités au compteur officiel avec reçu numérique téléchargeable et partageable.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Espace Organisateur -->
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-start justify-between gap-6 rounded-3xl border border-slate-800 bg-slate-900 p-6 sm:flex-row sm:items-center sm:p-8">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600/15 text-emerald-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-white">Vous organisez un concours ou une élection ?</h2>
                        <p class="mt-1 max-w-xl text-xs leading-relaxed text-slate-400">Créez votre campagne de vote monétisée et suivez les paiements en direct.</p>
                    </div>
                </div>
                <a href="login.php" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-xs font-bold text-white transition-colors hover:bg-emerald-500">
                    Espace organisateur 
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        </section>

    </main>

    <!-- Footer Component (footer.php) -->
    <footer class="mt-auto border-t border-slate-800 bg-slate-900/60 pt-12 pb-8 text-slate-400">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
                
                <!-- Marque & Description -->
                <div class="space-y-4 md:col-span-1">
                    <div class="font-display flex items-center gap-2 text-xl font-bold text-white">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-white">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>    
                        </span>
                        IVote
                    </div>
                    <p class="text-xs leading-relaxed text-slate-400">
                        La plateforme moderne pour vos concours, élections et votes en ligne sécurisés et en temps réel.
                    </p>
                </div>

                <!-- Navigation -->
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-white">Navigation</h3>
                    <ul class="mt-4 space-y-2 text-xs">
                        <li><a href="/" class="transition-colors hover:text-emerald-400">Accueil</a></li>
                        <li><a href="/campaigns" class="transition-colors hover:text-emerald-400">Explorer les campagnes</a></li>
                        <li><a href="/dashboard" class="transition-colors hover:text-emerald-400">Espace organisateur</a></li>
                    </ul>
                </div>

                <!-- Liens Légaux -->
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-white">Légal</h3>
                    <ul class="mt-4 space-y-2 text-xs">
                        <li><a href="#" class="transition-colors hover:text-emerald-400">Conditions d'utilisation</a></li>
                        <li><a href="#" class="transition-colors hover:text-emerald-400">Politique de confidentialité</a></li>
                        <li><a href="#" class="transition-colors hover:text-emerald-400">Mentions légales</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-white">Support</h3>
                    <ul class="mt-4 space-y-2 text-xs">
                        <li><a href="mailto:support@ivote.com" class="text-emerald-400">support@ivote.com</a></li>
                    </ul>
                </div>

            </div>

            <!-- Bas de page -->
            <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-slate-800/80 pt-6 text-xs text-slate-500 sm:flex-row">
                <p>&copy; <?= date('Y') ?> IVote. Tous droits réservés.</p>
                <p>Votez en toute sécurité.</p>
            </div>
        </div>
    </footer>

</body>
</html>