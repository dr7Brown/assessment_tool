-- ============================================================
-- AceICT — Migration v12
-- Google Meet Attendance Integration
--
-- Tables:
--   meetings          — scheduled Google Meet sessions
--   meeting_attendance — per-student join records + heartbeat
-- ============================================================

USE royayfxh_aceict;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS meetings (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id        INT UNSIGNED  NOT NULL,
    title            VARCHAR(255)  NOT NULL,
    subject_id       INT UNSIGNED  NULL,
    teacher_id       INT UNSIGNED  NOT NULL,
    class_id         INT UNSIGNED  NOT NULL,
    google_meet_link VARCHAR(1000) NOT NULL,
    meeting_date     DATE          NOT NULL,
    start_time       TIME          NOT NULL,
    end_time         TIME          NOT NULL,
    status           ENUM('scheduled','live','ended','cancelled') NOT NULL DEFAULT 'scheduled',
    description      TEXT          NULL,
    created_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_school_date (school_id, meeting_date),
    INDEX idx_class       (class_id),
    INDEX idx_teacher     (teacher_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS meeting_attendance (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    meeting_id        INT UNSIGNED NOT NULL,
    student_id        INT UNSIGNED NOT NULL,
    joined_at         TIMESTAMP    NULL,
    last_seen         TIMESTAMP    NULL,
    duration_minutes  INT UNSIGNED NOT NULL DEFAULT 0,
    attendance_status ENUM('absent','partial','late','present') NOT NULL DEFAULT 'absent',
    ip_address        VARCHAR(45)  NULL,
    device_info       VARCHAR(500) NULL,
    created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_meeting_student (meeting_id, student_id),
    INDEX idx_meeting (meeting_id),
    INDEX idx_student (student_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Attendance status rules (enforced in PHP):
--   duration = 0        → absent
--   1–9  minutes        → partial
--   10–19 minutes       → late
--   20+  minutes        → present

-- Verify:
--   SHOW TABLES LIKE 'meeting%';
--   DESCRIBE meetings;
--   DESCRIBE meeting_attendance;
