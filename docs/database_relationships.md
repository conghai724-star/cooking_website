# Bảng mối quan hệ CSDL

- Nguồn: khóa ngoại thực tế trong MySQL `cooking_website`
- Cập nhật: 2026-03-30 01:06:38

| STT | Bảng 1 | Bảng 2 | Loại | Bảng trung gian / FK | Mô tả |
|---|---|---|---|---|---|
| 1 | categories (danh mục) | ingredients (nguyên liệu) | 1 - N | ingredients.category_id -> categories.id | Một danh mục có thể có nhiều nguyên liệu. |
| 2 | categories (danh mục) | recipes (công thức) | 1 - N | recipes.category_id -> categories.id | Một danh mục có thể có nhiều công thức. |
| 3 | comments (bình luận) | comments (bình luận) | 1 - N (đệ quy) | comments.parent_id -> comments.id | Một bình luận có thể liên kết nhiều bình luận cùng bảng. |
| 4 | ingredients (nguyên liệu) | ingredient_nutrition (dinh dưỡng nguyên liệu) | 1 - N | ingredient_nutrition.ingredient_id -> ingredients.id | Một nguyên liệu có thể có nhiều dinh dưỡng nguyên liệu. |
| 5 | ingredients (nguyên liệu) | recipe_ingredients (nguyên liệu công thức) | 1 - N | recipe_ingredients.ingredient_id -> ingredients.id | Một nguyên liệu có thể có nhiều nguyên liệu công thức. |
| 6 | permissions (quyền) | role_permissions (phân quyền vai trò) | 1 - N | role_permissions.permission_id -> permissions.id | Một quyền có thể có nhiều phân quyền vai trò. |
| 7 | recipes (công thức) | comments (bình luận) | 1 - N | comments.recipe_id -> recipes.id | Một công thức có thể có nhiều bình luận. |
| 8 | recipes (công thức) | meal_plans (kế hoạch bữa ăn) | 1 - N | meal_plans.recipe_id -> recipes.id | Một công thức có thể có nhiều kế hoạch bữa ăn. |
| 9 | recipes (công thức) | ratings (đánh giá) | 1 - N | ratings.recipe_id -> recipes.id | Một công thức có thể có nhiều đánh giá. |
| 10 | recipes (công thức) | recipe_ingredients (nguyên liệu công thức) | 1 - N | recipe_ingredients.recipe_id -> recipes.id | Một công thức có thể có nhiều nguyên liệu công thức. |
| 11 | recipes (công thức) | recipe_steps (bước nấu công thức) | 1 - N | recipe_steps.recipe_id -> recipes.id | Một công thức có thể có nhiều bước nấu công thức. |
| 12 | roles (vai trò) | role_permissions (phân quyền vai trò) | 1 - N | role_permissions.role_id -> roles.id | Một vai trò có thể có nhiều phân quyền vai trò. |
| 13 | roles (vai trò) | user_roles (vai trò người dùng) | 1 - N | user_roles.role_id -> roles.id | Một vai trò có thể có nhiều vai trò người dùng. |
| 14 | tips (mẹo vặt) | tip_saves (lưu mẹo vặt) | 1 - N | tip_saves.tip_id -> tips.id | Một mẹo vặt có thể có nhiều lưu mẹo vặt. |
| 15 | users (người dùng) | comments (bình luận) | 1 - N | comments.deleted_by -> users.id | Một người dùng có thể có nhiều bình luận. |
| 16 | users (người dùng) | comments (bình luận) | 1 - N | comments.user_id -> users.id | Một người dùng có thể có nhiều bình luận. |
| 17 | users (người dùng) | email_change_requests (yêu cầu đổi email) | 1 - N | email_change_requests.user_id -> users.id | Một người dùng có thể có nhiều yêu cầu đổi email. |
| 18 | users (người dùng) | follows (theo dõi) | 1 - N | follows.follower_id -> users.id | Một người dùng có thể có nhiều theo dõi. |
| 19 | users (người dùng) | follows (theo dõi) | 1 - N | follows.following_id -> users.id | Một người dùng có thể có nhiều theo dõi. |
| 20 | users (người dùng) | meal_plans (kế hoạch bữa ăn) | 1 - N | meal_plans.user_id -> users.id | Một người dùng có thể có nhiều kế hoạch bữa ăn. |
| 21 | users (người dùng) | meal_plan_day_locks (khóa ngày kế hoạch bữa ăn) | 1 - N | meal_plan_day_locks.user_id -> users.id | Một người dùng có thể có nhiều khóa ngày kế hoạch bữa ăn. |
| 22 | users (người dùng) | meal_plan_settings (cài đặt kế hoạch bữa ăn) | 1 - N | meal_plan_settings.user_id -> users.id | Một người dùng có thể có nhiều cài đặt kế hoạch bữa ăn. |
| 23 | users (người dùng) | meal_plan_week_locks (khóa tuần kế hoạch bữa ăn) | 1 - N | meal_plan_week_locks.user_id -> users.id | Một người dùng có thể có nhiều khóa tuần kế hoạch bữa ăn. |
| 24 | users (người dùng) | moderation_reports (báo cáo kiểm duyệt) | 1 - N | moderation_reports.reporter_id -> users.id | Một người dùng có thể có nhiều báo cáo kiểm duyệt. |
| 25 | users (người dùng) | notifications (thông báo) | 1 - N | notifications.user_id -> users.id | Một người dùng có thể có nhiều thông báo. |
| 26 | users (người dùng) | ratings (đánh giá) | 1 - N | ratings.user_id -> users.id | Một người dùng có thể có nhiều đánh giá. |
| 27 | users (người dùng) | recipes (công thức) | 1 - N | recipes.user_id -> users.id | Một người dùng có thể có nhiều công thức. |
| 28 | users (người dùng) | reports (báo cáo) | 1 - N | reports.reporter_id -> users.id | Một người dùng có thể có nhiều báo cáo. |
| 29 | users (người dùng) | saved_items (mục đã lưu) | 1 - N | saved_items.user_id -> users.id | Một người dùng có thể có nhiều mục đã lưu. |
| 30 | users (người dùng) | tip_saves (lưu mẹo vặt) | 1 - N | tip_saves.user_id -> users.id | Một người dùng có thể có nhiều lưu mẹo vặt. |
| 31 | users (người dùng) | user_bans (cấm người dùng) | 1 - N | user_bans.banned_by -> users.id | Một người dùng có thể có nhiều cấm người dùng. |
| 32 | users (người dùng) | user_bans (cấm người dùng) | 1 - N | user_bans.user_id -> users.id | Một người dùng có thể có nhiều cấm người dùng. |
| 33 | users (người dùng) | user_blocks (chặn người dùng) | 1 - N | user_blocks.blocked_user_id -> users.id | Một người dùng có thể có nhiều chặn người dùng. |
| 34 | users (người dùng) | user_blocks (chặn người dùng) | 1 - N | user_blocks.blocker_id -> users.id | Một người dùng có thể có nhiều chặn người dùng. |
| 35 | users (người dùng) | user_penalties (xử phạt người dùng) | 1 - N | user_penalties.admin_id -> users.id | Một người dùng có thể có nhiều xử phạt người dùng. |
| 36 | users (người dùng) | user_penalties (xử phạt người dùng) | 1 - N | user_penalties.user_id -> users.id | Một người dùng có thể có nhiều xử phạt người dùng. |
| 37 | users (người dùng) | user_roles (vai trò người dùng) | 1 - N | user_roles.user_id -> users.id | Một người dùng có thể có nhiều vai trò người dùng. |
