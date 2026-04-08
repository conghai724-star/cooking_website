-- Gộp toàn bộ comment về một bảng comments

ALTER TABLE comments
    ADD COLUMN IF NOT EXISTS content_type ENUM('recipe', 'tip', 'ingredient') NOT NULL DEFAULT 'recipe' AFTER recipe_id,
    ADD COLUMN IF NOT EXISTS content_id INT NULL AFTER content_type;

ALTER TABLE comments
    MODIFY COLUMN recipe_id INT NULL;

UPDATE comments
SET content_type = 'recipe'
WHERE content_type IS NULL OR content_type = '';

UPDATE comments
SET content_id = recipe_id
WHERE content_id IS NULL AND recipe_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_comments_content ON comments(content_type, content_id);

INSERT INTO comments (user_id, recipe_id, parent_id, content, status, created_at, content_type, content_id)
SELECT tc.user_id, NULL, NULL, tc.content, tc.status, tc.created_at, 'tip', tc.tip_id
FROM tip_comments tc
LEFT JOIN comments c
       ON c.content_type = 'tip'
      AND c.content_id = tc.tip_id
      AND c.user_id = tc.user_id
      AND c.content = tc.content
      AND c.created_at = tc.created_at
WHERE c.id IS NULL;

INSERT INTO comments (user_id, recipe_id, parent_id, content, status, created_at, content_type, content_id)
SELECT ic.user_id, NULL, NULL, ic.content, ic.status, ic.created_at, 'ingredient', ic.ingredient_id
FROM ingredient_comments ic
LEFT JOIN comments c
       ON c.content_type = 'ingredient'
      AND c.content_id = ic.ingredient_id
      AND c.user_id = ic.user_id
      AND c.content = ic.content
      AND c.created_at = ic.created_at
WHERE c.id IS NULL;

CREATE TABLE IF NOT EXISTS comment_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    comment_id INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'reviewed', 'resolved') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comment_reports_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_reports_comment FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    UNIQUE KEY uq_comment_report_once (reporter_id, comment_id),
    INDEX idx_comment_reports_comment (comment_id),
    INDEX idx_comment_reports_reporter (reporter_id)
);

