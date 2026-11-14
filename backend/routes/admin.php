<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/AuthController.php';

$authController = new AuthController();
$authResult = $authController->me();

if (empty($authResult['success']) || empty($authResult['user'])) {
    header('Location: /login');
    exit;
}

$currentUser = $authResult['user'];
if (($currentUser['role'] ?? '') !== 'super_admin') {
    // http_response_code(403);
    // echo 'Accès réservé au super-administrateur.';

    http_response_code(404);
    require_once $_SERVER['DOCUMENT_ROOT'] . '/routes/404.html';
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/public/ivote_logo.svg" type="image/svg+xml">
    <title>IVote - Administration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="/public/js/tailwindcss/tailwindcss.js"></script>
    <script src="/public/js/tailwindcss/tailwindcss.config.js"></script>
    <link rel="stylesheet" href="/public/css/index.css">
</head>
<body class="antialiased min-h-screen bg-surface text-fore">
    <header class="sticky top-0 z-40 bg-surface/90 backdrop-blur-md border-b border-bordercustom">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white font-extrabold">IV</div>
                <div><div class="text-xl font-extrabold">IVote</div><div class="text-[10px] text-fore-secondary font-semibold tracking-wider">ADMINISTRATION</div></div>
            </a>
            <div class="flex items-center gap-3 text-xs font-semibold">
                <span class="hidden sm:inline text-fore-secondary"><?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <a href="/notifications" class="relative inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-bordercustom hover:bg-surface-secondary" aria-label="Notifications"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg><span id="notification-count" class="hidden rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] text-white"></span></a>
                <a href="/logout" class="text-red-500">Déconnexion</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
        <section class="bg-surface-secondary border border-bordercustom rounded-3xl p-6 sm:p-8">
            <p class="text-xs font-bold uppercase tracking-wider text-primary">Centre de contrôle</p>
            <h1 class="mt-2 text-3xl font-extrabold">Administration IVote</h1>
            <p class="mt-2 text-sm text-fore-secondary">Modérez les comptes, surveillez les campagnes et traitez les retraits.</p>
        </section>

        <section id="admin-overview" class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="admin-stat"><span data-stat="organizers">--</span><small>Organisateurs</small></div>
            <div class="admin-stat"><span data-stat="published_campaigns">--</span><small>Campagnes en ligne</small></div>
            <div class="admin-stat"><span data-stat="suspended_users">--</span><small>Comptes suspendus</small></div>
            <div class="admin-stat"><span data-stat="suspended_campaigns">--</span><small>Campagnes suspendues</small></div>
            <div class="admin-stat"><span data-stat="pending_payouts">--</span><small>Retraits à traiter</small></div>
        </section>

        <section class="grid lg:grid-cols-2 gap-6">
            <div class="bg-surface-secondary border border-bordercustom rounded-3xl overflow-hidden">
                <div class="p-5 border-b border-bordercustom flex items-center justify-between"><div><h2 class="font-extrabold">Organisateurs</h2><p class="text-xs text-fore-secondary mt-1">Suspendre ou réactiver un compte.</p></div><button class="admin-refresh text-primary" data-refresh="users">Actualiser</button></div>
                <div id="admin-users" class="divide-y divide-bordercustom"><p class="p-5 text-sm text-fore-secondary">Chargement...</p></div>
            </div>
            <div class="bg-surface-secondary border border-bordercustom rounded-3xl overflow-hidden">
                <div class="p-5 border-b border-bordercustom flex items-center justify-between"><div><h2 class="font-extrabold">Campagnes</h2><p class="text-xs text-fore-secondary mt-1">Suspendre une campagne indésirable.</p></div><button class="admin-refresh text-primary" data-refresh="campaigns">Actualiser</button></div>
                <div id="admin-campaigns" class="divide-y divide-bordercustom"><p class="p-5 text-sm text-fore-secondary">Chargement...</p></div>
            </div>
        </section>

        <section class="bg-surface-secondary border border-bordercustom rounded-3xl p-6 space-y-5">
            <div>
                <h2 class="font-extrabold">Finances de la plateforme</h2>
                <p class="text-xs text-fore-secondary mt-1">Les montants sont calculés uniquement sur les paiements confirmés.</p>
            </div>
            <div id="admin-finance-cards" class="grid grid-cols-2 lg:grid-cols-4 gap-3"></div>
            <div class="grid lg:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-bold text-sm mb-3">Solde des organisateurs</h3>
                    <div id="admin-organizer-finance" class="overflow-x-auto text-xs"></div>
                </div>
                <div class="space-y-4">
                    <form id="admin-settings-form" class="space-y-3">
                        <h3 class="font-bold text-sm">Paramètres financiers</h3>
                        <label class="block text-xs font-semibold">Frais totaux annoncés aux organisateurs (%)
                            <input id="admin-fee-rate" type="number" min="0" max="100" step="0.01" required class="mt-1 w-full rounded-xl border border-bordercustom bg-surface px-3 py-2 text-sm">
                        </label>
                        <input id="admin-payout-holder" required maxlength="100" placeholder="Titulaire du compte IVote" class="w-full rounded-xl border border-bordercustom bg-surface px-3 py-2 text-sm">
                        <select id="admin-payout-method" required class="w-full rounded-xl border border-bordercustom bg-surface px-3 py-2 text-sm">
                            <option value="">Opérateur du compte IVote</option>
                            <option value="mtn_momo">MTN MoMo</option>
                            <option value="orange_money">Orange Money</option>
                        </select>
                        <input id="admin-payout-wallet" required maxlength="50" placeholder="Numéro Mobile Money IVote" class="w-full rounded-xl border border-bordercustom bg-surface px-3 py-2 text-sm">
                        <button class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white" type="submit">Enregistrer</button>
                    </form>
                    <form id="admin-platform-withdraw-form" class="space-y-3 border-t border-bordercustom pt-4">
                        <h3 class="font-bold text-sm">Retirer les revenus IVote</h3>
                        <p id="admin-platform-balance" class="text-xs text-fore-secondary"></p>
                        <input id="admin-platform-amount" type="number" min="1" required placeholder="Montant en FCFA" class="w-full rounded-xl border border-bordercustom bg-surface px-3 py-2 text-sm">
                        <button class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white" type="submit">Retirer maintenant</button>
                    </form>
                </div>
            </div>
            <div>
                <h3 class="font-bold text-sm mb-3">Retraits IVote</h3>
                <div id="admin-platform-payouts" class="overflow-x-auto text-xs"></div>
            </div>
        </section>

        <section class="bg-surface-secondary border border-bordercustom rounded-3xl overflow-hidden">
            <div class="p-5 border-b border-bordercustom flex items-center justify-between"><div><h2 class="font-extrabold">Retraits</h2><p class="text-xs text-fore-secondary mt-1">Valider ou refuser une demande en attente.</p></div><div class="flex gap-4"><a class="admin-refresh text-primary" href="/admin/payouts.csv">Exporter CSV</a><button class="admin-refresh text-primary" data-refresh="payouts">Actualiser</button></div></div>
            <div id="admin-payouts" class="overflow-x-auto"><p class="p-5 text-sm text-fore-secondary">Chargement...</p></div>
        </section>

        <section class="bg-surface-secondary border border-bordercustom rounded-3xl p-6 space-y-4">
            <div><h2 class="font-extrabold">Annonce aux organisateurs</h2><p class="text-xs text-fore-secondary mt-1">Envoi individuel ou à tous les organisateurs actifs.</p></div>
            <form id="admin-announcement-form" class="grid gap-3">
                <label class="flex items-center gap-2 text-sm"><input id="announcement-broadcast" type="checkbox" checked> Envoyer à tous les organisateurs actifs</label>
                <select id="announcement-recipient" class="hidden w-full rounded-xl border border-bordercustom bg-surface px-3 py-2 text-sm"><option value="">Choisir un organisateur</option></select>
                <input id="announcement-title" required maxlength="150" class="w-full rounded-xl border border-bordercustom bg-surface px-3 py-2 text-sm" placeholder="Titre">
                <textarea id="announcement-message" required rows="4" class="w-full rounded-xl border border-bordercustom bg-surface px-3 py-2 text-sm" placeholder="Message"></textarea>
                <div class="flex justify-end"><button class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white" type="submit">Envoyer l’annonce</button></div>
            </form>
        </section>

        <section class="flex flex-wrap gap-3">
            <a href="/admin/transactions.csv" class="rounded-xl border border-bordercustom px-4 py-2 text-sm font-semibold">Télécharger l’historique des votes CSV</a>
        </section>
    </main>

    <script src="/public/js/theme_toggle.js"></script>
    <script src="/public/js/notification_badge.js" defer></script>
    <script src="/public/js/admin_dashboard.js" defer></script>
</body>
</html>
