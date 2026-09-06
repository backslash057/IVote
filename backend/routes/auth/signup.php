<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/controllers/authController.php";

// Try, load and verify the user data from cookies
$controller = new Authcontroller();
$userData = $controller->checkAuthentification();

if ($userData) {
    header("Location: /logout");
    exit;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - IVote</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- <script src="/public/js/debug.js"></script> -->
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center px-4 py-12 relative overflow-hidden font-sans">

    <!-- Boules en arriere plan -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 right-10 w-[500px] h-[300px] bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl relative z-10">
        
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/25 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">
                Espace Organisateur <span class="text-emerald-400">IVote</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Plateforme sécurisée de gestion des votes monétisés
            </p>
        </div>

        <!-- Dynamic Error / Success Message Box -->
        <div class="error_frame hidden mb-4 p-3 rounded-xl text-xs text-center"></div>

        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">
                    Nom
                </label>
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    <input type="text" required name="name" placeholder="Ex: Pr. Marcelle Ebongue" class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <!-- <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">
                    Numéro de Téléphone
                </label>
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/>
                    </svg>
                    <input type="text" required name="phone" placeholder="+237 6XX XX XX XX" class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                </div>
            </div> -->

            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">
                    Adresse Email
                </label>
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/>
                    </svg>
                    <input type="email" required name="email" placeholder="contact@uy1-events.cm" class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">
                    Mot de passe
                </label>
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input type="password" required name="password" placeholder="••••••••••••" class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 transition-all flex items-center justify-center gap-2 mt-2 disabled:opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                Créer mon Espace Organisateur
            </button>
        </form>

        <div class="w-full flex justify-end gap-2 mt-4 text-xs text-slate-400">
            Déja un compte? 
            <a href="/login" class="text-emerald-400 hover:underline">Se connecter</a>
        </div>
    </div>

    <script src="/public/js/auth.js" defer></script>
</body>
</html>