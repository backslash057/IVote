-- 003_aangaraa_payments.sql
-- Suivi des paiements Mobile Money initiés via AangaraaPay.
-- Idempotent : les votes ne sont enregistrés qu'une fois le paiement
-- confirmé (statut SUCCESSFUL), que la confirmation vienne du webhook
-- ou d'un sondage GET /api/votes/status/{ref}.

CREATE TABLE IF NOT EXISTS Payment (
  payment_id INT PRIMARY KEY AUTO_INCREMENT,
  transaction_ref VARCHAR(100) UNIQUE NOT NULL,
  campaign_id INT NOT NULL,
  candidate_id INT NOT NULL,
  phone_number VARCHAR(20) NOT NULL,
  payment_method ENUM('mtn_momo', 'orange_money') NOT NULL,
  operator VARCHAR(20) NOT NULL DEFAULT '',
  amount_fcfa INT NOT NULL,
  vote_count INT NOT NULL,
  pay_token VARCHAR(255) NULL,
  status ENUM('pending', 'successful', 'failed', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
  gateway_raw TEXT NULL,
  sent_at TIMESTAMP NULL,
  checked_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (campaign_id) REFERENCES Campaign(campaign_id) ON DELETE CASCADE,
  FOREIGN KEY (candidate_id) REFERENCES Candidate(candidate_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_payment_transaction_ref ON Payment (transaction_ref);
CREATE INDEX idx_payment_campaign ON Payment (campaign_id, candidate_id);
CREATE INDEX idx_payment_status ON Payment (status, created_at);