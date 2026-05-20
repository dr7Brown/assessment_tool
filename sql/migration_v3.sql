-- ============================================================
-- AceICT — Migration v3
-- Run this on your LIVE production database.
-- Compatible with MySQL 8.0+ and MariaDB 10.5+
--
-- What this adds:
--   1. live_sessions       — live quiz room state
--   2. live_participants   — students in a live quiz room
--   3. live_answers        — per-question answers in live quiz
--   4. departments         — school departments
--   5. class_teachers      — many-to-many class ↔ teacher (subjects)
--   6. users.must_change_password — force first-login password change
--   7. users.department_id — which department a teacher belongs to
--
-- SAFE TO RUN ON EXISTING DB — all statements use IF NOT EXISTS.
-- Replace `royayfxh_aceict` with your production database name.
-- ============================================================

USE royayfxh_aceict;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


-- ============================================================
-- 1. LIVE QUIZ — SESSIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS live_sessions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    test_id     INT UNSIGNED NOT NULL,
    room_code   VARCHAR(8)   UNIQUE NOT NULL,
    teacher_id  INT UNSIGNED NOT NULL,
    school_id   INT UNSIGNED NOT NULL,
    status      ENUM('waiting','active','ended') DEFAULT 'waiting',
    current_q   SMALLINT DEFAULT -1,
    started_at  TIMESTAMP NULL,
    ended_at    TIMESTAMP NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room_code (room_code),
    INDEX idx_teacher   (teacher_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- 2. LIVE QUIZ — PARTICIPANTS (students who joined a room)
-- ============================================================
CREATE TABLE IF NOT EXISTS live_participants (
    session_id       INT UNSIGNED NOT NULL,
    student_id       INT UNSIGNED NOT NULL,
    score            INT UNSIGNED DEFAULT 0,
    last_q_answered  SMALLINT DEFAULT -1,
    joined_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (session_id, student_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- 3. LIVE QUIZ — ANSWERS (per-question responses)
-- ============================================================
CREATE TABLE IF NOT EXISTS live_answers (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id    INT UNSIGNED NOT NULL,
    student_id    INT UNSIGNED NOT NULL,
    q_index       SMALLINT UNSIGNED NOT NULL,
    selected_opt  CHAR(1),
    is_correct    TINYINT(1) DEFAULT 0,
    marks_awarded DECIMAL(5,2) DEFAULT 0,
    answered_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_live_ans (session_id, student_id, q_index),
    INDEX idx_session_q    (session_id, q_index)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- 4. DEPARTMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS departments (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id        INT UNSIGNED NOT NULL,
    name             VARCHAR(200) NOT NULL,
    description      TEXT,
    head_teacher_id  INT UNSIGNED NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_dept_name (school_id, name)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- 5. CLASS ↔ TEACHER (many-to-many, per subject)
--    A class can have multiple subject teachers.
--    A teacher can teach multiple classes.
-- ============================================================
CREATE TABLE IF NOT EXISTS class_teachers (
    class_id     INT UNSIGNED NOT NULL,
    teacher_id   INT UNSIGNED NOT NULL,
    subject      VARCHAR(100) DEFAULT 'ICT',
    assigned_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (class_id, teacher_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- 6. NEW COLUMN: users.must_change_password
--    Set to 1 for admin-created accounts so the user is forced
--    to choose a new password on first login.
-- ============================================================
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS
    must_change_password TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = user must set a new password on next login';


-- ============================================================
-- 7. NEW COLUMN: users.department_id
--    Teachers belong to one department (nullable).
-- ============================================================
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS
    department_id INT UNSIGNED NULL
    COMMENT 'FK → departments.id (teachers only)';


SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- 8. SEED — DEPARTMENTS
--
-- ⚠ IMPORTANT: set @school_id to your school's ID before running.
--   Run this first to find your school ID:
--       SELECT id, name FROM schools;
--   Then replace the 1 below with your actual school ID.
-- ============================================================

-- SET @school_id = 1;   -- ← CHANGE THIS to your school's ID

INSERT INTO departments (school_id, name, description)
VALUES
  (@school_id,
   'Science Department',
   'Covers Biology, Chemistry, Physics and Integrated Science. Prepares students for WASSCE science papers and tertiary STEM programmes.'),

  (@school_id,
   'Mathematics Department',
   'Covers Core Mathematics and Elective Mathematics. Builds numerical reasoning, algebra, statistics and problem-solving skills required across all WASSCE tracks.'),

  (@school_id,
   'ICT Department',
   'ICT and Computing. Covers computer hardware, software, networking, cybersecurity, spreadsheets, databases and programming — aligned with Ghana GES SHS ICT curriculum.'),

  (@school_id,
   'English Department',
   'English Language and Literature in English. Develops reading, writing, oral communication and critical analysis skills essential for all WASSCE subjects.'),

  (@school_id,
   'Languages Department',
   'Modern and Ghanaian languages including French, Twi, Ga, Ewe and Hausa. Promotes multilingual competency and cultural awareness.'),

  (@school_id,
   'Social Studies Department',
   'Covers Social Studies, History, Geography and Civic Education. Equips students with knowledge of Ghanaian society, government, environment and global citizenship.'),

  (@school_id,
   'Arts Department',
   'General arts track covering Literature, Government, Economics and History. Prepares students for humanities and social science pathways at the tertiary level.'),

  (@school_id,
   'Business Department',
   'Business Management, Accounting, Economics and Costing. Equips students with financial literacy, entrepreneurship and management skills relevant to the Ghanaian business environment.'),

  (@school_id,
   'Visual Arts Department',
   'Painting, Sculpture, Graphic Design, Textiles, Basketry and Ceramics. Develops creative expression, design thinking and technical craft skills for the arts and creative industries.'),

  (@school_id,
   'Home Economics Department',
   'Food and Nutrition, Textiles and Clothing, and Management in Living. Provides practical skills in catering, health, garment making and household management.'),

  (@school_id,
   'Technical Department',
   'Technical Drawing, Auto Mechanics, Metalwork, Woodwork and Building Construction. Develops hands-on technical skills aligned with Ghana TVET and industry pathways.'),

  (@school_id,
   'Agricultural Science Department',
   'Crop Science, Animal Science, Soil Science and Agribusiness. Promotes modern farming techniques, food security knowledge and agricultural entrepreneurship.'),

  (@school_id,
   'Physical Education Department',
   'Sports, Health Education and Recreation. Promotes physical fitness, teamwork, sportsmanship and overall student wellness through structured physical activity and competitive sports.');

-- ============================================================
-- Done. Verify with:
--   SHOW TABLES;
--   DESCRIBE users;
--   SELECT id, name FROM departments WHERE school_id = @school_id;
-- ============================================================



