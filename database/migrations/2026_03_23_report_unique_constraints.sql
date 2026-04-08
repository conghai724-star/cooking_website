-- Enforce: 1 user can report 1 object only once
-- Applies to:
--   reports         -> unique (reporter_id, recipe_id)
--   comment_reports -> unique (reporter_id, comment_id)

-- 1) reports
SET @reports_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'reports'
);

SET @sql := IF(
    @reports_table_exists > 0,
    'DELETE r1 FROM reports r1
      INNER JOIN reports r2
        ON r1.reporter_id = r2.reporter_id
       AND r1.recipe_id = r2.recipe_id
       AND r1.id > r2.id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @reports_unique_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'reports'
      AND index_name = 'uq_reports_once'
);

SET @sql := IF(
    @reports_table_exists > 0 AND @reports_unique_exists = 0,
    'ALTER TABLE reports ADD UNIQUE KEY uq_reports_once (reporter_id, recipe_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) comment_reports
SET @comment_reports_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'comment_reports'
);

SET @sql := IF(
    @comment_reports_table_exists > 0,
    'DELETE c1 FROM comment_reports c1
      INNER JOIN comment_reports c2
        ON c1.reporter_id = c2.reporter_id
       AND c1.comment_id = c2.comment_id
       AND c1.id > c2.id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @comment_reports_unique_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'comment_reports'
      AND index_name = 'uq_comment_report_once'
);

SET @sql := IF(
    @comment_reports_table_exists > 0 AND @comment_reports_unique_exists = 0,
    'ALTER TABLE comment_reports ADD UNIQUE KEY uq_comment_report_once (reporter_id, comment_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
