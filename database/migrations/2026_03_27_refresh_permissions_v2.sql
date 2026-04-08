USE cooking_website;

START TRANSACTION;

-- Ensure 4 roles exist
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

-- Reset permissions IDs from 1
DELETE FROM permissions;
ALTER TABLE permissions AUTO_INCREMENT = 1;

INSERT INTO permissions (id, permission_name, description) VALUES
(1,  'admin.dashboard.view', 'Xem trang tổng quan quản trị'),
(2,  'admin.users.view', 'Xem danh sách người dùng'),
(3,  'admin.users.manage', 'Quản lý người dùng'),
(4,  'admin.users.ban', 'Ban/mở ban/xóa mềm/khôi phục tài khoản user'),
(5,  'admin.users.role.assign', 'Gán vai trò cho tài khoản'),
(6,  'admin.roles.manage', 'Quản lý vai trò và phân quyền'),
(7,  'admin.categories.manage', 'Quản lý danh mục'),
(8,  'admin.recipes.review', 'Duyệt hoặc từ chối công thức'),
(9,  'admin.recipes.manage', 'Sửa hoặc xóa công thức'),
(10, 'admin.ingredients.review', 'Duyệt hoặc từ chối nguyên liệu'),
(11, 'admin.ingredients.manage', 'Sửa hoặc xóa nguyên liệu'),
(12, 'admin.tips.review', 'Duyệt hoặc từ chối mẹo vặt'),
(13, 'admin.tips.manage', 'Sửa hoặc xóa mẹo vặt'),
(14, 'admin.comments.moderate', 'Ẩn, hiện hoặc xóa bình luận'),
(15, 'admin.reports.view', 'Xem danh sách báo cáo vi phạm'),
(16, 'admin.reports.resolve', 'Xử lý báo cáo vi phạm'),
(17, 'admin.notifications.manage', 'Quản lý thông báo hệ thống'),
(18, 'admin.banners.manage', 'Quản lý banner hệ thống'),
(19, 'admin.stats.view', 'Xem thống kê hệ thống'),
(20, 'admin.logs.view', 'Xem nhật ký hệ thống'),
(21, 'admin.mealplans.view', 'Xem meal plan hệ thống'),
(22, 'admin.mealplans.moderate', 'Điều phối/quản trị meal plan hệ thống'),
(23, 'admin.relationships.view', 'Xem dữ liệu mối quan hệ follow'),
(24, 'admin.relationships.moderate', 'Xử lý mối quan hệ follow và khóa follow'),
(25, 'user.profile.manage', 'Quản lý hồ sơ cá nhân'),
(26, 'user.follow.manage', 'Theo dõi hoặc hủy theo dõi'),
(27, 'user.recipes.create', 'Tạo công thức mới'),
(28, 'user.recipes.edit_own', 'Sửa công thức của chính mình'),
(29, 'user.recipes.delete_own', 'Xóa công thức của chính mình'),
(30, 'user.recipes.submit', 'Gửi công thức chờ duyệt'),
(31, 'user.recipes.save', 'Lưu hoặc bỏ lưu công thức'),
(32, 'user.recipes.report', 'Báo cáo công thức'),
(33, 'user.ingredients.suggest', 'Gợi ý nguyên liệu'),
(34, 'user.ingredients.resubmit_own', 'Gửi lại nguyên liệu của chính mình'),
(35, 'user.tips.suggest', 'Gợi ý mẹo vặt'),
(36, 'user.tips.resubmit_own', 'Gửi lại mẹo vặt của chính mình'),
(37, 'user.comments.create', 'Tạo bình luận'),
(38, 'user.comments.reply', 'Trả lời bình luận'),
(39, 'user.comments.report', 'Báo cáo bình luận'),
(40, 'user.mealplans.manage', 'Lập và quản lý kế hoạch bữa ăn'),
(41, 'user.mealplans.lock', 'Khóa kế hoạch bữa ăn'),
(42, 'user.mealplans.share', 'Chia sẻ kế hoạch bữa ăn'),
(43, 'user.ingredients.save', 'Lưu hoặc bỏ lưu nguyên liệu'),
(44, 'user.tips.save', 'Lưu hoặc bỏ lưu mẹo vặt'),
(45, 'user.ingredients.report', 'Báo cáo nguyên liệu'),
(46, 'user.tips.report', 'Báo cáo mẹo vặt');

-- Rebuild role_permissions
DELETE FROM role_permissions;

-- super_admin: all permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'super_admin';

-- mod
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_name IN (
    'admin.dashboard.view',
    'admin.users.view',
    'admin.users.ban',
    'admin.categories.manage',
    'admin.recipes.review',
    'admin.ingredients.review',
    'admin.tips.review',
    'admin.comments.moderate',
    'admin.reports.view',
    'admin.reports.resolve',
    'admin.notifications.manage',
    'admin.logs.view',
    'admin.mealplans.view',
    'admin.relationships.view',
    'admin.relationships.moderate'
)
WHERE r.role_name = 'mod';

-- support
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_name IN (
    'admin.dashboard.view',
    'admin.users.view',
    'admin.reports.view',
    'admin.reports.resolve',
    'admin.notifications.manage',
    'admin.stats.view',
    'admin.logs.view',
    'admin.mealplans.view',
    'admin.relationships.view'
)
WHERE r.role_name = 'support';

-- user
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_name IN (
    'user.profile.manage',
    'user.follow.manage',
    'user.recipes.create',
    'user.recipes.edit_own',
    'user.recipes.delete_own',
    'user.recipes.submit',
    'user.recipes.save',
    'user.recipes.report',
    'user.ingredients.suggest',
    'user.ingredients.resubmit_own',
    'user.tips.suggest',
    'user.tips.resubmit_own',
    'user.comments.create',
    'user.comments.reply',
    'user.comments.report',
    'user.mealplans.manage',
    'user.mealplans.lock',
    'user.mealplans.share',
    'user.ingredients.save',
    'user.tips.save',
    'user.ingredients.report',
    'user.tips.report'
)
WHERE r.role_name = 'user';

COMMIT;

-- Verify
SELECT COUNT(*) AS total_permissions FROM permissions;
SELECT MIN(id) AS min_id, MAX(id) AS max_id FROM permissions;
