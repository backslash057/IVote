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

-- MySQL does not support `CREATE INDEX IF NOT EXISTS` consistently across
-- supported server versions. Add the indexes only when they are absent so
-- this migration remains safe after schema.sql or a partial previous run.
SET @database_name = DATABASE();

SET @has_index = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = @database_name
    AND table_name = 'Payment'
    AND index_name = 'idx_payment_transaction_ref'
);
SET @sql = IF(
  @has_index = 0,
  'CREATE INDEX idx_payment_transaction_ref ON Payment (transaction_ref)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_index = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = @database_name
    AND table_name = 'Payment'
    AND index_name = 'idx_payment_campaign'
);
SET @sql = IF(
  @has_index = 0,
  'CREATE INDEX idx_payment_campaign ON Payment (campaign_id, candidate_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_index = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = @database_name
    AND table_name = 'Payment'
    AND index_name = 'idx_payment_status'
);
SET @sql = IF(
  @has_index = 0,
  'CREATE INDEX idx_payment_status ON Payment (status, created_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;