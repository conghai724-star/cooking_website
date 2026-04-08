-- Drop legacy split comment tables after unified comments migration
-- Safe to run multiple times.

DROP TABLE IF EXISTS tip_comment_reports;
DROP TABLE IF EXISTS ingredient_comment_reports;
DROP TABLE IF EXISTS tip_comments;
DROP TABLE IF EXISTS ingredient_comments;

