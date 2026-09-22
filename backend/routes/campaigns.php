<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IVote - Toutes les Campagnes</title>
    
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
                    <a href="/" class="text-sm font-medium text-fore-secondary hover:text-fore transition">Accueil</a>
                    <a href="/campaigns" class="text-sm font-semibold text-primary">Explorer les campagnes</a>
                    <a href="/dashboard" class="text-sm font-medium text-fore-secondary hover:text-fore transition">Espace organisateur</a>
                </nav>

                <!-- Actions: Dark Theme & Create -->
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

                    <!-- Lancer une campagne -->
                    <a href="/campaigns/new" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white font-semibold text-xs px-5 py-2.5 rounded-xl shadow-md transition transform hover:-translate-y-0.5">
                        <span>Lancer une campagne</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
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
            <div class="pt-3 border-t border-bordercustom">
                <a href="/campaigns/new" class="block w-full text-center py-2.5 font-semibold bg-primary text-white rounded-xl text-xs">Lancer une campagne</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">
        
        <!-- Hero Header & Stats Banner -->
        <section class="bg-surface-secondary rounded-3xl p-6 sm:p-10 border border-bordercustom shadow-sm flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8">
            <div class="space-y-3 max-w-2xl">
                <h1 class="text-3xl sm:text-4xl font-extrabold text-fore tracking-tight">
                    Toutes les Campagnes de Vote
                </h1>
                <p class="text-xs sm:text-sm text-fore-secondary leading-relaxed">
                    Découvrez les scrutins actifs, soutenez vos candidats favoris et réglez vos voix en direct via MTN MoMo et Orange Money en toute sécurité.
                </p>
            </div>

            <!-- Aggregate Metric Counters -->
            <div id="stats-wrapper" class="grid grid-cols-3 gap-3 w-full lg:w-auto">
                <div class="bg-surface border border-bordercustom rounded-2xl p-4 text-center shadow-sm">
                    <span id="stat-active" class="text-xl sm:text-2xl font-extrabold text-green-600 block">--</span>
                    <span class="text-[11px] font-semibold text-fore-secondary uppercase tracking-wider">Actives</span>
                </div>
                <div class="bg-surface border border-bordercustom rounded-2xl p-4 text-center shadow-sm">
                    <span id="stat-total" class="text-xl sm:text-2xl font-extrabold text-primary block">--</span>
                    <span class="text-[11px] font-semibold text-fore-secondary uppercase tracking-wider">Total</span>
                </div>
                <div class="bg-surface border border-bordercustom rounded-2xl p-4 text-center shadow-sm">
                    <span id="stat-votes" class="text-xl sm:text-2xl font-extrabold text-fore block">--</span>
                    <span class="text-[11px] font-semibold text-fore-secondary uppercase tracking-wider">Votes</span>
                </div>
            </div>
        </section>

        <!-- Dynamic Content Section -->
        <section id="campaigns-container">
            <!-- Skeleton Loading State -->
            <div id="loading-state" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-28">
                <!-- Skeleton Card 1 -->
                <div class="bg-surface rounded-2xl border border-bordercustom overflow-hidden shadow-sm flex flex-col justify-between animate-pulse">
                    <div>
                        <!-- Placeholder Image & Badge -->
                        <div class="relative h-48 bg-slate-200 dark:bg-slate-800/80 flex items-center justify-center">
                            <svg class="w-10 h-10 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div class="absolute top-3 right-3 h-5 w-20 bg-slate-300 dark:bg-slate-700 rounded-md"></div>
                        </div>

                        <!-- Placeholder Textes -->
                        <div class="p-5 space-y-3">
                            <div class="h-3.5 bg-slate-200 dark:bg-slate-800 rounded w-1/3"></div>
                            <div class="h-5 bg-slate-300 dark:bg-slate-700 rounded-md w-3/4"></div>
                            <div class="space-y-1.5 pt-1">
                                <div class="h-3 bg-slate-200 dark:bg-slate-800 rounded w-full"></div>
                                <div class="h-3 bg-slate-200 dark:bg-slate-800 rounded w-4/5"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Placeholder Footer & Bouton -->
                    <div class="p-5 pt-0 space-y-3">
                        <div class="flex items-center justify-between border-t border-bordercustom pt-3">
                            <div class="h-3.5 bg-slate-200 dark:bg-slate-800 rounded w-20"></div>
                            <div class="h-3.5 bg-slate-200 dark:bg-slate-800 rounded w-16"></div>
                        </div>
                        <div class="h-10 bg-slate-300 dark:bg-slate-700 rounded-xl w-full"></div>
                    </div>
                </div>

                <!-- Skeleton Card 2 (visible sur tablette et +) -->
                <div class="bg-surface rounded-2xl border border-bordercustom overflow-hidden shadow-sm flex flex-col justify-between animate-pulse hidden sm:flex">
                    <div>
                        <div class="relative h-48 bg-slate-200 dark:bg-slate-800/80 flex items-center justify-center">
                            <svg class="w-10 h-10 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div class="absolute top-3 right-3 h-5 w-20 bg-slate-300 dark:bg-slate-700 rounded-md"></div>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="h-3.5 bg-slate-200 dark:bg-slate-800 rounded w-1/3"></div>
                            <div class="h-5 bg-slate-300 dark:bg-slate-700 rounded-md w-3/4"></div>
                            <div class="space-y-1.5 pt-1">
                                <div class="h-3 bg-slate-200 dark:bg-slate-800 rounded w-full"></div>
                                <div class="h-3 bg-slate-200 dark:bg-slate-800 rounded w-4/5"></div>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 pt-0 space-y-3">
                        <div class="flex items-center justify-between border-t border-bordercustom pt-3">
                            <div class="h-3.5 bg-slate-200 dark:bg-slate-800 rounded w-20"></div>
                            <div class="h-3.5 bg-slate-200 dark:bg-slate-800 rounded w-16"></div>
                        </div>
                        <div class="h-10 bg-slate-300 dark:bg-slate-700 rounded-xl w-full"></div>
                    </div>
                </div>

                <!-- Skeleton Card 3 (visible sur desktop) -->
                <div class="bg-surface rounded-2xl border border-bordercustom overflow-hidden shadow-sm flex flex-col justify-between animate-pulse hidden lg:flex">
                    <div>
                        <div class="relative h-48 bg-slate-200 dark:bg-slate-800/80 flex items-center justify-center">
                            <svg class="w-10 h-10 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div class="absolute top-3 right-3 h-5 w-20 bg-slate-300 dark:bg-slate-700 rounded-md"></div>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="h-3.5 bg-slate-200 dark:bg-slate-800 rounded w-1/3"></div>
                            <div class="h-5 bg-slate-300 dark:bg-slate-700 rounded-md w-3/4"></div>
                            <div class="space-y-1.5 pt-1">
                                <div class="h-3 bg-slate-200 dark:bg-slate-800 rounded w-full"></div>
                                <div class="h-3 bg-slate-200 dark:bg-slate-800 rounded w-4/5"></div>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 pt-0 space-y-3">
                        <div class="flex items-center justify-between border-t border-bordercustom pt-3">
                            <div class="h-3.5 bg-slate-200 dark:bg-slate-800 rounded w-20"></div>
                            <div class="h-3.5 bg-slate-200 dark:bg-slate-800 rounded w-16"></div>
                        </div>
                        <div class="h-10 bg-slate-300 dark:bg-slate-700 rounded-xl w-full"></div>
                    </div>
                </div>
            </div>

            <!-- Error State (Hidden by default) -->
            <div id="error-state" class="hidden bg-surface-secondary border border-bordercustom rounded-3xl p-10 text-center max-w-lg mx-auto my-28 space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-fore">Impossible de charger les campagnes</h3>
                <p id="error-message" class="text-xs text-fore-secondary">Une erreur de connexion au serveur s'est produite.</p>
                <button onclick="loadCampaigns()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-dark text-white text-xs font-bold rounded-xl transition shadow">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Réessayer</span>
                </button>
            </div>

            <!-- Empty State (Hidden by default) -->
            <div id="empty-state" class="hidden bg-surface-secondary border border-bordercustom rounded-3xl p-12 text-center max-w-md mx-auto my-28 space-y-4">
                <div class="w-14 h-14 rounded-2xl bg-surface border border-bordercustom text-fore-secondary flex items-center justify-center mx-auto shadow-sm">
                    <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11.636 6A13 13 0 0 0 19.4 3.2 1 1 0 0 1 21 4v11.344"/>
                        <path d="M14.378 14.357A13 13 0 0 0 11 14H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h1"/>
                        <path d="m2 2 20 20"/><path d="M6 14a12 12 0 0 0 2.4 7.2 2 2 0 0 0 3.2-2.4A8 8 0 0 1 10 14"/>
                        <path d="M8 8v6"/>
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-fore">Aucune campagne disponible</h3>
                    <p class="text-xs text-fore-secondary leading-relaxed">
                        Il n'y a actuellement aucun concours ou scrutin public ouvert au vote.
                    </p>
                </div>
                <a href="/campaigns/new" class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary hover:bg-primary-dark text-white text-xs font-bold rounded-xl transition shadow">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Créer la première campagne
                </a>
            </div>

            <!-- Campaign Cards Grid (Filled dynamically) -->
            <div id="campaigns-grid" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"></div>
        </section>

    </main>

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
        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        async function loadCampaigns() {
            const loadingEl = document.getElementById('loading-state');
            const errorEl = document.getElementById('error-state');
            const emptyEl = document.getElementById('empty-state');
            const gridEl = document.getElementById('campaigns-grid');
            const errorMsg = document.getElementById('error-message');

            // Reset states
            loadingEl.classList.remove('hidden');
            errorEl.classList.add('hidden');
            emptyEl.classList.add('hidden');
            gridEl.classList.add('hidden');
            gridEl.innerHTML = '';

            try {
                const response = await fetch('/api/campaigns', {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }

                const result = await response.json();
                const campaigns = result.data || [];

                loadingEl.classList.add('hidden');

                if (campaigns.length === 0) {
                    emptyEl.classList.remove('hidden');
                    document.getElementById('stat-active').textContent = '0';
                    document.getElementById('stat-total').textContent = '0';
                    document.getElementById('stat-votes').textContent = '0';
                    return;
                }

                // Update aggregate stats
                let activeCount = 0;
                let totalVotesAcrossAll = 0;

                campaigns.forEach(c => {
                    if (c.status === 'active') activeCount++;
                    totalVotesAcrossAll += parseInt(c.totalVotes || 0, 10);
                });

                document.getElementById('stat-active').textContent = activeCount;
                document.getElementById('stat-total').textContent = campaigns.length;
                document.getElementById('stat-votes').textContent = new Intl.NumberFormat('fr-FR').format(totalVotesAcrossAll);

                // Build cards
                campaigns.forEach(campaign => {
                    const isActive = campaign.status === 'active';
                    const title = escapeHtml(campaign.title || 'Campagne sans titre');
                    const organizer = escapeHtml(campaign.organizer_name || 'Organisateur');
                    const description = escapeHtml(campaign.description || 'Participez à cette campagne de vote.');
                    const candidateCount = parseInt(campaign.candidate_count || 0, 10);
                    const totalVotes = parseInt(campaign.totalVotes || 0, 10);
                    const campaignId = parseInt(campaign.campaign_id, 10);
                    const imageUrl = campaign.image_url ? escapeHtml(campaign.image_url) : null;

                    // Image or Local Placeholder
                    const imageHtml = imageUrl 
                        ? `<img src="${imageUrl}" alt="${title}" class="w-full h-full object-cover">`
                        : `<div class="flex flex-col items-center justify-center text-fore-secondary p-4 text-center">
                                <svg class="w-10 h-10 mb-1 opacity-40 text-fore" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-[11px] font-medium opacity-60">Aucune image</span>
                           </div>`;

                    // Status Badge
                    const badgeHtml = isActive 
                        ? `<span class="bg-surface/90 backdrop-blur-md text-fore text-[10px] font-bold px-2.5 py-1 rounded-md border border-bordercustom shadow-sm flex items-center gap-1.5">
                               <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                               <span>En direct</span>
                           </span>`
                        : `<span class="bg-surface/90 backdrop-blur-md text-fore-secondary text-[10px] font-bold px-2.5 py-1 rounded-md border border-bordercustom shadow-sm">
                               Terminé
                           </span>`;

                    const cardHtml = `
                        <article class="bg-surface rounded-2xl border border-bordercustom overflow-hidden shadow-sm flex flex-col justify-between hover:border-primary/50 transition">
                            <div>
                                <div class="relative h-48 bg-surface-secondary flex items-center justify-center overflow-hidden">
                                    ${imageHtml}
                                    <div class="absolute top-3 right-3">
                                        ${badgeHtml}
                                    </div>
                                </div>
                                <div class="p-5 space-y-1.5">
                                    <span class="text-xs text-primary font-semibold block">Par ${organizer}</span>
                                    <h2 class="text-base font-bold text-fore leading-snug line-clamp-1" title="${title}">${title}</h2>
                                    <p class="text-xs text-fore-secondary line-clamp-2 leading-relaxed">${description}</p>
                                </div>
                            </div>

                            <div class="p-5 pt-0 space-y-3">
                                <div class="flex items-center justify-between text-xs font-semibold text-fore-secondary border-t border-bordercustom pt-3">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-fore-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        ${candidateCount} candidat${candidateCount > 1 ? 's' : ''}
                                    </span>
                                    <span class="font-bold text-fore flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        ${new Intl.NumberFormat('fr-FR').format(totalVotes)} votes
                                    </span>
                                </div>

                                <a href="/campaigns/${campaignId}" class="block w-full py-2.5 ${isActive ? 'bg-primary hover:bg-primary-dark text-white' : 'bg-surface-secondary hover:bg-surface border border-bordercustom text-fore'} text-xs font-bold text-center rounded-xl shadow transition">
                                    ${isActive ? 'Accéder au Vote en Direct' : 'Consulter les Résultats'}
                                </a>
                            </div>
                        </article>
                    `;

                    gridEl.insertAdjacentHTML('beforeend', cardHtml);
                });

                gridEl.classList.remove('hidden');

            } catch (err) {
                console.error("Erreur lors de la récupération des campagnes:", err);
                loadingEl.classList.add('hidden');
                errorMsg.textContent = err.message || "Erreur de communication avec l'API.";
                errorEl.classList.remove('hidden');
            }
        }

        // Initialisation automatique au chargement
        document.addEventListener('DOMContentLoaded', loadCampaigns);
    </script>
</body>
</html>