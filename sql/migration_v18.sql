-- ============================================================
-- AceICT — Migration v18
-- Custom abbreviations table:
--   Stores school-specific abbreviations shown in the
--   Abbreviations modal (all user roles).
--   Built-in/default abbreviations live in frontend JS;
--   this table holds admin-managed additions only.
-- ============================================================

USE royayfxh_aceict;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS abbreviations (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id   INT UNSIGNED NOT NULL,
  abbr        VARCHAR(30)  NOT NULL,
  meaning     VARCHAR(400) NOT NULL,
  category    VARCHAR(60)  NOT NULL DEFAULT 'Custom',
  sort_order  SMALLINT UNSIGNED DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_abbr_school (school_id, sort_order, abbr)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify:
--   SHOW TABLES LIKE 'abbreviations';
--   DESCRIBE abbreviations;
