USE cooking_website;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS ban_reason TEXT NULL AFTER deleted_at,
    ADD COLUMN IF NOT EXISTS banned_until DATETIME NULL DEFAULT NULL AFTER ban_reason;

CREATE INDEX IF NOT EXISTS idx_users_banned_until ON users(banned_until);
