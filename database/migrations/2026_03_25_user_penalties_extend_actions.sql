USE cooking_website;

ALTER TABLE user_penalties
    MODIFY action ENUM('warn', 'comment_lock_temp', 'comment_lock_permanent', 'ban_temp', 'ban_permanent') NOT NULL;

