<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/campaignController.php';

// Load campaigns and their aggregates from the database.
$controller = new CampaignController();
$campaigns = $controller->getCampaigns();

// Calcul des métriques globales
$activeCount = count(array_filter($campaigns, fn($c) => ($c['status'] ?? '') === 'active'));
$totalVotesAcrossAll = array_reduce($campaigns, fn($acc, $c) => $acc + ($c['totalVotes'] ?? 0), 0);
?>
<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IVote - Campagnes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col font-sans">

    <!-- Fixed Header -->
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

    <!-- Main Content Wrapper -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-12 space-y-8">
        
        <!-- Hero & Métriques -->
        <section class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <h1 class="text-2xl sm:text-4xl font-extrabold text-white">Toutes les Campagnes de Vote</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-2 max-w-2xl">
                    Exprimez votre voix en toute transparence avec un règlement direct par Orange Money ou MTN Mobile Money.
                </p>
            </div>

            <?php if (!empty($campaigns)): ?>
                <div class="grid grid-cols-3 gap-3 w-full md:w-auto">
                    <div class="bg-slate-950/50 border border-slate-800 rounded-2xl p-3 text-center">
                        <span class="text-xl font-black text-emerald-400 font-mono block"><?= $activeCount ?></span>
                        <span class="text-[11px] text-slate-400">Actives</span>
                    </div>
                    <div class="bg-slate-950/50 border border-slate-800 rounded-2xl p-3 text-center">
                        <span class="text-xl font-black text-amber-400 font-mono block"><?= count($campaigns) ?></span>
                        <span class="text-[11px] text-slate-400">Total</span>
                    </div>
                    <div class="bg-slate-950/50 border border-slate-800 rounded-2xl p-3 text-center">
                        <span class="text-xl font-black text-white font-mono block"><?= number_format($totalVotesAcrossAll) ?></span>
                        <span class="text-[11px] text-slate-400">Votes</span>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- Affichage des campagnes ou état vide -->
        <?php if (empty($campaigns)): ?>
            <div class="bg-slate-900/40 border border-slate-800 rounded-2xl py-16 px-6 text-center max-w-md mx-auto flex flex-col items-center gap-4">
                <svg class="w-10 h-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M11.636 6A13 13 0 0 0 19.4 3.2 1 1 0 0 1 21 4v11.344"/>
                    <path d="M14.378 14.357A13 13 0 0 0 11 14H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h1"/>
                    <path d="m2 2 20 20"/>
                    <path d="M6 14a12 12 0 0 0 2.4 7.2 2 2 0 0 0 3.2-2.4A8 8 0 0 1 10 14"/>
                    <path d="M8 8v6"/>
                </svg>
                <div>
                    <h3 class="font-bold text-slate-200">Aucune campagne</h3>
                    <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">
                        Il n'y a aucune élection ou concours disponible pour le moment. Lancez votre propre campagne de vote en quelques clics.
                    </p>
                </div>
                <a href="/campaigns/new" class="mt-2 inline-flex items-center justify-center rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2 text-xs font-semibold text-slate-200 transition-colors">
                    Créer une campagne
                </a>
            </div>
        <?php else: ?>
            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($campaigns as $camp): ?>
                    <?php 
                        $isActive = ($camp['status'] ?? '') === 'active';
                        $isScheduled = ($camp['status'] ?? '') === 'scheduled';
                        $campaignId = (int) ($camp['campaign_id'] ?? 0);
                        $imageUrl = $camp['image_url'] ?? 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=1400&auto=format&fit=crop&q=80';
                        $organization = $camp['organizer_name'] ?? 'Organisateur IVote';
                        $description = $camp['description'] ?? 'Participez à cette campagne de vote.';
                        $candidateCount = (int) ($camp['candidate_count'] ?? 0);
                    ?>
                    <article class="bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-3xl overflow-hidden flex flex-col justify-between shadow-lg transition-all">
                        
                        <!-- Bannière & Badges -->
                        <div class="relative h-48 w-full bg-slate-950">
                            <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" 
                                 alt="<?= htmlspecialchars($camp['title'], ENT_QUOTES, 'UTF-8') ?>" 
                                 class="w-full h-full object-cover opacity-85">
                            
                            <div class="absolute top-3 inset-x-3 flex items-center justify-between">
                                <?php if ($isActive): ?>
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-600 text-white text-[11px] font-bold">
                                        • En Direct
                                    </span>
                                <?php elseif ($isScheduled): ?>
                                    <span class="px-2.5 py-1 rounded-full bg-amber-600 text-white text-[11px] font-bold">
                                        ⏰ À Venir
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-full bg-slate-700 text-slate-200 text-[11px] font-semibold">
                                        Terminé
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="p-5 flex-1 flex flex-col justify-between gap-4">
                            <div class="space-y-1.5">
                                <span class="text-xs text-emerald-400 block"><?= htmlspecialchars($organization, ENT_QUOTES, 'UTF-8') ?></span>
                                <h2 class="text-lg font-bold text-white line-clamp-1"><?= htmlspecialchars($camp['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="text-xs text-slate-400 line-clamp-2"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>

                            <div class="pt-3 border-t border-slate-800/80 space-y-3 mt-auto">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-300 font-medium flex items-center gap-1.5">
                                        <!-- SVG Candidats -->
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <?= $candidateCount ?> candidat<?= $candidateCount > 1 ? 's' : '' ?>
                                    </span>

                                    <span class="text-slate-300 font-mono font-semibold flex items-center gap-1.5">
                                        <!-- SVG Votes -->
                                        <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <?= number_format($camp['totalVotes'] ?? 0) ?> votes
                                    </span>
                                </div>

                                <a href="/campaigns/<?= $campaignId ?>" 
                                class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-center inline-flex items-center justify-center gap-2 transition <?= $isActive ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-200' ?>">
                                    <?= $isActive ? 'Accéder au Vote en Direct' : 'Consulter les Détails' ?>
                                    
                                    <?php if ($isActive): ?>
                                        <!-- SVG Flèche (Tableau de bord) -->
                                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M5 12h14"/>
                                            <path d="m12 5 7 7-7 7"/>
                                        </svg>
                                    <?php else: ?>
                                        <!-- SVG Page Publique -->
                                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M15 3h6v6"/><path d="M10 14 21 3"/>
                                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                        </svg>
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>

                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

    </main>

    <!-- Footer Sticky -->
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