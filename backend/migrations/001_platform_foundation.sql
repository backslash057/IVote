-- IVote platform foundation migration.
-- Run once against an existing database created from an older schema.sql.

ALTER TABLE Users
  ADD COLUMN role ENUM('organizer', 'super_admin') NOT NULL DEFAULT 'organizer',
  ADD COLUMN status ENUM('active', 'suspended', 'disabled') NOT NULL DEFAULT 'active',
  ADD COLUMN suspended_at DATETIME NULL,
  ADD COLUMN suspended_by INT NULL,
  ADD COLUMN suspension_reason VARCHAR(500) NULL,
  ADD COLUMN session_version INT NOT NULL DEFAULT 0;

ALTER TABLE Campaign
  ADD COLUMN status ENUM('draft', 'pending_review', 'published', 'suspended', 'closed', 'archived') NOT NULL DEFAULT 'draft',
  ADD COLUMN suspended_at DATETIME NULL,
  ADD COLUMN suspended_by INT NULL,
  ADD COLUMN suspension_reason VARCHAR(500) NULL;

UPDATE Campaign SET status = CASE
  WHEN is_draft = 1 THEN 'draft'
  WHEN date_cloture IS NOT NULL AND date_cloture < NOW() THEN 'closed'
  ELSE 'published'
END;

ALTER TABLE Category ADD UNIQUE KEY uq_category_name_per_campaign (campaign_id, name);

ALTER TABLE Vote
  ADD COLUMN payment_status ENUM('pending', 'confirmed', 'failed', 'refunded', 'cancelled') NOT NULL DEFAULT 'confirmed',
  ADD COLUMN platform_fee_rate DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  ADD COLUMN platform_fee_amount_fcfa INT NOT NULL DEFAULT 0,
  ADD COLUMN organizer_net_amount_fcfa INT NOT NULL DEFAULT 0;

UPDATE Vote
SET platform_fee_amount_fcfa = ROUND(amount_fcfa * platform_fee_rate / 100),
    organizer_net_amount_fcfa = amount_fcfa - ROUND(amount_fcfa * platform_fee_rate / 100);

ALTER TABLE Payout
  MODIFY status ENUM('pending', 'under_review', 'approved', 'paid', 'completed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
  ADD COLUMN rejection_reason VARCHAR(500) NULL,
  ADD COLUMN requested_by INT NULL,
  ADD COLUMN reviewed_by INT NULL,
  ADD COLUMN reviewed_at DATETIME NULL,
  ADD COLUMN payment_reference VARCHAR(150) NULL,
  ADD COLUMN processed_at DATETIME NULL;

UPDATE Payout SET status = 'paid' WHERE status = 'completed';

ALTER TABLE Payout
  MODIFY status ENUM('pending', 'under_review', 'approved', 'paid', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending';

CREATE TABLE IF NOT EXISTS PlatformSetting (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL,
  updated_by INT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS AuditLog (
  audit_id BIGINT PRIMARY KEY AUTO_INCREMENT,
  actor_id INT NULL,
  action VARCHAR(100) NOT NULL,
  resource_type VARCHAR(100) NOT NULL,
  resource_id INT NULL,
  reason VARCHAR(500) NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Notification (
  notification_id BIGINT PRIMARY KEY AUTO_INCREMENT,
  recipient_id INT NOT NULL,
  sender_id INT NULL,
  type VARCHAR(50) NOT NULL,
  title VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  resource_type VARCHAR(100) NULL,
  resource_id INT NULL,
  read_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO Users (email, name, password, role)
SELECT 'admin@ivote.cm', 'IVote Super Admin', '$2y$12$/4hF1uNr4OZJu57Y0HxMb.AxUzbAHwbTRuG8yoRZkdt7bF8FQI50u', 'super_admin'
WHERE NOT EXISTS (SELECT 1 FROM Users WHERE role = 'super_admin');

INSERT INTO PlatformSetting (setting_key, setting_value)
VALUES ('platform_fee_rate', '10.00')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);