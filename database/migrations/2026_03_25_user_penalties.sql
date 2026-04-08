USE cooking_website;

CREATE TABLE IF NOT EXISTS user_penalties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    admin_id INT NULL,
    source_type ENUM('comment', 'recipe', 'tip', 'ingredient', 'account') NOT NULL DEFAULT 'account',
    source_id INT NULL,
    action ENUM('warn', 'comment_lock_temp', 'comment_lock_permanent', 'ban_temp', 'ban_permanent') NOT NULL,
    reason TEXT NULL,
    duration_days INT NULL,
    banned_until DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_penalties_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_penalties_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_penalties_user (user_id),
    INDEX idx_user_penalties_action (action),
    INDEX idx_user_penalties_created_at (created_at)
);
