-- Chuẩn hóa UTF-8 toàn bộ cơ sở dữ liệu (MySQL 8+)
-- Quy trình an toàn:
-- 1) Sao lưu DB trước.
-- 2) Chạy phần A.
-- 3) Chạy các lệnh SQL được sinh ra ở phần B.
-- 4) (Tùy chọn) chạy script PHP để sửa dữ liệu mojibake cũ.

-- A) Chuẩn hóa charset/collation mặc định của database
SET @db := DATABASE();
SELECT @db AS active_database;

-- Nếu active_database là NULL, chạy: USE your_database_name;
SET @sql_db := CONCAT(
    'ALTER DATABASE `', @db, '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
);
PREPARE stmt_db FROM @sql_db;
EXECUTE stmt_db;
DEALLOCATE PREPARE stmt_db;

-- B) Sinh lệnh ALTER TABLE cho tất cả bảng trong DB hiện tại
-- Copy kết quả và chạy.
SELECT CONCAT(
    'ALTER TABLE `', TABLE_SCHEMA, '`.`', TABLE_NAME,
    '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
) AS alter_sql
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = @db
  AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME;

-- C) Kiểm tra collation hiện tại của từng bảng
SELECT
    t.TABLE_NAME,
    t.TABLE_COLLATION
FROM information_schema.TABLES t
WHERE t.TABLE_SCHEMA = @db
ORDER BY t.TABLE_NAME;

-- D) Kiểm tra cột text chưa ở utf8mb4 (kết quả nên rỗng sau khi convert)
SELECT
    c.TABLE_NAME,
    c.COLUMN_NAME,
    c.CHARACTER_SET_NAME,
    c.COLLATION_NAME,
    c.DATA_TYPE
FROM information_schema.COLUMNS c
WHERE c.TABLE_SCHEMA = @db
  AND c.DATA_TYPE IN ('char', 'varchar', 'text', 'tinytext', 'mediumtext', 'longtext')
  AND (
      c.CHARACTER_SET_NAME <> 'utf8mb4'
      OR c.COLLATION_NAME NOT LIKE 'utf8mb4\_%'
  )
ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION;
