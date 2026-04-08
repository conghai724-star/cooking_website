-- Cooking Website database schema (updated from project requirements)
-- MySQL 8+

CREATE DATABASE IF NOT EXISTS cooking_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cooking_website;

-- =========================
-- Core identity tables
-- =========================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    full_name VARCHAR(100) NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) NULL,
    bio TEXT NULL,
    role ENUM('user', 'admin', 'moderator') NOT NULL DEFAULT 'user',
    status ENUM('active', 'banned') NOT NULL DEFAULT 'active',
    deleted_at DATETIME NULL DEFAULT NULL,
    ban_reason TEXT NULL,
    banned_until DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_status (status),
    INDEX idx_users_deleted_at (deleted_at),
    INDEX idx_users_banned_until (banned_until)
);

CREATE TABLE IF NOT EXISTS user_bans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    banned_by INT NULL,
    reason TEXT NULL,
    ban_type ENUM('temporary', 'permanent') NOT NULL DEFAULT 'temporary',
    ban_until DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_user_bans_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_bans_admin FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_bans_user_active (user_id, is_active),
    INDEX idx_user_bans_until (ban_until)
);

CREATE TABLE IF NOT EXISTS user_penalties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    admin_id INT NULL,
    source_type ENUM('comment', 'recipe', 'tip', 'ingredient', 'account') NOT NULL DEFAULT 'account',
    source_id INT NULL,
    action ENUM('warn', 'comment_lock_temp', 'comment_lock_permanent', 'ban_temp', 'ban_permanent') NOT NULL,
    reason TEXT NULL,
    duration_days INT NULL,
    banned_until DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_penalties_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_penalties_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_penalties_user (user_id),
    INDEX idx_user_penalties_action (action),
    INDEX idx_user_penalties_created_at (created_at)
);

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL
);

CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL
);

CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- =========================
-- Content taxonomy
-- =========================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('recipe', 'ingredient', 'knowledge') NOT NULL DEFAULT 'recipe',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categories_name_type (name, type)
);

-- =========================
-- Recipe domain
-- =========================
CREATE TABLE IF NOT EXISTS recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    image VARCHAR(255) NULL,
    cooking_time INT NULL,
    difficulty ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'easy',
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    view_count INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_recipes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_recipes_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_recipes_user (user_id),
    INDEX idx_recipes_category (category_id),
    INDEX idx_recipes_status (status)
);

CREATE TABLE IF NOT EXISTS recipe_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NOT NULL,
    step_number INT NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) NULL,
    CONSTRAINT fk_recipe_steps_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    UNIQUE KEY uq_recipe_step_no (recipe_id, step_number)
);

CREATE TABLE IF NOT EXISTS ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NULL,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(255) NULL,
    description TEXT NULL,
    usage TEXT NULL,
    preparation TEXT NULL,
    storage TEXT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    source ENUM('library', 'recipe') NOT NULL DEFAULT 'library',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ingredients_name_source (name, source),
    CONSTRAINT fk_ingredients_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS ingredient_nutrition (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_id INT NOT NULL,
    calories FLOAT NULL,
    protein FLOAT NULL,
    fat FLOAT NULL,
    carb FLOAT NULL,
    CONSTRAINT fk_nutrition_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE,
    UNIQUE KEY uq_nutrition_ingredient (ingredient_id)
);

CREATE TABLE IF NOT EXISTS recipe_ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    quantity VARCHAR(50) NULL,
    unit VARCHAR(50) NULL,
    CONSTRAINT fk_recipe_ingredients_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    CONSTRAINT fk_recipe_ingredients_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE,
    UNIQUE KEY uq_recipe_ingredient (recipe_id, ingredient_id)
);

-- =========================
-- Interaction tables
-- =========================
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NULL,
    content_type ENUM('recipe', 'tip', 'ingredient') NOT NULL DEFAULT 'recipe',
    content_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_id INT NULL,
    content TEXT NOT NULL,
    status ENUM('visible', 'hidden') NOT NULL DEFAULT 'visible',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_parent FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    INDEX idx_comments_recipe (recipe_id),
    INDEX idx_comments_content (content_type, content_id),
    INDEX idx_comments_user (user_id)
);

CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NOT NULL,
    user_id INT NOT NULL,
    star TINYINT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ratings_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    CONSTRAINT fk_ratings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT ck_ratings_star CHECK (star BETWEEN 1 AND 5),
    UNIQUE KEY uq_rating_recipe_user (recipe_id, user_id)
);

CREATE TABLE IF NOT EXISTS saved_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    item_type ENUM('recipe', 'ingredient', 'tip') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_saved_items_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_saved_items_user (user_id),
    INDEX idx_saved_items_type_id (item_type, item_id)
);

CREATE TABLE IF NOT EXISTS meal_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_date DATE NOT NULL,
    recipe_id INT NOT NULL,
    meal_type ENUM('breakfast', 'lunch', 'dinner') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_meal_plans_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_meal_plans_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    UNIQUE KEY uq_meal_plan (user_id, plan_date, meal_type)
);

CREATE TABLE IF NOT EXISTS meal_plan_settings (
    user_id INT NOT NULL PRIMARY KEY,
    visibility ENUM('private', 'public', 'followers', 'friends', 'link') NOT NULL DEFAULT 'private',
    share_token VARCHAR(64) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_meal_plan_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_meal_plan_settings_token (share_token)
);

CREATE TABLE IF NOT EXISTS meal_plan_week_locks (
    user_id INT NOT NULL,
    week_start_date DATE NOT NULL,
    is_locked TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, week_start_date),
    CONSTRAINT fk_meal_plan_week_locks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS meal_plan_day_locks (
    user_id INT NOT NULL,
    lock_date DATE NOT NULL,
    is_locked TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, lock_date),
    CONSTRAINT fk_meal_plan_day_locks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Keep compatibility with existing app model FollowModel (table `follows`)
CREATE TABLE IF NOT EXISTS follows (
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (follower_id, following_id),
    CONSTRAINT fk_follows_follower FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_follows_following FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT ck_follows_not_self CHECK (follower_id <> following_id)
);

-- Reports table for reporting recipes
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    recipe_id INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'reviewed', 'resolved') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reports_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    UNIQUE KEY uq_reports_once (reporter_id, recipe_id),
    INDEX idx_reports_recipe (recipe_id),
    INDEX idx_reports_reporter (reporter_id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user_read (user_id, is_read)
);

-- =========================
-- Seed data
-- =========================
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
-- admin: toàn quyền trong danh sách hiện có
(2, 1),  (2, 2),  (2, 3),  (2, 4),  (2, 5),  (2, 6),  (2, 7),  (2, 8),
(2, 9),  (2, 10), (2, 11), (2, 12), (2, 13), (2, 14), (2, 15),
(2, 16), (2, 17), (2, 18), (2, 19), (2, 20), (2, 21), (2, 22), (2, 23),
(2, 24), (2, 25), (2, 26), (2, 27), (2, 28), (2, 29), (2, 30), (2, 31), (2, 32),
(2, 33), (2, 34),

-- moderator: tập trung kiểm duyệt nội dung
(3, 1), (3, 5), (3, 7), (3, 9), (3, 11), (3, 12),

-- user: chức năng phía người dùng
(1, 16), (1, 17), (1, 18), (1, 19), (1, 20), (1, 21), (1, 22), (1, 23),
(1, 24), (1, 25), (1, 26), (1, 27), (1, 28), (1, 29), (1, 30), (1, 31), (1, 32);

INSERT IGNORE INTO categories (id, name, type) VALUES
(1, 'Vietnamese', 'recipe'),
(2, 'Dessert', 'recipe'),
(3, 'Healthy', 'recipe'),
(4, 'Gia vị', 'ingredient'),
(5, 'Kitchen Tips', 'knowledge'),
(6, 'Rau củ', 'ingredient'),
(7, 'Thịt', 'ingredient'),
(8, 'Hải sản', 'ingredient');
