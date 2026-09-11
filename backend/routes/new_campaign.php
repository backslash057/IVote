<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/authController.php';

$authController = new AuthController();
$userSession = $authController->checkAuthentification();

if (!$userSession) {
    header('Location: /login');
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr" class="bg-slate-950 text-slate-100 min-h-screen flex flex-col">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une Campagne - IVote</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col pt-20 font-sans">
    
    <!-- Header / Navbar Simplifiée -->
    <header class="fixed top-0 left-0 right-0 z-40 bg-slate-950/90 backdrop-blur-xl border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-md shadow-emerald-600/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                </div>
                <span class="text-base font-bold text-white">IVote</span>
            </a>
            <div class="flex items-center gap-2">
                <a href="/dashboard" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-xs font-semibold text-slate-300 hover:text-emerald-400 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Mes campagnes
                </a>
                <div class="flex items-center gap-2.5 px-2 py-1.5 rounded-full bg-slate-900 border border-slate-800/80 text-xs shadow-inner">
                    <div class="w-6 h-6 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold text-[10px]">
                        <?= strtoupper(substr($userSession['name'] ?? 'U', 0, 2)) ?>
                    </div>
                    <span class="font-semibold text-slate-200"><?= htmlspecialchars($userSession['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <a href="/logout" title="Se déconnecter" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-900 border border-slate-800/80 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 hover:border-rose-500/20 transition-all cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" x2="9" y1="12" y2="12"/>
                    </svg>
                </a>
            </div>
        </div>
    </header>
    
    <main class="flex-grow max-w-3xl mx-auto px-4 sm:px-6 py-10 w-full">
        <div class="rounded-3xl bg-slate-900/90 border border-slate-800 p-6 sm:p-10 shadow-2xl space-y-6">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Nouvelle Campagne</span>
                <h1 class="text-2xl sm:text-3xl font-bold text-white mt-1">Configurez votre élection</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">La campagne sera créée en mode <strong>Brouillon</strong>. Vous pourrez ajouter vos candidats et configurer les catégories ensuite.</p>
            </div>

            <form id="createCampaignForm" enctype="multipart/form-data" class="space-y-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Titre de la Campagne / Événement *</label>
                    <input type="text" name="title" required placeholder="Ex: Miss & Master Campus 2026" class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:border-emerald-500 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Description *</label>
                    <textarea name="description" rows="4" required placeholder="Présentez les enjeux, règles et récompenses du concours..." class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:border-emerald-500 outline-none"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Prix par Vote (FCFA) *</label>
                        <input type="number" name="price_per_vote" value="100" min="50" step="50" required class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:border-emerald-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Date et Heure de Clôture (Optionnel)</label>
                        <input type="datetime-local" name="date_cloture" class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:border-emerald-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Bannière / Photo Principale</label>
                    <input type="file" name="image" accept="image/*" class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-400 file:mr-4 file:py-1.5 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-500">
                </div>

                <!-- Error Alert Box -->
                <div id="formErrorMessage" class="hidden p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-xs text-red-400"></div>
                <div id="formSuccessMessage" class="hidden p-3 rounded-xl bg-green-500/10 border border-green-500/30 text-xs text-green-700"></div>
                
                <!-- Actions with Loading Button -->
                <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                    <a href="/organizer-campaigns.php" class="px-5 py-2.5 rounded-xl bg-slate-800 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition">
                        Annuler
                    </a>
                    <button type="submit" id="submitBtn" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs sm:text-sm font-bold shadow-lg shadow-emerald-600/30 transition">
                        <!-- Spinner (Hidden by default) -->
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

    <!-- Footer Standardisé -->
    <footer class="mt-auto border-t border-slate-800 bg-slate-900/60 pt-12 pb-8 text-slate-400">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
                <div class="space-y-4 md:col-span-1">
                    <div class="font-display flex items-center gap-2 text-xl font-bold text-white">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-white">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>    
                        </span>
                        IVote
                    </div>
                    <p class="text-xs leading-relaxed text-slate-400">
                        La plateforme moderne pour vos concours, élections et votes en ligne sécurisés et en temps réel.
                    </p>
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-white">Navigation</h3>
                    <ul class="mt-4 space-y-2 text-xs">
                        <li><a href="/" class="transition-colors hover:text-emerald-400">Accueil</a></li>
                        <li><a href="/campaigns" class="transition-colors hover:text-emerald-400">Explorer les campagnes</a></li>
                        <li><a href="/dashboard" class="transition-colors hover:text-emerald-400">Espace organisateur</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-white">Légal</h3>
                    <ul class="mt-4 space-y-2 text-xs">
                        <li><a href="#" class="transition-colors hover:text-emerald-400">Conditions d'utilisation</a></li>
                        <li><a href="#" class="transition-colors hover:text-emerald-400">Politique de confidentialité</a></li>
                        <li><a href="#" class="transition-colors hover:text-emerald-400">Mentions légales</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-white">Support</h3>
                    <ul class="mt-4 space-y-2 text-xs">
                        <li><a href="mailto:support@ivote.com" class="text-emerald-400">support@ivote.com</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-slate-800/80 pt-6 text-xs text-slate-500 sm:flex-row">
                <p>&copy; <?= date('Y') ?> IVote. Tous droits réservés.</p>
                <p>Votez en toute sécurité.</p>
            </div>
        </div>
    </footer>

    <!-- Link to external JS before closing </body> -->
    <script src="/public/js/create-campaign.js"></script>
</body>
</html>