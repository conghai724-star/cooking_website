-- Merge legacy tip_saves into shared saved_items.

CREATE TABLE IF NOT EXISTS saved_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    item_type ENUM('recipe', 'ingredient', 'tip') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_saved_items_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_saved_items_once (user_id, item_id, item_type),
    INDEX idx_saved_items_user (user_id),
    INDEX idx_saved_items_type_id (item_type, item_id)
);

ALTER TABLE saved_items
    MODIFY item_type ENUM('recipe', 'ingredient', 'tip') NOT NULL;

INSERT IGNORE INTO saved_items (user_id, item_id, item_type, created_at)
SELECT ts.user_id, ts.tip_id, 'tip', ts.created_at
FROM tip_saves ts;

-- Optional cleanup once code no longer depends on tip_saves:
-- DROP TABLE IF EXISTS tip_saves;
