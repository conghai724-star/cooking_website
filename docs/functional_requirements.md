# Bảng Yêu Cầu Chức Năng (Phiên bản rà soát theo trạng thái thực tế dự án)

## A. Chuẩn dùng chung

### A1) Chuẩn kiểu dữ liệu
- `INT UNSIGNED`: các ID (`id`, `user_id`, `recipe_id`, `ingredient_id`, `tip_id`, `comment_id`, `report_id`) và phải `> 0`.
- `VARCHAR(n)`: dữ liệu ngắn (`username`, `email`, `title`, `slug`, `reason`).
- `TEXT/LONGTEXT`: dữ liệu mô tả dài (`description`, `content`, `bio`, `note`).
- `ENUM`: trạng thái, quyền, phân loại cố định.
- `TINYINT(1)` hoặc `BOOLEAN`: cờ khóa/mở, bật/tắt.
- `DATETIME`: `created_at`, `updated_at`, `expires_at`, `used_at`.

### A2) Chuẩn ENUM (đề xuất chuẩn hóa để bảo vệ đồ án)
- `recipes.status`: `draft`, `pending`, `approved`, `rejected`.
- `recipes.user_state`: `draft`, `completed`, `published`.
- `ingredients.status`: `pending`, `approved`, `rejected`.
- `tips.status`: `pending`, `approved`, `rejected`.
- `users.status`: `active`, `banned`.
- `users.role`: `user`, `admin`, `moderator` (hệ thống hiện chủ yếu dùng `user`, `admin`).
- `reports.status`: `pending`, `reviewed`, `resolved`.
- `comment_reports.status`: `pending`, `reviewed`, `resolved`.
- `meal_plan_settings.visibility`: `private`, `public`, `followers`, `friends`, `link`.

### A3) Chuẩn API response/error
- `200/201`: thành công.
- `400`: yêu cầu không hợp lệ.
- `401`: chưa đăng nhập.
- `403`: không đủ quyền.
- `404`: không tìm thấy dữ liệu.
- `409`: xung đột trạng thái.
- `422`: vi phạm rule nghiệp vụ.
- `500`: lỗi hệ thống.

## B. Bảng chức năng

### B1) Vai trò và phạm vi quyền hiện có (rà soát theo route + middleware)

| Vai trò | Phạm vi chức năng |
|---|---|
| `guest` | Xem trang chủ, xem danh sách/chi tiết công thức, nguyên liệu, mẹo vặt; xem meal plan được chia sẻ công khai/link hợp lệ. |
| `user` | Toàn bộ chức năng người dùng: hồ sơ, follow, tạo/sửa nội dung của mình, bình luận, báo cáo, lưu nội dung, meal plan, mở thông báo. |
| `support` | Vào admin để xem dashboard/users, xử lý reports, gửi thông báo hệ thống, xem stats/logs, xem meal plans và relationships. |
| `mod` | Ngoài quyền xem admin: kiểm duyệt recipe/ingredient/tip, kiểm duyệt comment, xử lý reports, moderate relationships/follow lock, phạt user. |
| `super_admin` | Toàn quyền tất cả chức năng admin (bao gồm role assign, users, content, banners, notifications, logs, stats...). |

### B2) Danh sách chức năng web hiện có theo tác nhân (chuẩn dữ liệu)

| STT | Tác nhân | Tên chức năng | Mục đích | Dữ liệu yêu cầu | Kiểm tra dữ liệu |
|---|---|---|---|---|---|
| 1 | Guest/User | Xem trang chủ | Truy cập landing + nội dung nổi bật | Không yêu cầu input | Không kiểm tra input người dùng. |
| 2 | Guest/User | Xem danh sách & chi tiết công thức | Duyệt recipe | `keyword?`, `page?`, `id` | `page` là số nguyên dương; `id` là số nguyên dương. |
| 3 | Guest/User | Xem danh sách & chi tiết nguyên liệu | Duyệt thư viện nguyên liệu | `keyword?`, `page?`, `id` | `page` là số nguyên dương; `id` là số nguyên dương. |
| 4 | Guest/User | Xem danh sách & chi tiết mẹo vặt | Duyệt thư viện mẹo | `keyword?`, `page?`, `slug` | `page` là số nguyên dương; `slug` chỉ gồm chữ thường/số/gạch nối. |
| 5 | Guest/User | Xem hồ sơ và kết nối | Xem profile, followers, following | `user_id` | `user_id` là số nguyên dương, user phải tồn tại. |
| 6 | User | Đăng ký/đăng nhập/đăng xuất user | Xác thực người dùng | `username`, `email`, `password` | `username` 3-100 ký tự; `email` đúng định dạng; `password` >= 6 ký tự. |
| 7 | Admin | Đăng nhập/đăng xuất admin | Truy cập cổng quản trị | `email`, `password` | `email` đúng định dạng; mật khẩu không rỗng; tài khoản có role admin. |
| 8 | User | Quản lý hồ sơ cá nhân | Sửa profile, đổi email, đổi mật khẩu | `name`, `email`, `bio?`, `avatar?`, `token?` | `name` không rỗng; `email` hợp lệ/không trùng; `bio` giới hạn độ dài; `avatar` đúng mime/size. |
| 9 | User | Theo dõi & quản lý kết nối | Follow/unfollow/remove follower | `target_user_id` | `target_user_id` là số nguyên dương; không được trùng `current_user_id`. |
| 10 | User | Báo cáo/chặn người dùng | An toàn cộng đồng | `target_user_id`, `reason`, `details?` | `target_user_id` hợp lệ; `reason` không rỗng; `details` giới hạn độ dài. |
| 11 | User | Mở thông báo cá nhân | Điều hướng từ thông báo | `notification_id` | `notification_id` là số nguyên dương; thuộc quyền sở hữu của user hiện tại. |
| 12 | User | Tạo/sửa/xóa công thức của mình | Quản lý nội dung recipe | `recipe_id?`, `title`, `description`, `difficulty`, `cooking_time`, `category_id?`, `ingredients[]`, `steps[]` | `title` không rỗng; `difficulty` thuộc enum; `cooking_time` số nguyên >= 0; mỗi `step` không rỗng. |
| 13 | User | Chuyển trạng thái công thức | Draft/submit/resubmit/move-to-draft | `recipe_id`, `action` | `recipe_id` hợp lệ; `action` thuộc tập cho phép. |
| 14 | User | Xem “công thức của tôi” | Quản lý recipe theo tab | `group?`, `page?` | `group` thuộc tập tab hợp lệ; `page` số nguyên dương. |
| 15 | User | Lưu/bỏ lưu & báo cáo công thức | Tương tác nội dung | `recipe_id`, `reason?` | `recipe_id` hợp lệ; nếu report thì `reason` không rỗng. |
| 16 | User | Góp ý nguyên liệu | Tạo/resubmit nguyên liệu | `ingredient_id?`, `name`, `description`, `usage?`, `preparation?`, `storage?`, `nutrition?` | `name` không rỗng; các trường text trong giới hạn độ dài; `ingredient_id` hợp lệ khi resubmit. |
| 17 | User | Lưu/bỏ lưu & báo cáo nguyên liệu | Tương tác nguyên liệu | `ingredient_id`, `reason?` | `ingredient_id` hợp lệ; nếu report thì `reason` không rỗng. |
| 18 | User | Góp ý mẹo vặt | Tạo/resubmit mẹo | `tip_id?`, `title`, `excerpt?`, `content` | `title` và `content` không rỗng; độ dài hợp lệ; `tip_id` hợp lệ khi resubmit. |
| 19 | User | Lưu/bỏ lưu & báo cáo mẹo vặt | Tương tác mẹo | `tip_id`, `reason?` | `tip_id` hợp lệ; nếu report thì `reason` không rỗng. |
| 20 | User | Bình luận nội dung | Comment recipe/tip/ingredient | `content_type`, `content_id`, `content` | `content_type` thuộc `recipe/tip/ingredient`; `content_id` hợp lệ; `content` không rỗng. |
| 21 | User | Trả lời bình luận | Reply theo luồng | `parent_id`, `content` | `parent_id` hợp lệ; `content` không rỗng. |
| 22 | User | Báo cáo bình luận | Tố cáo comment vi phạm | `comment_id`, `reason` | `comment_id` hợp lệ; `reason` không rỗng. |
| 23 | User | Quản lý meal plan cá nhân | Xem tuần/ngày, thêm/xóa món | `plan_date`, `meal_type`, `recipe_id`, `dish_role?` | `plan_date` đúng `Y-m-d`; `meal_type` thuộc enum; `recipe_id` hợp lệ; `dish_role` thuộc enum. |
| 24 | User | Tự động gợi ý meal plan tuần | Sinh nhanh kế hoạch tuần | `week_start`, `preferences?` | `week_start` đúng `Y-m-d`; `preferences` đúng cấu trúc JSON nếu có. |
| 25 | User | Khóa kế hoạch | Khóa theo ngày/tuần | `lock_date?`, `week_start?`, `is_locked` | `lock_date/week_start` đúng `Y-m-d`; `is_locked` thuộc `0/1`. |
| 26 | User/Guest | Chia sẻ & xem meal plan | Chia sẻ theo visibility/token | `visibility`, `share_token?` | `visibility` thuộc enum; `share_token` đúng định dạng token nếu có. |
| 27 | Admin | Dashboard & danh sách users | Vận hành hệ thống | `keyword?`, `state?`, `page?` | `page` số nguyên dương; `state` thuộc enum trạng thái user. |
| 28 | Admin | Ban/unban/xóa mềm/khôi phục user | Kiểm soát tài khoản | `user_id`, `reason?`, `until?` | `user_id` hợp lệ; `until` đúng datetime nếu có. |
| 29 | Admin | Penalty theo hành vi | Khóa tạm theo action | `user_id`, `action`, `expires_at?`, `reason?` | `action` thuộc enum action; `expires_at` đúng datetime nếu có. |
| 30 | Admin | Gán role / tạo admin account | Quản trị phân quyền | `user_id?`, `role`, `name?`, `email?`, `password?` | `role` thuộc danh sách role; `email` hợp lệ; `password` >= 6 ký tự khi tạo mới. |
| 31 | Admin | Quản lý relationships | Theo dõi và điều phối follow | `follower_id?`, `following_id?`, `risk?`, `side?`, `page?` | ID hợp lệ; `risk/side` thuộc tập filter cho phép; `page` số nguyên dương. |
| 32 | Admin | Quản lý kiểm duyệt recipe | Duyệt/từ chối/xóa công thức | `recipe_id`, `status`, `reason?` | `status` thuộc `approved/rejected/deleted`; `reason` bắt buộc khi từ chối. |
| 33 | Admin | Quản lý kiểm duyệt ingredient | Duyệt/từ chối/sửa/xóa nguyên liệu | `ingredient_id`, `status`, `reason?`, `fields?` | `ingredient_id` hợp lệ; `status` thuộc enum; field sửa đúng kiểu. |
| 34 | Admin | Quản lý kiểm duyệt tip | Duyệt/từ chối/xóa mẹo vặt | `tip_id`, `status`, `reason?` | `tip_id` hợp lệ; `status` thuộc enum; `reason` bắt buộc khi từ chối. |
| 35 | Admin | Quản lý bình luận vi phạm | Ẩn/khôi phục/xóa comment | `comment_id`, `action` | `comment_id` hợp lệ; `action` thuộc `hide/restore/delete`. |
| 36 | Admin | Quản lý reports tổng hợp | Xem + cập nhật trạng thái report | `report_id`, `status`, `action?` | `report_id` hợp lệ; `status` thuộc `pending/reviewed/resolved`. |
| 37 | Admin | Quản lý banner & nội dung nổi bật | Banner, featured recipes, recipe of day | `title?`, `subtitle?`, `image?`, `cta_text?`, `cta_url?`, `featured_ids[]?`, `for_date?`, `recipe_id?` | `image` đúng mime/size; `cta_url` đúng URL; `featured_ids` là mảng số nguyên dương; `for_date` đúng `Y-m-d`. |
| 38 | Admin | Quản lý thông báo hệ thống | Soạn và gửi notification campaign | `title`, `message`, `target_type`, `target_ids?`, `action_url?` | `title/message` không rỗng; `target_type` hợp lệ; `target_ids` là mảng số nguyên dương; `action_url` đúng URL nếu có. |
| 39 | Admin | Xem stats/logs/mealplans | Giám sát vận hành | `from?`, `to?`, `keyword?`, `page?` | `from/to` đúng ngày; `page` số nguyên dương. |

## C. Quy tắc nghiệp vụ bắt buộc

0. Giới hạn đăng nhập: tối đa 5 lần đăng nhập sai liên tiếp theo tài khoản/IP, sau đó khóa tạm phiên đăng nhập và yêu cầu chờ hoặc xác minh bổ sung.

1. Không cho sửa công thức đã `approved` (hoặc nếu mở sửa thì bắt buộc quay về `pending` để duyệt lại).
2. Không cho xóa công thức nếu đang được sử dụng trong `meal_plans`.
3. Route ghi dữ liệu phải kiểm tra đăng nhập và quyền sở hữu (hoặc quyền admin).
4. Dữ liệu render ra view phải escape để chống XSS.
5. Upload ảnh phải whitelist extension và xử lý lỗi upload rõ ràng.
6. Trạng thái chuyển luồng phải hợp lệ theo workflow hiện tại của từng module.
7. Với đổi email: token một lần, có hạn dùng, không cho xác nhận lại token đã dùng.
8. Comment áp dụng soft delete, không hard delete trực tiếp ở luồng người dùng; dữ liệu ẩn vẫn phục vụ audit/report.


## D. Ghi chú triển khai (để khớp chuẩn trên)

- Hiện code cũ còn dùng user_state = submitted/rejected ở một số luồng.
- Khi chuẩn hóa, nên map như sau:
  - submitted -> status = pending và user_state = completed.
  - rejected không lưu ở user_state, chỉ lưu ở status = rejected.













