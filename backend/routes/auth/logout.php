<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/authController.php';

try {
    $authController = new AuthController();
    $userSession = $authController->checkAuthentification();

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
$userName = htmlspecialchars($userSession['name'] ?? $userSession['full_name'] ?? 'Organisateur', ENT_QUOTES, 'UTF-8');
$userEmail = htmlspecialchars($userSession['email'] ?? '', ENT_QUOTES, 'UTF-8');
$userRole = htmlspecialchars($userSession['role'] ?? 'Organisateur', ENT_QUOTES, 'UTF-8');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion - IVote</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center px-4 py-12 relative overflow-hidden font-sans">
    
    <!-- Boules en arrière-plan -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 right-10 w-[500px] h-[300px] bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl relative z-10">
        
        <!-- Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/25 mb-3">
                <!-- Icon User / Session -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">
                Session Active
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Espace Organisateur <span class="text-emerald-400">IVote</span>
            </p>
        </div>

        <!-- Zone de notification JS -->
        <div id="statusMessage" class="hidden mb-4 p-3 rounded-xl text-xs text-center border"></div>

        <!-- Bloc Identité Utilisateur -->
        <div id="userInfoBlock" class="bg-slate-950 border border-slate-800/80 rounded-2xl p-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-500/20 font-bold text-sm">
                    <?= strtoupper(substr($userName, 0, 2)) ?>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-sm font-bold text-white truncate"><?= $userName ?></h2>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-semibold">
                            <?= $userRole ?>
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 truncate mt-0.5"><?= $userEmail ?></p>
                </div>
            </div>
        </div>

        <!-- Formulaire / Action de déconnexion -->
        <form id="logoutForm" class="space-y-3">
            <button type="submit" id="logoutBtn" class="w-full py-3 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow-lg shadow-rose-600/20 transition-all flex items-center justify-center gap-2 cursor-pointer">
                <!-- SVG Logout -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" x2="9" y1="12" y2="12"/>
                </svg>
                <span>Se Déconnecter</span>
            </button>

            <a href="/" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition-all flex items-center justify-center gap-2 text-center block">
                Annuler et retourner à l'accueil
            </a>
        </form>
    </div>

    <!-- Script de soumission AJAX -->
    <script>
        document.getElementById('logoutForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('logoutBtn');
            const statusMsg = document.getElementById('statusMessage');
            
            // Désactivation du bouton pendant la requête
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            btn.innerHTML = `
                <svg class="animate-spin h-4 w-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Déconnexion en cours...</span>
            `;

            try {
                const response = await fetch('/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    statusMsg.className = 'mb-4 p-3 rounded-xl text-xs text-center bg-emerald-500/10 border-emerald-500/30 text-emerald-400 block';
                    statusMsg.textContent = (data.message || 'Déconnexion réussie !') + ' Redirection...';

                    // Redirection après 2 secondes
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Une erreur est survenue lors de la déconnexion.');
                }
            } catch (err) {
                statusMsg.className = 'mb-4 p-3 rounded-xl text-xs text-center bg-rose-500/10 border-rose-500/30 text-rose-400 block';
                statusMsg.textContent = err.message;
                
                // Réinitialisation du bouton en cas d'erreur
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
                btn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" x2="9" y1="12" y2="12"/>
                    </svg>
                    <span>Se Déconnecter</span>
                `;
            }
        });
    </script>
</body>
</html>