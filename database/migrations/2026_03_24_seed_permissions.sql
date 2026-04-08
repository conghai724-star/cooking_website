USE cooking_website;

INSERT IGNORE INTO roles (id, role_name, description) VALUES
(1, 'user', 'Tài khoản thành viên mặc định'),
(2, 'admin', 'Quản trị viên hệ thống'),
(3, 'moderator', 'Kiểm duyệt nội dung');

INSERT IGNORE INTO permissions (id, permission_name, description) VALUES
(1,  'admin.dashboard.view',              'Xem trang tổng quan quản trị'),
(2,  'admin.users.manage',                'Quản lý người dùng'),
(3,  'admin.roles.manage',                'Quản lý vai trò và phân quyền'),
(4,  'admin.categories.manage',           'Quản lý danh mục'),
(5,  'admin.recipes.review',              'Duyệt hoặc từ chối công thức'),
(6,  'admin.recipes.manage',              'Sửa hoặc xóa công thức'),
(7,  'admin.ingredients.review',          'Duyệt hoặc từ chối nguyên liệu'),
(8,  'admin.ingredients.manage',          'Sửa hoặc xóa nguyên liệu'),
(9,  'admin.tips.review',                 'Duyệt hoặc từ chối mẹo vặt'),
(10, 'admin.tips.manage',                 'Sửa hoặc xóa mẹo vặt'),
(11, 'admin.comments.manage',             'Ẩn, hiện hoặc xóa bình luận'),
(12, 'admin.reports.manage',              'Xử lý báo cáo vi phạm'),
(13, 'admin.mealplans.manage',            'Xem và quản trị meal plan hệ thống'),
(14, 'admin.system.logs.view',            'Xem nhật ký hệ thống'),
(15, 'admin.system.notifications.manage', 'Quản lý thông báo hệ thống'),
(16, 'user.profile.update',               'Cập nhật hồ sơ cá nhân'),
(17, 'user.password.change',              'Đổi mật khẩu'),
(18, 'user.email.change',                 'Đổi email có xác thực'),
(19, 'user.follow.manage',                'Theo dõi hoặc hủy theo dõi'),
(20, 'user.recipes.create',               'Tạo công thức mới'),
(21, 'user.recipes.edit',                 'Sửa công thức của chính mình'),
(22, 'user.recipes.delete',               'Xóa công thức của chính mình'),
(23, 'user.recipes.submit',               'Gửi công thức chờ duyệt'),
(24, 'user.ingredients.suggest',          'Góp ý nguyên liệu'),
(25, 'user.tips.suggest',                 'Góp ý mẹo vặt'),
(26, 'user.comments.create',              'Tạo bình luận'),
(27, 'user.comments.reply',               'Trả lời bình luận'),
(28, 'user.comments.report',              'Báo cáo bình luận'),
(29, 'user.recipes.report',               'Báo cáo công thức'),
(30, 'user.mealplans.manage',             'Lập và quản lý kế hoạch bữa ăn'),
(31, 'user.mealplans.share',              'Chia sẻ kế hoạch bữa ăn'),
(32, 'user.saved_recipes.manage',         'Lưu hoặc bỏ lưu công thức'),
(33, 'admin.banners.manage',              'Quản lý banner hệ thống'),
(34, 'admin.stats.view',                  'Xem thống kê hệ thống');

INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
(2, 1),  (2, 2),  (2, 3),  (2, 4),  (2, 5),  (2, 6),  (2, 7),  (2, 8),
(2, 9),  (2, 10), (2, 11), (2, 12), (2, 13), (2, 14), (2, 15),
(2, 16), (2, 17), (2, 18), (2, 19), (2, 20), (2, 21), (2, 22), (2, 23),
(2, 24), (2, 25), (2, 26), (2, 27), (2, 28), (2, 29), (2, 30), (2, 31), (2, 32),
(2, 33), (2, 34),
(3, 1), (3, 5), (3, 7), (3, 9), (3, 11), (3, 12),
(1, 16), (1, 17), (1, 18), (1, 19), (1, 20), (1, 21), (1, 22), (1, 23),
(1, 24), (1, 25), (1, 26), (1, 27), (1, 28), (1, 29), (1, 30), (1, 31), (1, 32);
