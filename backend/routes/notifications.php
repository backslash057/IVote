<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/AuthController.php';
$user = (new AuthController())->me();
if (empty($user['success'])) {
    header('Location: /login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IVote - Notifications</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="/public/js/tailwindcss/tailwindcss.js"></script>
    <script src="/public/js/tailwindcss/tailwindcss.config.js"></script>
    <link rel="stylesheet" href="/public/css/index.css">
</head>
<body class="min-h-screen bg-surface text-fore">
    <header class="border-b border-bordercustom bg-surface-secondary">
        <div class="mx-auto flex h-16 max-w-4xl items-center justify-between px-4">
            <a href="/" class="text-lg font-extrabold">IVote</a>
            <nav class="flex gap-4 text-sm"><a href="/dashboard">Campagnes</a><?php if (($user['user']['role'] ?? '') === 'super_admin'): ?><a href="/admin">Administration</a><?php endif; ?></nav>
        </div>
    </header>
    <main class="mx-auto max-w-4xl space-y-5 px-4 py-10">
        <div class="flex items-center justify-between"><div><h1 class="text-3xl font-extrabold">Notifications</h1><p class="mt-1 text-sm text-fore-secondary">Annonces et mises à jour concernant vos campagnes et retraits.</p></div><button id="mark-all-read" class="text-sm font-bold text-primary">Tout marquer comme lu</button></div>
        <div id="notification-list" class="divide-y divide-bordercustom rounded-2xl border border-bordercustom bg-surface-secondary"><p class="p-5 text-sm text-fore-secondary">Chargement...</p></div>
    </main>
    <script src="/public/js/notification_badge.js" defer></script>
    <script src="/public/js/notifications.js" defer></script>
</body>
</html>
