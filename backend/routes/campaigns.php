<?php
// =========================================================================
// DONNÉES ET DONNÉES MOCKÉES (À remplacer par vos données BDD / Session)
// =========================================================================

$userSession = $userSession ?? [
    'organizationName' => 'Organisation IVote',
    'organizerId' => 'org-1'
];

$activeCampaign = $activeCampaign ?? [
    'id' => 'camp-1',
    'title' => 'Election Miss & Master Université 2026',
    'organization' => 'Comité Etudiant',
    'pricePerVoteFCFA' => 100,
    'totalRevenueFCFA' => 7100000,
    'totalVotes' => 71000,
    'candidates' => [
        [
            'id' => 'cand-1',
            'name' => 'Aïcha Kone',
            'number' => '01',
            'category' => 'Miss',
            'faculty' => 'Faculté de Droit',
            'votes' => 25400,
            'percentage' => 35.7,
            'imageUrl' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150'
        ],
        [
            'id' => 'cand-2',
            'name' => 'Jean-Luc Koffi',
            'number' => '02',
            'category' => 'Master',
            'faculty' => 'Sciences Économiques',
            'votes' => 18200,
            'percentage' => 25.6,
            'imageUrl' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150'
        ]
    ]
];

$campaigns = $campaigns ?? [
    $activeCampaign,
    ['id' => 'camp-2', 'title' => 'Concours d\'Éloquence 2026']
];

$transactions = $transactions ?? [
    [
        'id' => 'tx-1',
        'campaignId' => 'camp-1',
        'transactionRef' => 'TX-984210',
        'candidateName' => 'Aïcha Kone',
        'candidateAvatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150',
        'paymentMethod' => 'orange_money',
        'votesCount' => 50,
        'amountFCFA' => 5000,
        'timestamp' => 'Aujourd\'hui, 14:32'
    ],
    [
        'id' => 'tx-2',
        'campaignId' => 'camp-1',
        'transactionRef' => 'TX-984211',
        'candidateName' => 'Jean-Luc Koffi',
        'candidateAvatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150',
        'paymentMethod' => 'mtn_momo',
        'votesCount' => 20,
        'amountFCFA' => 2000,
        'timestamp' => 'Aujourd\'hui, 14:28'
    ]
];

$payoutRequests = $payoutRequests ?? [
    [
        'id' => 'PO-3021',
        'amountFCFA' => 1500000,
        'paymentMethod' => 'orange_money',
        'accountHolder' => 'Kouassi Paul',
        'walletNumber' => '+225 0707070707',
        'requestedAt' => '02 Sep 2026, 10:15',
        'status' => 'completed'
    ]
];

// Helper Formattage FCFA
function formatFCFA(float $amount): string {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

// Calculs statistiques
$totalRevenue = $activeCampaign['totalRevenueFCFA'];
$totalVotes = $activeCampaign['totalVotes'];
$ivotePlatformFee = (int)round($totalRevenue * 0.10);
$availableBalance = (int)round($totalRevenue * 0.90);

$chartData = [
    ['day' => 'Lun', 'revenue' => 420000, 'votes' => 4200],
    ['day' => 'Mar', 'revenue' => 680000, 'votes' => 6800],
    ['day' => 'Mer', 'revenue' => 950000, 'votes' => 9500],
    ['day' => 'Jeu', 'revenue' => 820000, 'votes' => 8200],
    ['day' => 'Ven', 'revenue' => 1240000, 'votes' => 12400],
    ['day' => 'Sam', 'revenue' => 1850000, 'votes' => 18500],
    ['day' => 'Dim', 'revenue' => 2150000, 'votes' => 21500],
];
$maxRevenue = max(array_column($chartData, 'revenue'));
?>

<!DOCTYPE html>
<html lang="fr" class="bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Organisateur</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen pb-28 pt-10 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto font-sans">
    <!-- Navbar Component (navbar.php) -->
    <header class="fixed top-0 left-0 right-0 z-40 bg-slate-950/90 backdrop-blur-xl border-b border-slate-800 transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        
        <!-- Left: Brand Identity -->
        <div class="flex items-center gap-3 sm:gap-6">
        <a href="index.php" class="flex items-center gap-2.5 text-left group cursor-pointer">
            <div class="w-8 h-8 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-md shadow-emerald-600/30 group-hover:scale-105 transition-transform">
            <!-- Sparkles Icon -->
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>
            </div>
            <div>
            <span class="text-base font-bold font-display tracking-tight text-white flex items-center gap-1.5">
                IVote
            </span>
            <span class="text-[10px] text-slate-400 hidden sm:block">Vote & Paiement Mobile Money</span>
            </div>
        </a>
        </div>

        <!-- Right Area: Clean spacing -->
        <div class="flex items-center gap-2">
        <!-- Zone droite laissée vide conformément au composant d'origine -->
        </div>
    </div>
    </header>

    <!-- 1. TOP HEADER -->
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 pb-6 border-b border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-1">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Tableau de Bord Organisateur • <?= htmlspecialchars($userSession['organizationName'] ?? $activeCampaign['organization']) ?></span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white">
                <?= htmlspecialchars($activeCampaign['title']) ?>
            </h1>
            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400 mt-1">
                <span>Prix unitaire : <strong class="text-white"><?= htmlspecialchars($activeCampaign['pricePerVoteFCFA']) ?> FCFA/vote</strong></span>
                <span>•</span>
                <span class="text-emerald-400 font-semibold flex items-center gap-1">
                    ✓ Votes Monétisés Ouverts
                </span>
            </div>
        </div>

        <!-- Header Actions -->
        <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
            <!-- Selector Campagne -->
            <form method="GET" class="inline-block">
                <select name="campaign_id" onchange="this.form.submit()" aria-label="Sélectionner une campagne active" class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs font-semibold text-white focus:outline-none focus:border-emerald-500 cursor-pointer shadow-sm">
                    <?php foreach ($campaigns as $camp): ?>
                        <option value="<?= $camp['id'] ?>" <?= $camp['id'] === $activeCampaign['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($camp['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <a href="/public-page" class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 text-xs font-semibold border border-slate-800 transition-colors">
                 Page Publique
            </a>

            <button onclick="toggleModal('payoutModal', true)" class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs sm:text-sm font-bold shadow-md transition-all cursor-pointer">
                Retirer les Fonds
            </button>

            <button onclick="toggleModal('createModal', true)" class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs sm:text-sm font-semibold transition-all cursor-pointer">
                + Nouvelle Campagne
            </button>
        </div>
    </div>

    <!-- 2. TABS NAVIGATION -->
    <div class="flex items-center gap-2 border-b border-slate-800 my-6 overflow-x-auto pb-1 scrollbar-none">
        <button onclick="switchTab('overview')" id="tab-btn-overview" class="tab-btn active border-b-2 border-emerald-500 text-white px-4 py-2.5 text-xs sm:text-sm font-bold whitespace-nowrap">
            Aperçu & Vélocité
        </button>
        <button onclick="switchTab('candidates')" id="tab-btn-candidates" class="tab-btn text-slate-400 hover:text-slate-200 px-4 py-2.5 text-xs sm:text-sm font-bold whitespace-nowrap">
            Classement Candidats
        </button>
        <button onclick="switchTab('transactions')" id="tab-btn-transactions" class="tab-btn text-slate-400 hover:text-slate-200 px-4 py-2.5 text-xs sm:text-sm font-bold whitespace-nowrap">
            Transactions (<?= count($transactions) ?>)
        </button>
        <button onclick="switchTab('payouts')" id="tab-btn-payouts" class="tab-btn text-slate-400 hover:text-slate-200 px-4 py-2.5 text-xs sm:text-sm font-bold whitespace-nowrap">
            Statut des Retraits (<?= count($payoutRequests) ?>)
        </button>
    </div>

    <!-- TAB 1: OVERVIEW -->
    <div id="tab-overview" class="tab-content space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl">
                <span class="text-xs font-semibold uppercase text-slate-400">Total Revenus Générés</span>
                <div class="text-2xl font-bold font-mono text-white mt-2"><?= formatFCFA($totalRevenue) ?></div>
                <div class="text-xs text-emerald-400 mt-1 font-medium">+24.8% de croissance 24h</div>
            </div>

            <div class="p-5 rounded-2xl bg-slate-900/80 border border-emerald-500/40 shadow-xl">
                <span class="text-xs font-semibold uppercase text-emerald-400 font-bold">Solde Net Retirable (90%)</span>
                <div class="text-2xl font-bold font-mono text-emerald-400 mt-2"><?= formatFCFA($availableBalance) ?></div>
                <div class="text-xs text-slate-300 mt-1">Disponible Orange / MTN</div>
            </div>

            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl">
                <span class="text-xs font-semibold uppercase text-slate-400">Votes Enregistrés</span>
                <div class="text-2xl font-bold font-mono text-white mt-2"><?= number_format($totalVotes) ?> votes</div>
                <div class="text-xs text-slate-400 mt-1"><?= count($activeCampaign['candidates']) ?> candidats en lice</div>
            </div>

            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl">
                <span class="text-xs font-semibold uppercase text-slate-400">Frais Plateforme (10%)</span>
                <div class="text-2xl font-bold font-mono text-slate-300 mt-2"><?= formatFCFA($ivotePlatformFee) ?></div>
                <div class="text-xs text-slate-400 mt-1">Sécurisation & télécoms</div>
            </div>
        </div>

        <!-- Graphique -->
        <div class="p-5 sm:p-6 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl">
            <div class="flex flex-col sm:flex-row justify-between gap-2 mb-6">
                <div>
                    <h3 class="text-base font-bold text-white">Évolution des Revenus & Vélocité</h3>
                    <p class="text-xs text-slate-400">Collecté par Mobile Money</p>
                </div>
                <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 font-semibold border border-emerald-500/30 h-fit">
                    Pic Horaire : 1 840 votes/h
                </span>
            </div>

            <div class="grid grid-cols-7 gap-2 h-48 items-end pt-4 pb-2 border-b border-slate-800">
                <?php foreach ($chartData as $index => $item): 
                    $heightPercent = ($item['revenue'] / $maxRevenue) * 100;
                    $isToday = $index === count($chartData) - 1;
                ?>
                    <div class="flex flex-col items-center gap-2 h-full justify-end group relative">
                        <div class="w-full max-w-[48px] bg-slate-800 rounded-t-xl overflow-hidden relative flex flex-col justify-end h-full">
                            <div style="height: <?= $heightPercent ?>%" class="w-full rounded-t-xl transition-all duration-500 <?= $isToday ? 'bg-emerald-500' : 'bg-slate-700 group-hover:bg-emerald-600' ?>"></div>
                        </div>
                        <span class="text-[10px] sm:text-xs font-medium <?= $isToday ? 'text-emerald-400 font-bold' : 'text-slate-400' ?>">
                            <?= $item['day'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- TAB 2: CANDIDATES -->
    <div id="tab-candidates" class="tab-content hidden rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl overflow-hidden">
        <div class="p-5 border-b border-slate-800">
            <h3 class="text-base font-bold text-white">Performance des Candidats</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-950/60 text-slate-400 uppercase text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">Rang & Candidat</th>
                        <th class="py-3.5 px-4">Catégorie</th>
                        <th class="py-3.5 px-4">Filière</th>
                        <th class="py-3.5 px-4 text-right">Votes</th>
                        <th class="py-3.5 px-4 text-right">Recette</th>
                        <th class="py-3.5 px-4">Part</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-200">
                    <?php foreach ($activeCampaign['candidates'] as $idx => $cand): 
                        $estRevenue = $cand['votes'] * $activeCampaign['pricePerVoteFCFA'];
                    ?>
                        <tr class="hover:bg-slate-800/40">
                            <td class="py-3.5 px-4 flex items-center gap-3">
                                <span class="w-6 h-6 rounded-lg flex items-center justify-center font-bold text-xs bg-slate-800 text-slate-400">
                                    #<?= $idx + 1 ?>
                                </span>
                                <img src="<?= $cand['imageUrl'] ?>" alt="<?= htmlspecialchars($cand['name']) ?>" class="w-9 h-9 rounded-full object-cover">
                                <div>
                                    <div class="font-bold text-white"><?= htmlspecialchars($cand['name']) ?></div>
                                    <div class="text-[11px] text-slate-400 font-mono">N° <?= $cand['number'] ?></div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4"><span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-600/20 text-emerald-300"><?= htmlspecialchars($cand['category']) ?></span></td>
                            <td class="py-3.5 px-4 text-slate-300"><?= htmlspecialchars($cand['faculty']) ?></td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-white"><?= number_format($cand['votes']) ?></td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-400"><?= formatFCFA($estRevenue) ?></td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-24 h-2 bg-slate-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-500" style="width: <?= $cand['percentage'] ?>%"></div>
                                    </div>
                                    <span class="font-mono text-xs text-slate-400"><?= $cand['percentage'] ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 3: TRANSACTIONS -->
    <div id="tab-transactions" class="tab-content hidden rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl p-4 sm:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <input type="text" id="txSearch" onkeyup="filterTransactions()" placeholder="Rechercher par réf, candidat..." class="flex-1 px-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
            <select id="paymentFilter" onchange="filterTransactions()" aria-label="Filtrer par moyen de paiement" class="px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-300">
                <option value="all">Tous les Moyens</option>
                <option value="mtn_momo">MTN MoMo</option>
                <option value="orange_money">Orange Money</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-800">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-950/80 text-slate-400 uppercase text-[10px]">
                    <tr>
                        <th class="py-3.5 px-4">Réf. Transaction</th>
                        <th class="py-3.5 px-4">Candidat Bénéficiaire</th>
                        <th class="py-3.5 px-4">Moyen de Paiement</th>
                        <th class="py-3.5 px-4 text-right">Votes</th>
                        <th class="py-3.5 px-4 text-right">Montant</th>
                        <th class="py-3.5 px-4">Date</th>
                    </tr>
                </thead>
                <tbody id="txTableBody" class="divide-y divide-slate-800/60 text-slate-200">
                    <?php foreach ($transactions as $tx): ?>
                        <tr class="tx-row hover:bg-slate-800/40" data-search="<?= strtolower($tx['transactionRef'] . ' ' . $tx['candidateName']) ?>" data-method="<?= $tx['paymentMethod'] ?>">
                            <td class="py-3.5 px-4 font-mono text-xs font-semibold text-emerald-300"><?= $tx['transactionRef'] ?></td>
                            <td class="py-3.5 px-4 flex items-center gap-2">
                                <img src="<?= $tx['candidateAvatar'] ?>" class="w-7 h-7 rounded-full object-cover">
                                <span><?= htmlspecialchars($tx['candidateName']) ?></span>
                            </td>
                            <td class="py-3.5 px-4"><span class="px-2 py-0.5 rounded-full text-[10px] uppercase bg-slate-800 text-slate-300"><?= str_replace('_', ' ', $tx['paymentMethod']) ?></span></td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-400">+<?= $tx['votesCount'] ?></td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-white"><?= formatFCFA($tx['amountFCFA']) ?></td>
                            <td class="py-3.5 px-4 text-xs text-slate-300"><?= $tx['timestamp'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 4: PAYOUTS -->
    <div id="tab-payouts" class="tab-content hidden rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl p-4 sm:p-6 space-y-4">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
            <h3 class="text-base font-bold text-white">Historique des Demandes de Retrait</h3>
            <button onclick="toggleModal('payoutModal', true)" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold">Demander un Retrait</button>
        </div>
        <div class="overflow-x-auto rounded-2xl border border-slate-800">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-950/80 text-slate-400 uppercase text-[10px]">
                    <tr>
                        <th class="py-3.5 px-4">Réf</th>
                        <th class="py-3.5 px-4">Montant</th>
                        <th class="py-3.5 px-4">Moyen</th>
                        <th class="py-3.5 px-4">Titulaire / Numéro</th>
                        <th class="py-3.5 px-4">Date</th>
                        <th class="py-3.5 px-4">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-200">
                    <?php foreach ($payoutRequests as $req): ?>
                        <tr class="hover:bg-slate-800/40">
                            <td class="py-3.5 px-4 font-mono text-emerald-300"><?= $req['id'] ?></td>
                            <td class="py-3.5 px-4 font-mono font-bold text-white"><?= formatFCFA($req['amountFCFA']) ?></td>
                            <td class="py-3.5 px-4"><span class="px-2 py-0.5 rounded-full text-[10px] uppercase bg-slate-800"><?= str_replace('_', ' ', $req['paymentMethod']) ?></span></td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-white"><?= htmlspecialchars($req['accountHolder']) ?></div>
                                <div class="text-[11px] text-slate-400 font-mono"><?= $req['walletNumber'] ?></div>
                            </td>
                            <td class="py-3.5 px-4 text-xs"><?= $req['requestedAt'] ?></td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $req['status'] === 'completed' ? 'bg-emerald-500/15 text-emerald-400' : 'bg-amber-500/15 text-amber-300' ?>">
                                    <?= $req['status'] === 'completed' ? 'Validé & Versé' : 'En attente' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL 1: RETRAIT (PAYOUT) -->
    <div id="payoutModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md space-y-4">
            <h3 class="text-lg font-bold text-white">Demander un Retrait</h3>
            <p class="text-xs text-slate-400">Solde disponible : <strong class="text-emerald-400"><?= formatFCFA($availableBalance) ?></strong></p>
            <form method="POST" action="/request-payout" class="space-y-3">
                <div>
                    <label class="text-xs text-slate-300">Montant à retirer (FCFA)</label>
                    <input type="number" name="amount" max="<?= $availableBalance ?>" required class="w-full mt-1 p-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-300">Moyen de Réception</label>
                    <select name="payment_method" class="w-full mt-1 p-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm">
                        <option value="orange_money">Orange Money</option>
                        <option value="mtn_momo">MTN MoMo</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="toggleModal('payoutModal', false)" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs rounded-xl">Annuler</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl">Confirmer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: NOUVELLE CAMPAGNE -->
    <div id="createModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md space-y-4">
            <h3 class="text-lg font-bold text-white">Créer une Campagne</h3>
            <form method="POST" action="/create-campaign" class="space-y-3">
                <div>
                    <label class="text-xs text-slate-300">Titre de la campagne</label>
                    <input type="text" name="title" required class="w-full mt-1 p-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-300">Prix unitaire du vote (FCFA)</label>
                    <input type="number" name="price" value="100" required class="w-full mt-1 p-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="toggleModal('createModal', false)" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs rounded-xl">Annuler</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl">Créer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- CLIENT SCRIPT (Minimal JS pour les ONGLET / FILTRES / MODALES) -->
    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('active', 'border-b-2', 'border-emerald-500', 'text-white');
                el.classList.add('text-slate-400');
            });

            document.getElementById('tab-' + tabId).classList.remove('hidden');
            const activeBtn = document.getElementById('tab-btn-' + tabId);
            activeBtn.classList.add('active', 'border-b-2', 'border-emerald-500', 'text-white');
            activeBtn.classList.remove('text-slate-400');
        }

        function filterTransactions() {
            const search = document.getElementById('txSearch').value.toLowerCase();
            const method = document.getElementById('paymentFilter').value;
            const rows = document.querySelectorAll('.tx-row');

            rows.forEach(row => {
                const matchSearch = row.dataset.search.includes(search);
                const matchMethod = method === 'all' || row.dataset.method === method;
                row.style.display = (matchSearch && matchMethod) ? '' : 'none';
            });
        }

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