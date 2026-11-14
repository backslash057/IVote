-- Identifiants publics stables pour les campagnes.
DROP PROCEDURE IF EXISTS ivote_ensure_campaign_code_column;
DELIMITER //
CREATE PROCEDURE ivote_ensure_campaign_code_column()
BEGIN
  IF NOT EXISTS (
    SELECT 1
      FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'Campaign'
        AND column_name = 'campaign_code'
  ) THEN
    ALTER TABLE Campaign
      ADD COLUMN campaign_code CHAR(7) NULL AFTER campaign_id;
  END IF;
END//
DELIMITER ;
CALL ivote_ensure_campaign_code_column();
DROP PROCEDURE ivote_ensure_campaign_code_column;

DROP PROCEDURE IF EXISTS ivote_backfill_campaign_codes;
DELIMITER //
CREATE PROCEDURE ivote_backfill_campaign_codes()
BEGIN
  DECLARE finished BOOLEAN DEFAULT FALSE;
  DECLARE current_campaign_id INT;
  DECLARE generated_code CHAR(7);
  DECLARE code_exists INT DEFAULT 0;
  DECLARE campaign_cursor CURSOR FOR
    SELECT campaign_id FROM Campaign WHERE campaign_code IS NULL ORDER BY campaign_id;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET finished = TRUE;

  OPEN campaign_cursor;

  campaign_loop: LOOP
    FETCH campaign_cursor INTO current_campaign_id;
    IF finished THEN
      LEAVE campaign_loop;
    END IF;

    code_loop: LOOP
      SET generated_code = CONCAT(
        SUBSTRING('BCDFGHJKLMNPQRSTVWXYZ', FLOOR(1 + RAND() * 21), 1),
        SUBSTRING('BCDFGHJKLMNPQRSTVWXYZ', FLOOR(1 + RAND() * 21), 1),
        SUBSTRING('BCDFGHJKLMNPQRSTVWXYZ', FLOOR(1 + RAND() * 21), 1),
        SUBSTRING('BCDFGHJKLMNPQRSTVWXYZ', FLOOR(1 + RAND() * 21), 1),
        SUBSTRING('BCDFGHJKLMNPQRSTVWXYZ', FLOOR(1 + RAND() * 21), 1),
        SUBSTRING('BCDFGHJKLMNPQRSTVWXYZ', FLOOR(1 + RAND() * 21), 1),
        SUBSTRING('BCDFGHJKLMNPQRSTVWXYZ', FLOOR(1 + RAND() * 21), 1)
      );
      SELECT COUNT(*) INTO code_exists
        FROM Campaign
        WHERE campaign_code = generated_code;
      IF code_exists = 0 THEN
        LEAVE code_loop;
      END IF;
    END LOOP;

    UPDATE Campaign
      SET campaign_code = generated_code
      WHERE campaign_id = current_campaign_id;
  END LOOP;

  CLOSE campaign_cursor;
END//
DELIMITER ;
CALL ivote_backfill_campaign_codes();
DROP PROCEDURE ivote_backfill_campaign_codes;

ALTER TABLE Campaign
  MODIFY COLUMN campaign_code CHAR(7) NOT NULL;

DROP PROCEDURE IF EXISTS ivote_ensure_campaign_code_index;
DELIMITER //
CREATE PROCEDURE ivote_ensure_campaign_code_index()
BEGIN
  IF NOT EXISTS (
    SELECT 1
      FROM information_schema.statistics
      WHERE table_schema = DATABASE()
        AND table_name = 'Campaign'
        AND index_name = 'uq_campaign_code'
  ) THEN
    ALTER TABLE Campaign
      ADD UNIQUE KEY uq_campaign_code (campaign_code);
  END IF;
END//
DELIMITER ;
CALL ivote_ensure_campaign_code_index();
DROP PROCEDURE ivote_ensure_campaign_code_index;
