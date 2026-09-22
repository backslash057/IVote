<?php
require_once $_SERVER["DOCUMENT_ROOT"] . '/controllers/CampaignController.php';

try {
    $campaignController = new CampaignController();
    $popularCampaigns = $campaignController->getActivePopularCampaigns(4);
} catch (Throwable $e) {
    error_log("[Home Popular Campaigns] " . $e->getMessage());
    $popularCampaigns = [];
}
?>

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IVote - Votes Monétisés</title>
    
    <!-- Police : Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="/public/js/tailwindcss/tailwindcss.js"></script>
    <script src="/public/js/tailwindcss/tailwindcss.config.js"></script>

    <link rel="stylesheet" href="/public/css/index.css">    
</head>
<body class="antialiased selection:bg-blue-500 selection:text-white flex flex-col min-h-screen">

    <!-- Navbar -->
    <header class="sticky top-0 z-40 bg-surface/90 backdrop-blur-md border-b border-bordercustom transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                
                <!-- Logo -->
                <a href="/" class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-md">
                        <!-- Lucide: vote / sparkles -->
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
                    <a href="#home" class="text-sm font-semibold text-primary hover:text-primary-dark transition">Accueil</a>
                    <a href="#campaigns" class="text-sm font-medium text-fore-secondary hover:text-fore transition">Campagnes</a>
                    <a href="#how-it-works" class="text-sm font-medium text-fore-secondary hover:text-fore transition">Comment ça marche</a>
                    <a href="#contact" class="text-sm font-medium text-fore-secondary hover:text-fore transition">Contact</a>
                </nav>

                <!-- Actions: Dark Theme, Get Started -->
                <div class="hidden md:flex items-center gap-3">
                    <!-- Bouton Dark Mode -->
                    <button id="theme-toggle" class="p-2.5 rounded-xl border border-bordercustom hover:bg-surface-secondary text-fore transition" aria-label="Basculer le mode sombre">
                        <!-- Sun Icon (visible in dark) -->
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
                        <!-- Moon Icon (visible in light) -->
                        <svg id="theme-icon-moon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </button>

                    <!-- Get Started -->
                    <a href="/dashboard" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white font-semibold text-xs px-5 py-2.5 rounded-xl shadow-md transition transform hover:-translate-y-0.5">
                        <span>Espace organisateur</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
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
            <a href="#home" class="block py-2 text-sm font-semibold text-primary">Accueil</a>
            <a href="#campaigns" class="block py-2 text-sm font-medium text-fore-secondary">Campagnes</a>
            <a href="#how-it-works" class="block py-2 text-sm font-medium text-fore-secondary">Comment ça marche</a>
            <a href="#contact" class="block py-2 text-sm font-medium text-fore-secondary">Contact</a>
            <div class="pt-3 border-t border-bordercustom">
                <a href="#how-it-works" class="block w-full text-center py-2.5 font-semibold bg-primary text-white rounded-xl text-xs">Lancer une campagne</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section id="home" class="py-14 lg:py-20 bg-surface">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                <!-- Left Column -->
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 bg-surface-secondary border border-bordercustom px-3 py-1.5 rounded-full text-xs font-semibold text-primary">
                        <svg class="w-4 h-4 text-primary-blue" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"></path>
                            <path d="M20 2v4"></path>
                            <path d="M22 4h-4"></path>
                            <circle cx="4" cy="20" r="2"></circle>
                        </svg>
                        <span>Plateforme autonome de votes monétisés</span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-fore tracking-tight leading-[1.15]">
                        Créez vos <span class="text-primary">campagnes de vote</span> et encaissez en direct
                    </h1>

                    <p class="text-base sm:text-lg text-fore-secondary max-w-2xl font-normal leading-relaxed mx-auto lg:mx-0">
                        Lancez votre concours ou scrutin en toute autonomie. Chaque organisateur gère ses participants, définit ses tarifs de vote et reçoit ses gains instantanément.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-2">
                        <a href="/campaigns/new" class="w-full sm:w-auto px-8 py-3.5 bg-primary hover:bg-primary-dark text-white font-bold text-sm rounded-xl shadow-md transition flex items-center justify-center gap-2">
                            <span>Créer ma campagne</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                        <a href="/campaigns" class="w-full sm:w-auto px-8 py-3.5 bg-surface-secondary hover:bg-surface border border-bordercustom text-fore font-bold text-sm rounded-xl transition flex items-center justify-center gap-2">
                            <span>Voir les campagnes en cours</span>
                        </a>
                    </div>
                </div>

                <!-- Right Column: Interactive Live Dashboard Card -->
                <div class="lg:col-span-5 relative">
                    <div class="bg-surface-secondary rounded-3xl p-6 shadow-xl border border-bordercustom space-y-5">
                        
                        <!-- Header of Widget -->
                        <div class="flex items-center justify-between pb-3 border-b border-bordercustom">
                            <div class="flex items-center gap-2 bg-green-500/10 rounded-full px-2.5 py-0.5">
                                <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
                                <span class="text-xs font-bold uppercase tracking-wider text-green-600">
                                    EN DIRECT
                                </span>
                            </div>
                            <span class="text-xs text-fore-secondary">Mis à jour à l'instant</span>
                        </div>

                        <!-- Info Campagne -->
                        <div>
                            <h3 class="text-base font-bold text-fore">Concours Voix de l'Année</h3>
                            <p class="text-xs text-fore-secondary mt-1">Total des votes : <span class="font-bold text-fore">184 920</span></p>
                        </div>

                        <!-- Candidats -->
                        <div class="space-y-3">
                            <div class="bg-surface p-3.5 rounded-xl border border-bordercustom flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-full bg-primary text-white font-bold text-xs flex items-center justify-center">01</div>
                                    <span class="text-xs font-bold text-fore">Ama Serwaa</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-extrabold text-fore block">88 760</span>
                                    <span class="text-[10px] text-green-600 font-bold">48%</span>
                                </div>
                            </div>

                            <div class="bg-surface p-3.5 rounded-xl border border-bordercustom flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-full bg-surface-secondary text-fore font-bold text-xs flex items-center justify-center border border-bordercustom">02</div>
                                    <span class="text-xs font-bold text-fore">Abena Kwarteng</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-extrabold text-fore block">59 170</span>
                                    <span class="text-[10px] text-fore-secondary font-bold">32%</span>
                                </div>
                            </div>

                            <div class="bg-surface p-3.5 rounded-xl border border-bordercustom flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-full bg-surface-secondary text-fore font-bold text-xs flex items-center justify-center border border-bordercustom">03</div>
                                    <span class="text-xs font-bold text-fore">Fatima Oumarou</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-extrabold text-fore block">36 990</span>
                                    <span class="text-[10px] text-fore-secondary font-bold">20%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Operateurs Mobile Money -->
                        <div class="pt-3 border-t border-bordercustom flex items-center justify-between text-xs text-fore-secondary">
                            <span>Paiements mobiles acceptés</span>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 bg-yellow-400/10 text-yellow-600 border border-yellow-400/30 text-[11px] font-bold rounded">🟡 MTN MoMo</span>
                                <span class="px-2 py-0.5 bg-orange-400/10 text-orange-600 border border-orange-400/30 text-[11px] font-bold rounded">🟠 Orange Money</span>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Popular Campaigns -->
    <section id="campaigns" class="py-16 bg-surface-secondary border-t border-bordercustom">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-10 text-center sm:text-left">
                <span class="text-xs font-extrabold uppercase tracking-widest text-primary">En vedette</span>
                <h2 class="text-3xl font-extrabold text-fore mt-1">Campagnes Populaires</h2>
                <p class="text-xs text-fore-secondary mt-1">Soutenez vos candidats favoris dès maintenant.</p>
            </div>

            <?php if (!empty($popularCampaigns)): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($popularCampaigns as $campaign): ?>
                        <?php
                            $campaignId = (int) ($campaign['campaign_id'] ?? 0);
                            $imageUrl = !empty($campaign['image_url']) ? $campaign['image_url'] : null;
                            $organizer = $campaign['organizer_name'] ?? 'Organisateur';
                            $title = $campaign['title'] ?? 'Campagne sans titre';
                            $totalVotes = (int) ($campaign['totalVotes'] ?? 0);
                            $candidateCount = (int) ($campaign['candidate_count'] ?? 0);
                        ?>
                        <div class="bg-surface rounded-2xl border border-bordercustom overflow-hidden shadow-sm flex flex-col justify-between hover:border-primary/50 transition">
                            <div>
                                <div class="relative h-44 bg-surface-secondary flex items-center justify-center overflow-hidden">
                                    <?php if ($imageUrl): ?>
                                        <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" 
                                             alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>" 
                                             class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="flex flex-col items-center justify-center text-fore-secondary p-4 text-center">
                                            <svg class="w-10 h-10 mb-1 opacity-40 text-fore" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span class="text-[11px] font-medium opacity-60">Aucune image</span>
                                        </div>
                                    <?php endif; ?>

                                    <span class="absolute top-3 right-3 bg-surface/90 backdrop-blur-md text-fore text-[10px] font-bold px-2.5 py-1 rounded-md border border-bordercustom shadow-sm flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                        <span>En direct</span>
                                    </span>
                                </div>
                                <div class="p-5 space-y-1">
                                    <h3 class="text-sm font-bold text-fore leading-snug line-clamp-1" title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                                    </h3>
                                    <p class="text-xs text-fore-secondary line-clamp-1">Par <?= htmlspecialchars($organizer, ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>

                            <div class="p-5 pt-0 space-y-3">
                                <div class="flex items-center justify-between text-xs font-semibold text-fore-secondary border-t border-bordercustom pt-3">
                                    <span><?= $candidateCount ?> candidat<?= $candidateCount > 1 ? 's' : '' ?></span>
                                    <span class="font-bold text-fore flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <?= number_format($totalVotes, 0, ',', ' ') ?> votes
                                    </span>
                                </div>
                                <a href="/campaigns/<?= $campaignId ?>" class="block w-full py-2.5 bg-primary hover:bg-primary-dark text-white text-xs font-bold text-center rounded-xl shadow transition">
                                    Voter
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-surface rounded-2xl border border-bordercustom p-8 text-center text-fore-secondary text-xs">
                    Aucune campagne active pour le moment.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Quick Onboarding (3 Steps) -->
    <section id="how-it-works" class="py-20 bg-surface">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs font-extrabold uppercase tracking-widest text-primary">Simple & Rapide</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-fore mt-2">
                    Lancez votre campagne en 3 étapes
                </h2>
                <p class="text-fore-secondary text-sm mt-2">Configurez votre scrutin et commencez à encaisser vos votes en toute liberté.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <div class="bg-surface-secondary p-8 rounded-2xl border border-bordercustom text-center">
                    <div class="w-12 h-12 rounded-xl bg-primary text-white font-extrabold text-lg flex items-center justify-center mx-auto mb-5 shadow">
                        1
                    </div>
                    <h3 class="text-base font-bold text-fore mb-2">Créez votre campagne</h3>
                    <p class="text-xs text-fore-secondary leading-relaxed">
                        Inscrivez-vous, ajoutez vos candidats ou options, et définissez le prix d'un vote unitaire.
                    </p>
                </div>

                <div class="bg-surface-secondary p-8 rounded-2xl border border-bordercustom text-center">
                    <div class="w-12 h-12 rounded-xl bg-orange-accent text-white font-extrabold text-lg flex items-center justify-center mx-auto mb-5 shadow">
                        2
                    </div>
                    <h3 class="text-base font-bold text-fore mb-2">Partagez le lien</h3>
                    <p class="text-xs text-fore-secondary leading-relaxed">
                        Diffusez votre lien unique et QR code à votre communauté et sur vos réseaux sociaux.
                    </p>
                </div>

                <div class="bg-surface-secondary p-8 rounded-2xl border border-bordercustom text-center">
                    <div class="w-12 h-12 rounded-xl bg-green-accent text-white font-extrabold text-lg flex items-center justify-center mx-auto mb-5 shadow">
                        3
                    </div>
                    <h3 class="text-base font-bold text-fore mb-2">Suivez et encaissez</h3>
                    <p class="text-xs text-fore-secondary leading-relaxed">
                        Consultez le décompte en direct et recevez automatiquement vos fonds sur votre compte.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- Contact & Demande d'informations -->
    <section id="contact" class="py-20 bg-surface-secondary border-t border-bordercustom">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-surface p-8 sm:p-10 rounded-3xl border border-bordercustom shadow-sm space-y-6">
                
                <div class="text-center space-y-2">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-primary">Assistance & Questions</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-fore">Une question sur la plateforme ?</h2>
                    <p class="text-xs text-fore-secondary max-w-md mx-auto">
                        Besoin d'aide pour paramétrer votre campagne ou d'un conseil ? Envoyez-nous votre demande ci-dessous.
                    </p>
                </div>

                <form id="info-form" class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-fore mb-1">Nom complet *</label>
                            <input type="text" required placeholder="Votre nom" class="w-full px-4 py-2.5 text-xs bg-surface-secondary border border-bordercustom rounded-xl text-fore focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-fore mb-1">Adresse e-mail *</label>
                            <input type="email" required placeholder="nom@domaine.com" class="w-full px-4 py-2.5 text-xs bg-surface-secondary border border-bordercustom rounded-xl text-fore focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-fore mb-1">Sujet de votre demande</label>
                        <select class="w-full px-4 py-2.5 text-xs bg-surface-secondary border border-bordercustom rounded-xl text-fore focus:ring-2 focus:ring-primary focus:outline-none">
                            <option>Informations générales</option>
                            <option>Aide à la configuration d'une campagne</option>
                            <option>Question sur les paiements et retraits</option>
                            <option>Autre demande</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-fore mb-1">Votre message *</label>
                        <textarea rows="4" required placeholder="Expliquez-nous brièvement votre besoin..." class="w-full px-4 py-2.5 text-xs bg-surface-secondary border border-bordercustom rounded-xl text-fore focus:ring-2 focus:ring-primary focus:outline-none"></textarea>
                    </div>

                    <button type="submit" class="w-full py-3 bg-primary hover:bg-primary-dark text-white font-bold text-xs rounded-xl shadow transition">
                        Envoyer ma demande
                    </button>
                </form>

            </div>
        </div>
    </section>

    <!-- Footer demandé avec style adapté aux variables de couleur -->
    <footer class="mt-auto border-t border-bordercustom bg-surface-secondary pt-12 pb-8 text-fore-secondary">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
                
                <!-- Marque & Description -->
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

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 bg-fore text-surface px-5 py-3 rounded-2xl shadow-xl border border-bordercustom flex items-center gap-2 transform translate-y-24 opacity-0 transition-all duration-300">
        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span id="toast-msg" class="text-xs font-bold">Message envoyé avec succès !</span>
    </div>

    <!-- Script : Thème Sombre -->
    <script src="/public/js/navbar.js"></script>

    <!-- Script : Section contact et toast de confirmation -->
    <script>
        // Contact Form
        document.getElementById('info-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            showToast("Votre demande a été transmise à notre équipe !");
            this.reset();
        });

        // Toast
        function showToast(msg) {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toast-msg');
            toastMsg.textContent = msg;
            toast.classList.remove('translate-y-24', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('translate-y-24', 'opacity-0');
            }, 3000);
        }
    </script>
</body>
</html>
