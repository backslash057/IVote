CREATE TABLE IF NOT EXISTS Users (
  user_id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) UNIQUE NOT NULL,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  password VARCHAR(256) NOT NULL,
  role ENUM('organizer', 'super_admin') NOT NULL DEFAULT 'organizer',
  status ENUM('active', 'suspended', 'disabled') NOT NULL DEFAULT 'active',
  suspended_at DATETIME NULL,
  suspended_by INT NULL,
  suspension_reason VARCHAR(500) NULL,
  session_version INT NOT NULL DEFAULT 0,
  FOREIGN KEY (suspended_by) REFERENCES Users(user_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS Campaign (
  campaign_id INT PRIMARY KEY AUTO_INCREMENT,
  campaign_code CHAR(7) NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  image_url VARCHAR(255) DEFAULT NULL,
  price_per_vote INT NOT NULL DEFAULT 100,
  is_draft TINYINT(1) NOT NULL DEFAULT 1,
  date_cloture DATETIME NULL,
  organiser_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('draft', 'pending_review', 'published', 'suspended', 'closed', 'archived') NOT NULL DEFAULT 'draft',
  suspended_at DATETIME NULL,
  suspended_by INT NULL,
  suspension_reason VARCHAR(500) NULL,
  status_before_suspension ENUM('draft', 'pending_review', 'published', 'closed', 'archived') NULL,
  UNIQUE KEY uq_campaign_code (campaign_code),
  FOREIGN KEY (organiser_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Category (
  category_id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  campaign_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (campaign_id) REFERENCES Campaign(campaign_id) ON DELETE CASCADE
  ,UNIQUE KEY uq_category_name_per_campaign (campaign_id, name)
);

CREATE TABLE IF NOT EXISTS Candidate (
  candidate_id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL, 
  candidate_number INT NOT NULL CHECK (candidate_number > 0),
  age INT CHECK (age >= 0),
  theme VARCHAR(100) NULL,
  description VARCHAR(255) NULL,
  bio TEXT NULL,
  image_url VARCHAR(255) NULL,
  category_id INT NULL,
  campaign_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES Category(category_id) ON DELETE SET NULL,
  FOREIGN KEY (campaign_id) REFERENCES Campaign(campaign_id) ON DELETE CASCADE,
  UNIQUE KEY uq_candidate_number_per_campaign (campaign_id, candidate_number)
);

CREATE TABLE IF NOT EXISTS Vote (
  vote_id INT PRIMARY KEY AUTO_INCREMENT,
  transaction_ref VARCHAR(100) UNIQUE NOT NULL,
  candidate_id INT NOT NULL,
  campaign_id INT NOT NULL,
  payment_method ENUM('mtn_momo', 'orange_money') NOT NULL,
  vote_count INT NOT NULL DEFAULT 1,
  amount_fcfa INT NOT NULL,
  payment_status ENUM('pending', 'confirmed', 'failed', 'refunded', 'cancelled') NOT NULL DEFAULT 'confirmed',
  platform_fee_rate DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  platform_fee_amount_fcfa INT NOT NULL DEFAULT 0,
  organizer_net_amount_fcfa INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (candidate_id) REFERENCES Candidate(candidate_id) ON DELETE CASCADE,
  FOREIGN KEY (campaign_id) REFERENCES Campaign(campaign_id) ON DELETE CASCADE
);

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
);

CREATE INDEX idx_payment_transaction_ref ON Payment (transaction_ref);
CREATE INDEX idx_payment_campaign ON Payment (campaign_id, candidate_id);
CREATE INDEX idx_payment_status ON Payment (status, created_at);

CREATE TABLE IF NOT EXISTS Payout (
  payout_id INT PRIMARY KEY AUTO_INCREMENT,
  campaign_id INT NOT NULL,
  amount_fcfa INT NOT NULL,
  payment_method ENUM('mtn_momo', 'orange_money') NOT NULL,
  account_holder VARCHAR(100) NOT NULL,
  wallet_number VARCHAR(50) NOT NULL,
  status ENUM('pending', 'under_review', 'approved', 'paid', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
  rejection_reason VARCHAR(500) NULL,
  requested_by INT NULL,
  reviewed_by INT NULL,
  reviewed_at DATETIME NULL,
  payment_reference VARCHAR(150) NULL,
  processed_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (requested_by) REFERENCES Users(user_id) ON DELETE SET NULL,
  FOREIGN KEY (reviewed_by) REFERENCES Users(user_id) ON DELETE SET NULL,
  FOREIGN KEY (campaign_id) REFERENCES Campaign(campaign_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS PlatformSetting (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL,
  updated_by INT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (updated_by) REFERENCES Users(user_id) ON DELETE SET NULL
);

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
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (actor_id) REFERENCES Users(user_id) ON DELETE SET NULL
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
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (recipient_id) REFERENCES Users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE SET NULL
);

-- ==============================================================================
-- SEEDS DE PRODUCTION (base propre)
--
--  * Comptes : SEUL un compte super admin est créé. Les organisateurs sont
--    créés plus tard via l'application (signup) — à ne pas semer en base.
--  * Mot de passe temporaire du super admin : TempOrga@123 (à changer après
--    la première connexion).
--  * Script relançable : toutes les instructions sont idempotentes.
--  * Pour des données de démonstration locales : `mysql ... < demo_seed.sql`.
-- ==============================================================================

-- Compte super admin (NE JAMAIS LE SUPPRIMER : porte d'entrée de l'interface admin).
INSERT INTO Users (email, name, password, role)
SELECT 'admin@ivote.cm', 'IVote Super Admin', '$2y$12$/4hF1uNr4OZJu57Y0HxMb.AxUzbAHwbTRuG8yoRZkdt7bF8FQI50u', 'super_admin'
WHERE NOT EXISTS (SELECT 1 FROM Users WHERE role = 'super_admin');

-- Paramètres globaux de la plateforme.
INSERT INTO PlatformSetting (setting_key, setting_value)
VALUES
  ('platform_fee_rate', '10.00'),
  ('admin_payout_payment_method', ''),
  ('admin_payout_account_holder', ''),
  ('admin_payout_wallet_number', '')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);