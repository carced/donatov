-- Anonymous referral accounts (no email/password). Run on existing DBs:
-- mysql donatov < database/referral_anonymous_migration.sql

ALTER TABLE referral_accounts
    MODIFY email VARCHAR(255) NULL,
    MODIFY password_hash VARCHAR(255) NULL;

-- Drop unique email if present (anonymous rows use NULL email)
SET @db = DATABASE();
SET @idx = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'referral_accounts' AND INDEX_NAME = 'email' AND NON_UNIQUE = 0
);
SET @sql = IF(@idx > 0, 'ALTER TABLE referral_accounts DROP INDEX email', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
