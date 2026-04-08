USE cooking_website;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL DEFAULT NULL AFTER status;

CREATE INDEX IF NOT EXISTS idx_users_deleted_at ON users(deleted_at);
