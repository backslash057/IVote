<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/AuthController.php';

$auth = (new AuthController())->me();
if (empty($auth['success']) || ($auth['user']['role'] ?? '') !== 'super_admin') {
    http_response_code(403);
    exit('Accès administrateur requis.');
}

$config = require $_SERVER['DOCUMENT_ROOT'] . '/config.php';
$db = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
    $config['username'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
$isPayoutExport = str_ends_with($path, '/payouts.csv');
if ($isPayoutExport) {
    $filename = 'ivote-retraits-' . date('Y-m-d') . '.csv';
    $stmt = $db->query(
        "SELECT p.payout_id, p.created_at, u.name AS organizer_name, u.email AS organizer_email,
                c.title AS campaign_title, p.amount_fcfa, p.payment_method, p.account_holder,
                p.wallet_number, p.status, p.rejection_reason, p.payment_reference, p.reviewed_at, p.processed_at
         FROM Payout p JOIN Campaign c ON c.campaign_id = p.campaign_id
         JOIN Users u ON u.user_id = c.organiser_id ORDER BY p.created_at DESC"
    );
    $headers = ['ID retrait', 'Créé le', 'Organisateur', 'Email', 'Campagne', 'Montant FCFA', 'Opérateur', 'Titulaire', 'Portefeuille', 'Statut', 'Motif refus', 'Référence paiement', 'Décidé le', 'Payé le'];
} else {
    $filename = 'ivote-votes-' . date('Y-m-d') . '.csv';
    $stmt = $db->query(
        "SELECT v.transaction_ref, v.created_at, u.name AS organizer_name, c.title AS campaign_title,
                cand.name AS candidate_name, v.vote_count, v.payment_method, v.amount_fcfa,
                v.payment_status, v.platform_fee_rate, v.platform_fee_amount_fcfa, v.organizer_net_amount_fcfa
         FROM Vote v JOIN Campaign c ON c.campaign_id = v.campaign_id
         JOIN Users u ON u.user_id = c.organiser_id
         JOIN Candidate cand ON cand.candidate_id = v.candidate_id ORDER BY v.created_at DESC"
    );
    $headers = ['Référence', 'Créé le', 'Organisateur', 'Campagne', 'Candidat', 'Nombre de votes', 'Opérateur', 'Montant brut FCFA', 'Statut paiement', 'Commission %', 'Commission FCFA', 'Net organisateur FCFA'];
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
$output = fopen('php://output', 'wb');
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, $headers, ';');
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $row = array_map(static function ($value) {
        $value = (string)($value ?? '');
        return preg_match('/^[=+@\-]/', $value) ? "'" . $value : $value;
    }, $row);
    fputcsv($output, $row, ';');
}
fclose($output);
exit;
