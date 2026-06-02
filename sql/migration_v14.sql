-- ============================================================
-- AceICT — Migration v14
-- Subject-specific strands and sub-strands
--
-- Previously strands were hardcoded as Ghana GES ICT curriculum
-- (S1/SS1, S1/SS2, S2/SS1 etc.) shared across all subjects.
-- This table allows each subject to have its own configured
-- strands and sub-strands (defined by admin per school).
-- ============================================================

USE royayfxh_aceict;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS subject_strands (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id        INT UNSIGNED NOT NULL,
  subject_id       INT UNSIGNED NOT NULL,
  strand_code      VARCHAR(50)  NOT NULL,
  strand_label     VARCHAR(200) NOT NULL,
  sub_strand_code  VARCHAR(50)  NOT NULL,
  sub_strand_label VARCHAR(200) NOT NULL,
  sort_order       TINYINT UNSIGNED DEFAULT 0,
  KEY idx_ss (school_id, subject_id, sort_order),
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify:
--   SHOW TABLES LIKE 'subject_strands';
--   DESCRIBE subject_strands;
