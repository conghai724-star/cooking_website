USE cooking_website;

START TRANSACTION;

SET @has_question_type := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'quiz_questions'
      AND COLUMN_NAME = 'question_type'
);
SET @sql_question_type := IF(
    @has_question_type = 0,
    "ALTER TABLE quiz_questions ADD COLUMN question_type VARCHAR(40) NOT NULL DEFAULT 'single_choice' AFTER quiz_set_id",
    'SELECT 1'
);
PREPARE stmt_question_type FROM @sql_question_type;
EXECUTE stmt_question_type;
DEALLOCATE PREPARE stmt_question_type;

SET @has_question_image := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'quiz_questions'
      AND COLUMN_NAME = 'question_image'
);
SET @sql_question_image := IF(
    @has_question_image = 0,
    'ALTER TABLE quiz_questions ADD COLUMN question_image VARCHAR(255) NULL AFTER question_text',
    'SELECT 1'
);
PREPARE stmt_question_image FROM @sql_question_image;
EXECUTE stmt_question_image;
DEALLOCATE PREPARE stmt_question_image;

SET @has_answer_key := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'quiz_questions'
      AND COLUMN_NAME = 'answer_key_json'
);
SET @sql_answer_key := IF(
    @has_answer_key = 0,
    'ALTER TABLE quiz_questions ADD COLUMN answer_key_json LONGTEXT NULL AFTER question_image',
    'SELECT 1'
);
PREPARE stmt_answer_key FROM @sql_answer_key;
EXECUTE stmt_answer_key;
DEALLOCATE PREPARE stmt_answer_key;

COMMIT;
