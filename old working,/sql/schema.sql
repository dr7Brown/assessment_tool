-- ============================================================
-- AceICT — Ghana SHS Assessment Platform
-- MySQL Database Schema v2.0
-- Compatible with MySQL 8.0+ / MariaDB 10.5+
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS aceict CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aceict;

-- ============================================================
-- SCHOOLS
-- ============================================================
CREATE TABLE schools (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200) NOT NULL,
    ges_id      VARCHAR(50) UNIQUE,
    region      VARCHAR(100),
    email       VARCHAR(200),
    phone       VARCHAR(20),
    plan        ENUM('free','school','district') DEFAULT 'free',
    plan_expires DATE,
    logo_url    VARCHAR(500),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- USERS  (teachers, students, admins — one table)
-- ============================================================
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id     INT UNSIGNED NOT NULL,
    role          ENUM('admin','teacher','student') NOT NULL,
    first_name    VARCHAR(100) NOT NULL,
    last_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(200) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,          -- bcrypt
    class_name    VARCHAR(50),                    -- e.g. SHS2A (students only)
    avatar_color  VARCHAR(7) DEFAULT '#1A7A4A',   -- hex colour for avatar
    is_active     TINYINT(1) DEFAULT 1,
    last_login    TIMESTAMP NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    INDEX idx_school_role (school_id, role),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ============================================================
-- SESSIONS  (API tokens / remember-me)
-- ============================================================
CREATE TABLE sessions (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    token      VARCHAR(128) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ============================================================
-- CLASSES  (teacher ↔ student groupings)
-- ============================================================
CREATE TABLE classes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id   INT UNSIGNED NOT NULL,
    teacher_id  INT UNSIGNED NOT NULL,
    name        VARCHAR(100) NOT NULL,    -- e.g. SHS2A
    year_group  TINYINT UNSIGNED NOT NULL, -- 1, 2 or 3
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id)  REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE class_students (
    class_id   INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    joined_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (class_id, student_id),
    FOREIGN KEY (class_id)   REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- QUESTION BANK
-- ============================================================
CREATE TABLE questions (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id    INT UNSIGNED,               -- NULL = platform-wide bank
    author_id    INT UNSIGNED,               -- teacher who created it
    type         ENUM('mcq','multi','short','essay','fill_blank','tf','matching','ordering') NOT NULL,
    sub_strand   VARCHAR(20) NOT NULL,       -- e.g. S2/SS2
    topic        VARCHAR(100) NOT NULL,      -- e.g. CIA Triad
    bloom_level  ENUM('Remember','Understand','Apply','Analyse','Evaluate','Create') DEFAULT 'Remember',
    difficulty   ENUM('Easy','Medium','Hard') DEFAULT 'Medium',
    year_group   TINYINT UNSIGNED DEFAULT 1,
    marks        TINYINT UNSIGNED DEFAULT 1,
    question_text TEXT NOT NULL,
    explanation   TEXT,                      -- shown after submission
    rubric        TEXT,                      -- for essays
    is_wassce     TINYINT(1) DEFAULT 0,      -- 1 = past paper question
    wassce_year   YEAR NULL,
    is_active     TINYINT(1) DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES users(id)   ON DELETE SET NULL,
    FULLTEXT INDEX ft_question (question_text, topic),
    INDEX idx_substrand (sub_strand),
    INDEX idx_difficulty (difficulty)
) ENGINE=InnoDB;

-- Options for MCQ / multi / tf / matching
CREATE TABLE question_options (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id  INT UNSIGNED NOT NULL,
    option_label CHAR(1) NOT NULL,           -- A, B, C, D
    option_text  TEXT NOT NULL,
    is_correct   TINYINT(1) DEFAULT 0,
    sort_order   TINYINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    INDEX idx_question (question_id)
) ENGINE=InnoDB;

-- ============================================================
-- TESTS
-- ============================================================
CREATE TABLE tests (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id       INT UNSIGNED NOT NULL,
    creator_id      INT UNSIGNED NOT NULL,
    title           VARCHAR(300) NOT NULL,
    description     TEXT,
    type            ENUM('quiz','mock','practice','live') DEFAULT 'quiz',
    status          ENUM('draft','published','closed','archived') DEFAULT 'draft',
    time_limit_min  SMALLINT UNSIGNED DEFAULT 0,   -- 0 = no limit
    max_attempts    TINYINT UNSIGNED DEFAULT 1,
    randomise_qs    TINYINT(1) DEFAULT 1,
    randomise_opts  TINYINT(1) DEFAULT 1,
    show_feedback   TINYINT(1) DEFAULT 1,
    available_from  TIMESTAMP NULL,
    due_at          TIMESTAMP NULL,
    access_code     VARCHAR(20) NULL,              -- optional password
    live_room_code  VARCHAR(8) NULL,               -- for live quiz
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id)  REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (creator_id) REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- Which questions are in which test, and in what order
CREATE TABLE test_questions (
    test_id     INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    sort_order  SMALLINT UNSIGNED DEFAULT 0,
    section     VARCHAR(50) DEFAULT 'A',      -- Section A, B, C
    PRIMARY KEY (test_id, question_id),
    FOREIGN KEY (test_id)     REFERENCES tests(id)     ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Which classes/students can see which test
CREATE TABLE test_assignments (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    test_id    INT UNSIGNED NOT NULL,
    class_id   INT UNSIGNED NULL,            -- assign to whole class
    student_id INT UNSIGNED NULL,            -- or individual student
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id)    REFERENCES tests(id)    ON DELETE CASCADE,
    FOREIGN KEY (class_id)   REFERENCES classes(id)  ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TEST ATTEMPTS  (one row per student attempt)
-- ============================================================
CREATE TABLE attempts (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    test_id       INT UNSIGNED NOT NULL,
    student_id    INT UNSIGNED NOT NULL,
    attempt_num   TINYINT UNSIGNED DEFAULT 1,
    status        ENUM('in_progress','submitted','marked','abandoned') DEFAULT 'in_progress',
    started_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    submitted_at  TIMESTAMP NULL,
    time_taken_s  INT UNSIGNED DEFAULT 0,     -- seconds
    score_auto    DECIMAL(6,2) DEFAULT 0,     -- auto-marked (MCQ etc.)
    score_manual  DECIMAL(6,2) DEFAULT 0,     -- teacher-marked (essays)
    score_total   DECIMAL(6,2) GENERATED ALWAYS AS (score_auto + score_manual) STORED,
    max_score     DECIMAL(6,2) DEFAULT 0,
    pct_score     DECIMAL(5,2) GENERATED ALWAYS AS (
                    IF(max_score > 0, (score_auto + score_manual) / max_score * 100, 0)
                  ) STORED,
    tab_switches  TINYINT UNSIGNED DEFAULT 0,  -- integrity flag
    ip_address    VARCHAR(45),
    FOREIGN KEY (test_id)    REFERENCES tests(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student_test (student_id, test_id),
    INDEX idx_test (test_id)
) ENGINE=InnoDB;

-- ============================================================
-- ANSWERS  (one row per question per attempt)
-- ============================================================
CREATE TABLE answers (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id     BIGINT UNSIGNED NOT NULL,
    question_id    INT UNSIGNED NOT NULL,
    selected_opts  JSON NULL,               -- e.g. ["B"] or ["A","C"] for multi
    text_response  MEDIUMTEXT NULL,         -- short answer / essay text
    is_flagged     TINYINT(1) DEFAULT 0,
    is_correct     TINYINT(1) NULL,         -- NULL = not yet marked (essay)
    marks_awarded  DECIMAL(5,2) NULL,
    teacher_feedback TEXT NULL,
    marked_by      INT UNSIGNED NULL,
    marked_at      TIMESTAMP NULL,
    time_on_q_s    SMALLINT UNSIGNED DEFAULT 0,  -- seconds spent on question
    answered_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id)  REFERENCES attempts(id)   ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id)  ON DELETE CASCADE,
    FOREIGN KEY (marked_by)   REFERENCES users(id)      ON DELETE SET NULL,
    INDEX idx_attempt (attempt_id),
    INDEX idx_question (question_id)
) ENGINE=InnoDB;

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
CREATE TABLE notifications (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    type        VARCHAR(50) NOT NULL,      -- test_due, result_ready, at_risk, essay_marked
    title       VARCHAR(200) NOT NULL,
    body        TEXT,
    link        VARCHAR(500),
    is_read     TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read)
) ENGINE=InnoDB;

-- ============================================================
-- STREAKS & GAMIFICATION
-- ============================================================
CREATE TABLE streaks (
    student_id       INT UNSIGNED PRIMARY KEY,
    current_streak   SMALLINT UNSIGNED DEFAULT 0,
    longest_streak   SMALLINT UNSIGNED DEFAULT 0,
    last_activity    DATE,
    total_xp         INT UNSIGNED DEFAULT 0,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE badges (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id   INT UNSIGNED NOT NULL,
    badge_key    VARCHAR(50) NOT NULL,    -- first_quiz, perfect_score, 7_day_streak
    awarded_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_student_badge (student_id, badge_key),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SPACED REPETITION QUEUE
-- ============================================================
CREATE TABLE spaced_repetition (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id   INT UNSIGNED NOT NULL,
    question_id  INT UNSIGNED NOT NULL,
    ease_factor  DECIMAL(4,2) DEFAULT 2.50,   -- SM-2 algorithm
    interval_d   SMALLINT UNSIGNED DEFAULT 1, -- days until next review
    repetitions  SMALLINT UNSIGNED DEFAULT 0,
    next_review  DATE NOT NULL,
    last_review  DATE,
    UNIQUE KEY uniq_student_q (student_id, question_id),
    FOREIGN KEY (student_id)  REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id)  ON DELETE CASCADE,
    INDEX idx_next_review (student_id, next_review)
) ENGINE=InnoDB;

-- ============================================================
-- AUDIT LOG  (tab switches, integrity events)
-- ============================================================
CREATE TABLE audit_log (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id  BIGINT UNSIGNED NOT NULL,
    event_type  VARCHAR(50) NOT NULL,   -- tab_switch, fullscreen_exit, paste_attempt
    event_data  JSON,
    logged_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- VIEWS (for fast dashboard queries)
-- ============================================================

-- Student performance by sub-strand
CREATE OR REPLACE VIEW v_student_substrand_avg AS
SELECT
    a.student_id,
    q.sub_strand,
    COUNT(DISTINCT a.id)        AS attempts_count,
    AVG(a.pct_score)            AS avg_pct,
    MAX(a.pct_score)            AS best_pct,
    MAX(a.submitted_at)         AS last_attempt
FROM attempts a
JOIN test_questions tq ON tq.test_id = a.test_id
JOIN questions q       ON q.id = tq.question_id
WHERE a.status IN ('submitted','marked')
GROUP BY a.student_id, q.sub_strand;

-- Class performance overview for teachers
CREATE OR REPLACE VIEW v_class_overview AS
SELECT
    cs.class_id,
    c.name          AS class_name,
    c.teacher_id,
    COUNT(DISTINCT cs.student_id)                   AS student_count,
    AVG(a.pct_score)                                AS class_avg,
    SUM(CASE WHEN a.pct_score < 50 THEN 1 ELSE 0 END) AS at_risk_count
FROM class_students cs
JOIN classes c ON c.id = cs.class_id
LEFT JOIN attempts a ON a.student_id = cs.student_id
    AND a.status IN ('submitted','marked')
GROUP BY cs.class_id, c.name, c.teacher_id;

-- Essays needing marking
CREATE OR REPLACE VIEW v_marking_queue AS
SELECT
    an.id           AS answer_id,
    an.attempt_id,
    an.question_id,
    an.text_response,
    u.first_name,
    u.last_name,
    u.class_name,
    t.title         AS test_title,
    q.sub_strand,
    q.marks         AS max_marks,
    q.rubric
FROM answers an
JOIN attempts a   ON a.id   = an.attempt_id
JOIN users u      ON u.id   = a.student_id
JOIN tests t      ON t.id   = a.test_id
JOIN questions q  ON q.id   = an.question_id
WHERE q.type = 'essay'
  AND an.is_correct IS NULL
  AND a.status = 'submitted';

-- ============================================================
-- STORED PROCEDURES
-- ============================================================
DELIMITER //

-- Auto-mark an MCQ/multi/tf answer and update attempt score
CREATE PROCEDURE sp_auto_mark_answer(
    IN p_answer_id BIGINT,
    IN p_attempt_id BIGINT,
    IN p_question_id INT,
    IN p_selected JSON
)
BEGIN
    DECLARE v_correct JSON;
    DECLARE v_marks TINYINT;
    DECLARE v_awarded DECIMAL(5,2);
    DECLARE v_is_correct TINYINT(1);

    -- Get correct options and marks
    SELECT JSON_ARRAYAGG(option_label), q.marks
    INTO v_correct, v_marks
    FROM question_options qo
    JOIN questions q ON q.id = qo.question_id
    WHERE qo.question_id = p_question_id AND qo.is_correct = 1
    GROUP BY q.marks;

    -- Compare (order-insensitive)
    SET v_is_correct = (JSON_OVERLAPS(p_selected, v_correct)
                        AND JSON_LENGTH(p_selected) = JSON_LENGTH(v_correct));

    SET v_awarded = IF(v_is_correct = 1, v_marks, 0);

    UPDATE answers
    SET is_correct   = v_is_correct,
        marks_awarded = v_awarded
    WHERE id = p_answer_id;

    -- Update attempt running total
    UPDATE attempts
    SET score_auto = (
        SELECT COALESCE(SUM(marks_awarded), 0)
        FROM answers
        WHERE attempt_id = p_attempt_id AND marks_awarded IS NOT NULL
    )
    WHERE id = p_attempt_id;
END //

-- Update spaced repetition schedule (SM-2 algorithm)
CREATE PROCEDURE sp_update_sr(
    IN p_student_id INT,
    IN p_question_id INT,
    IN p_quality TINYINT     -- 0-5: 0=blackout, 5=perfect
)
BEGIN
    DECLARE v_ef DECIMAL(4,2);
    DECLARE v_interval SMALLINT;
    DECLARE v_reps SMALLINT;

    SELECT ease_factor, interval_d, repetitions
    INTO v_ef, v_interval, v_reps
    FROM spaced_repetition
    WHERE student_id = p_student_id AND question_id = p_question_id;

    IF v_ef IS NULL THEN
        SET v_ef = 2.50; SET v_interval = 1; SET v_reps = 0;
    END IF;

    IF p_quality >= 3 THEN
        IF v_reps = 0 THEN SET v_interval = 1;
        ELSEIF v_reps = 1 THEN SET v_interval = 6;
        ELSE SET v_interval = ROUND(v_interval * v_ef);
        END IF;
        SET v_reps = v_reps + 1;
    ELSE
        SET v_interval = 1; SET v_reps = 0;
    END IF;

    SET v_ef = GREATEST(1.30, v_ef + 0.1 - (5 - p_quality) * (0.08 + (5 - p_quality) * 0.02));

    INSERT INTO spaced_repetition (student_id, question_id, ease_factor, interval_d, repetitions, next_review, last_review)
    VALUES (p_student_id, p_question_id, v_ef, v_interval, v_reps, DATE_ADD(CURDATE(), INTERVAL v_interval DAY), CURDATE())
    ON DUPLICATE KEY UPDATE
        ease_factor  = v_ef,
        interval_d   = v_interval,
        repetitions  = v_reps,
        next_review  = DATE_ADD(CURDATE(), INTERVAL v_interval DAY),
        last_review  = CURDATE();
END //

DELIMITER ;

-- ============================================================
-- SEED DATA — Platform-wide question bank (sample)
-- ============================================================
INSERT INTO schools (id, name, ges_id, region, email, plan) VALUES
(1, 'Platform Bank', NULL, NULL, NULL, 'district');

INSERT INTO users (id, school_id, role, first_name, last_name, email, password_hash) VALUES
(1, 1, 'admin', 'Platform', 'Admin', 'admin@aceict.app', '$2y$12$placeholder');

-- Sample questions (add your full 40+ here)
INSERT INTO questions (school_id, author_id, type, sub_strand, topic, bloom_level, difficulty, year_group, marks, question_text, explanation) VALUES
(NULL, NULL, 'mcq', 'S2/SS2', 'CIA Triad', 'Remember', 'Easy', 1, 1,
 'What does CIA stand for in the context of information security?',
 'CIA = Confidentiality (only authorised access), Integrity (accurate, unaltered), Availability (accessible when needed).'),
(NULL, NULL, 'mcq', 'S2/SS2', 'CIA Triad', 'Understand', 'Medium', 1, 1,
 'A hacker changes a student grade in the school database. Which CIA pillar is violated?',
 'Integrity means data is accurate and has not been tampered with. Changing a grade violates Integrity.'),
(NULL, NULL, 'mcq', 'S2/SS2', 'Malware', 'Understand', 'Medium', 1, 1,
 'Which malware self-replicates and spreads across networks automatically, without user action?',
 'A worm self-replicates independently. A virus needs a user to run an infected file.'),
(NULL, NULL, 'mcq', 'S2/SS1', 'Topologies', 'Remember', 'Easy', 1, 1,
 'Which network topology connects all devices to one central switch — the most common in school labs?',
 'Star topology: every device connects to a central switch. One device failing does not affect others.'),
(NULL, NULL, 'mcq', 'S1/SS2', 'Cloud Computing', 'Apply', 'Medium', 1, 1,
 'A business uses Google Workspace (Gmail, Docs, Sheets). This is which cloud model?',
 'SaaS = Software as a Service. The software is hosted and run by someone else — accessed via browser.'),
(NULL, NULL, 'essay', 'S2/SS2', 'Malware', 'Analyse', 'Hard', 1, 15,
 'What is computer security? Describe FIVE types of malware, stating how each spreads and what damage it causes. For each type, give one specific Ghanaian example or context.',
 NULL);

INSERT INTO question_options (question_id, option_label, option_text, is_correct, sort_order) VALUES
(1, 'A', 'Central Intelligence Agency', 0, 1),
(1, 'B', 'Confidentiality, Integrity, Availability', 1, 2),
(1, 'C', 'Computer Integrity Assessment', 0, 3),
(1, 'D', 'Cyber Intelligence Architecture', 0, 4),
(2, 'A', 'Confidentiality', 0, 1),
(2, 'B', 'Integrity', 1, 2),
(2, 'C', 'Availability', 0, 3),
(2, 'D', 'Authentication', 0, 4),
(3, 'A', 'Virus', 0, 1),
(3, 'B', 'Worm', 1, 2),
(3, 'C', 'Trojan horse', 0, 3),
(3, 'D', 'Adware', 0, 4),
(4, 'A', 'Bus', 0, 1),
(4, 'B', 'Ring', 0, 2),
(4, 'C', 'Star', 1, 3),
(4, 'D', 'Mesh', 0, 4),
(5, 'A', 'IaaS', 0, 1),
(5, 'B', 'PaaS', 0, 2),
(5, 'C', 'SaaS', 1, 3),
(5, 'D', 'DaaS', 0, 4);

SET FOREIGN_KEY_CHECKS = 1;
