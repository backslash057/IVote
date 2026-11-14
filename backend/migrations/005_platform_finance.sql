-- Finance de la plateforme : retrait direct IVote et compte Mobile Money admin.
CREATE TABLE IF NOT EXISTS PlatformPayout (
  platform_payout_id INT PRIMARY KEY AUTO_INCREMENT,
  amount_fcfa INT NOT NULL,
  payment_method ENUM('mtn_momo', 'orange_money') NOT NULL,
  account_holder VARCHAR(100) NOT NULL,
  wallet_number VARCHAR(50) NOT NULL,
  status ENUM('processing', 'successful', 'failed', 'cancelled') NOT NULL DEFAULT 'processing',
  payment_reference VARCHAR(255) NULL,
  gateway_raw TEXT NULL,
  requested_by INT NOT NULL,
  processed_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (requested_by) REFERENCES Users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO PlatformSetting (setting_key, setting_value)
VALUES
  ('platform_fee_rate', '10.00'),
  ('admin_payout_payment_method', ''),
  ('admin_payout_account_holder', ''),
  ('admin_payout_wallet_number', '')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);
