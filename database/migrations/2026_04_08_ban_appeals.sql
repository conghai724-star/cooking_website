USE cooking_website;

CREATE TABLE IF NOT EXISTS ban_appeals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    target_type ENUM('user_ban', 'user_penalty') NOT NULL,
    target_id INT NOT NULL,
    appeal_reason TEXT NOT NULL,
    evidence_text TEXT NULL,
    status ENUM('pending', 'reviewing', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_note TEXT NULL,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ban_appeals_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ban_appeals_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ban_appeals_user_target_status (user_id, target_type, target_id, status),
    INDEX idx_ban_appeals_status_created (status, created_at),
    INDEX idx_ban_appeals_target (target_type, target_id)
);
