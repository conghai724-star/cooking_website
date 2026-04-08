# Cấu trúc bảng CSDL

- Nguồn: MySQL `cooking_website` (127.0.0.1:3307)
- Cập nhật: 2026-03-29 21:54:25

1. admin_action_logs - nhật ký hành động admin

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của hành động admin. |
| admin_id | int(11) | ID đối tượng liên quan. |
| action_key | varchar(100) | Mã hành động admin. |
| target_type | varchar(50) | Loại đối tượng mục tiêu. |
| target_id | int(11) | ID đối tượng bị tác động/báo cáo. |
| details | text | Chi tiết bổ sung của hành động admin. |
| created_at | datetime | Thời điểm tạo hành động admin. |

2. admin_notification_campaigns - chiến dịch thông báo admin

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của chiến dịch thông báo. |
| created_by | int(11) | ID admin tạo chiến dịch. |
| title | varchar(255) | Tiêu đề hiển thị. |
| message | text | Thông điệp hiển thị. |
| action_url | varchar(255) | Đường dẫn điều hướng. |
| target_scope | enum('all','role','users') | Phạm vi áp dụng. |
| target_value | text | Giá trị phạm vi áp dụng. |
| sent_count | int(11) | Số lượng đã gửi. |
| created_at | datetime | Thời điểm tạo chiến dịch thông báo. |

3. categories - danh mục

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của danh mục. |
| name | varchar(100) | Tên hiển thị. |
| type | enum('recipe','ingredient','knowledge') | Loại dữ liệu. |
| created_at | datetime | Thời điểm tạo danh mục. |

4. comments - bình luận

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của bình luận. |
| recipe_id | int(11) (FK) | Khóa ngoại tới recipes.id. |
| content_type | enum('recipe','tip','ingredient') | Loại nội dung. |
| content_id | int(11) | ID nội dung gốc được bình luận. |
| user_id | int(11) (FK) | Khóa ngoại tới users.id. |
| parent_id | int(11) (FK) | Khóa ngoại tới comments.id. |
| content | text | Nội dung chính. |
| like_count | int(11) | Số lượt thích. |
| reply_count | int(11) | Số lượt phản hồi. |
| status | enum('active','hidden','deleted') | Trạng thái của bình luận. |
| is_edited | tinyint(1) | Đánh dấu đã chỉnh sửa. |
| edited_at | datetime | Thời điểm chỉnh sửa. |
| deleted_at | datetime | Thời điểm xóa mềm bình luận. |
| deleted_by | int(11) (FK) | Khóa ngoại tới users.id. |
| delete_reason | varchar(255) | Lý do xóa bình luận. |
| created_at | datetime | Thời điểm tạo bình luận. |
| updated_at | datetime | Thời điểm cập nhật bình luận. |

5. email_change_requests - yêu cầu đổi email

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(10) unsigned (PK) | Định danh duy nhất của yêu cầu đổi email. |
| user_id | int(11) (FK) | Khóa ngoại tới users.id. |
| new_email | varchar(255) | Email mới cần đổi. |
| token_hash | char(64) | Băm token xác thực. |
| expires_at | datetime | Thời điểm hết hạn. |
| used_at | datetime | Thời điểm đã sử dụng. |
| created_at | datetime | Thời điểm tạo yêu cầu đổi email. |

6. follows - theo dõi

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| follower_id | int(11) (PK) (FK) | Khóa ghép, tham chiếu users.id. |
| following_id | int(11) (PK) (FK) | Khóa ghép, tham chiếu users.id. |
| created_at | datetime | Thời điểm tạo quan hệ theo dõi. |

7. home_banners - banner trang chủ

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của banner. |
| title | varchar(255) | Tiêu đề hiển thị. |
| subtitle | text | Mô tả phụ hiển thị. |
| image_url | varchar(255) | Ảnh minh họa. |
| cta_text | varchar(80) | Nhãn nút hành động. |
| cta_url | varchar(255) | Đường dẫn điều hướng. |
| is_active | tinyint(1) | Trạng thái bật/tắt. |
| start_at | datetime | Thời điểm bắt đầu hiệu lực. |
| end_at | datetime | Thời điểm kết thúc hiệu lực. |
| updated_at | datetime | Thời điểm cập nhật banner. |
| created_at | datetime | Thời điểm tạo banner. |

8. home_featured_recipes - công thức nổi bật trang chủ

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của mục công thức nổi bật. |
| recipe_id | int(11) | ID đối tượng liên quan. |
| sort_order | int(11) | Thứ tự hiển thị trên trang chủ. |
| is_active | tinyint(1) | Trạng thái bật/tắt. |
| created_at | datetime | Thời điểm tạo mục công thức nổi bật. |

9. home_recipe_of_day - công thức hôm nay trang chủ

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của mục công thức trong ngày. |
| for_date | date | Ngày áp dụng. |
| recipe_id | int(11) | ID đối tượng liên quan. |
| updated_at | datetime | Thời điểm cập nhật mục công thức trong ngày. |
| created_at | datetime | Thời điểm tạo mục công thức trong ngày. |

10. ingredients - nguyên liệu

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của nguyên liệu. |
| category_id | int(11) (FK) | Khóa ngoại tới categories.id. |
| name | varchar(100) | Tên hiển thị. |
| image | varchar(255) | Ảnh minh họa. |
| description | text | Mô tả nội dung. |
| preparation | text | Hướng dẫn sơ chế nguyên liệu. |
| storage | text | Hướng dẫn bảo quản nguyên liệu. |
| created_at | datetime | Thời điểm tạo nguyên liệu. |
| status | enum('pending','approved','rejected') | Trạng thái của nguyên liệu. |
| rejection_reason | text | Lý do từ chối duyệt. |
| user_id | int(11) | ID đối tượng liên quan. |

11. ingredient_nutrition - dinh dưỡng nguyên liệu

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của hồ sơ dinh dưỡng nguyên liệu. |
| ingredient_id | int(11) (FK) | Khóa ngoại tới ingredients.id. |
| calories | float | Năng lượng ước tính của nguyên liệu. |
| protein | float | Hàm lượng protein của nguyên liệu. |
| fat | float | Hàm lượng chất béo của nguyên liệu. |
| carb | float | Hàm lượng carbohydrate của nguyên liệu. |

12. login_attempts - lần đăng nhập

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(10) unsigned (PK) | Định danh duy nhất của lần đăng nhập. |
| credential | varchar(255) | Email/username dùng để đăng nhập. |
| ip_address | varchar(45) | Địa chỉ IP truy cập. |
| failed_count | int(11) | Số lần đăng nhập thất bại liên tiếp. |
| lock_until | datetime | Thời điểm khóa đăng nhập đến. |
| last_attempt_at | datetime | Thời điểm thử đăng nhập gần nhất. |
| updated_at | datetime | Thời điểm cập nhật lần đăng nhập. |

13. meal_plans - kế hoạch bữa ăn

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của mục kế hoạch bữa ăn. |
| user_id | int(11) (FK) | Khóa ngoại tới users.id. |
| plan_date | date | Ngày áp dụng. |
| recipe_id | int(11) (FK) | Khóa ngoại tới recipes.id. |
| meal_type | enum('breakfast','lunch','dinner') | Loại bữa ăn. |
| dish_role | enum('main','side','soup','dessert','drink','other') | Vai trò món trong bữa ăn. |
| created_at | datetime | Thời điểm tạo mục kế hoạch bữa ăn. |

14. meal_plan_day_locks - khóa ngày kế hoạch bữa ăn

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| user_id | int(11) (PK) (FK) | Khóa ghép, tham chiếu users.id. |
| lock_date | date (PK) | Thuộc khóa chính. |
| is_locked | tinyint(1) | Đánh dấu ngày/tuần đã khóa chỉnh sửa. |
| created_at | datetime | Thời điểm tạo cấu hình khóa ngày. |
| updated_at | datetime | Thời điểm cập nhật cấu hình khóa ngày. |

15. meal_plan_settings - cài đặt kế hoạch bữa ăn

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| user_id | int(11) (PK) (FK) | Khóa ghép, tham chiếu users.id. |
| visibility | enum('private','public','followers','friends','link') | Phạm vi chia sẻ kế hoạch ăn. |
| share_token | varchar(64) | Token dùng cho link chia sẻ kế hoạch ăn. |
| created_at | datetime | Thời điểm tạo cài đặt kế hoạch bữa ăn. |
| updated_at | datetime | Thời điểm cập nhật cài đặt kế hoạch bữa ăn. |

16. meal_plan_week_locks - khóa tuần kế hoạch bữa ăn

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| user_id | int(11) (PK) (FK) | Khóa ghép, tham chiếu users.id. |
| week_start_date | date (PK) | Thuộc khóa chính. |
| is_locked | tinyint(1) | Đánh dấu ngày/tuần đã khóa chỉnh sửa. |
| created_at | datetime | Thời điểm tạo cấu hình khóa tuần. |
| updated_at | datetime | Thời điểm cập nhật cấu hình khóa tuần. |

17. moderation_reports - báo cáo kiểm duyệt

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của báo cáo kiểm duyệt. |
| reporter_id | int(11) (FK) | Khóa ngoại tới users.id. |
| target_type | enum('recipe','comment','user') | Loại đối tượng mục tiêu. |
| target_id | int(11) | ID đối tượng bị tác động/báo cáo. |
| context_type | enum('recipe','tip','ingredient','account') | Ngữ cảnh nơi phát sinh báo cáo. |
| reason | text | Lý do xử lý. |
| details | text | Chi tiết bổ sung của báo cáo kiểm duyệt. |
| status | enum('pending','reviewed','resolved') | Trạng thái của báo cáo kiểm duyệt. |
| created_at | datetime | Thời điểm tạo báo cáo kiểm duyệt. |

18. notifications - thông báo

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của thông báo. |
| user_id | int(11) (FK) | Khóa ngoại tới users.id. |
| type | varchar(50) | Loại dữ liệu. |
| message | text | Thông điệp hiển thị. |
| action_url | varchar(255) | Đường dẫn điều hướng. |
| is_read | tinyint(1) | Đã đọc hay chưa. |
| created_at | datetime | Thời điểm tạo thông báo. |

19. permissions - quyền

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của quyền. |
| permission_name | varchar(100) | Tên quyền dùng trong phân quyền. |
| description | text | Mô tả nội dung. |

20. ratings - đánh giá

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của đánh giá. |
| recipe_id | int(11) (FK) | Khóa ngoại tới recipes.id. |
| user_id | int(11) (FK) | Khóa ngoại tới users.id. |
| star | tinyint(4) | Số sao đánh giá (1-5). |
| created_at | datetime | Thời điểm tạo đánh giá. |

21. recipes - công thức

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của công thức. |
| user_id | int(11) (FK) | Khóa ngoại tới users.id. |
| category_id | int(11) (FK) | Khóa ngoại tới categories.id. |
| title | varchar(255) | Tiêu đề hiển thị. |
| description | text | Mô tả nội dung. |
| image | varchar(255) | Ảnh minh họa. |
| cooking_time | int(11) | Thời gian nấu (phút). |
| difficulty | enum('easy','medium','hard') | Mức độ khó. |
| status | varchar(20) | Trạng thái của công thức. |
| user_state | varchar(20) | Trạng thái phía tác giả. |
| view_count | int(11) | Số lượt xem. |
| created_at | datetime | Thời điểm tạo công thức. |
| updated_at | datetime | Thời điểm cập nhật công thức. |
| deleted_at | datetime | Thời điểm xóa mềm công thức. |

22. recipe_ingredients - nguyên liệu công thức

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của dòng nguyên liệu công thức. |
| recipe_id | int(11) (FK) | Khóa ngoại tới recipes.id. |
| ingredient_id | int(11) (FK) | Khóa ngoại tới ingredients.id. |
| quantity | varchar(50) | Số lượng sử dụng. |
| unit | varchar(50) | Đơn vị đo. |

23. recipe_steps - bước nấu công thức

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của bước nấu. |
| recipe_id | int(11) (FK) | Khóa ngoại tới recipes.id. |
| step_number | int(11) | Thứ tự bước nấu. |
| content | text | Nội dung chính. |
| image | varchar(255) | Ảnh minh họa. |

24. reports - báo cáo

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của báo cáo. |
| reporter_id | int(11) (FK) | Khóa ngoại tới users.id. |
| recipe_id | int(11) | ID đối tượng liên quan. |
| target_type | varchar(20) | Loại đối tượng mục tiêu. |
| target_id | int(11) | ID đối tượng bị tác động/báo cáo. |
| reason | text | Lý do xử lý. |
| details | text | Chi tiết bổ sung của báo cáo. |
| status | enum('pending','reviewed','resolved') | Trạng thái của báo cáo. |
| created_at | datetime | Thời điểm tạo báo cáo. |
| updated_at | datetime | Thời điểm cập nhật báo cáo. |

25. roles - vai trò

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của vai trò. |
| role_name | varchar(50) | Tên vai trò hệ thống. |
| description | text | Mô tả nội dung. |

26. role_permissions - phân quyền vai trò

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| role_id | int(11) (PK) (FK) | Khóa ghép, tham chiếu roles.id. |
| permission_id | int(11) (PK) (FK) | Khóa ghép, tham chiếu permissions.id. |

27. saved_items - mục đã lưu

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của mục đã lưu. |
| user_id | int(11) (FK) | Khóa ngoại tới users.id. |
| item_id | int(11) | ID đối tượng liên quan. |
| item_type | enum('recipe','ingredient') | Loại mục được người dùng lưu. |
| created_at | datetime | Thời điểm tạo mục đã lưu. |

28. system_logs - nhật ký hệ thống

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | bigint(20) (PK) | Định danh duy nhất của sự kiện hệ thống. |
| event_type | varchar(50) | Loại sự kiện hệ thống. |
| action_key | varchar(120) | Mã hành động admin. |
| actor_id | int(11) | ID đối tượng liên quan. |
| actor_role | varchar(50) | Vai trò của người thực hiện hành động. |
| target_type | varchar(50) | Loại đối tượng mục tiêu. |
| target_id | int(11) | ID đối tượng bị tác động/báo cáo. |
| result | varchar(20) | Kết quả thực thi hành động. |
| reason | varchar(255) | Lý do xử lý. |
| meta_json | text | Dữ liệu JSON bổ sung của sự kiện. |
| ip_address | varchar(45) | Địa chỉ IP truy cập. |
| user_agent | varchar(255) | Thông tin thiết bị truy cập. |
| created_at | datetime | Thời điểm tạo sự kiện hệ thống. |

29. tips - mẹo vặt

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của mẹo vặt. |
| user_id | int(11) | ID đối tượng liên quan. |
| title | varchar(255) | Tiêu đề hiển thị. |
| slug | varchar(255) | Chuỗi URL thân thiện. |
| excerpt | text | Tóm tắt ngắn nội dung mẹo. |
| content | longtext | Nội dung chính. |
| cover_image | varchar(255) | Ảnh minh họa. |
| author_name | varchar(150) | Tên tác giả hiển thị. |
| status | enum('draft','pending','approved','rejected') | Trạng thái của mẹo vặt. |
| view_count | int(11) | Số lượt xem. |
| created_at | datetime | Thời điểm tạo mẹo vặt. |
| updated_at | datetime | Thời điểm cập nhật mẹo vặt. |
| rejection_reason | text | Lý do từ chối duyệt. |

30. tip_saves - lưu mẹo vặt

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của lượt lưu mẹo. |
| user_id | int(11) (FK) | Khóa ngoại tới users.id. |
| tip_id | int(11) (FK) | Khóa ngoại tới tips.id. |
| created_at | datetime | Thời điểm tạo lượt lưu mẹo. |

31. users - người dùng

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của người dùng. |
| username | varchar(50) | Tên đăng nhập duy nhất. |
| name | varchar(120) | Tên hiển thị. |
| full_name | varchar(100) | Họ và tên đầy đủ của người dùng. |
| email | varchar(150) | Email tài khoản. |
| password | varchar(255) | Mật khẩu đã băm. |
| avatar | varchar(255) | Ảnh đại diện. |
| bio | text | Mô tả hồ sơ. |
| role | varchar(50) | Vai trò tài khoản hiện tại. |
| status | enum('active','banned') | Trạng thái của người dùng. |
| deleted_at | datetime | Thời điểm xóa mềm người dùng. |
| ban_reason | text | Lý do tài khoản bị cấm. |
| banned_until | datetime | Thời điểm hết hạn cấm. |
| created_at | datetime | Thời điểm tạo người dùng. |
| updated_at | datetime | Thời điểm cập nhật người dùng. |

32. user_bans - cấm người dùng

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của lệnh cấm người dùng. |
| user_id | int(11) (FK) | Khóa ngoại tới users.id. |
| banned_by | int(11) (FK) | Khóa ngoại tới users.id. |
| reason | text | Lý do xử lý. |
| ban_type | enum('temporary','permanent') | Loại cấm: tạm thời hoặc vĩnh viễn. |
| ban_until | datetime | Thời điểm kết thúc lệnh cấm. |
| created_at | datetime | Thời điểm tạo lệnh cấm người dùng. |
| updated_at | datetime | Thời điểm cập nhật lệnh cấm người dùng. |
| is_active | tinyint(1) | Trạng thái bật/tắt. |

33. user_blocks - chặn người dùng

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của quan hệ chặn người dùng. |
| blocker_id | int(11) (FK) | Khóa ngoại tới users.id. |
| blocked_user_id | int(11) (FK) | Khóa ngoại tới users.id. |
| created_at | datetime | Thời điểm tạo quan hệ chặn người dùng. |

34. user_penalties - xử phạt người dùng

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| id | int(11) (PK) | Định danh duy nhất của quyết định xử phạt. |
| user_id | int(11) (FK) | Khóa ngoại tới users.id. |
| admin_id | int(11) (FK) | Khóa ngoại tới users.id. |
| source_type | enum('comment','recipe','tip','ingredient','account') | Loại nguồn vi phạm bị xử lý. |
| source_id | int(11) | ID đối tượng liên quan. |
| action | enum('warn','comment_lock_temp','comment_lock_permanent','recipe_post_lock_temp','recipe_post_lock_permanent','tip_post_lock_temp','tip_post_lock_permanent','ingredient_post_lock_temp','ingredient_post_lock_permanent','follow_lock_temp','follow_lock_permanent','ban_temp','ban_permanent') | Hình thức xử phạt áp dụng. |
| reason | text | Lý do xử lý. |
| duration_days | int(11) | Số ngày áp dụng xử phạt. |
| banned_until | datetime | Thời điểm hết hạn cấm. |
| created_at | datetime | Thời điểm tạo quyết định xử phạt. |
| is_active | tinyint(1) | Trạng thái bật/tắt. |

35. user_roles - vai trò người dùng

| Tên cột | Kiểu dữ liệu | Mô tả |
|---|---|---|
| user_id | int(11) (PK) (FK) | Khóa ghép, tham chiếu users.id. |
| role_id | int(11) (PK) (FK) | Khóa ghép, tham chiếu roles.id. |


