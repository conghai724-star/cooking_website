USE cooking_website;

START TRANSACTION;

CREATE TABLE IF NOT EXISTS quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_set_id INT NOT NULL,
    user_id INT NOT NULL,
    score_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    total_questions INT NOT NULL DEFAULT 0,
    correct_answers INT NOT NULL DEFAULT 0,
    is_passed TINYINT(1) NOT NULL DEFAULT 0,
    submitted_answers_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_attempts_set FOREIGN KEY (quiz_set_id) REFERENCES quiz_sets(id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_attempts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_quiz_attempts_user_set (user_id, quiz_set_id),
    INDEX idx_quiz_attempts_created_at (created_at)
);

CREATE TABLE IF NOT EXISTS quiz_certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_set_id INT NOT NULL,
    user_id INT NOT NULL,
    certificate_code VARCHAR(64) NOT NULL,
    score_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    awarded_reputation_points INT NOT NULL DEFAULT 0,
    awarded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_certificates_set FOREIGN KEY (quiz_set_id) REFERENCES quiz_sets(id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_certificates_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_quiz_certificate_user_set (quiz_set_id, user_id),
    UNIQUE KEY uq_quiz_certificate_code (certificate_code),
    INDEX idx_quiz_certificates_user_time (user_id, awarded_at)
);

SET @has_reputation_points := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'reputation_points'
);
SET @add_reputation_sql := IF(
    @has_reputation_points = 0,
    'ALTER TABLE users ADD COLUMN reputation_points INT NOT NULL DEFAULT 0 AFTER bio',
    'SELECT 1'
);
PREPARE add_reputation_stmt FROM @add_reputation_sql;
EXECUTE add_reputation_stmt;
DEALLOCATE PREPARE add_reputation_stmt;

COMMIT;
