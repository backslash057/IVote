<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IVote - Vote en Direct</title>
    
    <!-- Police : Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS & Config -->
    <script src="/public/js/tailwindcss/tailwindcss.js"></script>
    <script src="/public/js/tailwindcss/tailwindcss.config.js"></script>

    <link rel="stylesheet" href="/public/css/index.css">
</head>
<body class="antialiased selection:bg-blue-500 selection:text-white flex flex-col min-h-screen bg-surface">

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
                        <span class="text-2xl font-extrabold tracking-tight text-fore leading-none">
                            I<span class="text-primary">Vote</span>
                        </span>
                        <span class="text-[10px] font-semibold text-fore-secondary tracking-wider uppercase">Votes Monétisés</span>
                    </div>
                </a>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center gap-8">
                    <a href="/" class="text-sm font-medium text-fore-secondary hover:text-fore transition">Accueil</a>
                    <a href="/campaigns" class="text-sm font-medium text-fore-secondary hover:text-fore transition">Explorer les campagnes</a>
                    <a href="/dashboard" class="text-sm font-medium text-fore-secondary hover:text-fore transition">Espace organisateur</a>
                </nav>

                <!-- Actions: Dark Theme & Buttons -->
                <div class="hidden md:flex items-center gap-3">
                    <!-- Bouton Dark Mode -->
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

                    <a href="/campaigns" class="px-4 py-2.5 rounded-xl border border-bordercustom hover:bg-surface-secondary text-xs font-semibold text-fore transition">
                        Toutes les campagnes
                    </a>

                    <a href="/dashboard" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white font-semibold text-xs px-5 py-2.5 rounded-xl shadow-md transition transform hover:-translate-y-0.5">
                        <span>Espace Organisateur</span>
                    </a>
                </div>

                <!-- Mobile Buttons -->
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
            <a href="/campaigns" class="block py-2 text-sm font-semibold text-primary">Explorer les campagnes</a>
            <a href="/dashboard" class="block py-2 text-sm font-medium text-fore-secondary">Espace organisateur</a>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-12">
        
        <!-- Loading Skeleton -->
        <section id="page-loading" class="space-y-8 animate-pulse">
            <div class="h-80 bg-surface-secondary rounded-3xl border border-bordercustom"></div>
            <div class="h-14 bg-surface-secondary rounded-2xl border border-bordercustom w-full max-w-md"></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="h-96 bg-surface-secondary rounded-3xl border border-bordercustom"></div>
                <div class="h-96 bg-surface-secondary rounded-3xl border border-bordercustom hidden sm:block"></div>
                <div class="h-96 bg-surface-secondary rounded-3xl border border-bordercustom hidden lg:block"></div>
            </div>
        </section>

        <!-- 404 / Error State (Hidden by default) -->
        <section id="page-error" class="hidden min-h-[50vh] flex items-center justify-center text-center">
            <div class="bg-surface-secondary border border-bordercustom rounded-3xl p-10 max-w-md mx-auto space-y-4 shadow-sm">
                <div class="w-12 h-12 rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <h1 id="error-title" class="text-xl font-extrabold text-fore">Campagne introuvable</h1>
                <p id="error-desc" class="text-xs text-fore-secondary">L'identifiant spécifié n'existe pas ou la campagne a été supprimée.</p>
                <div class="pt-2">
                    <a href="/campaigns" class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary hover:bg-primary-dark text-white text-xs font-bold rounded-xl transition shadow">
                        Retour aux campagnes
                    </a>
                </div>
            </div>
        </section>

        <!-- Dynamic Loaded Campaign Content -->
        <div id="campaign-content" class="hidden space-y-12">
            
            <!-- Hero Section -->
            <section class="relative overflow-hidden rounded-3xl border border-bordercustom bg-surface-secondary shadow-sm">
                <div id="hero-image-container" class="h-64 sm:h-80 w-full bg-surface relative flex items-center justify-center overflow-hidden">
                    <!-- Image or local placeholder injectée ici -->
                </div>
                
                <div class="p-6 sm:p-10 border-t border-bordercustom bg-surface-secondary">
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-8">
                        <div class="max-w-3xl space-y-4">
                            <div class="flex items-center gap-2">
                                <span id="campaign-badge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold shadow-sm">
                                    <!-- Badge injecté en JS -->
                                </span>
                            </div>
                            <h1 id="campaign-title" class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-fore tracking-tight leading-tight"></h1>
                            <p id="campaign-description" class="text-xs sm:text-sm text-fore-secondary leading-relaxed max-w-2xl"></p>
                            
                            <div class="flex flex-wrap gap-4 pt-2 text-xs text-fore-secondary border-t border-bordercustom/60">
                                <span>Organisé par : <strong id="campaign-organizer" class="text-primary font-bold"></strong></span>
                                <span id="campaign-deadline-wrapper" class="hidden">Clôture : <strong id="campaign-deadline" class="text-fore font-bold"></strong></span>
                            </div>
                        </div>

                        <!-- Aggregate metric badges -->
                        <div class="grid grid-cols-2 gap-3 w-full lg:w-auto text-center shrink-0">
                            <div class="rounded-2xl border border-bordercustom bg-surface p-4 shadow-sm">
                                <div id="hero-total-votes" class="text-2xl font-extrabold text-primary">0</div>
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-fore-secondary mt-0.5">Votes</div>
                            </div>
                            <div class="rounded-2xl border border-bordercustom bg-surface p-4 shadow-sm">
                                <div id="hero-candidate-count" class="text-2xl font-extrabold text-fore">0</div>
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-fore-secondary mt-0.5">Candidats</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Filter Bar & Search -->
            <section class="space-y-4">
                <div class="flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                    <!-- Catégories Pills -->
                    <div id="category-filters" class="flex gap-2 overflow-x-auto pb-2 scrollbar-none">
                        <!-- Pills générées dynamiquement -->
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full md:w-80 shrink-0">
                        <svg class="w-4 h-4 text-fore-secondary absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input id="candidate-search" type="search" placeholder="Rechercher un candidat..." class="w-full pl-10 pr-4 py-2.5 text-xs rounded-2xl bg-surface-secondary border border-bordercustom text-fore placeholder:text-fore-secondary focus:ring-2 focus:ring-primary focus:outline-none transition">
                    </div>
                </div>
            </section>

            <!-- Candidates Grid / Categories List -->
            <section id="categories-list" class="space-y-12">
                <!-- Groupes de catégories & candidats injectés ici -->
            </section>

            <!-- Empty Search State (Hidden by default) -->
            <div id="empty-search" class="hidden rounded-3xl border border-bordercustom bg-surface-secondary p-12 text-center max-w-md mx-auto space-y-2">
                <h3 class="text-base font-bold text-fore">Aucun candidat trouvé</h3>
                <p class="text-xs text-fore-secondary">Modifiez vos mots-clés ou réinitialisez le filtre de catégorie.</p>
            </div>

            <!-- How it Works (3 Steps) -->
            <section class="rounded-3xl border border-bordercustom bg-surface-secondary p-6 sm:p-10 space-y-8">
                <div class="max-w-xl mx-auto text-center space-y-1">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-primary">Simple & Transparent</span>
                    <h3 class="text-2xl font-extrabold text-fore">
                        Comment voter en 3 étapes ?
                    </h3>
                    <p class="text-xs text-fore-secondary">
                        Aucun compte requis pour les votants. Paiement direct et instantané via votre compte Mobile Money.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-3 rounded-2xl border border-bordercustom bg-surface p-5 text-center sm:text-left shadow-sm">
                        <div class="w-10 h-10 rounded-xl bg-primary text-white font-extrabold text-sm flex items-center justify-center">
                            1
                        </div>
                        <h4 class="text-sm font-bold text-fore">Choisissez votre candidat</h4>
                        <p class="text-xs leading-relaxed text-fore-secondary">
                            Consultez les fiches et sélectionnez votre pack de votes (1x, 5x, 10x, 50x ou quantité personnalisée).
                        </p>
                    </div>

                    <div class="space-y-3 rounded-2xl border border-bordercustom bg-surface p-5 text-center sm:text-left shadow-sm">
                        <div class="w-10 h-10 rounded-xl bg-orange-500 text-white font-extrabold text-sm flex items-center justify-center">
                            2
                        </div>
                        <h4 class="text-sm font-bold text-fore">Validez par Mobile Money</h4>
                        <p class="text-xs leading-relaxed text-fore-secondary">
                            Renseignez votre numéro MTN MoMo ou Orange Money et confirmez l'invite USSD sur votre téléphone.
                        </p>
                    </div>

                    <div class="space-y-3 rounded-2xl border border-bordercustom bg-surface p-5 text-center sm:text-left shadow-sm">
                        <div class="w-10 h-10 rounded-xl bg-green-500 text-white font-extrabold text-sm flex items-center justify-center">
                            3
                        </div>
                        <h4 class="text-sm font-bold text-fore">Suffrages comptabilisés</h4>
                        <p class="text-xs leading-relaxed text-fore-secondary">
                            Les voix sont créditées immédiatement au compteur en direct avec reçu de transaction vérifiable.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Call to Action -->
            <section class="rounded-3xl border border-bordercustom bg-surface-secondary p-6 sm:p-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-fore">Vous organisez un concours ou une élection ?</h2>
                        <p class="text-xs text-fore-secondary mt-1">Créez votre propre campagne de vote monétisée et recevez vos fonds directement.</p>
                    </div>
                </div>
                <a href="/campaigns/new" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-primary hover:bg-primary-dark text-white text-xs font-bold shadow-md transition transform hover:-translate-y-0.5 shrink-0">
                    <span>Créer une campagne</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </section>

        </div>
    </main>

    <!-- Modal de Vote Sécurisé -->
    <div id="vote-modal" class="fixed inset-0 z-50 hidden items-end sm:items-center justify-center p-0 sm:p-4" role="dialog" aria-modal="true">
        <div id="vote-backdrop" class="fixed inset-0 bg-fore/60 backdrop-blur-sm transition-opacity"></div>
        
        <div class="relative z-10 w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-t-3xl sm:rounded-3xl border border-bordercustom bg-surface p-6 sm:p-8 text-fore shadow-2xl space-y-6">
            
            <!-- Header Modal -->
            <div class="flex items-center justify-between pb-4 border-b border-bordercustom">
                <div class="flex items-center gap-3">
                    <div class="relative w-12 h-12 rounded-2xl bg-surface-secondary border border-bordercustom flex items-center justify-center text-primary font-black text-lg overflow-hidden shadow-sm">
                        <span id="vote-candidate-avatar">?</span>
                        <span class="absolute -bottom-1 -right-1 bg-primary text-white text-[9px] font-extrabold px-1.5 py-0.5 rounded-full">#<span id="vote-candidate-number"></span></span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-primary block">Paiement Mobile Sécurisé</span>
                        <h2 class="text-base sm:text-lg font-bold text-fore">Voter pour <span id="vote-candidate-name"></span></h2>
                    </div>
                </div>
                <button type="button" id="close-vote-modal" class="w-8 h-8 rounded-xl bg-surface-secondary border border-bordercustom text-fore-secondary hover:text-fore flex items-center justify-center transition" aria-label="Fermer">✕</button>
            </div>

            <!-- Contenu Formulaire de Vote -->
            <form id="vote-form" class="space-y-6">
                <div id="vote-fields" class="space-y-6">
                    
                    <!-- 1. Sélection Pack de votes -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-xs font-bold uppercase tracking-wider text-fore">1. Choisissez un pack de votes</label>
                            <button type="button" id="toggle-custom-votes" class="text-xs font-semibold text-primary hover:underline">Quantité libre</button>
                        </div>
                        
                        <div id="recommended-packages" class="grid grid-cols-3 gap-2 sm:gap-3">
                            <!-- Généré dynamiquement en JS en fonction du prix_per_vote -->
                        </div>

                        <!-- Panneau Curseur / Quantité Personnalisée -->
                        <div id="custom-votes-panel" class="mt-3 hidden space-y-3 rounded-2xl border border-bordercustom bg-surface-secondary p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-fore-secondary">Nombre de voix :</span>
                                <span class="font-mono text-base font-extrabold text-primary"><span id="custom-votes-label">10</span> Votes</span>
                            </div>
                            <input id="custom-votes" type="range" min="1" max="500" step="5" value="10" class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-surface border border-bordercustom accent-primary">
                            <div class="flex justify-between text-[10px] text-fore-secondary font-mono">
                                <span>1 Vote</span>
                                <span>250 Votes</span>
                                <span>500 Votes</span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Mode de paiement -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-fore mb-2.5">2. Mode de paiement</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" class="payment-method selected flex items-center gap-3 p-3 rounded-2xl border-2 border-yellow-500 bg-yellow-500/10 text-fore transition cursor-pointer text-left" data-payment="mtn_momo">
                                <span class="w-8 h-8 rounded-xl bg-yellow-400 text-black font-extrabold text-xs flex items-center justify-center shrink-0 shadow-sm">MTN</span>
                                <div>
                                    <strong class="block text-xs font-bold text-fore">MTN MoMo</strong>
                                    <span class="text-[10px] text-fore-secondary font-mono">*126#</span>
                                </div>
                            </button>
                            <button type="button" class="payment-method flex items-center gap-3 p-3 rounded-2xl border border-bordercustom bg-surface-secondary text-fore hover:border-orange-500/50 transition cursor-pointer text-left" data-payment="orange_money">
                                <span class="w-8 h-8 rounded-xl bg-orange-500 text-white font-extrabold text-xs flex items-center justify-center shrink-0 shadow-sm">OM</span>
                                <div>
                                    <strong class="block text-xs font-bold text-fore">Orange Money</strong>
                                    <span class="text-[10px] text-fore-secondary font-mono">#150#</span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- 3. Coordonnées -->
                    <div class="space-y-3">
                        <label class="block text-xs font-bold uppercase tracking-wider text-fore">3. Numéro de compte</label>
                        <input id="voter-phone" type="tel" required placeholder="Numéro Mobile Money (ex: 699001122)" class="w-full px-4 py-2.5 text-xs bg-surface-secondary border border-bordercustom rounded-xl text-fore placeholder:text-fore-secondary focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                </div>

                <!-- Message d'erreur -->
                <p id="vote-error" class="hidden text-xs text-red-500 bg-red-500/10 border border-red-500/20 p-3 rounded-xl font-medium"></p>
                
                <!-- État de chargement USSD -->
                <div id="vote-loading" class="hidden space-y-3 py-6 text-center">
                    <div class="w-10 h-10 border-4 border-primary/30 border-t-primary rounded-full animate-spin mx-auto"></div>
                    <p class="text-xs font-bold text-fore">Génération de la transaction Mobile Money...</p>
                    <p class="text-[11px] text-fore-secondary">Veuillez patienter pendant l'envoi de l'invite de paiement.</p>
                </div>

                <!-- Invite USSD Simulation -->
                <div id="vote-ussd" class="hidden space-y-5 py-4 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-yellow-500/10 text-yellow-600 flex items-center justify-center mx-auto border border-yellow-500/20">
                        <svg class="w-7 h-7 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="5" y="2" width="14" height="20" rx="2" stroke-width="2"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 18h6"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-yellow-600 uppercase bg-yellow-500/10 px-2.5 py-1 rounded-full border border-yellow-500/30">Invite USSD Transmise</span>
                        <h4 class="text-base font-bold text-fore mt-2">Validez sur votre mobile</h4>
                        <p class="text-xs text-fore-secondary mt-1">Tapez votre code secret PIN pour autoriser le paiement de <strong id="ussd-price" class="text-primary"></strong>.</p>
                    </div>

                    <div class="space-y-2 pt-2">
                        <button type="button" id="confirm-ussd" class="w-full py-3 bg-primary hover:bg-primary-dark text-white font-bold text-xs rounded-xl shadow transition">
                            Confirmer la validation du PIN
                        </button>
                        <button type="button" id="edit-vote" class="text-xs text-fore-secondary hover:text-fore underline">
                            Modifier les informations
                        </button>
                    </div>
                </div>

                <!-- Succès -->
                <div id="vote-success" class="hidden space-y-4 py-4 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-green-500/10 text-green-600 flex items-center justify-center mx-auto border border-green-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-green-600 uppercase tracking-wider">Suffrages Enregistrés</span>
                        <h3 class="text-xl font-extrabold text-fore mt-1">Merci pour votre vote !</h3>
                    </div>

                    <div class="rounded-2xl border border-bordercustom bg-surface-secondary p-4 text-left text-xs font-mono space-y-2">
                        <div class="flex justify-between text-fore-secondary">
                            <span>Réf. Transaction :</span>
                            <span id="vote-reference" class="text-fore font-bold"></span>
                        </div>
                        <div class="flex justify-between text-fore-secondary">
                            <span>Voix Ajoutées :</span>
                            <span id="vote-success-votes" class="text-primary font-bold"></span>
                        </div>
                        <div class="flex justify-between text-fore-secondary border-t border-bordercustom pt-2">
                            <span>Montant Débité :</span>
                            <span id="vote-success-price" class="text-green-600 font-bold"></span>
                        </div>
                    </div>

                    <button type="button" id="finish-vote" class="w-full py-3 bg-primary hover:bg-primary-dark text-white font-bold text-xs rounded-xl shadow transition">
                        Terminer & Fermer
                    </button>
                </div>

                <!-- Footer / Bouton Soumettre -->
                <div id="vote-order-summary" class="border-t border-bordercustom pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs text-fore-secondary">Total à régler :</span>
                        <span id="vote-total-price" class="font-mono text-lg font-bold text-primary">-- FCFA</span>
                    </div>
                    <button id="submit-vote" type="submit" class="w-full py-3.5 bg-primary hover:bg-primary-dark text-white font-bold text-xs sm:text-sm rounded-xl shadow transition flex items-center justify-center gap-2">
                        <span>Payer <span id="submit-price">--</span> & Valider <span id="submit-votes">--</span> Vote(s)</span>
                    </button>
                    <div class="mt-2 text-center text-[10px] text-fore-secondary">
                        🔒 Règlement sécurisé direct sans intermédiaire
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 bg-fore text-surface px-5 py-3 rounded-2xl shadow-xl border border-bordercustom flex items-center gap-2 transform translate-y-24 opacity-0 transition-all duration-300">
        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span id="toast-msg" class="text-xs font-bold">Lien copié avec succès !</span>
    </div>

    <!-- Footer -->
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
                <p>&copy; <?= date('Y') ?> IVote. Tous droits réservés.</p>
                <p>Votez en toute sécurité.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="/public/js/theme_toggle.js"></script>
    <script>
        // State
        let campaignData = null;
        let selectedCandidate = null;
        let selectedVoteCount = 10;
        let selectedPaymentMethod = 'mtn_momo';
        let currentFilterCategory = 'all';

        function getCampaignIdFromUrl() {
            const parts = window.location.pathname.split('/').filter(Boolean);
            const id = parts[parts.length - 1];
            return parseInt(id, 10) || null;
        }

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function showToast(msg) {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toast-msg');
            toastMsg.textContent = msg;
            toast.classList.remove('translate-y-24', 'opacity-0');
            setTimeout(() => toast.classList.add('translate-y-24', 'opacity-0'), 3000);
        }

        // Calcul du prix unitaire avec remises
        function calculatePrice(voteCount, basePrice) {
            let discount = 1.0;
            if (voteCount >= 50) discount = 0.8;
            else if (voteCount >= 20) discount = 0.85;
            else if (voteCount >= 10) discount = 0.9;
            return Math.round(voteCount * basePrice * discount);
        }

        // Chargement des données de la campagne
        async function loadCampaignDetails() {
            const campaignId = getCampaignIdFromUrl();
            const loadingEl = document.getElementById('page-loading');
            const errorEl = document.getElementById('page-error');
            const contentEl = document.getElementById('campaign-content');

            if (!campaignId) {
                loadingEl.classList.add('hidden');
                errorEl.classList.remove('hidden');
                return;
            }

            try {
                const response = await fetch(`/api/campaigns/${campaignId}`, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error("Campagne introuvable.");
                }

                const result = await response.json();
                campaignData = result.data;

                renderCampaign(campaignData);

                loadingEl.classList.add('hidden');
                contentEl.classList.remove('hidden');

            } catch (err) {
                console.error(err);
                loadingEl.classList.add('hidden');
                errorEl.classList.remove('hidden');
            }
        }

        // Rendu de la campagne
        function renderCampaign(data) {
            const isClosed = data.date_cloture && new Date(data.date_cloture) < new Date();
            const isActive = !data.is_draft && !isClosed;

            document.title = `${data.title} - IVote`;
            document.getElementById('campaign-title').textContent = data.title;
            document.getElementById('campaign-description').textContent = data.description || '';
            document.getElementById('campaign-organizer').textContent = data.organizer_name || 'Organisateur IVote';

            // Image Hero
            const heroImageEl = document.getElementById('hero-image-container');
            if (data.image_url) {
                heroImageEl.innerHTML = `<img src="${escapeHtml(data.image_url)}" alt="${escapeHtml(data.title)}" class="w-full h-full object-cover">`;
            } else {
                heroImageEl.innerHTML = `
                    <div class="flex flex-col items-center justify-center text-fore-secondary p-4 text-center">
                        <svg class="w-12 h-12 mb-2 opacity-30 text-fore" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-xs font-medium opacity-60">Aucune bannière de campagne</span>
                    </div>`;
            }

            // Statut Badge
            const badgeEl = document.getElementById('campaign-badge');
            if (isActive) {
                badgeEl.className = "inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-green-500/10 text-green-600 border border-green-500/20";
                badgeEl.innerHTML = `<span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> En direct`;
            } else {
                badgeEl.className = "inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-surface border border-bordercustom text-fore-secondary";
                badgeEl.innerHTML = `Campagne clôturée`;
            }

            // Clôture
            if (data.date_cloture) {
                document.getElementById('campaign-deadline-wrapper').classList.remove('hidden');
                document.getElementById('campaign-deadline').textContent = new Date(data.date_cloture).toLocaleString('fr-FR', {
                    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                });
            }

            // Aggregates
            document.getElementById('hero-total-votes').textContent = new Intl.NumberFormat('fr-FR').format(data.totalVotes || 0);
            document.getElementById('hero-candidate-count').textContent = data.candidateCount || 0;

            // Filtres de catégories
            renderCategoryPills(data.categories || []);

            // Rendu des candidats
            renderCandidateSections(data.categories || [], isActive);

            // Configuration des packs de vote dans la modal
            setupVotePacks(data.price_per_vote || 100);
        }

        // Filtres par catégorie
        function renderCategoryPills(categories) {
            const container = document.getElementById('category-filters');
            container.innerHTML = `
                <button type="button" data-category="all" class="category-pill active whitespace-nowrap rounded-2xl px-4 py-2 text-xs font-bold transition bg-primary text-white shadow-sm">
                    Toutes les catégories
                </button>
            `;

            categories.forEach(cat => {
                const count = cat.candidates ? cat.candidates.length : 0;
                container.insertAdjacentHTML('beforeend', `
                    <button type="button" data-category="${escapeHtml(cat.name)}" class="category-pill whitespace-nowrap rounded-2xl border border-bordercustom bg-surface-secondary px-4 py-2 text-xs font-bold text-fore-secondary hover:text-fore transition">
                        ${escapeHtml(cat.name)} (${count})
                    </button>
                `);
            });

            // Événements boutons de filtre
            container.querySelectorAll('.category-pill').forEach(btn => {
                btn.addEventListener('click', () => {
                    container.querySelectorAll('.category-pill').forEach(b => {
                        b.classList.remove('bg-primary', 'text-white', 'shadow-sm');
                        b.classList.add('border', 'border-bordercustom', 'bg-surface-secondary', 'text-fore-secondary');
                    });
                    btn.classList.remove('border', 'border-bordercustom', 'bg-surface-secondary', 'text-fore-secondary');
                    btn.classList.add('bg-primary', 'text-white', 'shadow-sm');

                    currentFilterCategory = btn.getAttribute('data-category');
                    filterCandidates();
                });
            });
        }

        // Rendu des sections et cartes candidats
        function renderCandidateSections(categories, isActive) {
            const listEl = document.getElementById('categories-list');
            listEl.innerHTML = '';

            categories.forEach(cat => {
                const candidates = cat.candidates || [];
                const catHtml = `
                    <div class="category-group space-y-6" data-category-name="${escapeHtml(cat.name)}">
                        <div class="flex items-center justify-between border-b border-bordercustom pb-4">
                            <h2 class="text-xl sm:text-2xl font-extrabold text-fore">${escapeHtml(cat.name)}</h2>
                            <span class="text-xs font-semibold text-fore-secondary">${candidates.length} candidat${candidates.length > 1 ? 's' : ''}</span>
                        </div>
                        
                        ${candidates.length === 0 ? `
                            <div class="rounded-2xl border border-bordercustom bg-surface-secondary py-8 text-center text-xs text-fore-secondary">
                                Aucun candidat enregistré dans cette catégorie.
                            </div>
                        ` : `
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                ${candidates.map(cand => renderCandidateCard(cand, isActive)).join('')}
                            </div>
                        `}
                    </div>
                `;
                listEl.insertAdjacentHTML('beforeend', catHtml);
            });

            // Écouteurs pour boutons de vote et copier le lien
            attachCandidateEventListeners();
        }

        function renderCandidateCard(cand, isActive) {
            const name = escapeHtml(cand.name);
            const theme = escapeHtml(cand.theme || '');
            const description = escapeHtml(cand.description || '');
            const bio = escapeHtml(cand.bio || '');
            const number = parseInt(cand.number || cand.candidate_number, 10);
            const votes = parseInt(cand.votes || 0, 10);
            const percentage = cand.percentage || 0;
            const id = parseInt(cand.id || cand.candidate_id, 10);
            const imageUrl = cand.image_url ? escapeHtml(cand.image_url) : null;

            const imageHtml = imageUrl
                ? `<img src="${imageUrl}" alt="${name}" class="w-full h-full object-cover">`
                : `<span class="text-5xl font-black text-fore-secondary/40">${name.charAt(0).toUpperCase()}</span>`;

            return `
                <article class="candidate-card bg-surface rounded-2xl border border-bordercustom overflow-hidden shadow-sm flex flex-col justify-between hover:border-primary/50 transition duration-300"
                         data-candidate-id="${id}"
                         data-candidate-name="${name}"
                         data-candidate-number="${number}"
                         data-search="${name.toLowerCase()} ${theme.toLowerCase()} ${description.toLowerCase()}">
                    
                    <div>
                        <div class="relative h-64 bg-surface-secondary flex items-center justify-center overflow-hidden">
                            ${imageHtml}
                            <div class="absolute top-3 left-3 bg-surface/90 backdrop-blur-md border border-bordercustom text-fore px-3 py-1 rounded-full text-xs font-bold shadow-sm flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-primary"></span>
                                <span>N° ${number}</span>
                            </div>
                        </div>

                        <div class="p-5 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-base font-bold text-fore leading-snug line-clamp-1">${name}</h3>
                                ${cand.age ? `<span class="text-xs font-semibold text-fore-secondary">${cand.age} ans</span>` : ''}
                            </div>
                            
                            ${theme ? `<p class="text-xs font-semibold text-primary line-clamp-1">${theme}</p>` : ''}
                            ${description ? `<p class="text-xs text-fore-secondary line-clamp-2 leading-relaxed">${description}</p>` : ''}
                            ${bio ? `<p class="text-xs italic text-fore-secondary bg-surface-secondary border border-bordercustom p-3 rounded-xl line-clamp-3">"${bio}"</p>` : ''}
                        </div>
                    </div>

                    <div class="p-5 pt-0 space-y-4">
                        <div class="space-y-1.5 border-t border-bordercustom pt-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-fore-secondary font-medium">Suffrages obtenus</span>
                                <span class="font-bold text-fore">${new Intl.NumberFormat('fr-FR').format(votes)} votes (${percentage}%)</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-surface-secondary border border-bordercustom overflow-hidden">
                                <div class="h-full bg-primary rounded-full transition-all duration-500" style="width: ${Math.min(100, percentage)}%"></div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" class="btn-vote-trigger flex-1 py-2.5 rounded-xl text-xs font-bold text-center transition shadow ${isActive ? 'bg-primary hover:bg-primary-dark text-white cursor-pointer' : 'bg-surface-secondary text-fore-secondary cursor-not-allowed border border-bordercustom'}" ${!isActive ? 'disabled' : ''} data-id="${id}" data-name="${name}" data-number="${number}">
                                ${isActive ? 'Voter' : 'Votes clôturés'}
                            </button>
                            <button type="button" class="btn-copy-link p-2.5 rounded-xl border border-bordercustom bg-surface-secondary text-fore-secondary hover:text-fore hover:bg-surface transition" data-id="${id}" title="Copier le lien du candidat">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                </article>
            `;
        }

        // Écouteurs de cartes
        function attachCandidateEventListeners() {
            // Ouvrir modal de vote
            document.querySelectorAll('.btn-vote-trigger').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-id');
                    const name = btn.getAttribute('data-name');
                    const number = btn.getAttribute('data-number');
                    openVoteModal(id, name, number);
                });
            });

            // Copier le lien
            document.querySelectorAll('.btn-copy-link').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-id');
                    const url = `${window.location.origin}${window.location.pathname}#candidate-${id}`;
                    navigator.clipboard.writeText(url).then(() => {
                        showToast("Lien du candidat copié !");
                    });
                });
            });
        }

        // Filtres & Recherche
        function filterCandidates() {
            const query = (document.getElementById('candidate-search')?.value || '').toLowerCase().trim();
            const groups = document.querySelectorAll('.category-group');
            let totalVisible = 0;

            groups.forEach(group => {
                const groupCategory = group.getAttribute('data-category-name');
                const matchesCategory = currentFilterCategory === 'all' || currentFilterCategory === groupCategory;

                let groupVisibleCards = 0;
                const cards = group.querySelectorAll('.candidate-card');

                cards.forEach(card => {
                    const searchData = card.getAttribute('data-search') || '';
                    const matchesSearch = !query || searchData.includes(query);

                    if (matchesCategory && matchesSearch) {
                        card.classList.remove('hidden');
                        groupVisibleCards++;
                        totalVisible++;
                    } else {
                        card.classList.add('hidden');
                    }
                });

                if (groupVisibleCards === 0 && (currentFilterCategory !== 'all' || query)) {
                    group.classList.add('hidden');
                } else {
                    group.classList.remove('hidden');
                }
            });

            const emptySearchEl = document.getElementById('empty-search');
            if (totalVisible === 0) {
                emptySearchEl.classList.remove('hidden');
            } else {
                emptySearchEl.classList.add('hidden');
            }
        }

        document.getElementById('candidate-search')?.addEventListener('input', filterCandidates);

        // Configuration de la Modal de Vote
        function setupVotePacks(pricePerVote) {
            const packages = [
                { count: 1, label: '' },
                { count: 5, label: '' },
                { count: 10, label: 'Populaire', selected: true },
                { count: 25, label: '' },
                { count: 50, label: 'Top Offre' },
                { count: 100, label: '-25%' }
            ];

            const container = document.getElementById('recommended-packages');
            container.innerHTML = '';

            packages.forEach(pkg => {
                const price = calculatePrice(pkg.count, pricePerVote);
                const isSelected = pkg.selected ? 'selected border-primary bg-primary/10 ring-2 ring-primary/20' : 'border-bordercustom bg-surface-secondary';

                container.insertAdjacentHTML('beforeend', `
                    <button type="button" class="vote-pack-btn relative p-3 rounded-2xl border ${isSelected} text-left transition cursor-pointer" data-votes="${pkg.count}">
                        ${pkg.label ? `<span class="absolute -top-2.5 right-2 bg-primary text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full uppercase">${pkg.label}</span>` : ''}
                        <span class="block font-mono text-base font-extrabold text-fore">${pkg.count}</span>
                        <span class="text-[10px] text-fore-secondary font-medium">${pkg.count === 1 ? 'Vote' : 'Votes'}</span>
                        <span class="block text-xs font-bold text-primary mt-1">${new Intl.NumberFormat('fr-FR').format(price)} FCFA</span>
                    </button>
                `);
            });

            // Écouteurs de sélection de pack
            container.querySelectorAll('.vote-pack-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    container.querySelectorAll('.vote-pack-btn').forEach(b => {
                        b.classList.remove('selected', 'border-primary', 'bg-primary/10', 'ring-2', 'ring-primary/20');
                        b.classList.add('border-bordercustom', 'bg-surface-secondary');
                    });
                    btn.classList.add('selected', 'border-primary', 'bg-primary/10', 'ring-2', 'ring-primary/20');
                    btn.classList.remove('border-bordercustom', 'bg-surface-secondary');

                    selectedVoteCount = parseInt(btn.getAttribute('data-votes'), 10);
                    updateOrderSummary();
                });
            });

            // Mode libre / curseur
            document.getElementById('toggle-custom-votes')?.addEventListener('click', () => {
                const customPanel = document.getElementById('custom-votes-panel');
                customPanel.classList.toggle('hidden');
            });

            document.getElementById('custom-votes')?.addEventListener('input', function() {
                selectedVoteCount = parseInt(this.value, 10);
                document.getElementById('custom-votes-label').textContent = selectedVoteCount;

                // Désélectionner les packs préétablis
                container.querySelectorAll('.vote-pack-btn').forEach(b => {
                    b.classList.remove('selected', 'border-primary', 'bg-primary/10', 'ring-2', 'ring-primary/20');
                    b.classList.add('border-bordercustom', 'bg-surface-secondary');
                });

                updateOrderSummary();
            });

            // Mode de paiement
            document.querySelectorAll('.payment-method').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.payment-method').forEach(b => {
                        b.classList.remove('border-yellow-500', 'bg-yellow-500/10', 'border-orange-500', 'bg-orange-500/10', 'border-2');
                        b.classList.add('border-bordercustom', 'bg-surface-secondary');
                    });

                    const method = btn.getAttribute('data-payment');
                    selectedPaymentMethod = method;

                    if (method === 'mtn_momo') {
                        btn.classList.add('border-2', 'border-yellow-500', 'bg-yellow-500/10');
                    } else {
                        btn.classList.add('border-2', 'border-orange-500', 'bg-orange-500/10');
                    }
                });
            });

            updateOrderSummary();
        }

        function updateOrderSummary() {
            if (!campaignData) return;
            const pricePerVote = campaignData.price_per_vote || 100;
            const totalPrice = calculatePrice(selectedVoteCount, pricePerVote);

            const formattedPrice = `${new Intl.NumberFormat('fr-FR').format(totalPrice)} FCFA`;
            document.getElementById('vote-total-price').textContent = formattedPrice;
            document.getElementById('submit-price').textContent = formattedPrice;
            document.getElementById('submit-votes').textContent = selectedVoteCount;
        }

        // Gestion de l'affichage de la Modal
        function openVoteModal(id, name, number) {
            selectedCandidate = { id, name, number };
            document.getElementById('vote-candidate-name').textContent = name;
            document.getElementById('vote-candidate-number').textContent = number;
            document.getElementById('vote-candidate-avatar').textContent = name.charAt(0).toUpperCase();

            // Reset modal states
            document.getElementById('vote-fields').classList.remove('hidden');
            document.getElementById('vote-order-summary').classList.remove('hidden');
            document.getElementById('vote-loading').classList.add('hidden');
            document.getElementById('vote-ussd').classList.add('hidden');
            document.getElementById('vote-success').classList.add('hidden');
            document.getElementById('vote-error').classList.add('hidden');

            document.getElementById('vote-modal').classList.remove('hidden');
            document.getElementById('vote-modal').classList.add('flex');
        }

        function closeVoteModal() {
            document.getElementById('vote-modal').classList.add('hidden');
            document.getElementById('vote-modal').classList.remove('flex');
        }

        document.getElementById('close-vote-modal')?.addEventListener('click', closeVoteModal);
        document.getElementById('vote-backdrop')?.addEventListener('click', closeVoteModal);
        document.getElementById('finish-vote')?.addEventListener('click', () => {
            closeVoteModal();
            loadCampaignDetails(); // Rafraîchir les compteurs officiels
        });

        // Soumission du formulaire de vote
        document.getElementById('vote-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!selectedCandidate) return;

            const phone = document.getElementById('voter-phone').value.trim();
            const errorEl = document.getElementById('vote-error');
            errorEl.classList.add('hidden');

            if (!phone) {
                errorEl.textContent = "Veuillez renseigner votre numéro Mobile Money.";
                errorEl.classList.remove('hidden');
                return;
            }

            // Écran de chargement USSD
            document.getElementById('vote-fields').classList.add('hidden');
            document.getElementById('vote-order-summary').classList.add('hidden');
            document.getElementById('vote-loading').classList.remove('hidden');

            setTimeout(() => {
                document.getElementById('vote-loading').classList.add('hidden');
                document.getElementById('vote-ussd').classList.remove('hidden');
                const price = calculatePrice(selectedVoteCount, campaignData.price_per_vote || 100);
                document.getElementById('ussd-price').textContent = `${new Intl.NumberFormat('fr-FR').format(price)} FCFA`;
            }, 1200);
        });

        document.getElementById('edit-vote')?.addEventListener('click', () => {
            document.getElementById('vote-ussd').classList.add('hidden');
            document.getElementById('vote-fields').classList.remove('hidden');
            document.getElementById('vote-order-summary').classList.remove('hidden');
        });

        // Confirmation du vote via l'API
        document.getElementById('confirm-ussd')?.addEventListener('click', async () => {
            const campaignId = getCampaignIdFromUrl();
            const btn = document.getElementById('confirm-ussd');
            btn.disabled = true;
            btn.textContent = "Enregistrement en cours...";

            try {
                const response = await fetch(`/api/campaigns/${campaignId}/votes`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        candidate_id: selectedCandidate.id,
                        vote_count: selectedVoteCount,
                        payment_method: selectedPaymentMethod
                    })
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || "Échec de validation du vote.");
                }

                // Succès
                document.getElementById('vote-ussd').classList.add('hidden');
                document.getElementById('vote-success').classList.remove('hidden');

                document.getElementById('vote-reference').textContent = result.transaction_ref || 'TX-' + Math.random().toString(36).substring(2, 9).toUpperCase();
                document.getElementById('vote-success-votes').textContent = `+${result.votes_added || selectedVoteCount} voix`;
                document.getElementById('vote-success-price').textContent = `${new Intl.NumberFormat('fr-FR').format(result.amount_fcfa || 0)} FCFA`;

            } catch (err) {
                alert(err.message);
                document.getElementById('vote-ussd').classList.add('hidden');
                document.getElementById('vote-fields').classList.remove('hidden');
                document.getElementById('vote-order-summary').classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.textContent = "Confirmer la validation du PIN";
            }
        });

        // Initialisation automatique au chargement
        document.addEventListener('DOMContentLoaded', loadCampaignDetails);
    </script>
</body>
</html>