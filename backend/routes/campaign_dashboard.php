<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/AuthController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/CampaignController.php';

$authController = new AuthController();
$authResult = $authController->me();

if (empty($authResult['success']) || empty($authResult['user'])) {
    header('Location: /login');
    exit;
}

$currentUser = $authResult['user'];
if (($currentUser['role'] ?? '') !== 'organizer') {
    header('Location: /admin');
    exit;
}
$campaignController = new CampaignController();
if (isset($_GET['code'])) {
    $campaignId = $campaignController->findCampaignIdByCode((string)$_GET['code']);
    if ($campaignId === null) {
        header('Location: /dashboard');
        exit;
    }
} else {
    $campaignId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
}
$dashboardResult = $campaignController->dashboard($campaignId);

if (empty($dashboardResult['success']) || empty($dashboardResult['data'])) {
    header('Location: /dashboard');
    exit;
}

$data = $dashboardResult['data'];
$campaign = $data['campaign'];
$categories = $data['categories'];
$candidates = $data['candidates'];
$transactions = $data['transactions'];
$payoutRequests = $data['payoutRequests'];
$stats = $data['stats'];

function formatFCFA(float|int $amount): string {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

$candidatesByCategory = [];
foreach ($candidates as $cand) {
    $catName = $cand['category_name'] ?? 'Sans Catégorie';
    $candidatesByCategory[$catName][] = $cand;
}
$candidateCount = count($candidates);
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/public/favicon.ico" type="image/x-icon">
    <title>IVote - Tableau de Bord : <?= htmlspecialchars($campaign['title'], ENT_QUOTES, 'UTF-8') ?></title>
    
    <!-- Police : Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">

    <!-- Tailwind CSS & Config -->
    <script src="/public/js/tailwindcss/tailwindcss.js"></script>
    <script src="/public/js/tailwindcss/tailwindcss.config.js"></script>

    <link rel="stylesheet" href="/public/css/index.css">
</head>
<body class="antialiased selection:bg-blue-500 selection:text-white flex flex-col min-h-screen bg-surface" 
      data-campaign-id="<?= (int)$campaign['campaign_id'] ?>" 
      data-candidates-count="<?= $candidateCount ?>"
      data-campaign-status="<?= htmlspecialchars($campaign['computed_status'], ENT_QUOTES, 'UTF-8') ?>"
      data-date-cloture="<?= $campaign['date_cloture'] ? date('c', strtotime($campaign['date_cloture'])) : '' ?>">

    <!-- Navbar -->
    <header class="sticky top-0 z-40 bg-surface/90 backdrop-blur-md border-b border-bordercustom transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                
                <!-- Logo -->
                <a href="/" class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-md">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-2xl font-extrabold tracking-tight text-fore leading-none">IVote</span>
                        <span class="text-[10px] font-semibold text-fore-secondary tracking-wider">Votes Monétisés</span>
                    </div>
                </a>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center gap-8">
                    <a href="/" class="text-sm font-medium text-fore-secondary hover:text-fore transition">Accueil</a>
                    <a href="/campaigns" class="text-sm font-medium text-fore-secondary hover:text-fore transition">Explorer les campagnes</a>
                    <a href="/dashboard" class="text-sm font-semibold text-primary">Espace organisateur</a>
                </nav>

                <!-- Actions: Dark Theme & Buttons -->
                <div class="hidden md:flex items-center gap-3">
                    <a href="/notifications" class="relative p-2 text-fore-secondary hover:text-fore" aria-label="Notifications">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
                        <span id="notification-count" class="hidden absolute -right-1 -top-1 rounded-full bg-red-500 px-1.5 py-0.5 text-[9px] text-white"></span>
                    </a>
                    <button id="theme-toggle" class="p-2.5 rounded-xl border border-bordercustom hover:bg-surface-secondary text-fore transition" aria-label="Basculer le mode sombre">
                        <svg id="theme-icon-sun" class="w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="5"></circle>
                            <line x1="12" y1="1" x2="12" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="23"></line>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                            <line x1="1" y1="12" x2="3" y2="12"></line>
                            <line x1="21" y1="12" x2="23" y2="12"></line>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                        </svg>
                        <svg id="theme-icon-moon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </button>

                    <a href="/dashboard" class="px-4 py-2.5 rounded-xl border border-bordercustom hover:bg-surface-secondary text-xs font-semibold text-fore transition">
                        Mes campagnes
                    </a>

                </div>

                <!-- Mobile Menu Button -->
                <div class="flex items-center gap-2 md:hidden">
                    <button id="mobile-theme-toggle" class="p-2 text-fore-secondary hover:text-fore">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </button>
                    <button id="mobile-menu-btn" class="p-2 text-fore-secondary hover:text-fore">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Drawer -->
        <div id="mobile-menu" class="hidden md:hidden bg-surface border-b border-bordercustom px-4 pt-2 pb-6 space-y-3">
            <a href="/" class="block py-2 text-sm font-medium text-fore-secondary">Accueil</a>
            <a href="/campaigns" class="block py-2 text-sm font-medium text-fore-secondary">Explorer les campagnes</a>
            <a href="/dashboard" class="block py-2 text-sm font-medium text-fore-secondary">Espace organisateur</a>
            <a href="/notifications" class="block py-2 text-sm font-medium text-fore-secondary">Notifications</a>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-10 space-y-8">
        
        <!-- HEADER DE LA CAMPAGNE AVEC PHOTO & ACTIONS -->
        <section class="relative rounded-3xl overflow-hidden bg-surface-secondary border border-bordercustom shadow-sm">
            <div class="relative h-56 sm:h-72 w-full bg-surface group flex items-center justify-center overflow-hidden">
                <?php if (!empty($campaign['image_url'])): ?>
                    <img id="campaignBanner" src="<?= htmlspecialchars($campaign['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($campaign['title'], ENT_QUOTES, 'UTF-8') ?>" class="w-full h-full object-cover opacity-85 group-hover:opacity-60 transition duration-300">
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center text-fore-secondary p-4 text-center">
                        <svg class="w-12 h-12 mb-2 opacity-30 text-fore" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-xs font-medium opacity-60">Aucune photo (Format 16:9)</span>
                    </div>
                <?php endif; ?>

                <?php if ($campaign['computed_status'] === 'draft'): ?>
                <button onclick="toggleModal('photoModal', true)" class="absolute inset-0 flex items-center justify-center gap-2 text-white bg-black/50 opacity-0 group-hover:opacity-100 transition duration-200 font-bold text-xs sm:text-sm cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Changer l'affiche (16:9)
                </button>
                <?php endif; ?>
            </div>

            <div class="p-6 sm:p-8 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 border-t border-bordercustom">
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-fore-secondary uppercase tracking-wider">
                            Organisé par <?= htmlspecialchars($campaign['organizer_name'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        
                        <?php if ($campaign['computed_status'] === 'draft'): ?>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20">Brouillon</span>
                        <?php elseif ($campaign['computed_status'] === 'live'): ?>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-500/10 text-green-600 border border-green-500/20 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> En direct
                            </span>
                        <?php elseif ($campaign['computed_status'] === 'suspended'): ?>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-500/10 text-red-600 border border-red-500/20">Suspendue</span>
                        <?php else: ?>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-surface border border-bordercustom text-fore-secondary">Clôturé</span>
                        <?php endif; ?>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold text-fore"><?= htmlspecialchars($campaign['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="text-xs sm:text-sm text-fore-secondary max-w-2xl leading-relaxed"><?= htmlspecialchars($campaign['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($campaign['computed_status'] === 'suspended'): ?>
                        <p class="text-xs font-semibold text-red-600">Motif : <?= htmlspecialchars($campaign['suspension_reason'] ?: 'Contactez IVote pour plus de détails.', ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    
                    <div class="flex flex-wrap items-center gap-4 text-xs text-fore-secondary pt-1">
                        <span>Tarif : <strong class="text-fore font-bold"><?= (int)$campaign['price_per_vote'] ?> FCFA / vote</strong></span>
                        <span>•</span>
                        <span>Clôture : <strong class="text-fore font-bold"><?= $campaign['date_cloture'] ? date('d/m/Y à H:i', strtotime($campaign['date_cloture'])) : 'Non définie' ?></strong></span>
                    </div>
                </div>

                <!-- Actions de statut -->
                <div class="grid grid-cols-2 items-center gap-2.5 w-full lg:w-[24rem]">
                    <?php if ($campaign['computed_status'] === 'draft'): ?>
                    <button onclick="toggleModal('editCampaignModal', true)" class="w-full justify-center px-3.5 py-2 rounded-xl bg-surface border border-bordercustom hover:bg-surface-secondary text-fore text-xs font-bold flex items-center gap-1.5 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Modifier
                    </button>
                    <?php endif; ?>

                    <?php if ($campaign['computed_status'] === 'draft'): ?>
                        <button onclick="updateCampaignAction('publish')" class="w-full px-4 py-2 rounded-xl bg-primary hover:bg-primary-dark text-white text-xs font-bold transition shadow-sm">
                            Publier (Passer en direct)
                        </button>
                    <?php elseif ($campaign['computed_status'] === 'live'): ?>
                        <button onclick="updateCampaignAction('close')" class="w-full px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition shadow-sm">
                            Clôturer la campagne
                        </button>
                    <?php elseif ($campaign['computed_status'] === 'closed'): ?>
                        <button onclick="toggleModal('relaunchModal', true)" class="w-full px-4 py-2 rounded-xl bg-primary hover:bg-primary-dark text-white text-xs font-bold transition shadow-sm">
                            Relancer la campagne
                        </button>
                    <?php endif; ?>

                    <button onclick="toggleModal('payoutModal', true)" class="w-full px-4 py-2 rounded-xl bg-surface border border-primary text-primary hover:bg-primary/10 text-xs font-bold transition">
                        Demander un Retrait
                    </button>

                    <a href="/vote/<?= htmlspecialchars($campaign['campaign_code'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-primary hover:bg-primary-dark text-white text-xs font-bold transition shadow-sm">
                        <span>Voir page publique</span>
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        </svg>
                    </a>
                    <button type="button" class="copy-campaign-link w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border border-bordercustom hover:bg-surface-secondary text-xs font-semibold text-fore transition" data-campaign-url="/vote/<?= htmlspecialchars($campaign['campaign_code'], ENT_QUOTES, 'UTF-8') ?>">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        </svg>
                        <span>Copier le lien</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- STATS CARDS -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Participants -->
            <div class="p-5 rounded-2xl bg-surface-secondary border border-bordercustom shadow-sm">
                <span class="text-xs font-semibold uppercase text-fore-secondary">Participants</span>
                <div class="text-2xl font-extrabold font-mono text-fore mt-2"><?= $candidateCount ?> candidats</div>
                <div class="text-xs text-fore-secondary mt-1"><?= count($categories) ?> catégorie(s) configurée(s)</div>
            </div>

            <!-- 2. Total Votes -->
            <div class="p-5 rounded-2xl bg-surface-secondary border border-bordercustom shadow-sm">
                <span class="text-xs font-semibold uppercase text-fore-secondary">Total Votes</span>
                <div class="text-2xl font-extrabold font-mono text-fore mt-2"><?= number_format($stats['totalVotes'], 0, ',', ' ') ?></div>
                <div class="text-xs text-fore-secondary mt-1">Votes enregistrés au total</div>
            </div>

            <!-- 3. Total Recette (90%) -->
            <div class="p-5 rounded-2xl bg-surface-secondary border border-bordercustom shadow-sm">
                <span class="text-xs font-semibold uppercase text-fore-secondary">Total Recette (90%)</span>
                <div class="text-2xl font-extrabold font-mono text-fore mt-2"><?= formatFCFA($stats['netRevenue']) ?></div>
                <div class="text-xs text-fore-secondary mt-1">Sur <?= formatFCFA($stats['totalRevenue']) ?> brut récoltés</div>
            </div>

            <!-- 4. Total Retirable -->
            <div class="p-5 rounded-2xl bg-surface-secondary border border-primary/40 shadow-sm">
                <span class="text-xs font-bold uppercase text-primary">Total Retirable</span>
                <div class="text-2xl font-extrabold font-mono text-primary mt-2"><?= formatFCFA($stats['availableBalance']) ?></div>
                <div class="text-xs text-fore-secondary mt-1">Disponible après déduction des retraits</div>
            </div>
        </section>

        <!-- ONGLETS -->
        <div class="flex items-center gap-2 border-b border-bordercustom overflow-x-auto pb-1 scrollbar-none">
            <button onclick="switchTab('candidates')" id="tab-btn-candidates" class="tab-btn active border-b-2 border-primary text-primary px-4 py-2.5 text-xs sm:text-sm font-bold whitespace-nowrap cursor-pointer">
                Candidats & Résultats
            </button>
            <button onclick="switchTab('transactions')" id="tab-btn-transactions" class="tab-btn text-fore-secondary hover:text-fore px-4 py-2.5 text-xs sm:text-sm font-bold whitespace-nowrap cursor-pointer">
                Transactions (<?= count($transactions) ?>)
            </button>
            <button onclick="switchTab('payouts')" id="tab-btn-payouts" class="tab-btn text-fore-secondary hover:text-fore px-4 py-2.5 text-xs sm:text-sm font-bold whitespace-nowrap cursor-pointer">
                Retraits (<?= count($payoutRequests) ?>)
            </button>
        </div>

        <!-- ONGLET 1 : CANDIDATS & CLASSEMENTS -->
        <div id="tab-candidates" class="tab-content space-y-6">
            <div class="rounded-3xl bg-surface-secondary border border-bordercustom shadow-sm overflow-hidden p-6 space-y-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-bold text-fore">Liste des Candidats</h3>
                        <p class="text-xs text-fore-secondary">Gérez les fiches et les dossards de vos participants.</p>
                    </div>
                    <?php if ($campaign['computed_status'] === 'draft'): ?>
                    <button onclick="toggleModal('candidateModal', true)" class="px-4 py-2.5 rounded-xl bg-primary hover:bg-primary-dark text-white text-xs font-bold flex items-center gap-2 transition shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Ajouter un candidat</span>
                    </button>
                    <?php endif; ?>
                </div>

                <?php if ($campaign['computed_status'] === 'draft'): ?>
                    <div class="flex flex-wrap items-center gap-2">
                        <?php foreach ($categories as $category): ?>
                            <span class="inline-flex items-center gap-1 rounded-full border border-bordercustom bg-surface px-3 py-1 text-xs">
                                <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                                <button type="button" class="category-rename text-primary" data-category-id="<?= (int)$category['category_id'] ?>" data-category-name="<?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>" aria-label="Renommer catégorie">✎</button>
                                <?php if (!array_filter($candidates, static fn(array $candidate): bool => (int)($candidate['category_id'] ?? 0) === (int)$category['category_id'])): ?>
                                    <button type="button" class="category-delete text-red-500" data-category-id="<?= (int)$category['category_id'] ?>" aria-label="Supprimer catégorie">×</button>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                        <button type="button" id="addCategoryBtn" class="rounded-full border border-primary px-3 py-1 text-xs font-bold text-primary">+ Catégorie</button>
                    </div>
                <?php endif; ?>

                <div class="overflow-x-auto rounded-2xl border border-bordercustom bg-surface">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead class="bg-surface-secondary text-fore-secondary uppercase text-[10px] border-b border-bordercustom font-bold">
                            <tr>
                                <th class="py-3 px-4">Dossard & Nom</th>
                                <th class="py-3 px-4">Catégorie</th>
                                <th class="py-3 px-4">Thème / Profil</th>
                                <th class="py-3 px-4 text-right">Votes</th>
                                <th class="py-3 px-4 text-right">Recettes</th>
                                <?php if ($campaign['computed_status'] === 'draft'): ?><th class="py-3 px-4 text-right">Actions</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-bordercustom text-fore">
                            <?php if (empty($candidates)): ?>
                                <tr>
                                    <td colspan="<?= $campaign['computed_status'] === 'draft' ? 6 : 5 ?>" class="py-8 text-center text-fore-secondary">Aucun candidat pour le moment. Cliquez sur "Ajouter un candidat".</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($candidates as $cand): ?>
                                    <tr class="hover:bg-surface-secondary/60 transition">
                                        <td class="py-3.5 px-4 flex items-center gap-3">
                                            <span class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs bg-surface-secondary border border-bordercustom text-fore font-mono shrink-0">
                                                <?= str_pad((string)$cand['candidate_number'], 2, '0', STR_PAD_LEFT) ?>
                                            </span>
                                            <?php if (!empty($cand['image_url'])): ?>
                                                <img src="<?= htmlspecialchars($cand['image_url'], ENT_QUOTES, 'UTF-8') ?>" class="w-9 h-12 rounded-lg object-cover shrink-0 border border-bordercustom">
                                            <?php else: ?>
                                                <div class="w-9 h-12 rounded-lg bg-surface-secondary border border-bordercustom flex items-center justify-center font-bold text-xs text-fore-secondary shrink-0">
                                                    <?= strtoupper(substr($cand['name'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="font-bold text-fore"><?= htmlspecialchars($cand['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="text-[11px] text-fore-secondary"><?= $cand['age'] ? (int)$cand['age'].' ans' : '' ?></div>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-primary/10 text-primary border border-primary/20">
                                                <?= htmlspecialchars($cand['category_name'] ?? 'Général', ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-fore-secondary"><?= htmlspecialchars($cand['theme'] ?? 'Non spécifié', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="py-3.5 px-4 text-right font-mono font-bold text-fore"><?= number_format($cand['total_votes']) ?></td>
                                        <td class="py-3.5 px-4 text-right font-mono font-bold text-primary"><?= formatFCFA($cand['revenue']) ?></td>
                                        <?php if ($campaign['computed_status'] === 'draft'): ?>
                                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                                <button type="button" class="candidate-edit text-primary font-bold" data-candidate-id="<?= (int)$cand['candidate_id'] ?>" data-name="<?= htmlspecialchars($cand['name'], ENT_QUOTES, 'UTF-8') ?>" data-number="<?= (int)$cand['candidate_number'] ?>" data-age="<?= (int)($cand['age'] ?? 0) ?>" data-theme="<?= htmlspecialchars($cand['theme'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-description="<?= htmlspecialchars($cand['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-bio="<?= htmlspecialchars($cand['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-category-id="<?= (int)($cand['category_id'] ?? 0) ?>">Modifier</button>
                                                <button type="button" class="candidate-delete ml-3 text-red-500 font-bold" data-candidate-id="<?= (int)$cand['candidate_id'] ?>" data-name="<?= htmlspecialchars($cand['name'], ENT_QUOTES, 'UTF-8') ?>">Supprimer</button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ONGLET 2 : TRANSACTIONS -->
        <div id="tab-transactions" class="tab-content hidden rounded-3xl bg-surface-secondary border border-bordercustom shadow-sm p-6 space-y-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <input type="text" id="txSearch" placeholder="Rechercher par référence, candidat..." class="flex-1 px-4 py-2.5 rounded-xl bg-surface border border-bordercustom text-xs text-fore placeholder:text-fore-secondary focus:ring-2 focus:ring-primary focus:outline-none transition">
                <select id="paymentFilter" class="px-3 py-2.5 rounded-xl bg-surface border border-bordercustom text-xs text-fore outline-none cursor-pointer">
                    <option value="all">Tous les modes de paiement</option>
                    <option value="mtn_momo">MTN MoMo</option>
                    <option value="orange_money">Orange Money</option>
                </select>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-bordercustom bg-surface">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead class="bg-surface-secondary text-fore-secondary uppercase text-[10px] font-bold border-b border-bordercustom">
                        <tr>
                            <th class="py-3 px-4">Réf. Transaction</th>
                            <th class="py-3 px-4">Candidat</th>
                            <th class="py-3 px-4">Moyen</th>
                            <th class="py-3 px-4 text-right">Votes</th>
                            <th class="py-3 px-4 text-right">Montant</th>
                            <th class="py-3 px-4">Date</th>
                        </tr>
                    </thead>
                    <tbody id="txTableBody" class="divide-y divide-bordercustom text-fore">
                        <?php if (empty($transactions)): ?>
                            <tr><td colspan="6" class="py-8 text-center text-fore-secondary">Aucune transaction enregistrée.</td></tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): ?>
                                <tr class="tx-row hover:bg-surface-secondary/60 transition" data-search="<?= strtolower($tx['transaction_ref'] . ' ' . $tx['candidate_name']) ?>" data-method="<?= $tx['payment_method'] ?>">
                                    <td class="py-3.5 px-4 font-mono text-xs font-semibold text-primary"><?= htmlspecialchars($tx['transaction_ref'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="py-3.5 px-4 flex items-center gap-2">
                                        <?php if (!empty($tx['candidate_avatar'])): ?>
                                            <img src="<?= htmlspecialchars($tx['candidate_avatar'], ENT_QUOTES, 'UTF-8') ?>" class="w-7 h-7 rounded-full object-cover border border-bordercustom">
                                        <?php endif; ?>
                                        <span class="font-bold"><?= htmlspecialchars($tx['candidate_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td class="py-3.5 px-4"><span class="px-2 py-0.5 rounded-full text-[10px] uppercase font-semibold bg-surface-secondary border border-bordercustom text-fore"><?= str_replace('_', ' ', $tx['payment_method']) ?></span></td>
                                    <td class="py-3.5 px-4 text-right font-mono font-bold text-primary">+<?= (int)$tx['vote_count'] ?></td>
                                    <td class="py-3.5 px-4 text-right font-mono font-bold text-fore"><?= formatFCFA($tx['amount_fcfa']) ?></td>
                                    <td class="py-3.5 px-4 text-xs text-fore-secondary"><?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ONGLET 3 : RETRAITS -->
        <div id="tab-payouts" class="tab-content hidden rounded-3xl bg-surface-secondary border border-bordercustom shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between pb-4 border-b border-bordercustom">
                <div>
                    <h3 class="text-base font-bold text-fore">Historique des Retraits</h3>
                    <p class="text-xs text-fore-secondary">Versements effectués vers vos comptes Mobile Money.</p>
                </div>
                <button onclick="toggleModal('payoutModal', true)" class="px-4 py-2.5 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary-dark transition shadow">Nouveau Retrait</button>
            </div>
            
            <div class="overflow-x-auto rounded-2xl border border-bordercustom bg-surface">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead class="bg-surface-secondary text-fore-secondary uppercase text-[10px] font-bold border-b border-bordercustom">
                        <tr>
                            <th class="py-3 px-4">Réf Retrait</th>
                            <th class="py-3 px-4">Montant</th>
                            <th class="py-3 px-4">Moyen</th>
                            <th class="py-3 px-4">Bénéficiaire / Mobile</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bordercustom text-fore">
                        <?php if (empty($payoutRequests)): ?>
                            <tr><td colspan="6" class="py-8 text-center text-fore-secondary">Aucune demande de retrait effectuée.</td></tr>
                        <?php else: ?>
                            <?php foreach ($payoutRequests as $req): ?>
                                <tr class="hover:bg-surface-secondary/60 transition">
                                    <td class="py-3.5 px-4 font-mono text-primary font-bold">#PO-<?= (int)$req['payout_id'] ?></td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-fore"><?= formatFCFA($req['amount_fcfa']) ?></td>
                                    <td class="py-3.5 px-4"><span class="px-2 py-0.5 rounded-full text-[10px] uppercase font-semibold bg-surface-secondary border border-bordercustom"><?= str_replace('_', ' ', $req['payment_method']) ?></span></td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-fore"><?= htmlspecialchars($req['account_holder'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-[11px] text-fore-secondary font-mono"><?= htmlspecialchars($req['wallet_number'], ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td class="py-3.5 px-4 text-xs text-fore-secondary"><?= date('d/m/Y H:i', strtotime($req['created_at'])) ?></td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $req['status'] === 'completed' ? 'bg-green-500/10 text-green-600 border border-green-500/20' : 'bg-amber-500/10 text-amber-500 border border-amber-500/20' ?>">
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

        <!-- Classements par Catégories -->
        <div class="space-y-4">
            <h3 class="text-lg font-bold text-fore">Classements par Catégories</h3>
            <?php if (empty($candidates)): ?>
                <div class="p-8 rounded-3xl bg-surface-secondary border border-bordercustom text-center text-fore-secondary text-xs">
                    Aucun classement disponible pour le moment. Veuillez ajouter des candidats pour visualiser la répartition des votes.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($candidatesByCategory as $categoryName => $cands): ?>
                        <div class="p-6 rounded-3xl bg-surface-secondary border border-bordercustom shadow-sm space-y-4">
                            <h4 class="text-xs font-bold text-primary uppercase tracking-wider flex items-center justify-between border-b border-bordercustom pb-3">
                                <span>Catégorie : <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="text-fore-secondary font-normal"><?= count($cands) ?> participant(s)</span>
                            </h4>

                            <div class="space-y-3">
                                <?php foreach ($cands as $rank => $cand): ?>
                                    <div class="p-3.5 rounded-2xl bg-surface border border-bordercustom flex items-center justify-between gap-3 shadow-sm">
                                        <div class="flex items-center gap-3">
                                            <span class="w-6 h-6 rounded-md flex items-center justify-center font-bold text-xs <?= $rank === 0 ? 'bg-amber-500 text-black' : 'bg-surface-secondary border border-bordercustom text-fore-secondary' ?>">
                                                #<?= $rank + 1 ?>
                                            </span>
                                            <div>
                                                <div class="font-bold text-xs sm:text-sm text-fore"><?= htmlspecialchars($cand['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="text-[10px] text-fore-secondary font-mono"><?= number_format($cand['total_votes']) ?> votes (<?= $cand['percentage'] ?>%)</div>
                                            </div>
                                        </div>

                                        <div class="w-24">
                                            <div class="w-full h-1.5 bg-surface-secondary border border-bordercustom rounded-full overflow-hidden">
                                                <div class="h-full bg-primary rounded-full transition-all duration-300" style="width: <?= (float)$cand['percentage'] ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- MODAL 1 : AJOUTER CANDIDAT -->
    <div id="candidateModal" class="fixed inset-0 bg-fore/60 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="bg-surface border border-bordercustom rounded-3xl p-6 sm:p-8 w-full max-w-lg space-y-5 max-h-[90vh] overflow-y-auto shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-bordercustom">
                <h3 class="text-base sm:text-lg font-bold text-fore">Ajouter un nouveau candidat</h3>
                <button type="button" onclick="toggleModal('candidateModal', false)" class="text-fore-secondary hover:text-fore">✕</button>
            </div>

            <form id="formCandidate" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-fore mb-1">Nom Complet *</label>
                    <input type="text" name="name" required placeholder="Ex: Jean Dupont" class="w-full px-4 py-2.5 bg-surface-secondary border border-bordercustom rounded-xl text-fore text-xs focus:ring-2 focus:ring-primary focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-fore mb-1">Numéro Dossard *</label>
                        <input type="number" name="candidate_number" min="1" required placeholder="Ex: 1" class="w-full px-4 py-2.5 bg-surface-secondary border border-bordercustom rounded-xl text-fore text-xs focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-fore mb-1">Âge</label>
                        <input type="number" name="age" min="1" placeholder="Ex: 22" class="w-full px-4 py-2.5 bg-surface-secondary border border-bordercustom rounded-xl text-fore text-xs focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-fore mb-1">Catégorie</label>
                    <select name="category_id" id="categorySelect" class="w-full px-4 py-2.5 bg-surface-secondary border border-bordercustom rounded-xl text-fore text-xs focus:ring-2 focus:ring-primary focus:outline-none">
                        <option value="">Sélectionner une catégorie...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['category_id'] ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                        <option value="new">+ Créer une nouvelle catégorie</option>
                    </select>
                </div>

                <div id="newCategoryWrapper" class="hidden">
                    <label class="block text-xs font-bold text-primary mb-1">Nom de la nouvelle catégorie *</label>
                    <input type="text" name="new_category_name" id="newCategoryName" placeholder="Ex: Miss, Master, Prix du Public..." class="w-full px-4 py-2.5 bg-surface-secondary border border-primary rounded-xl text-fore text-xs focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-fore mb-1">Filière / Thème représenté</label>
                    <input type="text" name="theme" placeholder="Ex: Faculté de Médecine" class="w-full px-4 py-2.5 bg-surface-secondary border border-bordercustom rounded-xl text-fore text-xs focus:ring-2 focus:ring-primary focus:outline-none">
                </div>

                <!-- Widget Photo Candidat -->
                <div>
                    <label class="block text-xs font-bold text-fore mb-1">Photo du Candidat (Format 3:4)</label>
                    <input type="file" id="candidateImageInput" accept="image/jpeg,image/png,image/webp" class="hidden">
                    
                    <div id="candidateUploadPlaceholder" class="border-2 border-dashed border-bordercustom hover:border-primary/60 bg-surface-secondary rounded-2xl p-4 text-center cursor-pointer transition flex flex-col items-center justify-center gap-1.5">
                        <span class="text-xs font-bold text-fore">Sélectionner une photo</span>
                        <span class="text-[10px] text-fore-secondary">Format portrait 3:4 (JPG, PNG, WEBP)</span>
                    </div>

                    <div id="candidatePreviewContainer" class="hidden mt-2">
                        <div class="relative w-32 aspect-[3/4] rounded-xl overflow-hidden border border-bordercustom mx-auto">
                            <img id="candidateImagePreview" src="" class="w-full h-full object-cover">
                            <button type="button" id="btnRemoveCandidateImage" class="absolute top-1 right-1 p-1 bg-red-500/90 text-white rounded-md text-xs">✕</button>
                        </div>
                    </div>

                    <div id="candidateProgressContainer" class="hidden mt-2 space-y-1">
                        <div class="flex justify-between text-[11px] font-bold text-fore">
                            <span id="candidateUploadStatus">Envoi...</span>
                            <span id="candidateUploadPercent">0%</span>
                        </div>
                        <div class="w-full bg-surface-secondary h-1.5 rounded-full overflow-hidden border border-bordercustom">
                            <div id="candidateProgressBar" class="bg-primary h-full w-0 transition-all duration-150"></div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-bordercustom">
                    <button type="button" onclick="toggleModal('candidateModal', false)" class="px-4 py-2.5 bg-surface-secondary border border-bordercustom text-fore text-xs font-bold rounded-xl hover:bg-surface transition">Annuler</button>
                    <button type="submit" id="submitCandidateBtn" class="px-5 py-2.5 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary-dark transition shadow">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2 : CHANGER LA PHOTO DE LA CAMPAGNE (16:9) -->
    <div id="photoModal" class="fixed inset-0 bg-fore/60 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="bg-surface border border-bordercustom rounded-3xl p-6 sm:p-8 w-full max-w-md space-y-4 shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-bordercustom">
                <h3 class="text-base font-bold text-fore">Changer l'affiche (16:9)</h3>
                <button type="button" onclick="toggleModal('photoModal', false)" class="text-fore-secondary hover:text-fore">✕</button>
            </div>

            <form id="formPhoto" class="space-y-4">
                <input type="file" id="campaignImageInput" accept="image/jpeg,image/png,image/webp" class="hidden">
                
                <div id="campaignUploadPlaceholder" class="border-2 border-dashed border-bordercustom hover:border-primary/60 bg-surface-secondary rounded-2xl p-6 text-center cursor-pointer transition flex flex-col items-center justify-center gap-2">
                    <span class="text-xs font-bold text-fore">Cliquez pour choisir une nouvelle affiche</span>
                    <span class="text-[11px] text-fore-secondary">Format paysage 16:9 (Max. 5 Mo)</span>
                </div>

                <div id="campaignPreviewContainer" class="hidden">
                    <div class="relative w-full aspect-video rounded-2xl overflow-hidden border border-bordercustom mx-auto shadow-md">
                        <img id="campaignImagePreview" src="" class="w-full h-full object-cover">
                        <button type="button" id="btnRemoveCampaignImage" class="absolute top-2 right-2 p-1.5 bg-red-500 text-white rounded-md text-xs">✕</button>
                    </div>
                </div>

                <div id="campaignProgressContainer" class="hidden space-y-1">
                    <div class="flex justify-between text-[11px] font-bold text-fore">
                        <span id="campaignUploadStatus">Téléversement...</span>
                        <span id="campaignUploadPercent">0%</span>
                    </div>
                    <div class="w-full bg-surface-secondary h-1.5 rounded-full overflow-hidden border border-bordercustom">
                        <div id="campaignProgressBar" class="bg-primary h-full w-0 transition-all duration-150"></div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-bordercustom">
                    <button type="button" onclick="toggleModal('photoModal', false)" class="px-4 py-2.5 bg-surface-secondary border border-bordercustom text-fore text-xs font-bold rounded-xl hover:bg-surface">Annuler</button>
                    <button type="submit" id="submitCampaignPhotoBtn" class="px-5 py-2.5 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary-dark shadow">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3 : MODIFIER INFOS CAMPAGNE -->
    <div id="editCampaignModal" class="fixed inset-0 bg-fore/60 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="bg-surface border border-bordercustom rounded-3xl p-6 sm:p-8 w-full max-w-lg space-y-4 max-h-[90vh] overflow-y-auto shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-bordercustom">
                <h3 class="text-base font-bold text-fore">Modifier la campagne</h3>
                <button type="button" onclick="toggleModal('editCampaignModal', false)" class="text-fore-secondary hover:text-fore">✕</button>
            </div>

            <form id="formEditCampaign" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-fore mb-1">Titre de la campagne *</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($campaign['title'], ENT_QUOTES, 'UTF-8') ?>" required class="w-full px-4 py-2.5 bg-surface-secondary border border-bordercustom rounded-xl text-fore text-xs focus:ring-2 focus:ring-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-fore mb-1">Description *</label>
                    <textarea name="description" rows="3" required class="w-full px-4 py-2.5 bg-surface-secondary border border-bordercustom rounded-xl text-fore text-xs focus:ring-2 focus:ring-primary focus:outline-none"><?= htmlspecialchars($campaign['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-fore mb-1">Prix par vote (FCFA) *</label>
                        <input type="number" name="price_per_vote" min="50" step="50" value="<?= (int)$campaign['price_per_vote'] ?>" required class="w-full px-4 py-2.5 bg-surface-secondary border border-bordercustom rounded-xl text-fore text-xs focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-fore mb-1">Date et heure de clôture</label>
                        <input type="datetime-local" id="edit_date_cloture" name="date_cloture" value="<?= $campaign['date_cloture'] ? date('Y-m-d\TH:i', strtotime($campaign['date_cloture'])) : '' ?>" class="w-full px-4 py-2.5 bg-surface-secondary border border-bordercustom rounded-xl text-fore text-xs focus:ring-2 focus:ring-primary focus:outline-none dark:[color-scheme:dark]">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-bordercustom">
                    <button type="button" onclick="toggleModal('editCampaignModal', false)" class="px-4 py-2.5 bg-surface-secondary border border-bordercustom text-fore text-xs font-bold rounded-xl hover:bg-surface">Annuler</button>
                    <button type="submit" class="px-5 py-2.5 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary-dark shadow">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4 : RELANCER UNE CAMPAGNE -->
    <div id="relaunchModal" class="fixed inset-0 bg-fore/60 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="bg-surface border border-bordercustom rounded-3xl p-6 sm:p-8 w-full max-w-md space-y-4 shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-bordercustom">
                <h3 class="text-base font-bold text-fore">Relancer la Campagne</h3>
                <button type="button" onclick="toggleModal('relaunchModal', false)" class="text-fore-secondary hover:text-fore">✕</button>
            </div>
            
            <p class="text-xs text-fore-secondary">Définissez une nouvelle date de clôture située au moins 5 minutes dans le futur.</p>
            
            <form id="formRelaunch" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-fore mb-1">Nouvelle date de clôture *</label>
                    <input type="datetime-local" id="relaunch_date_cloture" name="date_cloture" required class="w-full px-4 py-2.5 bg-surface-secondary border border-bordercustom rounded-xl text-fore text-xs focus:ring-2 focus:ring-primary focus:outline-none dark:[color-scheme:dark]">
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-bordercustom">
                    <button type="button" onclick="toggleModal('relaunchModal', false)" class="px-4 py-2.5 bg-surface-secondary border border-bordercustom text-fore text-xs font-bold rounded-xl hover:bg-surface">Annuler</button>
                    <button type="submit" class="px-5 py-2.5 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary-dark shadow">Repasser en direct</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5 : RETRAIT DE FONDS -->
    <div id="payoutModal" class="fixed inset-0 bg-fore/60 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="bg-surface border border-bordercustom rounded-3xl p-6 sm:p-8 w-full max-w-md space-y-4 shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-bordercustom">
                <h3 class="text-base font-bold text-fore">Demande de Retrait</h3>
                <button type="button" onclick="toggleModal('payoutModal', false)" class="text-fore-secondary hover:text-fore">✕</button>
            </div>

            <p class="text-xs text-fore-secondary">Solde disponible : <strong class="text-primary font-bold"><?= formatFCFA($stats['availableBalance']) ?></strong></p>
            
            <form id="formPayout" class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-fore mb-1">Montant à retirer (FCFA) *</label>
                    <input type="number" name="amount" min="500" max="<?= (int)$stats['availableBalance'] ?>" required placeholder="Ex: 50000" class="w-full px-4 py-2.5 bg-surface-secondary border border-bordercustom rounded-xl text-fore text-xs focus:ring-2 focus:ring-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-fore mb-1">Moyen de Réception *</label>
                    <select name="payment_method" class="w-full px-4 py-2.5 bg-surface-secondary border border-bordercustom rounded-xl text-fore text-xs focus:ring-2 focus:ring-primary focus:outline-none">
                        <option value="mtn_momo">MTN MoMo</option>
                        <option value="orange_money">Orange Money</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-fore mb-1">Nom du Bénéficiaire *</label>
                    <input type="text" name="account_holder" required placeholder="Ex: Jean Paul" class="w-full px-4 py-2.5 bg-surface-secondary border border-bordercustom rounded-xl text-fore text-xs focus:ring-2 focus:ring-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-fore mb-1">Numéro Mobile Money *</label>
                    <input type="tel" name="wallet_number" required placeholder="699001122" class="w-full px-4 py-2.5 bg-surface-secondary border border-bordercustom rounded-xl text-fore text-xs focus:ring-2 focus:ring-primary focus:outline-none">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-bordercustom">
                    <button type="button" onclick="toggleModal('payoutModal', false)" class="px-4 py-2.5 bg-surface-secondary border border-bordercustom text-fore text-xs font-bold rounded-xl hover:bg-surface transition">Annuler</button>
                    <button type="submit" class="px-5 py-2.5 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary-dark shadow transition">Confirmer le Retrait</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL GÉNÉRIQUE DE RECADRAGE -->
    <div id="genericCropModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
        <div class="bg-surface rounded-3xl border border-bordercustom p-6 max-w-lg w-full space-y-4 shadow-2xl">
            <h3 class="text-base font-bold text-fore" id="genericCropTitle">Recadrer la photo</h3>
            <div class="w-full max-h-96 overflow-hidden rounded-xl bg-black">
                <img id="genericCropTarget" class="max-w-full block" alt="Image à recadrer">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" id="btnCancelGenericCrop" class="px-4 py-2 rounded-xl border border-bordercustom text-xs font-bold text-fore hover:bg-surface-secondary transition">
                    Annuler
                </button>
                <button type="button" id="btnApplyGenericCrop" class="px-5 py-2 rounded-xl bg-primary hover:bg-primary-dark text-white text-xs font-bold shadow-md transition">
                    Valider le recadrage
                </button>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="mt-auto border-t border-bordercustom bg-surface-secondary pt-12 pb-8 text-fore-secondary">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
                
                <div class="space-y-4 md:col-span-1">
                    <div class="font-display flex items-center gap-2 text-xl font-bold text-fore">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-white">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>    
                        </span>
                        IVote
                    </div>
                    <p class="text-xs leading-relaxed text-fore-secondary">
                        La plateforme moderne pour vos concours, élections et votes en ligne sécurisés et en temps réel.
                    </p>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-fore">Navigation</h3>
                    <ul class="mt-4 space-y-2 text-xs">
                        <li><a href="/" class="transition-colors hover:text-primary">Accueil</a></li>
                        <li><a href="/campaigns" class="transition-colors hover:text-primary">Explorer les campagnes</a></li>
                        <li><a href="/dashboard" class="transition-colors hover:text-primary">Espace organisateur</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-fore">Légal</h3>
                    <ul class="mt-4 space-y-2 text-xs">
                        <li><a href="#" class="transition-colors hover:text-primary">Conditions d'utilisation</a></li>
                        <li><a href="#" class="transition-colors hover:text-primary">Politique de confidentialité</a></li>
                        <li><a href="#" class="transition-colors hover:text-primary">Mentions légales</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-fore">Support</h3>
                    <ul class="mt-4 space-y-2 text-xs">
                        <li><a href="mailto:support@ivote.com" class="text-primary hover:underline">support@ivote.com</a></li>
                    </ul>
                </div>

            </div>
            
            <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-bordercustom pt-6 text-xs text-fore-secondary sm:flex-row">
                <p>&copy; 2026 IVote. Tous droits réservés.</p>
                <p>Votez en toute sécurité.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script src="/public/js/theme_toggle.js"></script>
    <script src="/public/js/notification_badge.js" defer></script>
    <script src="/public/js/campaign_dashboard.js"></script>
</body>
</html>