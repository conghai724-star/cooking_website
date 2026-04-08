USE cooking_website;

START TRANSACTION;

-- Normalize old role values to new 4-role model
UPDATE users SET role = 'super_admin' WHERE role = 'admin';
UPDATE users SET role = 'mod' WHERE role = 'moderator';

-- Keep only 4 roles in roles table
DELETE FROM roles WHERE role_name IN ('admin', 'moderator');

INSERT INTO roles (role_name, description)
SELECT 'user', 'Tài khoản thành viên mặc định'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE role_name = 'user');

INSERT INTO roles (role_name, description)
SELECT 'super_admin', 'Toàn quyền hệ thống'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE role_name = 'super_admin');

INSERT INTO roles (role_name, description)
SELECT 'mod', 'Kiểm duyệt nội dung và báo cáo'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE role_name = 'mod');

INSERT INTO roles (role_name, description)
SELECT 'support', 'Hỗ trợ xử lý báo cáo và thông báo'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE role_name = 'support');

COMMIT;
