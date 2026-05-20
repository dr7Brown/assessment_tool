-- ============================================================
-- AceICT — Class Seeding
-- 90 classes across 3 year groups
--
-- ⚠ BEFORE RUNNING:
--   1. Set @school_id to your school's ID:
--         SELECT id, name FROM schools;
--      Then replace the 1 below.
--
--   2. This script makes teacher_id nullable (classes can exist
--      without a teacher assigned). Run ALTER TABLE once only.
--
--   3. Safe to run on existing DB — uses INSERT IGNORE so
--      duplicate class names won't error.
-- ============================================================

USE royayfxh_aceict;   -- ← your production database name

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Allow classes to exist with no teacher yet assigned
ALTER TABLE classes MODIFY COLUMN teacher_id INT UNSIGNED NULL;

SET @school_id = 1;   -- ← CHANGE THIS to your school's ID


-- ============================================================
-- YEAR 1  (year_group = 1)
-- ============================================================

-- Science (5 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '1 SCIENCE1', 1),
  (@school_id, NULL, '1 SCIENCE2', 1),
  (@school_id, NULL, '1 SCIENCE3', 1),
  (@school_id, NULL, '1 SCIENCE4', 1),
  (@school_id, NULL, '1 SCIENCE5', 1);

-- General Arts (10 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '1 GA1',  1),
  (@school_id, NULL, '1 GA2',  1),
  (@school_id, NULL, '1 GA3',  1),
  (@school_id, NULL, '1 GA4',  1),
  (@school_id, NULL, '1 GA5',  1),
  (@school_id, NULL, '1 GA6',  1),
  (@school_id, NULL, '1 GA7',  1),
  (@school_id, NULL, '1 GA8',  1),
  (@school_id, NULL, '1 GA9',  1),
  (@school_id, NULL, '1 GA10', 1);

-- BUSINESS (5 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '1 BUSINESS1', 1),
  (@school_id, NULL, '1 BUSINESS2', 1),
  (@school_id, NULL, '1 BUSINESS3', 1),
  (@school_id, NULL, '1 BUSINESS4', 1),
  (@school_id, NULL, '1 BUSINESS5', 1);

-- Visual Arts (3 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '1 VA1', 1),
  (@school_id, NULL, '1 VA2', 1),
  (@school_id, NULL, '1 VA3', 1);

-- Home Economics (5 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '1 HOME ECONS1', 1),
  (@school_id, NULL, '1 HOME ECONS2', 1),
  (@school_id, NULL, '1 HOME ECONS3', 1),
  (@school_id, NULL, '1 HOME ECONS4', 1),
  (@school_id, NULL, '1 HOME ECONS5', 1);

-- Agricultural Science (2 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '1 AGRIC1', 1),
  (@school_id, NULL, '1 AGRIC2', 1);


-- ============================================================
-- YEAR 2  (year_group = 2)
-- ============================================================

-- Science (5 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '2 SCIENCE1', 2),
  (@school_id, NULL, '2 SCIENCE2', 2),
  (@school_id, NULL, '2 SCIENCE3', 2),
  (@school_id, NULL, '2 SCIENCE4', 2),
  (@school_id, NULL, '2 SCIENCE5', 2);

-- General Arts (10 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '2 GA1',  2),
  (@school_id, NULL, '2 GA2',  2),
  (@school_id, NULL, '2 GA3',  2),
  (@school_id, NULL, '2 GA4',  2),
  (@school_id, NULL, '2 GA5',  2),
  (@school_id, NULL, '2 GA6',  2),
  (@school_id, NULL, '2 GA7',  2),
  (@school_id, NULL, '2 GA8',  2),
  (@school_id, NULL, '2 GA9',  2),
  (@school_id, NULL, '2 GA10', 2);

-- BUSINESS (5 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '2 BUSINESS1', 2),
  (@school_id, NULL, '2 BUSINESS2', 2),
  (@school_id, NULL, '2 BUSINESS3', 2),
  (@school_id, NULL, '2 BUSINESS4', 2),
  (@school_id, NULL, '2 BUSINESS5', 2);

-- Visual Arts (3 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '2 VA1', 2),
  (@school_id, NULL, '2 VA2', 2),
  (@school_id, NULL, '2 VA3', 2);

-- Home Economics (5 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '2 HOME ECONS1', 2),
  (@school_id, NULL, '2 HOME ECONS2', 2),
  (@school_id, NULL, '2 HOME ECONS3', 2),
  (@school_id, NULL, '2 HOME ECONS4', 2),
  (@school_id, NULL, '2 HOME ECONS5', 2);

-- Agricultural Science (2 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '2 AGRIC1', 2),
  (@school_id, NULL, '2 AGRIC2', 2);


-- ============================================================
-- YEAR 3  (year_group = 3)
-- ============================================================

-- Science (5 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '3 SCIENCE1', 3),
  (@school_id, NULL, '3 SCIENCE2', 3),
  (@school_id, NULL, '3 SCIENCE3', 3),
  (@school_id, NULL, '3 SCIENCE4', 3),
  (@school_id, NULL, '3 SCIENCE5', 3);

-- General Arts (10 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '3 GA1',  3),
  (@school_id, NULL, '3 GA2',  3),
  (@school_id, NULL, '3 GA3',  3),
  (@school_id, NULL, '3 GA4',  3),
  (@school_id, NULL, '3 GA5',  3),
  (@school_id, NULL, '3 GA6',  3),
  (@school_id, NULL, '3 GA7',  3),
  (@school_id, NULL, '3 GA8',  3),
  (@school_id, NULL, '3 GA9',  3),
  (@school_id, NULL, '3 GA10', 3);

-- BUSINESS (5 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '3 BUSINESS1', 3),
  (@school_id, NULL, '3 BUSINESS2', 3),
  (@school_id, NULL, '3 BUSINESS3', 3),
  (@school_id, NULL, '3 BUSINESS4', 3),
  (@school_id, NULL, '3 BUSINESS5', 3);

-- Visual Arts (3 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '3 VA1', 3),
  (@school_id, NULL, '3 VA2', 3),
  (@school_id, NULL, '3 VA3', 3);

-- Home Economics (5 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '3 HOME ECONS1', 3),
  (@school_id, NULL, '3 HOME ECONS2', 3),
  (@school_id, NULL, '3 HOME ECONS3', 3),
  (@school_id, NULL, '3 HOME ECONS4', 3),
  (@school_id, NULL, '3 HOME ECONS5', 3);

-- Agricultural Science (2 classes)
INSERT IGNORE INTO classes (school_id, teacher_id, name, year_group) VALUES
  (@school_id, NULL, '3 AGRIC1', 3),
  (@school_id, NULL, '3 AGRIC2', 3);


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Verify — should return 90 rows:
--   SELECT year_group, COUNT(*) AS total FROM classes
--   WHERE school_id = @school_id
--   GROUP BY year_group ORDER BY year_group;
-- ============================================================
