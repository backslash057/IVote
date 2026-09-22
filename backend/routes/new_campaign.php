<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/AuthController.php';

$authController = new AuthController();
$userSession = $authController->me();

if (empty($userSession['success'])) {
    header('Location: /login');
    exit;
}

$user = $userSession['user'];
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IVote - Créer une Campagne</title>
    
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

                <!-- User Session & Theme Controls -->
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

                    <a href="/dashboard" class="px-4 py-2.5 rounded-xl border border-bordercustom hover:bg-surface-secondary text-xs font-semibold text-fore transition">
                        Mes campagnes
                    </a>

                    <!-- Profil & Déconnexion -->
                    <div class="flex items-center gap-2 pl-2 border-l border-bordercustom">
                        <div class="flex items-center gap-2.5 px-3 py-1.5 rounded-full bg-surface-secondary border border-bordercustom text-xs">
                            <div class="w-6 h-6 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center text-primary font-bold text-[10px]">
                                <?= htmlspecialchars(strtoupper(substr($user['name'] ?? 'OR', 0, 2)), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <span class="font-semibold text-fore"><?= htmlspecialchars($user['name'] ?? 'Organisateur', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <a href="/logout" title="Se déconnecter" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-surface-secondary border border-bordercustom text-fore-secondary hover:text-red-500 hover:bg-red-500/10 hover:border-red-500/20 transition cursor-pointer">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" x2="9" y1="12" y2="12"/>
                            </svg>
                        </a>
                    </div>
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
            <a href="/campaigns" class="block py-2 text-sm font-medium text-fore-secondary">Explorer les campagnes</a>
            <a href="/dashboard" class="block py-2 text-sm font-medium text-fore-secondary">Espace organisateur</a>
            <div class="pt-3 border-t border-bordercustom flex items-center justify-between">
                <span class="text-xs font-semibold text-fore"><?= htmlspecialchars($user['name'] ?? 'Organisateur', ENT_QUOTES, 'UTF-8') ?></span>
                <a href="/logout" class="text-xs font-bold text-red-500 hover:underline">Déconnexion</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow max-w-3xl mx-auto px-4 sm:px-6 py-12 w-full">
        <div class="rounded-3xl bg-surface-secondary border border-bordercustom p-6 sm:p-10 shadow-sm space-y-6">
            
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 bg-surface border border-bordercustom px-3 py-1 rounded-full text-xs font-semibold text-primary">
                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                    <span>Nouvelle Campagne</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-fore tracking-tight">Configurez votre scrutin</h1>
                <p class="text-xs sm:text-sm text-fore-secondary leading-relaxed">
                    La campagne sera créée en mode <strong>Brouillon</strong>. Vous pourrez ajouter des catégories, des candidats et ajuster les paramètres avant publication.
                </p>
            </div>

            <form id="createCampaignForm" enctype="multipart/form-data" class="space-y-5 pt-2">
                <div>
                    <label class="block text-xs font-bold text-fore mb-1.5">Titre de la Campagne / Événement *</label>
                    <input type="text" name="title" required placeholder="Ex: Miss & Master Campus 2026" class="w-full px-4 py-3 rounded-xl bg-surface border border-bordercustom text-xs sm:text-sm text-fore placeholder:text-fore-secondary focus:ring-2 focus:ring-primary focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-fore mb-1.5">Description *</label>
                    <textarea name="description" rows="4" required placeholder="Présentez les enjeux, règles et récompenses du concours..." class="w-full px-4 py-3 rounded-xl bg-surface border border-bordercustom text-xs sm:text-sm text-fore placeholder:text-fore-secondary focus:ring-2 focus:ring-primary focus:outline-none transition"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-fore mb-1.5">Prix par Vote (FCFA) *</label>
                        <input type="number" name="price_per_vote" value="100" min="50" step="50" required class="w-full px-4 py-3 rounded-xl bg-surface border border-bordercustom text-xs sm:text-sm text-fore focus:ring-2 focus:ring-primary focus:outline-none transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-fore mb-1.5">Date et Heure de Clôture (Optionnel)</label>
                        <input type="datetime-local" name="date_cloture" class="w-full px-4 py-3 rounded-xl bg-surface border border-bordercustom text-xs sm:text-sm text-fore focus:ring-2 focus:ring-primary focus:outline-none transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-fore mb-1.5">Bannière / Photo Principale</label>
                    <input type="file" name="image" accept="image/*" class="w-full p-2.5 rounded-xl bg-surface border border-bordercustom text-xs text-fore-secondary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white hover:file:bg-primary-dark cursor-pointer transition">
                </div>

                <!-- Messages d'erreur et succès -->
                <div id="formErrorMessage" class="hidden p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-xs font-medium text-red-500"></div>
                <div id="formSuccessMessage" class="hidden p-4 rounded-xl bg-green-500/10 border border-green-500/20 text-xs font-medium text-green-600"></div>
                
                <!-- Actions -->
                <div class="pt-4 border-t border-bordercustom flex items-center justify-end gap-3">
                    <a href="/dashboard" class="px-5 py-2.5 rounded-xl border border-bordercustom bg-surface text-xs font-bold text-fore hover:bg-surface-secondary transition">
                        Annuler
                    </a>
                    <button type="submit" id="submitBtn" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-dark disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-bold shadow-md transition">
                        <svg id="btnSpinner" class="hidden animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span id="btnText">Créer la Campagne</span>
                    </button>
                </div>
            </form>

        </div>
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
    <script src="/public/js/create-campaign.js"></script>
</body>
</html>