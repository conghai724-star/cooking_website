USE cooking_website;

ALTER TABLE users
MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'user';
