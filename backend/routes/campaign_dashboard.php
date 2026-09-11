<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/authController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/campaignController.php';

$authController = new AuthController();
$currentUser = $authController->checkAuthentification();

if (!$currentUser) {
    header('Location: /login.php');
    exit;
}

$campaignId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$campaignController = new CampaignController();
$data = $campaignController->getDashboardData($campaignId, (int)$currentUser['user_id']);

if (!$data) {
    header('Location: /organizer-campaigns.php');
    exit;
}

$campaign = $data['campaign'];
$categories = $data['categories'];
$candidates = $data['candidates'];
$transactions = $data['transactions'];
$payoutRequests = $data['payoutRequests'];
$stats = $data['stats'];

function formatFCFA(float $amount): string {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

$candidatesByCategory = [];
foreach ($candidates as $cand) {
    $catName = $cand['category_name'] ?? 'Sans Catégorie';
    $candidatesByCategory[$catName][] = $cand;
}
?>
<!DOCTYPE html>
<html lang="fr" class="bg-slate-950 text-slate-100 h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - <?= htmlspecialchars($campaign['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col pt-20 font-sans" data-campaign-id="<?= (int)$campaign['campaign_id'] ?>">
    
    <!-- Navbar -->
    <header class="fixed top-0 left-0 right-0 z-40 bg-slate-950/90 backdrop-blur-xl border-b border-slate-800 transition-all">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="/organizer-campaigns.php" class="flex items-center gap-2.5 group">
                    <div class="w-8 h-8 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-md shadow-emerald-600/30 group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                    <span class="text-base font-bold tracking-tight text-white">IVote</span>
                </a>
            </div>
            <div>
                <a href="/organizer-campaigns.php" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-xs font-semibold text-slate-300 hover:text-emerald-400 hover:border-emerald-500/50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Mes campagnes
                </a>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full pb-20">
        
        <!-- HEADER DE LA CAMPAGNE AVEC PHOTO & ÉDITION -->
        <div class="mt-6 relative rounded-3xl overflow-hidden bg-slate-900 border border-slate-800">
            <div class="relative h-48 sm:h-64 w-full bg-slate-950 group">
                <img id="campaignBanner" src="<?= $campaign['image_url'] ?: 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1200' ?>" class="w-full h-full object-cover opacity-60 group-hover:opacity-40 transition-opacity">
                <button onclick="toggleModal('photoModal', true)" class="absolute inset-0 flex items-center justify-center gap-2 text-white bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity font-semibold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Changer la photo
                </button>
            </div>

            <div class="p-6 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                            Organisé par <?= htmlspecialchars($campaign['organizer_name']) ?>
                        </span>
                        
                        <?php if ($campaign['computed_status'] === 'draft'): ?>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-800 text-slate-400 border border-slate-700">Brouillon</span>
                        <?php elseif ($campaign['computed_status'] === 'live'): ?>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> En direct
                            </span>
                        <?php else: ?>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-500/15 text-red-400 border border-red-500/30">Clôturé</span>
                        <?php endif; ?>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-bold text-white"><?= htmlspecialchars($campaign['title']) ?></h1>
                    <p class="text-xs sm:text-sm text-slate-400 max-w-2xl"><?= htmlspecialchars($campaign['description']) ?></p>
                    
                    <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400 pt-1">
                        <span>Prix unitaire : <strong class="text-white"><?= (int)$campaign['price_per_vote'] ?> FCFA/vote</strong></span>
                        <span>•</span>
                        <span>Fin : <strong class="text-white"><?= $campaign['date_cloture'] ? date('d/m/Y H:i', strtotime($campaign['date_cloture'])) : 'Non définie' ?></strong></span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
                    <button onclick="toggleModal('editCampaignModal', true)" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center gap-1.5 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Modifier
                    </button>

                    <?php if ($campaign['computed_status'] === 'draft'): ?>
                        <button onclick="updateCampaignAction('publish')" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition">
                            Publier (Passer en direct)
                        </button>
                    <?php elseif ($campaign['computed_status'] === 'live'): ?>
                        <button onclick="updateCampaignAction('close')" class="px-4 py-2 rounded-xl bg-red-600/80 hover:bg-red-500 text-white text-xs font-bold transition">
                            Clôturer la campagne
                        </button>
                    <?php else: ?>
                        <button onclick="toggleModal('relaunchModal', true)" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition">
                            Relancer la campagne
                        </button>
                    <?php endif; ?>

                    <button onclick="toggleModal('payoutModal', true)" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/40 text-xs font-bold transition">
                        Retirer les Fonds
                    </button>
                </div>
            </div>
        </div>

        <!-- STATS CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 my-6">
            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl">
                <span class="text-xs font-semibold uppercase text-slate-400">Total Recettes</span>
                <div class="text-2xl font-bold font-mono text-white mt-2"><?= formatFCFA($stats['totalRevenue']) ?></div>
                <div class="text-xs text-slate-400 mt-1"><?= number_format($stats['totalVotes']) ?> votes au total</div>
            </div>

            <div class="p-5 rounded-2xl bg-slate-900/80 border border-emerald-500/40 shadow-xl">
                <span class="text-xs font-semibold uppercase text-emerald-400 font-bold">Solde Net Retirable (90%)</span>
                <div class="text-2xl font-bold font-mono text-emerald-400 mt-2"><?= formatFCFA($stats['availableBalance']) ?></div>
                <div class="text-xs text-slate-300 mt-1">Disponible Mobile Money</div>
            </div>

            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl">
                <span class="text-xs font-semibold uppercase text-slate-400">Candidats Inscrits</span>
                <div class="text-2xl font-bold font-mono text-white mt-2"><?= count($candidates) ?> candidats</div>
                <div class="text-xs text-slate-400 mt-1"><?= count($categories) ?> catégories</div>
            </div>

            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl">
                <span class="text-xs font-semibold uppercase text-slate-400">Frais Plateforme (10%)</span>
                <div class="text-2xl font-bold font-mono text-slate-300 mt-2"><?= formatFCFA($stats['platformFee']) ?></div>
                <div class="text-xs text-slate-400 mt-1">Infrastructure & Sécurisation</div>
            </div>
        </div>

        <!-- ONGLETS -->
        <div class="flex items-center gap-2 border-b border-slate-800 mb-6 overflow-x-auto pb-1 scrollbar-none">
            <button onclick="switchTab('candidates')" id="tab-btn-candidates" class="tab-btn active border-b-2 border-emerald-500 text-white px-4 py-2.5 text-xs sm:text-sm font-bold whitespace-nowrap">
                Candidats & Résultats
            </button>
            <button onclick="switchTab('transactions')" id="tab-btn-transactions" class="tab-btn text-slate-400 hover:text-slate-200 px-4 py-2.5 text-xs sm:text-sm font-bold whitespace-nowrap">
                Transactions (<?= count($transactions) ?>)
            </button>
            <button onclick="switchTab('payouts')" id="tab-btn-payouts" class="tab-btn text-slate-400 hover:text-slate-200 px-4 py-2.5 text-xs sm:text-sm font-bold whitespace-nowrap">
                Retraits (<?= count($payoutRequests) ?>)
            </button>
        </div>

        <!-- ONGLET 1 : CANDIDATS & CLASSEMENTS -->
        <div id="tab-candidates" class="tab-content space-y-6">
            <div class="rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl overflow-hidden p-5">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-base font-bold text-white">Liste des Candidats</h3>
                        <p class="text-xs text-slate-400">Gestion des participants de votre campagne.</p>
                    </div>
                    <button onclick="toggleModal('candidateModal', true)" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-1.5 transition shadow-lg shadow-emerald-600/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Ajouter un candidat
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead class="bg-slate-950/60 text-slate-400 uppercase text-[10px] border-b border-slate-800">
                            <tr>
                                <th class="py-3 px-4">N° & Participant</th>
                                <th class="py-3 px-4">Catégorie</th>
                                <th class="py-3 px-4">Thème / Filière</th>
                                <th class="py-3 px-4 text-right">Votes</th>
                                <th class="py-3 px-4 text-right">Recettes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-200">
                            <?php if (empty($candidates)): ?>
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-500">Aucun candidat pour le moment. Cliquez sur "Ajouter un candidat".</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($candidates as $cand): ?>
                                    <tr class="hover:bg-slate-800/40">
                                        <td class="py-3.5 px-4 flex items-center gap-3">
                                            <span class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs bg-slate-800 text-slate-300 font-mono">
                                                <?= str_pad((string)$cand['candidate_number'], 2, '0', STR_PAD_LEFT) ?>
                                            </span>
                                            <img src="<?= $cand['image_url'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150' ?>" class="w-9 h-9 rounded-full object-cover">
                                            <div>
                                                <div class="font-bold text-white"><?= htmlspecialchars($cand['name']) ?></div>
                                                <div class="text-[11px] text-slate-400"><?= $cand['age'] ? $cand['age'].' ans' : '' ?></div>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-600/20 text-emerald-300 border border-emerald-500/20">
                                                <?= htmlspecialchars($cand['category_name'] ?? 'Général') ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-slate-300"><?= htmlspecialchars($cand['theme'] ?? 'Non spécifié') ?></td>
                                        <td class="py-3.5 px-4 text-right font-mono font-bold text-white"><?= number_format($cand['total_votes']) ?></td>
                                        <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-400"><?= formatFCFA($cand['revenue']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Classements -->
            <div class="space-y-4">
                <h3 class="text-lg font-bold text-white">Classements par Catégories</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($candidatesByCategory as $categoryName => $cands): ?>
                        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
                            <h4 class="text-sm font-bold text-emerald-400 uppercase tracking-wider flex items-center justify-between">
                                <span>Catégorie : <?= htmlspecialchars($categoryName) ?></span>
                                <span class="text-xs text-slate-400"><?= count($cands) ?> participants</span>
                            </h4>

                            <div class="space-y-3">
                                <?php foreach ($cands as $rank => $cand): ?>
                                    <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800/80 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <span class="w-6 h-6 rounded-md flex items-center justify-center font-bold text-xs <?= $rank === 0 ? 'bg-amber-500 text-slate-950' : 'bg-slate-800 text-slate-400' ?>">
                                                #<?= $rank + 1 ?>
                                            </span>
                                            <div>
                                                <div class="font-bold text-xs sm:text-sm text-white"><?= htmlspecialchars($cand['name']) ?></div>
                                                <div class="text-[10px] text-slate-400 font-mono"><?= number_format($cand['total_votes']) ?> votes (<?= $cand['percentage'] ?>%)</div>
                                            </div>
                                        </div>

                                        <div class="w-24">
                                            <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                                                <div class="h-full bg-emerald-500" style="width: <?= $cand['percentage'] ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ONGLET 2 : TRANSACTIONS -->
        <div id="tab-transactions" class="tab-content hidden rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl p-4 sm:p-6 space-y-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <input type="text" id="txSearch" placeholder="Rechercher par référence, candidat..." class="flex-1 px-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-emerald-500 outline-none">
                <select id="paymentFilter" class="px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-300 outline-none">
                    <option value="all">Tous les Moyens</option>
                    <option value="mtn_momo">MTN MoMo</option>
                    <option value="orange_money">Orange Money</option>
                </select>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-800">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead class="bg-slate-950/80 text-slate-400 uppercase text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Réf. Transaction</th>
                            <th class="py-3 px-4">Candidat</th>
                            <th class="py-3 px-4">Moyen</th>
                            <th class="py-3 px-4 text-right">Votes</th>
                            <th class="py-3 px-4 text-right">Montant</th>
                            <th class="py-3 px-4">Date</th>
                        </tr>
                    </thead>
                    <tbody id="txTableBody" class="divide-y divide-slate-800/60 text-slate-200">
                        <?php if (empty($transactions)): ?>
                            <tr><td colspan="6" class="py-8 text-center text-slate-500">Aucune transaction enregistrée.</td></tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): ?>
                                <tr class="tx-row hover:bg-slate-800/40" data-search="<?= strtolower($tx['transaction_ref'] . ' ' . $tx['candidate_name']) ?>" data-method="<?= $tx['payment_method'] ?>">
                                    <td class="py-3.5 px-4 font-mono text-xs font-semibold text-emerald-300"><?= $tx['transaction_ref'] ?></td>
                                    <td class="py-3.5 px-4 flex items-center gap-2">
                                        <img src="<?= $tx['candidate_avatar'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150' ?>" class="w-7 h-7 rounded-full object-cover">
                                        <span><?= htmlspecialchars($tx['candidate_name']) ?></span>
                                    </td>
                                    <td class="py-3.5 px-4"><span class="px-2 py-0.5 rounded-full text-[10px] uppercase bg-slate-800 text-slate-300"><?= str_replace('_', ' ', $tx['payment_method']) ?></span></td>
                                    <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-400">+<?= $tx['vote_count'] ?></td>
                                    <td class="py-3.5 px-4 text-right font-mono font-bold text-white"><?= formatFCFA($tx['amount_fcfa']) ?></td>
                                    <td class="py-3.5 px-4 text-xs text-slate-400"><?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ONGLET 3 : RETRAITS -->
        <div id="tab-payouts" class="tab-content hidden rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl p-4 sm:p-6 space-y-4">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <div>
                    <h3 class="text-base font-bold text-white">Historique des Retraits</h3>
                    <p class="text-xs text-slate-400">Versements effectués vers vos comptes Mobile Money.</p>
                </div>
                <button onclick="toggleModal('payoutModal', true)" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-500 transition">Nouveau Retrait</button>
            </div>
            <div class="overflow-x-auto rounded-2xl border border-slate-800">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead class="bg-slate-950/80 text-slate-400 uppercase text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Réf Retrait</th>
                            <th class="py-3 px-4">Montant</th>
                            <th class="py-3 px-4">Moyen</th>
                            <th class="py-3 px-4">Bénéficiaire / Mobile</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-200">
                        <?php if (empty($payoutRequests)): ?>
                            <tr><td colspan="6" class="py-8 text-center text-slate-500">Aucune demande de retrait effectuée.</td></tr>
                        <?php else: ?>
                            <?php foreach ($payoutRequests as $req): ?>
                                <tr class="hover:bg-slate-800/40">
                                    <td class="py-3.5 px-4 font-mono text-emerald-300">#PO-<?= $req['payout_id'] ?></td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-white"><?= formatFCFA($req['amount_fcfa']) ?></td>
                                    <td class="py-3.5 px-4"><span class="px-2 py-0.5 rounded-full text-[10px] uppercase bg-slate-800"><?= str_replace('_', ' ', $req['payment_method']) ?></span></td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-semibold text-white"><?= htmlspecialchars($req['account_holder']) ?></div>
                                        <div class="text-[11px] text-slate-400 font-mono"><?= $req['wallet_number'] ?></div>
                                    </td>
                                    <td class="py-3.5 px-4 text-xs text-slate-400"><?= date('d/m/Y H:i', strtotime($req['created_at'])) ?></td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $req['status'] === 'completed' ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/15 text-amber-300 border border-amber-500/20' ?>">
                                            <?= $req['status'] === 'completed' ? 'Validé & Versé' : 'En attente' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- MODAL 1 : AJOUTER CANDIDAT -->
    <div id="candidateModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-lg space-y-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-white">Ajouter un nouveau candidat</h3>
            <form id="formCandidate" class="space-y-4">
                <div>
                    <label class="text-xs text-slate-300">Nom Complet *</label>
                    <input type="text" name="name" required class="w-full mt-1 p-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none focus:border-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-slate-300">Numéro Dossard *</label>
                        <input type="number" name="candidate_number" min="1" required class="w-full mt-1 p-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="text-xs text-slate-300">Âge</label>
                        <input type="number" name="age" min="1" class="w-full mt-1 p-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="text-xs text-slate-300">Catégorie</label>
                    <select name="category_id" id="categorySelect" class="w-full mt-1 p-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none focus:border-emerald-500">
                        <option value="">Sélectionner une catégorie...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                        <option value="new">+ Créer une nouvelle catégorie</option>
                    </select>
                </div>

                <div id="newCategoryWrapper" class="hidden">
                    <label class="text-xs text-emerald-400">Nom de la nouvelle catégorie *</label>
                    <input type="text" name="new_category_name" id="newCategoryName" placeholder="Ex: Miss, Master, Prix du Public..." class="w-full mt-1 p-2.5 bg-slate-950 border border-emerald-500/50 rounded-xl text-white text-sm outline-none">
                </div>

                <div>
                    <label class="text-xs text-slate-300">Filière / Thème représenté</label>
                    <input type="text" name="theme" placeholder="Ex: Faculté de Médecine" class="w-full mt-1 p-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none focus:border-emerald-500">
                </div>

                <div>
                    <label class="text-xs text-slate-300">Photo du Candidat</label>
                    <input type="file" name="image" accept="image/*" class="w-full mt-1 p-2 bg-slate-950 border border-slate-800 rounded-xl text-slate-400 text-xs file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-500">
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" onclick="toggleModal('candidateModal', false)" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs font-semibold rounded-xl hover:bg-slate-700">Annuler</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-500">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2 : CHANGER LA PHOTO -->
    <div id="photoModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md space-y-4">
            <h3 class="text-lg font-bold text-white">Changer la photo de la campagne</h3>
            <form id="formPhoto" class="space-y-4">
                <div>
                    <label class="text-xs text-slate-300">Sélectionnez une nouvelle image (JPG, PNG, WEBP)</label>
                    <input type="file" name="campaign_image" accept="image/*" required class="w-full mt-2 p-2 bg-slate-950 border border-slate-800 rounded-xl text-slate-400 text-xs file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-500">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="toggleModal('photoModal', false)" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs rounded-xl hover:bg-slate-700">Annuler</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-500">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3 : MODIFIER INFOS CAMPAGNE -->
    <div id="editCampaignModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-lg space-y-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-white">Modifier la campagne</h3>
            <form id="formEditCampaign" class="space-y-4">
                <div>
                    <label class="text-xs text-slate-300">Titre de la campagne *</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($campaign['title']) ?>" required class="w-full mt-1 p-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="text-xs text-slate-300">Description *</label>
                    <textarea name="description" rows="3" required class="w-full mt-1 p-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none focus:border-emerald-500"><?= htmlspecialchars($campaign['description']) ?></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-slate-300">Prix unitaire d'un vote (FCFA) *</label>
                        <input type="number" name="price_per_vote" min="1" value="<?= (int)$campaign['price_per_vote'] ?>" required class="w-full mt-1 p-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="text-xs text-slate-300">Date et heure de fin</label>
                        <input type="datetime-local" name="date_cloture" value="<?= $campaign['date_cloture'] ? date('Y-m-d\TH:i', strtotime($campaign['date_cloture'])) : '' ?>" class="w-full mt-1 p-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none focus:border-emerald-500">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" onclick="toggleModal('editCampaignModal', false)" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs font-semibold rounded-xl">Annuler</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-500">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4 : RELANCER UNE CAMPAGNE -->
    <div id="relaunchModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md space-y-4">
            <h3 class="text-lg font-bold text-white">Relancer la Campagne</h3>
            <p class="text-xs text-slate-400">Définissez une nouvelle date de fin pour réactiver les votes.</p>
            <form id="formRelaunch" class="space-y-4">
                <div>
                    <label class="text-xs text-slate-300">Nouvelle date et heure de fin</label>
                    <input type="datetime-local" name="date_cloture" required class="w-full mt-1 p-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none focus:border-emerald-500">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="toggleModal('relaunchModal', false)" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs rounded-xl hover:bg-slate-700">Annuler</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-500">Repasser en direct</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5 : RETRAIT DE FONDS -->
    <div id="payoutModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md space-y-4">
            <h3 class="text-lg font-bold text-white">Demande de Retrait</h3>
            <p class="text-xs text-slate-400">Solde disponible : <strong class="text-emerald-400"><?= formatFCFA($stats['availableBalance']) ?></strong></p>
            <form id="formPayout" class="space-y-3">
                <div>
                    <label class="text-xs text-slate-300">Montant à retirer (FCFA)</label>
                    <input type="number" name="amount" max="<?= (int)$stats['availableBalance'] ?>" required class="w-full mt-1 p-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="text-xs text-slate-300">Moyen de Réception</label>
                    <select name="payment_method" class="w-full mt-1 p-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none">
                        <option value="orange_money">Orange Money</option>
                        <option value="mtn_momo">MTN MoMo</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-slate-300">Nom & Prénom du Titulaire</label>
                    <input type="text" name="account_holder" required class="w-full mt-1 p-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none">
                </div>
                <div>
                    <label class="text-xs text-slate-300">Numéro de téléphone</label>
                    <input type="tel" name="wallet_number" required placeholder="+237..." class="w-full mt-1 p-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm outline-none">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="toggleModal('payoutModal', false)" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs rounded-xl">Annuler</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-500">Confirmer le Retrait</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT EXTERNE -->
    <script src="/public/js/campaign_dashboard.js"></script>
</body>
</html>