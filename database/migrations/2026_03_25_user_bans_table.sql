USE cooking_website;

CREATE TABLE IF NOT EXISTS user_bans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    banned_by INT NULL,
    reason TEXT NULL,
    ban_type ENUM('temporary', 'permanent') NOT NULL DEFAULT 'temporary',
    ban_until DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_user_bans_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_bans_admin FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_bans_user_active (user_id, is_active),
    INDEX idx_user_bans_until (ban_until)
);
