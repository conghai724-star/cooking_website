# Từ Điển CSDL (Schema)

- Nguồn: `d:\ncHAI_DATN\cooking_website.sql`
- Cập nhật: 2026-03-29 00:46:38

## Danh sách bảng

- `admin_action_logs`
- `admin_notification_campaigns`
- `categories`
- `comments`
- `email_change_requests`
- `follows`
- `home_featured_recipes`
- `home_recipe_of_day`
- `ingredients`
- `ingredient_nutrition`
- `login_attempts`
- `meal_plans`
- `meal_plan_day_locks`
- `meal_plan_settings`
- `meal_plan_week_locks`
- `moderation_reports`
- `notifications`
- `permissions`
- `ratings`
- `recipe_ingredients`
- `recipe_steps`
- `reports`
- `roles`
- `role_permissions`
- `saved_items`
- `system_logs`
- `tips`
- `tip_saves`
- `users`
- `user_bans`
- `user_blocks`
- `user_penalties`
- `user_roles`

## Bảng `admin_action_logs`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `admin_id` | `int(11) DEFAULT NULL` | YES | `NULL` | ID tham chiếu |
| `action_key` | `varchar(100) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `target_type` | `varchar(50) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `target_id` | `int(11) DEFAULT NULL` | YES | `NULL` | ID tham chiếu |
| `details` | `text DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |

## Bảng `admin_notification_campaigns`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `created_by` | `int(11) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `title` | `varchar(255) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `message` | `text NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `action_url` | `varchar(255) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `target_scope` | `enum('all','role','users') NOT NULL DEFAULT 'all'` | NO | `'all'` | Thông tin dữ liệu |
| `target_value` | `text DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `sent_count` | `int(11) NOT NULL DEFAULT 0` | NO | `0` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |

## Bảng `categories`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `name` | `varchar(100) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `type` | `enum('recipe','ingredient','knowledge') NOT NULL DEFAULT 'recipe'` | NO | `'recipe'` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |

## Bảng `comments`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `recipe_id` | `int(11) DEFAULT NULL` | YES | `NULL` | ID tham chiếu |
| `content_type` | `enum('recipe','tip','ingredient') NOT NULL DEFAULT 'recipe'` | NO | `'recipe'` | Thông tin dữ liệu |
| `content_id` | `int(11) DEFAULT NULL` | YES | `NULL` | ID tham chiếu |
| `user_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `parent_id` | `int(11) DEFAULT NULL` | YES | `NULL` | ID tham chiếu |
| `content` | `text NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `like_count` | `int(11) NOT NULL DEFAULT 0` | NO | `0` | Thông tin dữ liệu |
| `reply_count` | `int(11) NOT NULL DEFAULT 0` | NO | `0` | Thông tin dữ liệu |
| `status` | `enum('active','hidden','deleted') NOT NULL DEFAULT 'active'` | NO | `'active'` | Trạng thái |
| `is_edited` | `tinyint(1) NOT NULL DEFAULT 0` | NO | `0` | Thông tin dữ liệu |
| `edited_at` | `datetime DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `deleted_at` | `datetime DEFAULT NULL` | YES | `NULL` | Ngày xóa mềm |
| `deleted_by` | `int(11) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `delete_reason` | `varchar(255) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |
| `updated_at` | `datetime DEFAULT NULL ON UPDATE current_timestamp()` | YES | `NULL` | Ngày cập nhật |

## Bảng `email_change_requests`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `user_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `new_email` | `varchar(255) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `token_hash` | `char(64) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `expires_at` | `datetime NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `used_at` | `datetime DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |

## Bảng `follows`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `follower_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `following_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `title` | `varchar(255) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `subtitle` | `text DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `image_url` | `varchar(255) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `cta_text` | `varchar(80) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `cta_url` | `varchar(255) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `is_active` | `tinyint(1) NOT NULL DEFAULT 1` | NO | `1` | Trạng thái kích hoạt |
| `start_at` | `datetime DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `end_at` | `datetime DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `updated_at` | `datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()` | NO | `current_timestamp()` | Ngày cập nhật |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |

## Bảng `home_featured_recipes`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `recipe_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `sort_order` | `int(11) NOT NULL DEFAULT 0` | NO | `0` | Thông tin dữ liệu |
| `is_active` | `tinyint(1) NOT NULL DEFAULT 1` | NO | `1` | Trạng thái kích hoạt |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |

## Bảng `home_recipe_of_day`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `for_date` | `date NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `recipe_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `updated_at` | `datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()` | NO | `current_timestamp()` | Ngày cập nhật |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |

## Bảng `ingredients`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `category_id` | `int(11) DEFAULT NULL` | YES | `NULL` | ID tham chiếu |
| `name` | `varchar(100) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `image` | `varchar(255) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `description` | `text DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `preparation` | `text DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `storage` | `text DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |
| `status` | `enum('pending','approved','rejected') NOT NULL DEFAULT 'approved'` | NO | `'approved'` | Trạng thái |
| `rejection_reason` | `text DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `user_id` | `int(11) DEFAULT NULL` | YES | `NULL` | ID tham chiếu |

## Bảng `ingredient_nutrition`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `ingredient_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `calories` | `float DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `protein` | `float DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `fat` | `float DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `carb` | `float DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |

## Bảng `login_attempts`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `credential` | `varchar(255) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `ip_address` | `varchar(45) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `failed_count` | `int(11) NOT NULL DEFAULT 0` | NO | `0` | Thông tin dữ liệu |
| `lock_until` | `datetime DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `last_attempt_at` | `datetime DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `updated_at` | `datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()` | NO | `current_timestamp()` | Ngày cập nhật |

## Bảng `meal_plans`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `user_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `plan_date` | `date NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `recipe_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `meal_type` | `enum('breakfast','lunch','dinner') NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `dish_role` | `enum('main','side','soup','dessert','drink','other') NOT NULL DEFAULT 'main'` | NO | `'main'` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |

## Bảng `meal_plan_day_locks`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `user_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `lock_date` | `date NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `is_locked` | `tinyint(1) NOT NULL DEFAULT 0` | NO | `0` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |
| `updated_at` | `datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()` | NO | `current_timestamp()` | Ngày cập nhật |

## Bảng `meal_plan_settings`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `user_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `visibility` | `enum('private','public','followers','friends','link') NOT NULL DEFAULT 'private'` | NO | `'private'` | Thông tin dữ liệu |
| `share_token` | `varchar(64) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |
| `updated_at` | `datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()` | NO | `current_timestamp()` | Ngày cập nhật |

## Bảng `meal_plan_week_locks`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `user_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `week_start_date` | `date NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `is_locked` | `tinyint(1) NOT NULL DEFAULT 0` | NO | `0` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |
| `updated_at` | `datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()` | NO | `current_timestamp()` | Ngày cập nhật |

## Bảng `moderation_reports`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `reporter_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `target_type` | `enum('recipe','comment','user') NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `target_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `context_type` | `enum('recipe','tip','ingredient','account') NOT NULL DEFAULT 'account'` | NO | `'account'` | Thông tin dữ liệu |
| `reason` | `text NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `details` | `text DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `status` | `enum('pending','reviewed','resolved') NOT NULL DEFAULT 'pending'` | NO | `'pending'` | Trạng thái |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |

## Bảng `notifications`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `user_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `type` | `varchar(50) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `message` | `text NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `action_url` | `varchar(255) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `is_read` | `tinyint(1) NOT NULL DEFAULT 0` | NO | `0` | Trạng thái đã đọc |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |

## Bảng `permissions`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `permission_name` | `varchar(100) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `description` | `text DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |

## Bảng `ratings`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `recipe_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `user_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `star` | `tinyint(4) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `user_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `category_id` | `int(11) DEFAULT NULL` | YES | `NULL` | ID tham chiếu |
| `title` | `varchar(255) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `description` | `text NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `image` | `varchar(255) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `cooking_time` | `int(11) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `difficulty` | `enum('easy','medium','hard') NOT NULL DEFAULT 'easy'` | NO | `'easy'` | Thông tin dữ liệu |
| `status` | `varchar(20) DEFAULT 'draft'` | YES | `'draft'` | Trạng thái |
| `user_state` | `varchar(20) DEFAULT 'draft'` | YES | `'draft'` | Thông tin dữ liệu |
| `view_count` | `int(11) NOT NULL DEFAULT 0` | NO | `0` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |
| `updated_at` | `datetime DEFAULT NULL ON UPDATE current_timestamp()` | YES | `NULL` | Ngày cập nhật |
| `deleted_at` | `datetime DEFAULT NULL` | YES | `NULL` | Ngày xóa mềm |

## Bảng `recipe_ingredients`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `recipe_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `ingredient_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `quantity` | `varchar(50) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `unit` | `varchar(50) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |

## Bảng `recipe_steps`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `recipe_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `step_number` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `content` | `text NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `image` | `varchar(255) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |

## Bảng `reports`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `reporter_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `recipe_id` | `int(11) DEFAULT NULL` | YES | `NULL` | ID tham chiếu |
| `target_type` | `varchar(20) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `target_id` | `int(11) DEFAULT NULL` | YES | `NULL` | ID tham chiếu |
| `reason` | `text NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `details` | `text DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `status` | `enum('pending','reviewed','resolved') NOT NULL DEFAULT 'pending'` | NO | `'pending'` | Trạng thái |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |
| `updated_at` | `datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()` | NO | `current_timestamp()` | Ngày cập nhật |

## Bảng `roles`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `role_name` | `varchar(50) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `description` | `text DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |

## Bảng `role_permissions`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `role_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `permission_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |

## Bảng `saved_items`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `user_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `item_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `item_type` | `enum('recipe','ingredient') NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |

## Bảng `system_logs`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `bigint(20) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `event_type` | `varchar(50) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `action_key` | `varchar(120) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `actor_id` | `int(11) DEFAULT NULL` | YES | `NULL` | ID tham chiếu |
| `actor_role` | `varchar(50) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `target_type` | `varchar(50) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `target_id` | `int(11) DEFAULT NULL` | YES | `NULL` | ID tham chiếu |
| `result` | `varchar(20) NOT NULL DEFAULT 'success'` | NO | `'success'` | Thông tin dữ liệu |
| `reason` | `varchar(255) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `meta_json` | `text DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `ip_address` | `varchar(45) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `user_agent` | `varchar(255) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |

## Bảng `tips`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `user_id` | `int(11) DEFAULT NULL` | YES | `NULL` | ID tham chiếu |
| `title` | `varchar(255) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `slug` | `varchar(255) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `excerpt` | `text DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `content` | `longtext NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `cover_image` | `varchar(255) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `author_name` | `varchar(150) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `status` | `enum('draft','pending','approved','rejected') NOT NULL DEFAULT 'pending'` | NO | `'pending'` | Trạng thái |
| `view_count` | `int(11) NOT NULL DEFAULT 0` | NO | `0` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |
| `updated_at` | `datetime DEFAULT NULL ON UPDATE current_timestamp()` | YES | `NULL` | Ngày cập nhật |
| `rejection_reason` | `text DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |

## Bảng `tip_saves`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `user_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `tip_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |

## Bảng `users`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | ID người dùng |
| `username` | `varchar(50) DEFAULT NULL` | YES | `NULL` | Tên đăng nhập |
| `name` | `varchar(120) NOT NULL` | NO | `NULL` | Họ tên |
| `full_name` | `varchar(100) DEFAULT NULL` | YES | `NULL` | Họ tên đầy đủ (bản mở rộng) |
| `email` | `varchar(150) NOT NULL` | NO | `NULL` | Email |
| `password` | `varchar(255) NOT NULL` | NO | `NULL` | Mật khẩu mã hóa |
| `avatar` | `varchar(255) DEFAULT NULL` | YES | `NULL` | Ảnh đại diện |
| `bio` | `text DEFAULT NULL` | YES | `NULL` | Giới thiệu |
| `role` | `varchar(50) NOT NULL DEFAULT 'user'` | NO | `'user'` | Vai trò tài khoản |
| `status` | `enum('active','banned') NOT NULL DEFAULT 'active'` | NO | `'active'` | Trạng thái tài khoản |
| `deleted_at` | `datetime DEFAULT NULL` | YES | `NULL` | Thời điểm xóa mềm tài khoản |
| `ban_reason` | `text DEFAULT NULL` | YES | `NULL` | Lý do khóa tài khoản |
| `banned_until` | `datetime DEFAULT NULL` | YES | `NULL` | Thời hạn khóa tài khoản |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |
| `updated_at` | `datetime DEFAULT NULL ON UPDATE current_timestamp()` | YES | `NULL` | Ngày cập nhật |

## Bảng `user_bans`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `user_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `banned_by` | `int(11) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `reason` | `text DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `ban_type` | `enum('temporary','permanent') NOT NULL DEFAULT 'temporary'` | NO | `'temporary'` | Thông tin dữ liệu |
| `ban_until` | `datetime DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |
| `updated_at` | `datetime DEFAULT NULL ON UPDATE current_timestamp()` | YES | `NULL` | Ngày cập nhật |
| `is_active` | `tinyint(1) NOT NULL DEFAULT 1` | NO | `1` | Trạng thái kích hoạt |

## Bảng `user_blocks`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `blocker_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `blocked_user_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |

## Bảng `user_penalties`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `id` | `int(11) NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `user_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `admin_id` | `int(11) DEFAULT NULL` | YES | `NULL` | ID tham chiếu |
| `source_type` | `enum('comment','recipe','tip','ingredient','account') NOT NULL DEFAULT 'account'` | NO | `'account'` | Thông tin dữ liệu |
| `source_id` | `int(11) DEFAULT NULL` | YES | `NULL` | ID tham chiếu |
| `action` | `enum('warn','comment_lock_temp','comment_lock_permanent','recipe_post_lock_temp','recipe_post_lock_permanent','tip_post_lock_temp','tip_post_lock_permanent','ingredient_post_lock_temp','ingredient_post_lock_permanent','follow_lock_temp','follow_lock_permanent','ban_temp','ban_permanent') NOT NULL` | NO | `NULL` | Thông tin dữ liệu |
| `reason` | `text DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `duration_days` | `int(11) DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `banned_until` | `datetime DEFAULT NULL` | YES | `NULL` | Thông tin dữ liệu |
| `created_at` | `datetime NOT NULL DEFAULT current_timestamp()` | NO | `current_timestamp()` | Ngày tạo |
| `is_active` | `tinyint(1) NOT NULL DEFAULT 1` | NO | `1` | Trạng thái kích hoạt |

## Bảng `user_roles`

| Thuộc tính | Kiểu dữ liệu | Null | Mặc định | Mô tả |
|---|---|---|---|---|
| `user_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |
| `role_id` | `int(11) NOT NULL` | NO | `NULL` | ID tham chiếu |

