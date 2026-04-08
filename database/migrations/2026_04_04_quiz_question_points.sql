USE cooking_website;

START TRANSACTION;

SET @has_points := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'quiz_questions'
      AND COLUMN_NAME = 'points'
);
SET @sql_points := IF(
    @has_points = 0,
    'ALTER TABLE quiz_questions ADD COLUMN points INT NOT NULL DEFAULT 1 AFTER question_image',
    'SELECT 1'
);
PREPARE stmt_points FROM @sql_points;
EXECUTE stmt_points;
DEALLOCATE PREPARE stmt_points;

COMMIT;
