-- ============================================================
-- AceICT — Migration v5
-- Messaging System
--
-- What this adds:
--   1. messages        — stores each message once (broadcast-safe)
--   2. message_reads   — tracks per-user read status
--
-- SAFE TO RUN ON EXISTING DB — uses IF NOT EXISTS.
-- Run after migration_v4.sql.
-- ============================================================

USE royayfxh_aceict;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. MESSAGES
--    One row per message sent.
--    recipient_type controls who sees it:
--      'individual'   — one user (recipient_id = user_id)
--      'class'        — all students in a class (recipient_id = class_id)
--      'all_students' — every active student in the school
--      'all_teachers' — every active teacher in the school
-- ============================================================
CREATE TABLE IF NOT EXISTS messages (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id      INT UNSIGNED NOT NULL,
    sender_id      INT UNSIGNED NOT NULL,
    sender_role    VARCHAR(20)  NOT NULL,
    recipient_type VARCHAR(20)  NOT NULL,
    recipient_id   INT UNSIGNED NULL
        COMMENT 'user_id or class_id; NULL for school-wide broadcasts',
    subject        VARCHAR(255) NOT NULL,
    body           TEXT         NOT NULL,
    sent_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_school (school_id),
    INDEX idx_sender (sender_id),
    INDEX idx_rcpt   (recipient_type, recipient_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- 2. MESSAGE_READS
--    Tracks which users have opened which messages.
--    Absence of a row = unread.
-- ============================================================
CREATE TABLE IF NOT EXISTS message_reads (
    message_id INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    read_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id, user_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Verify:
--   SHOW TABLES LIKE 'messages';
--   SHOW TABLES LIKE 'message_reads';
-- ============================================================
