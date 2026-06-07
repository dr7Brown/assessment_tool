-- ============================================================
-- AceICT — Migration v17
-- Teacher role/designation column:
--   Allows admin to assign a school role to each teacher
--   e.g. "Subject Teacher", "Class Teacher", "Head of Department"
-- ============================================================

USE royayfxh_aceict;
SET NAMES utf8mb4;

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS teacher_role VARCHAR(60) NULL
  COMMENT 'School role/designation for teacher accounts only'
  AFTER department_id;

-- Verify:
--   SHOW COLUMNS FROM users LIKE 'teacher_role';
