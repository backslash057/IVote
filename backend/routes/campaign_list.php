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
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IVote - Campagnes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen pt-20 pb-16 font-sans">

    <!-- Fixed Header / Navigation -->
    <header class="fixed top-0 inset-x-0 z-50 h-16 border-b border-slate-800 bg-slate-950/90 backdrop-blur-xl">
        <div class="mx-auto flex h-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="/" class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-md shadow-emerald-600/30 transition-transform hover:scale-105">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-base font-bold tracking-tight text-white">IVote</span>
                    <span class="hidden text-[10px] text-slate-400 sm:block">Vote & Paiement Mobile Money</span>
                </div>
            </a>
        </div>
    </header>

    <!-- Main Content Wrapper -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        
        <!-- Hero & Métriques rapides -->
        <section class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <h1 class="text-2xl sm:text-4xl font-extrabold text-white">Toutes les Campagnes de Vote</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-2 max-w-2xl">
                    Exprimez votre voix en toute transparence avec un règlement direct par Orange Money ou MTN Mobile Money.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-3 shrink-0 w-full md:w-auto">
                <div class="bg-slate-950/50 border border-slate-800 rounded-2xl p-3 text-center">
                    <span class="text-xl font-black text-emerald-400 font-mono block"><?= $activeCount ?></span>
                    <span class="text-[11px] text-slate-400">Campagnes actives</span>
                </div>
                <div class="bg-slate-950/50 border border-slate-800 rounded-2xl p-3 text-center">
                    <span class="text-xl font-black text-amber-400 font-mono block"><?= count($campaigns) ?></span>
                    <span class="text-[11px] text-slate-400">Campagnes</span>
                </div>
                <div class="bg-slate-950/50 border border-slate-800 rounded-2xl p-3 text-center">
                    <span class="text-xl font-black text-white font-mono block"><?= number_format($totalVotesAcrossAll) ?></span>
                    <span class="text-[11px] text-slate-400">Total Votes</span>
                </div>
            </div>
        </section>

        <!-- Affichage des campagnes ou cas vide -->
        <?php if (empty($campaigns)): ?>
            <section class="text-center py-16 bg-slate-900 border border-slate-800 rounded-3xl p-8 max-w-md mx-auto space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-slate-800 flex items-center justify-center mx-auto text-slate-400 font-bold text-xl">
                    📁
                </div>
                <h2 class="text-base font-bold text-white">Aucune campagne disponible</h2>
                <p class="text-xs text-slate-400">
                    Il n'y a aucune élection ou concours enregistré pour le moment.
                </p>
            </section>
        <?php else: ?>
            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($campaigns as $camp): ?>
                    <?php 
                        $isActive = ($camp['status'] ?? '') === 'active';
                        $isScheduled = ($camp['status'] ?? '') === 'scheduled';
                        $campaignId = (int) ($camp['campaign_id'] ?? 0);
                        $imageUrl = $camp['image_url'] ?? 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=1400&auto=format&fit=crop&q=80';
                        $category = $camp['categories'] ?? 'Campagne de vote';
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

                        <!-- Contenu -->
                        <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                            <div class="space-y-2">
                                <div class="text-xs text-emerald-400 select-none"> <?= htmlspecialchars($organization, ENT_QUOTES, 'UTF-8') ?></div>
                                <h2 class="text-lg font-bold text-white line-clamp-1"><?= htmlspecialchars($camp['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="text-xs text-slate-400 line-clamp-2"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>

                            <div class="pt-3 border-t border-slate-800/80 space-y-3">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-300 font-medium">
                                        <?= $candidateCount ?> candidat<?= $candidateCount > 1 ? 's' : '' ?>
                                    </span>

                                    <span class="text-slate-300 font-mono font-semibold">
                                        🗳️ <?= number_format($camp['totalVotes'] ?? 0) ?> votes
                                    </span>
                                </div>

                                <a href="/campaigns/<?= $campaignId ?>" 
                                   class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-center block transition <?= $isActive ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-200' ?>">
                                    <?= $isActive ? 'Accéder au Vote en Direct →' : 'Consulter les Détails ↗' ?>
                                </a>
                            </div>
                        </div>

                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

    </main>

    <!-- Footer Component (footer.php) -->
    <footer class="mt-auto border-t border-slate-800 bg-slate-900/60 pt-12 pb-8 text-slate-400">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
                
                <!-- Marque & Description -->
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

                <!-- Navigation -->
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-white">Navigation</h3>
                    <ul class="mt-4 space-y-2 text-xs">
                        <li><a href="/" class="transition-colors hover:text-emerald-400">Accueil</a></li>
                        <li><a href="/campaigns" class="transition-colors hover:text-emerald-400">Explorer les campagnes</a></li>
                        <li><a href="/login" class="transition-colors hover:text-emerald-400">Espace organisateur</a></li>
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
                        <li><a href="#" class="transition-colors hover:text-emerald-400">Centre d'aide</a></li>
                        <li><a href="#" class="transition-colors hover:text-emerald-400">Contact</a></li>
                        <li><span class="text-emerald-400">support@ivote.com</span></li>
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