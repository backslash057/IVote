<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/authController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/campaignController.php';

$authController = new AuthController();
$userSession = $authController->checkAuthentification();

if (!$userSession) {
    header('Location: /login');
    exit;
}

$campaignController = new CampaignController();
$campaigns = $campaignController->getCampaignsByOrganizer((int) $userSession['user_id']);

// Calcul des métriques globales
$activeCount = count(array_filter($campaigns, fn($c) => ($c['status'] ?? '') === 'active'));
$totalVotesAcrossAll = array_reduce($campaigns, fn($acc, $c) => $acc + ($c['totalVotes'] ?? 0), 0);
?>
<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IVote - Mes Campagnes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col font-sans">

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

            <div class="flex items-center gap-2">
                <div class="flex items-center gap-2.5 px-2 py-1.5 rounded-full bg-slate-900 border border-slate-800/80 text-xs shadow-inner">
                    <div class="w-6 h-6 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold text-[10px]">
                        <?= strtoupper(substr($userSession['name'] ?? 'U', 0, 2)) ?>
                    </div>
                    <span class="font-semibold text-slate-200"><?= htmlspecialchars($userSession['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <a href="/logout" title="Se déconnecter" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-900 border border-slate-800/80 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 hover:border-rose-500/20 transition-all cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" x2="9" y1="12" y2="12"/>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Wrapper -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-12 space-y-8">
        
        <!-- Hero & Métriques -->
        <section class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div class="space-y-3">
                <h1 class="text-2xl sm:text-4xl font-extrabold text-white">Gestion de vos Campagnes</h1>
                <p class="text-xs sm:text-sm text-slate-400 max-w-xl">
                    Supervisez vos scrutins en temps réel, lancez de nouveaux votes monétisés et suivez la collecte par Mobile Money.
                </p>
                <a href="/campaigns/new" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs sm:text-sm font-bold shadow-md shadow-emerald-600/20 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nouvelle Campagne
                </a>
            </div>

            <!-- Stats Aggregates (Conditionnel si non vide) -->
            <?php if (!empty($campaigns)): ?>
                <div class="grid grid-cols-3 gap-3 shrink-0 w-full md:w-auto">
                    <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 text-center">
                        <span class="text-xl font-black text-emerald-400 font-mono block"><?= $activeCount ?></span>
                        <span class="text-[11px] text-slate-400">Actives</span>
                    </div>
                    <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 text-center">
                        <span class="text-xl font-black text-amber-400 font-mono block"><?= count($campaigns) ?></span>
                        <span class="text-[11px] text-slate-400">Total Créées</span>
                    </div>
                    <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 text-center">
                        <span class="text-xl font-black text-white font-mono block"><?= number_format($totalVotesAcrossAll) ?></span>
                        <span class="text-[11px] text-slate-400">Total Votes</span>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- Affichage des campagnes ou cas vide -->
        <?php if (empty($campaigns)): ?>
            <div class="bg-slate-900/40 border border-slate-800 rounded-2xl py-16 px-6 text-center max-w-md mx-auto flex flex-col items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-slate-800/80 border border-slate-700/50 flex items-center justify-center text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 013 10c0-2.21 1.791-4 4-4 1.202 0 2.281.531 3 1.373M19 19l-4-4m0 0l-4-4m4 4l4-4m-4 4l-4 4" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-200">Aucune campagne créée</h3>
                    <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">
                        Vous n'avez pas encore configuré de campagne de vote dans votre espace organisateur. Lancez votre premier scrutin en quelques clics pour commencer.
                    </p>
                </div>
                <a href="/campaigns/new" class="mt-2 inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-4 py-2.5 text-xs font-bold text-white transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Créer ma première campagne
                </a>
            </div>
        <?php else: ?>
            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($campaigns as $camp): ?>
                    <?php 
                        $isActive = ($camp['status'] ?? '') === 'active';
                        $campaignId = $camp['campaign_id'];
                        $imageUrl = $camp['image_url'] ?? 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=1400';
                        $organization = $camp['organizer_name'] ?? 'Comité Organisateur';
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
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-600 text-white text-[11px] font-bold shadow-md flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> En Direct
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-full bg-slate-700 text-slate-200 text-[11px] font-semibold">
                                        Terminé
                                    </span>
                                <?php endif; ?>

                            </div>
                        </div>

                        <!-- Contenu -->
                        <div class="p-5 flex-1 flex flex-col justify-between gap-4">
                            <div class="space-y-1.5">
                                <span class="text-xs text-emerald-400 block"><?= htmlspecialchars($organization, ENT_QUOTES, 'UTF-8') ?></span>
                                <h2 class="text-lg font-bold text-white line-clamp-1"><?= htmlspecialchars($camp['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="text-xs text-slate-400 line-clamp-2"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>

                            <div class="pt-3 border-t border-slate-800/80 space-y-3 mt-auto">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-300 font-medium flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <?= $candidateCount ?> candidat<?= $candidateCount > 1 ? 's' : '' ?>
                                    </span>

                                    <span class="text-slate-300 font-mono font-semibold flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <?= number_format($camp['totalVotes'] ?? 0) ?> votes
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <a href="/campaigns/<?= $campaignId ?>/dashboard" 
                                       class="inline-flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs font-semibold whitespace-nowrap transition bg-emerald-600 hover:bg-emerald-500 text-white">
                                        Tableau de bord
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M5 12h14"/>
                                            <path d="m12 5 7 7-7 7"/>
                                        </svg>
                                    </a>
                                    <a href="/campaigns/<?= $campaignId ?>" target="_blank" class="inline-flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs font-semibold whitespace-nowrap transition bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700">
                                        Page Publique
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M15 3h6v6"/><path d="M10 14 21 3"/>
                                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>

                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

    </main>

    <!-- Footer Sticky -->
    <footer class="border-t border-slate-800 bg-slate-900/60 pt-10 pb-8 text-slate-400 mt-auto">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
                
                <!-- Marque & Description -->
                <div class="space-y-3">
                    <div class="flex items-center gap-2 text-xl font-bold text-white">
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
                    <ul class="mt-3 space-y-2 text-xs">
                        <li><a href="/" class="transition-colors hover:text-emerald-400">Accueil</a></li>
                        <li><a href="/campaigns" class="transition-colors hover:text-emerald-400">Explorer les campagnes</a></li>
                        <li><a href="/login" class="transition-colors hover:text-emerald-400">Espace organisateur</a></li>
                    </ul>
                </div>

                <!-- Liens Légaux -->
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-white">Légal</h3>
                    <ul class="mt-3 space-y-2 text-xs">
                        <li><a href="#" class="transition-colors hover:text-emerald-400">Conditions d'utilisation</a></li>
                        <li><a href="#" class="transition-colors hover:text-emerald-400">Politique de confidentialité</a></li>
                        <li><a href="#" class="transition-colors hover:text-emerald-400">Mentions légales</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-white">Support</h3>
                    <ul class="mt-3 space-y-2 text-xs">
                        <li><a href="mailto:support@ivote.com" class="text-emerald-400 hover:underline">support@ivote.com</a></li>
                    </ul>
                </div>

            </div>

            <!-- Bas de page -->
            <div class="mt-8 flex flex-col items-center justify-between gap-4 border-t border-slate-800/80 pt-6 text-xs text-slate-500 sm:flex-row">
                <p>&copy; <?= date('Y') ?> IVote. Tous droits réservés.</p>
                <p>Votez en toute sécurité.</p>
            </div>
        </div>
    </footer>
</body>
</html>