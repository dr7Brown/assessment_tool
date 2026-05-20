-- ============================================================
-- AceICT — Migration v4
-- Subject-Based Teaching and Access Model
--
-- What this adds:
--   1. teacher_subjects  — which subjects each teacher is assigned to
--   2. questions.subject_id  — which subject a question belongs to
--   3. questions.updated_by  — last editor (audit trail)
--   4. tests.subject_id      — which subject a test covers
--   5. tests.updated_by      — last editor (audit trail)
--
-- SAFE TO RUN ON EXISTING DB — uses IF NOT EXISTS / IF NOT EXISTS column checks.
-- Run migration_v3.sql AND seed_subjects.sql FIRST.
-- Replace `royayfxh_aceict` with your production database name if different.
-- ============================================================

USE royayfxh_aceict;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


-- ============================================================
-- 1. TEACHER ↔ SUBJECT (many-to-many)
--    Records which subjects each teacher is assigned to teach.
--    This is independent of classes — a teacher may teach a
--    subject across multiple classes and year groups.
-- ============================================================
CREATE TABLE IF NOT EXISTS teacher_subjects (
    teacher_id  INT UNSIGNED NOT NULL,
    subject_id  INT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT UNSIGNED NULL COMMENT 'Admin user_id who made the assignment',
    PRIMARY KEY (teacher_id, subject_id),
    INDEX idx_ts_teacher (teacher_id),
    INDEX idx_ts_subject (subject_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- 2. questions.subject_id
--    Links each question to its subject.
--    NULL = legacy question (created before this migration).
-- ============================================================
ALTER TABLE questions
    ADD COLUMN IF NOT EXISTS subject_id INT UNSIGNED NULL
    COMMENT 'FK → subjects.id — subject this question covers';


-- ============================================================
-- 3. questions.updated_by
--    Tracks who last edited a question (audit trail).
-- ============================================================
ALTER TABLE questions
    ADD COLUMN IF NOT EXISTS updated_by INT UNSIGNED NULL
    COMMENT 'FK → users.id — last user to modify this question';


-- ============================================================
-- 4. tests.subject_id
--    Links each test to its subject.
--    NULL = legacy test (created before this migration).
-- ============================================================
ALTER TABLE tests
    ADD COLUMN IF NOT EXISTS subject_id INT UNSIGNED NULL
    COMMENT 'FK → subjects.id — subject this test assesses';


-- ============================================================
-- 5. tests.updated_by
--    Tracks who last edited a test (audit trail).
-- ============================================================
ALTER TABLE tests
    ADD COLUMN IF NOT EXISTS updated_by INT UNSIGNED NULL
    COMMENT 'FK → users.id — last user to modify this test';


SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- 6. MOVE CORE SUBJECTS TO 'General' CATEGORY
--
--    English Language, Core Mathematics, Integrated Science, and
--    Social Studies are compulsory for ALL programmes — they belong
--    in their own 'General' category, not scattered across Sciences,
--    Languages, and Humanities.
-- ============================================================

-- Move the three that were already seeded to the right category
UPDATE subjects
SET    category   = 'General',
       sort_order = CASE name
                      WHEN 'English Language' THEN 10
                      WHEN 'Core Mathematics' THEN 20
                      WHEN 'Social Studies'   THEN 40
                    END
WHERE  school_id IS NULL
  AND  name IN ('English Language', 'Core Mathematics', 'Social Studies');

-- Add Integrated Science if it does not already exist
INSERT IGNORE INTO subjects (school_id, name, short_name, category, sort_order)
VALUES (NULL, 'Integrated Science', 'Int. Sci.', 'General', 30);


-- ============================================================
-- 7. BACK-FILL — assign all existing questions to ICT
--
--    Every question currently in the bank was created for the
--    ICT curriculum, so we tag them all with the ICT subject.
--    Questions added after this migration should have their
--    subject_id set explicitly when created.
-- ============================================================
SET @ict_id = (
    SELECT id FROM subjects
    WHERE name = 'Information and Communication Technology (ICT)'
    LIMIT 1
);

UPDATE questions
SET subject_id = @ict_id
WHERE subject_id IS NULL
  AND @ict_id IS NOT NULL;


-- ============================================================
-- Verify:
--   DESCRIBE questions;  -- should show subject_id, updated_by
--   DESCRIBE tests;      -- should show subject_id, updated_by
--   SHOW TABLES LIKE 'teacher_subjects';
--   SELECT subject_id, COUNT(*) FROM questions GROUP BY subject_id;
--   -- All rows should show the ICT subject_id (not NULL).
-- ============================================================
