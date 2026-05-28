-- ============================================================
-- AceICT — Migration v6
-- Student Promotion & Graduation System
--
-- What this adds:
--   1. promotions           — immutable audit log of every action
--   2. student_enrollments  — full class-history per student
--   3. users.is_graduated   — graduated flag
--   4. users.graduation_year
--
-- DESIGN PRINCIPLE:
--   History is NEVER overwritten. Attempts, results, transcripts
--   remain linked to the student_id forever regardless of class.
--   class_students is updated to reflect current enrolment;
--   promotions + student_enrollments carry the full history.
-- ============================================================

USE royayfxh_aceict;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. PROMOTIONS  (immutable audit log)
-- ============================================================
CREATE TABLE IF NOT EXISTS promotions (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id     INT UNSIGNED NOT NULL,
    academic_year VARCHAR(20)  NOT NULL   COMMENT 'e.g. 2024/2025',
    student_id    INT UNSIGNED NOT NULL,
    from_class_id INT UNSIGNED NULL       COMMENT 'NULL only if student had no prior class',
    to_class_id   INT UNSIGNED NULL       COMMENT 'NULL for graduates',
    action        ENUM('promoted','repeated','transferred','graduated') NOT NULL,
    promoted_by   INT UNSIGNED NOT NULL   COMMENT 'Admin user_id',
    promoted_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes         TEXT NULL,
    INDEX idx_student   (student_id),
    INDEX idx_school    (school_id),
    INDEX idx_acad_year (school_id, academic_year)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- 2. STUDENT_ENROLLMENTS  (per-year class history)
-- ============================================================
CREATE TABLE IF NOT EXISTS student_enrollments (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id    INT UNSIGNED NOT NULL,
    school_id     INT UNSIGNED NOT NULL,
    class_id      INT UNSIGNED NOT NULL,
    class_name    VARCHAR(200) NOT NULL,
    academic_year VARCHAR(20)  NOT NULL,
    status        ENUM('active','promoted','repeated','transferred','graduated','withdrawn')
                  DEFAULT 'active',
    enrolled_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    ended_at      TIMESTAMP    NULL,
    notes         TEXT         NULL,
    INDEX idx_student (student_id),
    INDEX idx_class   (class_id),
    UNIQUE KEY uniq_enrol (student_id, class_id, academic_year)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- 3. NEW COLUMNS ON users
-- ============================================================
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS is_graduated   TINYINT(1)   NOT NULL DEFAULT 0
    COMMENT '1 once student has graduated',
    ADD COLUMN IF NOT EXISTS graduation_year VARCHAR(10)  NULL
    COMMENT 'e.g. 2026/2027 — the year they completed Year 3';


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Verify:
--   SHOW TABLES LIKE 'promotions';
--   SHOW TABLES LIKE 'student_enrollments';
--   DESCRIBE users;   -- should show is_graduated, graduation_year
-- ============================================================
