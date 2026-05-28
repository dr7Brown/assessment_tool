-- ============================================================
-- AceICT — Migration v9
-- Chat enhancements + Academic period dates
-- ============================================================

USE royayfxh_aceict;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- academic_periods: add semester date range
ALTER TABLE academic_periods
    ADD COLUMN IF NOT EXISTS start_date DATE NULL COMMENT 'Semester start date',
    ADD COLUMN IF NOT EXISTS end_date   DATE NULL COMMENT 'Semester end date';

-- chat_groups: add active flag
ALTER TABLE chat_groups
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0 = deactivated by admin';

-- chat_group_members: role + send restriction
ALTER TABLE chat_group_members
    ADD COLUMN IF NOT EXISTS role     ENUM('member','admin') NOT NULL DEFAULT 'member',
    ADD COLUMN IF NOT EXISTS can_send TINYINT(1)             NOT NULL DEFAULT 1 COMMENT '0 = muted by admin';

-- chat_messages: reply + deletion
ALTER TABLE chat_messages
    ADD COLUMN IF NOT EXISTS reply_to_id BIGINT UNSIGNED NULL COMMENT 'parent message id for replies',
    ADD COLUMN IF NOT EXISTS is_deleted  TINYINT(1)     NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS deleted_by  INT UNSIGNED   NULL,
    ADD COLUMN IF NOT EXISTS deleted_at  TIMESTAMP      NULL,
    ADD COLUMN IF NOT EXISTS is_pinned   TINYINT(1)     NOT NULL DEFAULT 0;

-- message reactions
CREATE TABLE IF NOT EXISTS message_reactions (
    message_id BIGINT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED    NOT NULL,
    emoji      VARCHAR(10)     NOT NULL,
    reacted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id, user_id),
    INDEX idx_message (message_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
