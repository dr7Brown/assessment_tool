-- ============================================================
-- AceICT — Subjects Table + National Curriculum Seed
-- 70 Ghana GES SHS subjects across 10 categories
--
-- HOW IT WORKS:
--   • subjects (school_id = NULL)  = platform-wide defaults
--   • subjects (school_id = X)     = custom subjects added by school X
--   • school_subjects              = which platform subjects school X has enabled
--
-- Run migration_v3.sql FIRST, then run this file.
-- Safe to run multiple times — uses IF NOT EXISTS / INSERT IGNORE.
-- ============================================================

USE royayfxh_aceict;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


-- ============================================================
-- TABLE 1: subjects
-- ============================================================
CREATE TABLE IF NOT EXISTS subjects (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id   INT UNSIGNED NULL
                COMMENT 'NULL = platform default visible to all schools',
    name        VARCHAR(200) NOT NULL,
    short_name  VARCHAR(80)  NULL       COMMENT 'Abbreviated display name',
    category    VARCHAR(100) NOT NULL DEFAULT 'General',
    sort_order  SMALLINT UNSIGNED DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_subj (school_id, name)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 2: school_subjects
-- Records which platform subjects each school has enabled.
-- School-custom subjects (school_id = X in subjects table) are
-- always visible to that school — no entry here needed for them.
-- ============================================================
CREATE TABLE IF NOT EXISTS school_subjects (
    school_id   INT UNSIGNED NOT NULL,
    subject_id  INT UNSIGNED NOT NULL,
    enabled_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (school_id, subject_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- SEED: 70 platform subjects (school_id = NULL)
-- INSERT IGNORE — safe to run multiple times
-- ============================================================

-- ── General (compulsory core subjects — all programmes) ──────
INSERT IGNORE INTO subjects (school_id, name, short_name, category, sort_order) VALUES
  (NULL, 'English Language',    'English',    'General', 10),
  (NULL, 'Core Mathematics',    'Core Maths', 'General', 20),
  (NULL, 'Integrated Science',  'Int. Sci.',  'General', 30),
  (NULL, 'Social Studies',      'Soc. Stud.', 'General', 40);

-- ── Sciences ──────────────────────────────────────────────────
INSERT IGNORE INTO subjects (school_id, name, short_name, category, sort_order) VALUES
  (NULL, 'Physics',              'Physics',    'Sciences', 10),
  (NULL, 'Chemistry',            'Chemistry',  'Sciences', 20),
  (NULL, 'Biology',              'Biology',    'Sciences', 30),
  (NULL, 'Elective Mathematics', 'Elec. Maths','Sciences', 40),
  (NULL, 'Further Mathematics',  'Fur. Maths', 'Sciences', 50),
  (NULL, 'Engineering Science',  'Eng. Sci.',  'Sciences', 60);

-- ── Agricultural Science ──────────────────────────────────────
INSERT IGNORE INTO subjects (school_id, name, short_name, category, sort_order) VALUES
  (NULL, 'General Agriculture',  'Gen. Agric',  'Agricultural Science', 10),
  (NULL, 'Animal Husbandry',     'Animal Hub.', 'Agricultural Science', 20),
  (NULL, 'Crop Husbandry',       'Crop Hub.',   'Agricultural Science', 30),
  (NULL, 'Horticulture',         'Horticulture','Agricultural Science', 40),
  (NULL, 'Crop Science',         'Crop Sci.',   'Agricultural Science', 50),
  (NULL, 'Fisheries',            'Fisheries',   'Agricultural Science', 60),
  (NULL, 'Forestry',             'Forestry',    'Agricultural Science', 70);

-- ── ICT & Computing ───────────────────────────────────────────
INSERT IGNORE INTO subjects (school_id, name, short_name, category, sort_order) VALUES
  (NULL, 'Information and Communication Technology (ICT)', 'ICT',         'ICT & Computing', 10),
  (NULL, 'Computing',                                      'Computing',   'ICT & Computing', 20),
  (NULL, 'Robotics',                                       'Robotics',    'ICT & Computing', 30),
  (NULL, 'Data Processing',                                'Data Proc.',  'ICT & Computing', 40),
  (NULL, 'Programming',                                    'Programming', 'ICT & Computing', 50);

-- ── Languages ─────────────────────────────────────────────────
INSERT IGNORE INTO subjects (school_id, name, short_name, category, sort_order) VALUES
  (NULL, 'Literature in English',  'Lit. Eng.','Languages', 10),
  (NULL, 'French',                 'French',   'Languages', 20),
  (NULL, 'Twi',                    'Twi',      'Languages', 40),
  (NULL, 'Ga',                     'Ga',       'Languages', 50),
  (NULL, 'Ewe',                    'Ewe',      'Languages', 60),
  (NULL, 'Fante',                  'Fante',    'Languages', 70),
  (NULL, 'Dagbani',                'Dagbani',  'Languages', 80),
  (NULL, 'Nzema',                  'Nzema',    'Languages', 90),
  (NULL, 'Dagaare',                'Dagaare',  'Languages', 100),
  (NULL, 'Gonja',                  'Gonja',    'Languages', 110),
  (NULL, 'Kasem',                  'Kasem',    'Languages', 120),
  (NULL, 'Arabic',                 'Arabic',   'Languages', 130);

-- ── Humanities & Social Studies ──────────────────────────────
INSERT IGNORE INTO subjects (school_id, name, short_name, category, sort_order) VALUES
  (NULL, 'Government',                        'Government',       'Humanities & Social Studies', 10),
  (NULL, 'History',                           'History',          'Humanities & Social Studies', 20),
  (NULL, 'Geography',                         'Geography',        'Humanities & Social Studies', 30),
  (NULL, 'Economics',                         'Economics',        'Humanities & Social Studies', 40),
  (NULL, 'Christian Religious Studies (CRS)', 'CRS',              'Humanities & Social Studies', 50),
  (NULL, 'Islamic Religious Studies (IRS)',   'IRS',              'Humanities & Social Studies', 60),
  (NULL, 'Sociology',                         'Sociology',        'Humanities & Social Studies', 70),
  (NULL, 'Psychology',                        'Psychology',       'Humanities & Social Studies', 80);
-- ── Business Studies ──────────────────────────────────────────
INSERT IGNORE INTO subjects (school_id, name, short_name, category, sort_order) VALUES
  (NULL, 'Financial Accounting',         'Fin. Acct.',    'Business Studies', 10),
  (NULL, 'Cost Accounting',              'Cost Acct.',    'Business Studies', 20),
  (NULL, 'Business Management',          'Bus. Mgt.',     'Business Studies', 30),
  (NULL, 'Principles of Cost Accounting','Prin. Cost.',   'Business Studies', 40),
  (NULL, 'Clerical Office Duties',       'Office Duties', 'Business Studies', 50);

-- ── Visual Arts ───────────────────────────────────────────────
INSERT IGNORE INTO subjects (school_id, name, short_name, category, sort_order) VALUES
  (NULL, 'General Knowledge in Art', 'GKA',          'Visual Arts', 10),
  (NULL, 'Graphic Design',           'Graphic Des.', 'Visual Arts', 20),
  (NULL, 'Picture Making',           'Picture Mak.', 'Visual Arts', 30),
  (NULL, 'Sculpture',                'Sculpture',    'Visual Arts', 40),
  (NULL, 'Ceramics',                 'Ceramics',     'Visual Arts', 50),
  (NULL, 'Textiles',                 'Textiles',     'Visual Arts', 60),
  (NULL, 'Leatherwork',              'Leatherwork',  'Visual Arts', 70),
  (NULL, 'Basketry',                 'Basketry',     'Visual Arts', 80),
  (NULL, 'Jewellery',                'Jewellery',    'Visual Arts', 90),
  (NULL, 'Graphic Communication',    'Graphic Com.', 'Visual Arts', 100);

-- ── Home Economics ────────────────────────────────────────────
INSERT IGNORE INTO subjects (school_id, name, short_name, category, sort_order) VALUES
  (NULL, 'Food and Nutrition',    'Food & Nutr.', 'Home Economics', 10),
  (NULL, 'Management in Living',  'Mgt in Living','Home Economics', 20),
  (NULL, 'Clothing and Textiles', 'Clothing',     'Home Economics', 30);

-- ── Technical & Vocational ────────────────────────────────────
INSERT IGNORE INTO subjects (school_id, name, short_name, category, sort_order) VALUES
  (NULL, 'Technical Drawing',      'Tech. Drawing', 'Technical & Vocational', 10),
  (NULL, 'Building Construction',  'Bldg. Constr.', 'Technical & Vocational', 20),
  (NULL, 'Woodwork',               'Woodwork',      'Technical & Vocational', 30),
  (NULL, 'Metalwork',              'Metalwork',     'Technical & Vocational', 40),
  (NULL, 'Auto Mechanics',         'Auto Mech.',    'Technical & Vocational', 50),
  (NULL, 'Applied Electricity',    'App. Elec.',    'Technical & Vocational', 60),
  (NULL, 'Electronics',            'Electronics',   'Technical & Vocational', 70),
  (NULL, 'Welding and Fabrication','Welding',       'Technical & Vocational', 80);

-- ── Physical Education ────────────────────────────────────────
INSERT IGNORE INTO subjects (school_id, name, short_name, category, sort_order) VALUES
  (NULL, 'Physical Education', 'P.E.',        'Physical Education', 10),
  (NULL, 'Sports and Recreation', 'Sports',   'Physical Education', 20),
  (NULL, 'Health Education',   'Health Ed.',  'Physical Education', 30);


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Verify:
--   SELECT category, COUNT(*) AS subjects
--   FROM subjects WHERE school_id IS NULL
--   GROUP BY category ORDER BY category;
-- Expected: 10 categories, 70 subjects total
-- ============================================================
