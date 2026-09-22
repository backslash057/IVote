<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/AuthController.php';

try {
    $authController = new AuthController();
    $userSession = $authController->getAuthUser();

    // Si l'utilisateur n'est pas connecté, on le redirige vers la page de connexion
    if (!$userSession) {
        header("Location: /login");
        exit;
    }
} catch (Exception $e) {
    error_log("[AuthController] /logout: Error verifying session");
    header("Location: /login");
    exit;
}

// Extraction des informations utilisateur avec valeurs de repli
$userName = htmlspecialchars($userSession['name'] ?? 'Organisateur', ENT_QUOTES, 'UTF-8');
$userEmail = htmlspecialchars($userSession['email'] ?? '', ENT_QUOTES, 'UTF-8');

?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion - IVote</title>
    
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

    <!-- Carte Principale -->
    <main class="w-full max-w-md my-auto">
        <div class="bg-surface-secondary border border-bordercustom rounded-3xl p-6 sm:p-8 shadow-xl relative backdrop-blur-md space-y-6">
            
            <!-- En-tête -->
            <div class="text-center space-y-3">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-red-500/10 text-red-500 border border-red-500/20 shadow-sm mx-auto">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </div>
                
                <div>
                    <h1 class="text-2xl font-extrabold text-fore tracking-tight">
                        Session Active
                    </h1>
                    <p class="text-xs text-fore-secondary mt-1">
                        Souhaitez-vous fermer votre session organisateur ?
                    </p>
                </div>
            </div>

            <!-- Message d'état AJAX -->
            <div id="statusMessage" class="hidden p-3 rounded-xl text-xs text-center font-medium"></div>

            <!-- Bloc Identité Utilisateur -->
            <div id="userInfoBlock" class="bg-surface border border-bordercustom rounded-2xl p-4 shadow-sm">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 border border-primary/20 font-bold text-sm">
                        <?= strtoupper(substr($userName, 0, 2)) ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-bold text-fore truncate"><?= $userName ?></h2>
                            <span class="px-2 py-0.5 rounded-full bg-green-500/10 text-green-600 text-[10px] font-bold border border-green-500/20">
                                Actif
                            </span>
                        </div>
                        <p class="text-xs text-fore-secondary truncate mt-0.5"><?= $userEmail ?></p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <form id="logoutForm" class="space-y-3">
                <button type="submit" 
                        id="logoutBtn" 
                        class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-md transition transform hover:-translate-y-0.5 flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Se Déconnecter</span>
                </button>

                <a href="/dashboard" 
                   class="w-full py-2.5 rounded-xl bg-surface hover:bg-surface-secondary border border-bordercustom text-fore font-semibold text-xs transition flex items-center justify-center gap-2 text-center block">
                    Annuler et aller au tableau de bord
                </a>
            </form>

        </div>
    </main>

    <!-- Footer -->
    <footer class="text-center text-xs text-fore-secondary pt-6">
        &copy; <?= date('Y') ?> IVote. Tous droits réservés.
    </footer>

    <!-- Scripts -->
    <script src="/public/js/theme_toggle.js"></script>
    <script>
        document.getElementById('logoutForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('logoutBtn');
            const statusMsg = document.getElementById('statusMessage');
            
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            btn.innerHTML = `
                <svg class="animate-spin h-4 w-4 text-white shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Déconnexion en cours...</span>
            `;

            try {
                const response = await fetch('/api/auth/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    statusMsg.className = 'p-3 rounded-xl text-xs text-center font-medium bg-green-500/10 border border-green-500/30 text-green-600 dark:text-green-400 block';
                    statusMsg.textContent = (data.message || 'Déconnexion réussie !') + ' Redirection...';

                    // Supprime le token éventuel stocké localement
                    localStorage.removeItem('auth_token');

                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 1200);
                } else {
                    throw new Error(data.message || 'Une erreur est survenue lors de la déconnexion.');
                }
            } catch (err) {
                statusMsg.className = 'p-3 rounded-xl text-xs text-center font-medium bg-red-500/10 border border-red-500/30 text-red-600 dark:text-red-400 block';
                statusMsg.textContent = err.message;
                
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                btn.innerHTML = `
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Se Déconnecter</span>
                `;
            }
        });
    </script>
</body>
</html>