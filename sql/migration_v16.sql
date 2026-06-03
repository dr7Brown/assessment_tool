-- ============================================================
-- AceICT — Migration v16
-- Activity log: records all important admin/teacher/student
-- actions for audit, compliance, and debugging.
-- ============================================================

USE royayfxh_aceict;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS activity_log (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id    INT UNSIGNED    NOT NULL,
  user_id      INT UNSIGNED    NULL,
  user_role    VARCHAR(20)     NULL,
  user_name    VARCHAR(200)    NULL,   -- snapshot at time of action
  action       VARCHAR(100)    NOT NULL,
  entity_type  VARCHAR(50)     NULL,
  entity_id    INT UNSIGNED    NULL,
  entity_label VARCHAR(300)    NULL,   -- name/title snapshot
  description  TEXT            NULL,
  ip_address   VARCHAR(45)     NULL,
  created_at   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  KEY idx_school_date (school_id, created_at),
  KEY idx_user        (user_id),
  KEY idx_action      (action(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify:
--   SHOW TABLES LIKE 'activity_log';
--   DESCRIBE activity_log;
