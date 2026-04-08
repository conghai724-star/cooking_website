-- UTF-8 normalization baseline
-- Replace `cooking_website` if your database name is different.

ALTER DATABASE `cooking_website`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Optional: normalize existing tables (run only if needed, one by one)
-- ALTER TABLE `users` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
