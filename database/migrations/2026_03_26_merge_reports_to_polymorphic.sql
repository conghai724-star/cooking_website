-- Merge legacy report tables into a unified polymorphic `reports` table
-- Date: 2026-03-26

START TRANSACTION;

CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    recipe_id INT NULL,
    target_type VARCHAR(20) NULL,
    target_id INT NULL,
    reason TEXT NOT NULL,
    details TEXT NULL,
    status ENUM('pending', 'reviewed', 'resolved') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reports_recipe (recipe_id),
    INDEX idx_reports_reporter (reporter_id),
    INDEX idx_reports_target (target_type, target_id),
    INDEX idx_reports_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill legacy recipe reports if target columns are empty
UPDATE reports
SET target_type = 'recipe',
    target_id = recipe_id
WHERE (target_type IS NULL OR target_type = '')
  AND recipe_id IS NOT NULL;

-- Migrate tip reports
INSERT INTO reports (reporter_id, target_type, target_id, reason, status, created_at)
SELECT tr.reporter_id, 'tip', tr.tip_id, tr.reason, tr.status, tr.created_at
FROM tip_reports tr
LEFT JOIN reports r
  ON r.reporter_id = tr.reporter_id
 AND r.target_type = 'tip'
 AND r.target_id = tr.tip_id
WHERE r.id IS NULL;

-- Migrate ingredient reports
INSERT INTO reports (reporter_id, target_type, target_id, reason, status, created_at)
SELECT ir.reporter_id, 'ingredient', ir.ingredient_id, ir.reason, ir.status, ir.created_at
FROM ingredient_reports ir
LEFT JOIN reports r
  ON r.reporter_id = ir.reporter_id
 AND r.target_type = 'ingredient'
 AND r.target_id = ir.ingredient_id
WHERE r.id IS NULL;

-- Migrate comment reports
INSERT INTO reports (reporter_id, target_type, target_id, reason, status, created_at)
SELECT cr.reporter_id, 'comment', cr.comment_id, cr.reason, cr.status, cr.created_at
FROM comment_reports cr
LEFT JOIN reports r
  ON r.reporter_id = cr.reporter_id
 AND r.target_type = 'comment'
 AND r.target_id = cr.comment_id
WHERE r.id IS NULL;

-- Migrate user reports
INSERT INTO reports (reporter_id, target_type, target_id, reason, details, status, created_at)
SELECT ur.reporter_id, 'user', ur.reported_user_id, ur.reason, ur.details, ur.status, ur.created_at
FROM user_reports ur
LEFT JOIN reports r
  ON r.reporter_id = ur.reporter_id
 AND r.target_type = 'user'
 AND r.target_id = ur.reported_user_id
WHERE r.id IS NULL;

-- Remove duplicates before adding unique key
DELETE r1 FROM reports r1
INNER JOIN reports r2
  ON r1.reporter_id = r2.reporter_id
 AND COALESCE(r1.target_type, 'recipe') = COALESCE(r2.target_type, 'recipe')
 AND COALESCE(r1.target_id, r1.recipe_id) = COALESCE(r2.target_id, r2.recipe_id)
 AND r1.id > r2.id;

-- Ensure target columns are filled for old recipe rows
UPDATE reports
SET target_type = COALESCE(NULLIF(target_type, ''), 'recipe'),
    target_id = COALESCE(target_id, recipe_id)
WHERE recipe_id IS NOT NULL;

-- Add unified unique key when missing
SET @has_uq := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'reports'
    AND index_name = 'uq_reports_once_target'
);
SET @sql_uq := IF(@has_uq = 0,
  'ALTER TABLE reports ADD UNIQUE KEY uq_reports_once_target (reporter_id, target_type, target_id)',
  'SELECT 1');
PREPARE stmt_uq FROM @sql_uq;
EXECUTE stmt_uq;
DEALLOCATE PREPARE stmt_uq;

COMMIT;

-- Optional cleanup (run after verifying admin/user report pages are OK):
-- DROP TABLE IF EXISTS tip_reports;
-- DROP TABLE IF EXISTS ingredient_reports;
-- DROP TABLE IF EXISTS comment_reports;
-- DROP TABLE IF EXISTS user_reports;
