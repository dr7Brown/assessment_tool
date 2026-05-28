-- ============================================================
-- AceICT — Migration v10
-- Rename question type 'blank' → 'fill-in'
--
-- Problem: 'blank' was rejected by the questions.type ENUM,
-- causing the field to be stored as '' (empty string).
-- 'fill-in' is a clean, unambiguous identifier.
-- ============================================================

USE royayfxh_aceict;
SET NAMES utf8mb4;

-- 1. Widen the type column to VARCHAR so any string is valid
ALTER TABLE questions
    MODIFY COLUMN type VARCHAR(20) NOT NULL DEFAULT 'mcq';

-- 2. Fix existing questions stored as empty string (ENUM fallback)
--    and any that were correctly stored as 'blank'
UPDATE questions SET type = 'fill-in'
WHERE type = '' OR type IS NULL OR type = 'blank';

-- Verify:
--   SELECT type, COUNT(*) FROM questions GROUP BY type;
