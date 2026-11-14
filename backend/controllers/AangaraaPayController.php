<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/BaseController.php';

/**
 * Webhook AangaraaPay : appelé par la passerelle quand un paiement change d'état.
 *
 * POST /api/webhooks/aangaraa   (public, aucun JWT requis)
 *
 * L'endpoint est mis en confiance par re-vérification auprès de la passerelle
 * (aangaraa_check_status) avant d'enregistrer le moindre vote : le payload du
 * webhook ne suffit jamais, seul l'état SUCCESSFUL confirmé côté agrégateur
 * déclenche l'écriture (idempotente via transaction_ref UNIQUE).
 */
class AangaraaPayController extends BaseController
{
    public function webhook(): array
    {
        $payload = $this->getRequestData();
        if ($payload === []) {
            return ['success' => false, 'message' => 'Payload vide.'];
        }

        $transactionRef = trim((string)($payload['transaction_id'] ?? $payload['transaction_ref'] ?? ''));
        $payToken = AangaraaPay::payToken($payload);
        if ($transactionRef === '' && is_array($payload['data'] ?? null)) {
            $transactionRef = trim((string)(
                $payload['data']['transaction_id']
                ?? $payload['data']['transaction_ref']
                ?? ''
            ));
        }

        $stmtPay = $this->db->prepare("SELECT * FROM Payment WHERE (transaction_ref = ? OR (? <> '' AND pay_token = ?)) LIMIT 1");
        $stmtPay->execute([$transactionRef, $payToken, $payToken]);
        $payment = $stmtPay->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            error_log('[IVote] Webhook AangaraaPay : paiement inconnu (ref=' . $transactionRef . ', paytoken=' . $payToken . ')');
            return ['success' => true, 'handled' => false, 'message' => 'Référence inconnue.'];
        }

        try {
            if (!empty($payment['pay_token'])) {
                $gateway = AangaraaPay::checkStatus((string)$payment['pay_token']);
                if (AangaraaPay::isSuccess($gateway)) {
                    $this->applySuccessfulPayment((string)$payment['transaction_ref']);
                } else {
                    $gatewayStatus = AangaraaPay::resolveStatus($gateway);
                    if (in_array($gatewayStatus, [AangaraaPay::STATUS_FAILED, AangaraaPay::STATUS_EXPIRED, AangaraaPay::STATUS_CANCELLED], true)) {
                        $this->db->prepare("UPDATE Payment SET status = ?, checked_at = NOW(), gateway_raw = ? WHERE payment_id = ? AND status = 'pending'")
                            ->execute([strtolower($gatewayStatus), json_encode($gateway, JSON_UNESCAPED_UNICODE), (int)$payment['payment_id']]);
                    }
                }
            }
        } catch (AangaraaPayException $error) {
            error_log('[IVote] Webhook AangaraaPay : re-vérification impossible (' . $error->getMessage() . ').');
        }

        return ['success' => true, 'handled' => true];
    }

    /**
     * Enregistre les votes d'un paiement confirmé (délégué à VoteController).
     */
    private function applySuccessfulPayment(string $transactionRef): array
    {
        $voteController = new VoteController();
        return $voteController->applySuccessfulPayment($transactionRef);
    }
}