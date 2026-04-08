-- Final unified comments schema (single table for recipe/tip/ingredient)
USE cooking_website;

ALTER TABLE comments
    ADD COLUMN IF NOT EXISTS content_type ENUM('recipe', 'tip', 'ingredient') NOT NULL DEFAULT 'recipe' AFTER recipe_id,
    ADD COLUMN IF NOT EXISTS content_id INT NULL AFTER content_type,
    ADD COLUMN IF NOT EXISTS like_count INT NOT NULL DEFAULT 0 AFTER content,
    ADD COLUMN IF NOT EXISTS reply_count INT NOT NULL DEFAULT 0 AFTER like_count,
    ADD COLUMN IF NOT EXISTS is_edited TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN IF NOT EXISTS edited_at DATETIME NULL AFTER is_edited,
    ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL AFTER edited_at,
    ADD COLUMN IF NOT EXISTS deleted_by INT NULL AFTER deleted_at,
    ADD COLUMN IF NOT EXISTS delete_reason VARCHAR(255) NULL AFTER deleted_by,
    ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

ALTER TABLE comments
    MODIFY COLUMN recipe_id INT NULL;

UPDATE comments
SET content_type = 'recipe'
WHERE content_type IS NULL OR content_type = '';

UPDATE comments
SET content_id = recipe_id
WHERE content_id IS NULL AND recipe_id IS NOT NULL;

UPDATE comments
SET status = 'active'
WHERE status = 'visible';

ALTER TABLE comments
    MODIFY COLUMN status ENUM('active','hidden','deleted') NOT NULL DEFAULT 'active';

CREATE INDEX IF NOT EXISTS idx_comments_content ON comments(content_type, content_id);
CREATE INDEX IF NOT EXISTS idx_comments_parent ON comments(parent_id);
CREATE INDEX IF NOT EXISTS idx_comments_status ON comments(status, created_at);
