<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/controllers/AuthController.php";

try {
    $controller = new AuthController();
    $userData = $controller->getAuthUser();

    if ($userData) {
        header("Location: /dashboard");
        exit;
    }
} catch (Exception $e) {
    error_log("[AuthController] /login: Error verifying login from database");
}

?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Organisateur - IVote</title>
    
    <!-- Police : Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS & Config -->
    <script src="/public/js/tailwindcss/tailwindcss.js"></script>
    <script src="/public/js/tailwindcss/tailwindcss.config.js"></script>

    <link rel="stylesheet" href="/public/css/index.css">
</head>
<body class="min-h-screen bg-surface text-fore flex flex-col justify-between items-center px-4 py-8 relative selection:bg-blue-500 selection:text-white font-sans">

    <!-- Bouton Retour Accueil & Theme Toggle en haut -->
    <header class="w-full max-w-5xl flex items-center justify-between pb-6">
        <a href="/" class="flex items-center gap-2 text-xs font-semibold text-fore-secondary hover:text-fore transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Retour à l'accueil</span>
        </a>

        <!-- Bouton Dark Mode -->
        <button id="theme-toggle" class="p-2 rounded-xl border border-bordercustom hover:bg-surface-secondary text-fore transition" aria-label="Basculer le mode sombre">
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
    </header>

    <!-- Carte Principale de Connexion -->
    <main class="w-full max-w-md my-auto">
        <div class="bg-surface-secondary border border-bordercustom rounded-3xl p-6 sm:p-8 shadow-xl relative backdrop-blur-md space-y-6">
            
            <!-- En-tête de la carte -->
            <div class="text-center space-y-3">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-primary text-white shadow-md mx-auto">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </div>
                
                <div>
                    <h1 class="text-2xl font-extrabold text-fore tracking-tight">
                        Espace Organisateur
                    </h1>
                    <p class="text-xs text-fore-secondary mt-1">
                        Connectez-vous pour piloter vos campagnes et retraits
                    </p>
                </div>
            </div>

            <!-- Formulaire -->
            <form id="login-form" action="/api/auth/login" method="POST" class="space-y-4">
                <!-- Zone d'affichage des messages d'erreur dynamique -->
                <div class="error_frame hidden p-3 rounded-xl text-xs text-center font-medium bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400"></div>

                <div>
                    <label class="block text-xs font-bold text-fore mb-1.5">
                        Adresse e-mail
                    </label>
                    <div class="relative">
                        <svg class="w-4 h-4 text-fore-secondary absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                        </svg>
                        <input type="email" 
                               required 
                               name="email" 
                               placeholder="nom@domaine.com" 
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-surface border border-bordercustom text-xs text-fore placeholder:text-fore-secondary/50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-fore mb-1.5">
                        Mot de passe
                    </label>
                    <div class="relative">
                        <svg class="w-4 h-4 text-fore-secondary absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input type="password" 
                               required 
                               name="password" 
                               placeholder="••••••••••••" 
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-surface border border-bordercustom text-xs text-fore placeholder:text-fore-secondary/50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
                    </div>
                </div>

                <button type="submit" 
                        class="w-full py-3 bg-primary hover:bg-primary-dark text-white font-bold text-xs rounded-xl shadow-md transition transform hover:-translate-y-0.5 flex items-center justify-center gap-2 mt-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <span>Se Connecter</span>
                </button>
            </form>

            <!-- Pied de la carte -->
            <div class="pt-4 border-t border-bordercustom text-center text-xs text-fore-secondary">
                <span>Nouvel organisateur ?</span>
                <a href="/signup" class="text-primary font-bold hover:underline ml-1">
                    Créer un compte
                </a>
            </div>

        </div>
    </main> 

    <!-- Copyright discret -->
    <footer class="text-center text-xs text-fore-secondary pt-6">
        &copy; <?= date('Y') ?> IVote. Tous droits réservés.
    </footer>

    <!-- Scripts -->
    <script src="/public/js/theme_toggle.js"></script>
    <script src="/public/js/auth.js" defer></script>
</body>
</html>