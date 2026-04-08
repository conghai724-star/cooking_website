USE cooking_website;

ALTER TABLE user_penalties
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER banned_until;

UPDATE user_penalties
SET is_active = 0
WHERE action IN ('comment_lock_temp', 'recipe_post_lock_temp', 'ban_temp')
  AND banned_until IS NOT NULL
  AND banned_until <= NOW();

