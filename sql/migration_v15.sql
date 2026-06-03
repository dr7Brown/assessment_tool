-- ============================================================
-- AceICT — Migration v15
-- Normalized curriculum hierarchy:
--   Subject → Strands → Sub-strands → Topics → Questions
--
-- Replaces the flat subject_strands table from migration_v14
-- with properly normalized separate tables.
-- ============================================================

USE royayfxh_aceict;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── 1. Drop old flat table (migration_v14 schema) ────────────
DROP TABLE IF EXISTS subject_strands;

-- ── 2. Strands (top-level topic groupings per subject) ────────
CREATE TABLE subject_strands (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id    INT UNSIGNED NOT NULL,
  subject_id   INT UNSIGNED NOT NULL,
  strand_code  VARCHAR(50)  NOT NULL,
  strand_label VARCHAR(255) NOT NULL,
  description  TEXT         NULL,
  sort_order   SMALLINT UNSIGNED DEFAULT 0,
  is_active    TINYINT(1)   DEFAULT 1,
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  KEY idx_strands (school_id, subject_id, sort_order),
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Sub-strands (groupings within a strand) ────────────────
CREATE TABLE IF NOT EXISTS subject_sub_strands (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  strand_id        INT UNSIGNED NOT NULL,
  sub_strand_code  VARCHAR(50)  NULL,
  sub_strand_label VARCHAR(255) NOT NULL,
  description      TEXT         NULL,
  sort_order       SMALLINT UNSIGNED DEFAULT 0,
  is_active        TINYINT(1)   DEFAULT 1,
  created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sub_strands (strand_id, sort_order),
  FOREIGN KEY (strand_id) REFERENCES subject_strands(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Topics (specific content within a sub-strand) ─────────
CREATE TABLE IF NOT EXISTS subject_topics (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sub_strand_id INT UNSIGNED NOT NULL,
  topic_code    VARCHAR(50)  NULL,
  topic_label   VARCHAR(255) NOT NULL,
  description   TEXT         NULL,
  sort_order    SMALLINT UNSIGNED DEFAULT 0,
  is_active     TINYINT(1)   DEFAULT 1,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  KEY idx_topics (sub_strand_id, sort_order),
  FOREIGN KEY (sub_strand_id) REFERENCES subject_sub_strands(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. Add FK columns to questions table ─────────────────────
-- Keep sub_strand (text) for legacy display; add FK IDs for structure
ALTER TABLE questions
  ADD COLUMN IF NOT EXISTS strand_id     INT UNSIGNED NULL AFTER topic,
  ADD COLUMN IF NOT EXISTS sub_strand_id INT UNSIGNED NULL AFTER strand_id,
  ADD COLUMN IF NOT EXISTS topic_id      INT UNSIGNED NULL AFTER sub_strand_id;

-- Optional indexes on questions for filtering by curriculum
CREATE INDEX IF NOT EXISTS idx_q_strand    ON questions (strand_id);
CREATE INDEX IF NOT EXISTS idx_q_substrand ON questions (sub_strand_id);
CREATE INDEX IF NOT EXISTS idx_q_topic     ON questions (topic_id);

SET FOREIGN_KEY_CHECKS = 1;

-- ── Verify ───────────────────────────────────────────────────
-- SHOW TABLES LIKE 'subject_%';
-- DESCRIBE subject_strands;
-- DESCRIBE subject_sub_strands;
-- DESCRIBE subject_topics;
-- SHOW COLUMNS FROM questions LIKE 'strand_id';
