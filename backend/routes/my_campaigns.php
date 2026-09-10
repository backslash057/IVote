<?php

$userSession = $userSession ?? [
    'organizationName' => 'Organisation IVote',
    'organizerId' => 'org-1'
];

$campaigns = $campaigns ?? [
    [
        'campaign_id' => 'camp-1',
        'title' => 'Election Miss & Master Université 2026',
        'description' => 'Élection annuelle des ambassadeurs et ambassadrices universitaires.',
        'status' => 'active',
        'image_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=600',
        'organizer_name' => 'Comité Étudiant',
        'candidate_count' => 2,
        'totalVotes' => 71000,
        'pricePerVoteFCFA' => 100
    ],
    [
        'campaign_id' => 'camp-2',
        'title' => 'Concours d\'Éloquence 2026',
        'description' => 'Compétition inter-facultés d\'art oratoire et de débat public.',
        'status' => 'scheduled',
        'image_url' => 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=600',
        'organizer_name' => 'Club Débat',
        'candidate_count' => 8,
        'totalVotes' => 0,
        'pricePerVoteFCFA' => 200
    ]
];

// Calcul des métriques globales
$activeCount = count(array_filter($campaigns, fn($c) => ($c['status'] ?? '') === 'active'));
$totalVotesAcrossAll = array_reduce($campaigns, fn($acc, $c) => $acc + ($c['totalVotes'] ?? 0), 0);
?>
<!DOCTYPE html>
<html lang="fr" class="bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Campagnes - IVote</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen pt-20 pb-16 font-sans">

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

            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-400 hidden sm:inline">
                    Connecté : <strong class="text-white"><?= htmlspecialchars($userSession['organizationName']) ?></strong>
                </span>
            </div>
        </div>
    </header>

    <!-- Main Content Wrapper -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        
        <!-- Hero & Métriques rapides + Bouton Nouvelle Campagne -->
        <section class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-xs font-semibold text-emerald-400 uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Espace Organisateur</span>
                </div>
                <h1 class="text-2xl sm:text-4xl font-extrabold text-white">Gestion de vos Campagnes</h1>
                <p class="text-xs sm:text-sm text-slate-400 max-w-xl">
                    Supervisez vos scrutins en temps réel, lancez de nouveaux votes monétisés et suivez la collecte par Mobile Money.
                </p>
                <div class="pt-2">
                    <button onclick="toggleModal('createModal', true)" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs sm:text-sm font-bold shadow-md transition-all cursor-pointer">
                        <span>+</span> Nouvelle Campagne
                    </button>
                </div>
            </div>

            <!-- Stats Aggregates -->
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
        </section>

        <!-- Affichage des campagnes ou cas vide -->
        <?php if (empty($campaigns)): ?>
            <section class="text-center py-16 bg-slate-900 border border-slate-800 rounded-3xl p-8 max-w-md mx-auto space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-slate-800 flex items-center justify-center mx-auto text-slate-400 font-bold text-xl">
                    📁
                </div>
                <h2 class="text-base font-bold text-white">Aucune campagne créée</h2>
                <p class="text-xs text-slate-400">
                    Vous n'avez pas encore configuré de scrutin ou de concours de vote.
                </p>
                <button onclick="toggleModal('createModal', true)" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition">
                    Créer ma première campagne
                </button>
            </section>
        <?php else: ?>
            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($campaigns as $camp): ?>
                    <?php 
                        $isActive = ($camp['status'] ?? '') === 'active';
                        $isScheduled = ($camp['status'] ?? '') === 'scheduled';
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
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-600 text-white text-[11px] font-bold shadow-md">
                                        • En Direct
                                    </span>
                                <?php elseif ($isScheduled): ?>
                                    <span class="px-2.5 py-1 rounded-full bg-amber-600 text-white text-[11px] font-bold shadow-md">
                                        ⏰ À Venir
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-full bg-slate-700 text-slate-200 text-[11px] font-semibold">
                                        Terminé
                                    </span>
                                <?php endif; ?>

                                <span class="px-2.5 py-1 rounded-full bg-slate-950/80 backdrop-blur text-emerald-400 text-[11px] font-mono font-bold border border-slate-800">
                                    <?= number_format($camp['pricePerVoteFCFA'] ?? 100) ?> FCFA/vote
                                </span>
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
                                        👥 <?= $candidateCount ?> candidat<?= $candidateCount > 1 ? 's' : '' ?>
                                    </span>

                                    <span class="text-slate-300 font-mono font-semibold">
                                        🗳️ <?= number_format($camp['totalVotes'] ?? 0) ?> votes
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <a href="/organizer-dashboard.php?campaign_id=<?= $campaignId ?>" 
                                       class="py-2.5 px-3 rounded-xl text-xs font-bold text-center block transition bg-emerald-600 hover:bg-emerald-500 text-white">
                                        Tableau de bord →
                                    </a>
                                    <a href="/public-campaign.php?id=<?= $campaignId ?>" target="_blank"
                                       class="py-2.5 px-3 rounded-xl text-xs font-semibold text-center block transition bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700">
                                        Page Publique ↗
                                    </a>
                                </div>
                            </div>
                        </div>

                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

    </main>

    <!-- MODAL: NOUVELLE CAMPAGNE -->
    <div id="createModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md space-y-4">
            <h3 class="text-lg font-bold text-white">Créer une Campagne de Vote</h3>
            <form method="POST" action="/create-campaign" class="space-y-3">
                <div>
                    <label class="text-xs text-slate-300">Titre de la campagne</label>
                    <input type="text" name="title" required placeholder="Ex: Election Miss Faculté 2026" class="w-full mt-1 p-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="text-xs text-slate-300">Description courte</label>
                    <textarea name="description" rows="2" placeholder="Brève description..." class="w-full mt-1 p-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none focus:border-emerald-500"></textarea>
                </div>
                <div>
                    <label class="text-xs text-slate-300">Prix unitaire du vote (FCFA)</label>
                    <input type="number" name="price" value="100" min="50" step="50" required class="w-full mt-1 p-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none focus:border-emerald-500">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="toggleModal('createModal', false)" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs rounded-xl hover:bg-slate-700">Annuler</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-500">Créer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT MODAL -->
    <script>
        function toggleModal(modalId, show) {
            const modal = document.getElementById(modalId);
            if (show) {
                modal.classList.remove('hidden');
            } else {
                modal.classList.add('hidden');
            }
        }
    </script>
</body>
</html>