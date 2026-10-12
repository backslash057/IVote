<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/BaseController.php';

class VoteController extends BaseController {

    /**
     * GET /api/campaigns/{id}/votes
     */
    public function index(string|int $id): array {
        $user = $this->requireOrganizer();
        if (!$user) {
            return ['success' => false, 'message' => 'Authentification et rôle organisateur requis.'];
        }

        $campaignId = (int)$id;
        if (!$this->verifyCampaignOwner($campaignId, (int)$user['user_id'])) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Action non autorisée.'];
        }

        $stmt = $this->db->prepare("
            SELECT v.*, c.name AS candidate_name, c.candidate_number, c.image_url AS candidate_avatar
            FROM Vote v
            JOIN Candidate c ON v.candidate_id = c.candidate_id
            WHERE v.campaign_id = ?
            ORDER BY v.created_at DESC
        ");
        $stmt->execute([$campaignId]);

        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * POST /api/campaigns/{id}/votes
     */
    public function recordVote(string|int $id): array {
        // Payment is still simulated, so this endpoint is the weakest link:
        // throttle it hard enough to slow scripted abuse, while staying
        // permissive for legitimate mobile carrier NAT pools.
        $this->enforceRateLimit(
            'vote_record',
            $this->clientIp(),
            60,
            60,
            'Trop de votes envoyés depuis cette adresse. Réessayez dans une minute.'
        );

        $campaignId = (int)$id;
        $data = $this->getRequestData();

        $candidateId = (int)($data['candidate_id'] ?? 0);
        $voteCount = (int)($data['vote_count'] ?? 1);
        $paymentMethod = $data['payment_method'] ?? '';

        if ($campaignId <= 0 || $candidateId <= 0 || $voteCount <= 0 || $voteCount > 500 || !in_array($paymentMethod, ['mtn_momo', 'orange_money'], true)) {
            http_response_code(422);
            return ['success' => false, 'message' => 'Données de vote invalides (1 à 500 votes, moyen de paiement valide requis).'];
        }

        $stmtCheck = $this->db->prepare("SELECT price_per_vote, status, date_cloture, title FROM Campaign WHERE campaign_id = ?");
        $stmtCheck->execute([$campaignId]);
        $camp = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$camp || $camp['status'] !== 'published' || ($camp['date_cloture'] && strtotime($camp['date_cloture']) < time())) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Cette campagne n\'est pas active ou est clôturée.'];
        }

        $stmtCand = $this->db->prepare("SELECT candidate_id FROM Candidate WHERE candidate_id = ? AND campaign_id = ?");
        $stmtCand->execute([$candidateId, $campaignId]);
        if (!$stmtCand->fetch()) {
            http_response_code(422);
            return ['success' => false, 'message' => 'Candidat introuvable dans cette campagne.'];
        }

        $price = (int)$camp['price_per_vote'];
        $discount = match (true) {
            $voteCount >= 50 => 0.8,
            $voteCount >= 20 => 0.85,
            $voteCount >= 10 => 0.9,
            default => 1.0,
        };

        $amountFcfa = (int)round($voteCount * $price * $discount);
        $feeRate = $this->getPlatformFeeRate();
        $feeAmount = (int)round($amountFcfa * $feeRate / 100);
        $organizerNet = $amountFcfa - $feeAmount;
        $transactionRef = 'TX-' . strtoupper(uniqid());

        // ---------------------------------------------------------------------
        // Parcours réel : AangaraaPay.
        // Un vote n'est enregistré (Vote) qu'une fois le paiement confirmé
        // (SUCCESSFUL). Tant que le client n'a pas validé l'USSD sur son
        // téléphone, on ne garde qu'une ligne de "Payment" en attente.
        // ---------------------------------------------------------------------
        if (AangaraaPay::configured()) {
            $phone = trim((string)($data['phone_number'] ?? ''));
            if ($phone === '') {
                http_response_code(400);
                return ['success' => false, 'message' => 'Le numéro Mobile Money est requis. Indiquez le numéro qui recevra la demande de paiement.'];
            }
            if (AangaraaPay::normalizePhone($phone) === null) {
                http_response_code(422);
                return ['success' => false, 'message' => 'Numéro de téléphone invalide. Format attendu : 6XXXXXXXX ou 2376XXXXXXXX.'];
            }
            if (!AangaraaPay::phoneMatchesMethod($phone, $paymentMethod)) {
                http_response_code(422);
                return ['success' => false, 'message' => 'Le numéro saisi n\'appartient pas à ' . AangaraaPay::operatorLabel($paymentMethod) . '. Vérifiez ou changez de moyen de paiement.'];
            }

            $operator = AangaraaPay::detectOperator($phone) ?: '';

            $sentAt = null;
            $stmtPay = $this->db->prepare(
                "INSERT INTO Payment (transaction_ref, campaign_id, candidate_id, phone_number, payment_method, operator, amount_fcfa, vote_count, status, sent_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)"
            );
            $stmtPay->execute([$transactionRef, $campaignId, $candidateId, AangaraaPay::normalizePhone($phone), $paymentMethod, $operator, $amountFcfa, $voteCount, $sentAt]);

            try {
                $gateway = AangaraaPay::initiate([
                    'phone_number' => $phone,
                    'amount' => $amountFcfa,
                    'description' => 'Vote - ' . substr(trim((string)($camp['title'] ?? 'IVote')), 0, 80),
                    'transaction_id' => $transactionRef,
                    'notify_url' => AangaraaPay::notifyUrl(),
                    'operator' => AangaraaPay::gatewayOperator($paymentMethod),
                ]);
            } catch (AangaraaPayException $error) {
                error_log('[IVote] AangaraaPay init fail : ' . $error->getMessage());
                $this->db->prepare("UPDATE Payment SET status = 'failed', gateway_raw = ? WHERE payment_id = ?")
                    ->execute([$error->getMessage(), (int)$this->db->lastInsertId()]);
                http_response_code(502);
                return ['success' => false, 'message' => 'Service de paiement indisponible. Réessayez dans un instant.'];
            }

            $gatewayStatus = AangaraaPay::resolveStatus($gateway);
            $payToken = trim((string)($gateway['payToken'] ?? $gateway['paytoken'] ?? $gateway['pay_token'] ?? ''));
            $storedStatus = 'failed';
            if (AangaraaPay::isSuccess($gateway)) {
                $storedStatus = 'successful';
            } elseif ($gatewayStatus === AangaraaPay::STATUS_PENDING) {
                $storedStatus = 'pending';
            }

            $this->db->prepare("UPDATE Payment SET pay_token = ?, status = ?, gateway_raw = ?, sent_at = NOW() WHERE transaction_ref = ? AND payment_id = ?")
                ->execute([$payToken, $storedStatus, json_encode($gateway, JSON_UNESCAPED_UNICODE), $transactionRef, (int)$this->db->lastInsertId()]);

            if ($storedStatus === 'successful') {
                $this->applySuccessfulPayment($transactionRef);

                http_response_code(201);
                return [
                    'success' => true,
                    'message' => 'Vote confirmé et enregistré avec succès.',
                    'transaction_ref' => $transactionRef,
                    'votes_added' => $voteCount,
                    'amount_fcfa' => $amountFcfa,
                ];
            }

            if ($storedStatus === 'failed') {
                error_log('[IVote] AangaraaPay init refusée : ' . json_encode($gateway, JSON_UNESCAPED_UNICODE));
                http_response_code(502);
                return ['success' => false, 'message' => 'Le paiement n\'a pas pu être initié par AangaraaPay. Vérifiez votre numéro et réessayez.'];
            }

            http_response_code(201);
            return [
                'success' => true,
                'pending' => true,
                'message' => 'Confirmez le paiement sur votre téléphone. Le vote sera enregistré automatiquement.',
                'transaction_ref' => $transactionRef,
                'payToken' => $payToken,
                'status' => 'PENDING',
                'votes_added' => 0,
                'amount_fcfa' => $amountFcfa,
            ];
        }

        // ---------------------------------------------------------------------
        // Parcours simulé (AANGARAA_ENABLED ≠ 1) : vote enregistré immédiatement.
        // ---------------------------------------------------------------------
        $stmt = $this->db->prepare("
            INSERT INTO Vote (transaction_ref, candidate_id, campaign_id, payment_method, vote_count, amount_fcfa, payment_status, platform_fee_rate, platform_fee_amount_fcfa, organizer_net_amount_fcfa)
            VALUES (?, ?, ?, ?, ?, ?, 'confirmed', ?, ?, ?)
        ");
        $stmt->execute([$transactionRef, $candidateId, $campaignId, $paymentMethod, $voteCount, $amountFcfa, $feeRate, $feeAmount, $organizerNet]);

        http_response_code(201);
        return [
            'success' => true,
            'message' => 'Vote enregistré avec succès.',
            'transaction_ref' => $transactionRef,
            'votes_added' => $voteCount,
            'amount_fcfa' => $amountFcfa,
        ];
    }

    /**
     * Enregistre durablement les votes d'un paiement réussi (idempotent).
     * La contrainte UNIQUE sur Vote.transaction_ref garantit qu'on ne
     * double jamais un vote, même en cas de course webhook/sondage.
     *
     * @return array ['recorded' => bool, 'votes_added' => int]
     */
    public function applySuccessfulPayment(string $transactionRef): array
    {
        $stmtPay = $this->db->prepare("SELECT * FROM Payment WHERE transaction_ref = ?");
        $stmtPay->execute([$transactionRef]);
        $payment = $stmtPay->fetch(PDO::FETCH_ASSOC);
        if (!$payment) {
            return ['recorded' => false, 'votes_added' => 0];
        }

        $this->db->beginTransaction();
        try {
            $stmtExisting = $this->db->prepare("SELECT 1 FROM Vote WHERE transaction_ref = ?");
            $stmtExisting->execute([$transactionRef]);
            if ($stmtExisting->fetch()) {
                $this->db->prepare("UPDATE Payment SET status = 'successful', checked_at = NOW() WHERE transaction_ref = ? AND status <> 'successful'")
                    ->execute([$transactionRef]);
                $this->db->commit();
                return ['recorded' => false, 'votes_added' => 0];
            }

            $feeRate = $this->getPlatformFeeRate();
            $feeAmount = (int)round((int)$payment['amount_fcfa'] * $feeRate / 100);
            $organizerNet = (int)$payment['amount_fcfa'] - $feeAmount;

            $stmtInsert = $this->db->prepare("
                INSERT INTO Vote (transaction_ref, candidate_id, campaign_id, payment_method, vote_count, amount_fcfa, payment_status, platform_fee_rate, platform_fee_amount_fcfa, organizer_net_amount_fcfa)
                VALUES (?, ?, ?, ?, ?, ?, 'confirmed', ?, ?, ?)
            ");
            $stmtInsert->execute([
                $payment['transaction_ref'],
                (int)$payment['candidate_id'],
                (int)$payment['campaign_id'],
                $payment['payment_method'],
                (int)$payment['vote_count'],
                (int)$payment['amount_fcfa'],
                $feeRate,
                $feeAmount,
                $organizerNet,
            ]);

            $this->db->prepare("UPDATE Payment SET status = 'successful', checked_at = NOW() WHERE transaction_ref = ?")
                ->execute([$transactionRef]);
            $this->db->commit();

            $this->writeAudit(
                null,
                'vote_paid',
                'payment',
                (int)$payment['payment_id'],
                null,
                $payment,
                ['recorded' => true, 'votes_added' => (int)$payment['vote_count'], 'amount_fcfa' => (int)$payment['amount_fcfa']]
            );

            return ['recorded' => true, 'votes_added' => (int)$payment['vote_count']];
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    /**
     * GET /api/votes/status/{ref}
     * Statut public d'un paiement + enregistrement automatique des votes dès
     * que la passerelle confirme. Utilisé par le mobile pour le sondage.
     */
    public function paymentStatus(string|int $ref): array
    {
        $transactionRef = (string)$ref;

        $stmtPay = $this->db->prepare("SELECT * FROM Payment WHERE transaction_ref = ?");
        $stmtPay->execute([$transactionRef]);
        $payment = $stmtPay->fetch(PDO::FETCH_ASSOC);
        if (!$payment) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Référence de paiement introuvable.'];
        }

        $status = strtoupper($payment['status']);
        $recorded = false;
        $votesAdded = 0;

        if ($status === 'PENDING' && !empty($payment['pay_token'])) {
            try {
                $gateway = AangaraaPay::checkStatus((string)$payment['pay_token']);
                $gatewayStatus = AangaraaPay::resolveStatus($gateway);
                if (AangaraaPay::isSuccess($gateway)) {
                    $result = $this->applySuccessfulPayment($transactionRef);
                    $recorded = $result['recorded'];
                    $votesAdded = $result['votes_added'];
                    $status = 'SUCCESSFUL';
                } elseif (in_array($gatewayStatus, [AangaraaPay::STATUS_FAILED, AangaraaPay::STATUS_EXPIRED, AangaraaPay::STATUS_CANCELLED], true)) {
                    $status = $gatewayStatus;
                    $this->db->prepare("UPDATE Payment SET status = ?, checked_at = NOW(), gateway_raw = ? WHERE payment_id = ?")
                        ->execute([strtolower($gatewayStatus), json_encode($gateway, JSON_UNESCAPED_UNICODE), (int)$payment['payment_id']]);
                }
            } catch (AangaraaPayException $error) {
                error_log('[IVote] AangaraaPay status fail : ' . $error->getMessage());
                http_response_code(502);
                return ['success' => false, 'message' => 'Service de paiement indisponible. Réessayez dans un instant.'];
            }
        }

        return [
            'success' => true,
            'status' => $status,
            'recorded' => $recorded,
            'transaction_ref' => $transactionRef,
            'votes_added' => $votesAdded,
            'candidate_id' => (int)$payment['candidate_id'],
            'campaign_id' => (int)$payment['campaign_id'],
            'amount_fcfa' => (int)$payment['amount_fcfa'],
            'message' => $status === 'SUCCESSFUL' ? 'Paiement confirmé. Vote enregistré.' : ($status === 'PENDING' ? 'Paiement en attente de confirmation.' : 'Paiement non confirmé.'),
        ];
    }
}