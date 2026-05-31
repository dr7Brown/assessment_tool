-- ============================================================
-- AceICT — Migration v11
-- Grade config table
--
-- Creates the grade_config table used for GES A1-F9 grade
-- boundaries (admin-configurable per school).  The API now
-- also auto-creates this table on first use via
-- ensureGradeConfigSchema(), so this migration is optional
-- but recommended for environments where the API user may
-- lack CREATE TABLE privileges.
-- ============================================================

USE royayfxh_aceict;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS grade_config (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id  INT UNSIGNED NOT NULL,
    grade      VARCHAR(5)   NOT NULL,
    label      VARCHAR(30)  NOT NULL,
    min_pct    DECIMAL(5,2) NOT NULL,
    max_pct    DECIMAL(5,2) NOT NULL,
    sort_order TINYINT      DEFAULT 0,
    UNIQUE KEY uniq_school_grade (school_id, grade)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Verify:
--   SELECT * FROM grade_config LIMIT 10;
