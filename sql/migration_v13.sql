-- ============================================================
-- AceICT — Migration v13
-- Offline-first support
--
-- 1. Add updated_at to tables used by the /sync endpoint
--    (meetings already has it from its CREATE TABLE)
-- 2. Add composite indexes on (school_id, subject_id) for the
--    questions and tests tables — recommended from the architecture
--    discussion; makes subject-filtered queries instant at scale.
-- ============================================================

USE royayfxh_aceict;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── updated_at for sync delta queries ────────────────────────
ALTER TABLE tests
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE questions
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE attempts
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Backfill updated_at from created_at for existing rows
UPDATE tests      SET updated_at = created_at WHERE updated_at IS NULL;
UPDATE questions  SET updated_at = created_at WHERE updated_at IS NULL;
UPDATE attempts   SET updated_at = COALESCE(submitted_at, started_at)             WHERE updated_at IS NULL;

-- ── Performance indexes (from architecture review) ────────────
-- Questions: most common filter — school + subject
CREATE INDEX IF NOT EXISTS idx_q_school_subject
    ON questions (school_id, subject_id);

-- Questions: question bank filter — school + type + difficulty + active
CREATE INDEX IF NOT EXISTS idx_q_bank
    ON questions (school_id, type, difficulty, is_active);

-- Questions: author lookup
CREATE INDEX IF NOT EXISTS idx_q_author
    ON questions (author_id);

-- Tests: school + subject (for teacher/admin filters)
CREATE INDEX IF NOT EXISTS idx_t_school_subject
    ON tests (school_id, subject_id);

-- Attempts: student lookups (already likely indexed, safe to add)
CREATE INDEX IF NOT EXISTS idx_a_student_status
    ON attempts (student_id, status);

SET FOREIGN_KEY_CHECKS = 1;

-- Verify:
--   SHOW COLUMNS FROM tests     LIKE 'updated_at';
--   SHOW COLUMNS FROM questions LIKE 'updated_at';
--   SHOW INDEX FROM questions   WHERE Key_name LIKE 'idx_q%';
