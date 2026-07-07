ALTER TABLE `issue_commits` ADD COLUMN `status` VARCHAR(20) DEFAULT NULL;

DROP VIEW IF EXISTS `issues_with_status`;
CREATE VIEW `issues_with_status` AS
  SELECT i.*, COALESCE(
    (
      SELECT status
      FROM (
        SELECT issue_id, status, posted_at  AS ts FROM issue_log    WHERE status IS NOT NULL
        UNION ALL
        SELECT issue_id, status, linked_at  AS ts FROM issue_commits WHERE status IS NOT NULL
      ) combined
      WHERE combined.issue_id = i.id
      ORDER BY ts DESC
      LIMIT 1
    ),
    'open'
  ) AS status
  FROM issues i;
