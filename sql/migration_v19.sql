-- ============================================================
-- AceICT — Migration v19
-- Rename first_name → given_name on the users table.
-- Reflects Ghanaian naming convention (given name vs first name).
-- ============================================================

USE royayfxh_aceict;
SET NAMES utf8mb4;

-- MariaDB 10.4 / MySQL 8.0 compatible syntax
ALTER TABLE users
  CHANGE COLUMN first_name given_name VARCHAR(100) NOT NULL DEFAULT '';

-- Verify:
--   SHOW COLUMNS FROM users LIKE 'given_name';
