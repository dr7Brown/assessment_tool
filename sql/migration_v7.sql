-- ============================================================
-- AceICT — Migration v7
-- Group Chat System
-- ============================================================

USE royayfxh_aceict;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS chat_groups (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id   INT UNSIGNED NOT NULL,
    name        VARCHAR(200) NOT NULL,
    description VARCHAR(500) NULL,
    type        ENUM('teachers','students','class','custom') NOT NULL DEFAULT 'custom',
    class_id    INT UNSIGNED NULL COMMENT 'Set when type=class',
    created_by  INT UNSIGNED NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_school (school_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_group_members (
    group_id     INT UNSIGNED NOT NULL,
    user_id      INT UNSIGNED NOT NULL,
    joined_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_read_at TIMESTAMP NULL,
    PRIMARY KEY (group_id, user_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_messages (
    id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id  INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    body      TEXT NOT NULL,
    sent_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_group  (group_id, sent_at),
    INDEX idx_sender (sender_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Verify:
--   SHOW TABLES LIKE 'chat_%';
