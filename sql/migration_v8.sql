-- ============================================================
-- AceICT — Migration v8
-- Academic Year & Semester Management
--
-- What this adds:
--   1. academic_periods  — per-school, per-year-group active period
--   2. tests.academic_year  — which year a test belongs to
--   3. attempts.academic_year / attempts.attempt_semester
--   4. Backfill existing records from created_at / submitted_at
-- ============================================================

USE royayfxh_aceict;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. ACADEMIC_PERIODS
--    One active row per (school, year_group).
--    History preserved with is_active=0.
-- ============================================================
CREATE TABLE IF NOT EXISTS academic_periods (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id     INT UNSIGNED NOT NULL,
    year_group    TINYINT      NOT NULL  COMMENT '1 = SHS1 / Year 1, 2 = SHS2, 3 = SHS3',
    academic_year VARCHAR(20)  NOT NULL  COMMENT 'e.g. 2024/2025',
    semester      TINYINT      NOT NULL  COMMENT '1 or 2',
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    started_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    ended_at      TIMESTAMP    NULL,
    set_by        INT UNSIGNED NOT NULL  COMMENT 'admin user_id',
    INDEX idx_school_active     (school_id, is_active),
    INDEX idx_school_year_group (school_id, year_group, is_active)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- 2. NEW COLUMNS
-- ============================================================
ALTER TABLE tests
    ADD COLUMN IF NOT EXISTS academic_year VARCHAR(20) NULL
        COMMENT 'e.g. 2024/2025 — populated on creation';

-- semester already added in migration_v7 session work; safe to re-add
ALTER TABLE tests
    ADD COLUMN IF NOT EXISTS semester TINYINT UNSIGNED NULL
        COMMENT '1 or 2';

ALTER TABLE attempts
    ADD COLUMN IF NOT EXISTS academic_year    VARCHAR(20)      NULL,
    ADD COLUMN IF NOT EXISTS attempt_semester TINYINT UNSIGNED NULL;

-- ============================================================
-- 3. BACKFILL EXISTING TESTS
--    academic_year: Sep-Aug cycle (e.g. Sep 2024 → 2024/2025)
--    semester:  Sep/Oct/Nov/Dec/Jan/Aug = 1,  Feb-Jul = 2
-- ============================================================
UPDATE tests
SET academic_year = CONCAT(
        IF(MONTH(created_at) >= 8, YEAR(created_at),     YEAR(created_at) - 1), '/',
        IF(MONTH(created_at) >= 8, YEAR(created_at) + 1, YEAR(created_at))
    )
WHERE academic_year IS NULL AND created_at IS NOT NULL;

UPDATE tests
SET semester = IF(MONTH(created_at) IN (8,9,10,11,12,1), 1, 2)
WHERE semester IS NULL AND created_at IS NOT NULL;

-- ============================================================
-- 4. BACKFILL EXISTING ATTEMPTS
-- ============================================================
UPDATE attempts
SET academic_year = CONCAT(
        IF(MONTH(submitted_at) >= 8, YEAR(submitted_at),     YEAR(submitted_at) - 1), '/',
        IF(MONTH(submitted_at) >= 8, YEAR(submitted_at) + 1, YEAR(submitted_at))
    ),
    attempt_semester = IF(MONTH(submitted_at) IN (8,9,10,11,12,1), 1, 2)
WHERE academic_year IS NULL AND submitted_at IS NOT NULL;

-- Fallback: attempts with no submitted_at (in_progress) — use started_at
UPDATE attempts
SET academic_year = CONCAT(
        IF(MONTH(started_at) >= 8, YEAR(started_at),     YEAR(started_at) - 1), '/',
        IF(MONTH(started_at) >= 8, YEAR(started_at) + 1, YEAR(started_at))
    ),
    attempt_semester = IF(MONTH(started_at) IN (8,9,10,11,12,1), 1, 2)
WHERE academic_year IS NULL AND started_at IS NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Verify:
--   SELECT id, year_group, academic_year, semester, is_active FROM academic_periods LIMIT 10;
--   SELECT id, title, academic_year, semester FROM tests LIMIT 10;
--   SELECT id, academic_year, attempt_semester FROM attempts LIMIT 10;
-- ============================================================
