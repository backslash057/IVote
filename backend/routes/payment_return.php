<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';

// AangaraaPay renvoie l'utilisateur ici après le paiement. La référence peut
// arriver sous plusieurs noms selon la passerelle ; on les accepte tous.
$reference = trim((string)(
    $_GET['transaction_ref']
    ?? $_GET['transaction_id']
    ?? $_GET['reference']
    ?? $_GET['ref']
    ?? ''
));

$status = null;
$amountFcfa = null;

if ($reference !== '') {
    try {
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/config.php';
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['dbname']
        );
        $db = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $stmt = $db->prepare("SELECT status, amount_fcfa FROM Payment WHERE transaction_ref = ?");
        $stmt->execute([$reference]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($payment) {
            $status = strtolower((string)$payment['status']);
            $amountFcfa = (int)$payment['amount_fcfa'];
        }
    } catch (Throwable $error) {
        error_log('[IVote] payment_return lookup failed : ' . $error->getMessage());
    }
}

$presentation = [
    'successful' => ['Paiement confirmé', 'Votre vote a bien été enregistré. Merci pour votre participation !', '#34d399'],
    'pending'    => ['Paiement en attente', 'Confirmez la demande reçue sur votre téléphone. Le vote sera enregistré automatiquement.', '#fcd34d'],
    'failed'     => ['Paiement échoué', "Le paiement n'a pas abouti. Aucun montant n'a été débité.", '#f87171'],
    'expired'    => ['Paiement expiré', 'La demande de paiement a expiré. Vous pouvez réessayer depuis IVote.', '#fbbf24'],
    'cancelled'  => ['Paiement annulé', 'Le paiement a été annulé.', '#9ca3af'],
];

[$title, $message, $accent] = $presentation[$status] ?? [
    'Retour de paiement',
    "Votre paiement a été reçu. Consultez le statut affiché dans IVote pour confirmer l'enregistrement du vote.",
    '#34d399',
];

http_response_code(200);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> - IVote</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               background: #0b0f0d; color: #e6efe9; padding: 32px 20px;
               font: 16px/1.6 ui-sans-serif, system-ui, "Segoe UI", sans-serif; }
        .card { max-width: 480px; width: 100%; background: #131a17; border: 1px solid #232d28;
                border-radius: 16px; padding: 32px 28px; text-align: center; box-shadow: 0 18px 48px rgba(0,0,0,.45); }
        .dot { width: 56px; height: 56px; border-radius: 50%; margin: 0 auto 18px;
               background: <?= $accent ?>22; border: 2px solid <?= $accent ?>; }
        h1 { margin: 0 0 10px; font-size: 22px; color: <?= $accent ?>; }
        p { margin: 0 0 18px; color: #a9b8b0; }
        .amount { font-weight: 700; color: #e6efe9; }
        a.button { display: inline-block; margin-top: 6px; padding: 12px 22px; border-radius: 12px;
                   background: #34d399; color: #05261b; font-weight: 700; text-decoration: none; }
    </style>
</head>
<body>
    <main class="card">
        <div class="dot"></div>
        <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php if ($amountFcfa !== null): ?>
            <p>Montant : <span class="amount"><?= number_format($amountFcfa, 0, ',', ' ') ?> FCFA</span></p>
        <?php endif; ?>
        <a class="button" href="/">Retour à IVote</a>
    </main>
</body>
</html>
