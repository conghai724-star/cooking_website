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

| STT | Tác nhân | Tên chức năng | Mục đích | Dữ liệu yêu cầu | Kiểm tra dữ liệu + nghiệp vụ |
|---|---|---|---|---|---|
| 1 | Admin | Đăng nhập admin | Truy cập khu quản trị | `email`, `password` | Tài khoản tồn tại, đúng mật khẩu, có quyền admin; giới hạn 5 lần sai liên tiếp (rate-limit). |
| 2 | Admin | Đăng xuất admin | Kết thúc phiên quản trị | `admin_session` | Hủy session/token an toàn. |
| 3 | Admin | Quản lý công thức chờ duyệt | Duyệt/từ chối công thức user gửi | `recipe_id`, `status`, `reason` | `status` chỉ nhận `approved/rejected`; từ chối nên có lý do. |
| 4 | Admin | Quản lý công thức đã duyệt | Xem/sửa/xóa công thức công khai | `recipe_id` | Không xóa khi đang được dùng trong meal plan. |
| 5 | Admin | Xem chi tiết công thức (admin) | Xem đầy đủ dữ liệu công thức | `recipe_id` | Cho phép xem mọi trạng thái. |
| 6 | Admin | Quản lý nguyên liệu chờ duyệt | Duyệt/từ chối nguyên liệu | `ingredient_id`, `status`, `reason` | `status` chỉ nhận `approved/rejected`. |
| 7 | Admin | Quản lý mẹo vặt chờ duyệt | Duyệt/từ chối mẹo vặt | `tip_id`, `status`, `reason` | `status` chỉ nhận `approved/rejected`. |
| 8 | Admin | Quản lý người dùng | Xem và điều phối tài khoản | `user_id`, `action` | Chỉ admin được phép thao tác. |
| 9 | Admin | Quản lý báo cáo | Theo dõi/xử lý báo cáo | `report_id`, `status` | `status` theo `pending/reviewed/resolved`. |
| 10 | Người dùng | Đăng ký | Tạo tài khoản mới | `username`, `email`, `password` | Email hợp lệ, không trùng; mật khẩu >= 6 ký tự. |
| 11 | Người dùng | Đăng nhập | Truy cập hệ thống user | `email`, `password` | Sai thông tin trả lỗi; giới hạn 5 lần sai liên tiếp (rate-limit). |
| 12 | Người dùng | Đăng xuất | Kết thúc phiên người dùng | `user_session` | Hủy session/token an toàn. |
| 13 | Người dùng | Xem hồ sơ | Xem thông tin cá nhân/người khác | `user_id` | User phải tồn tại. |
| 14 | Người dùng | Sửa hồ sơ | Cập nhật thông tin hồ sơ | `name`, `email`, `bio`, `avatar` | Validate email, độ dài, định dạng ảnh. |
| 15 | Người dùng | Đổi email có xác thực | Đổi email đăng nhập | `new_email`, `current_password`, `token` | Bắt buộc đúng mật khẩu hiện tại; token 1 lần, hết hạn 30 phút. |
| 16 | Người dùng | Đổi mật khẩu | Cập nhật mật khẩu đăng nhập | `new_password` | Mật khẩu mới >= 6 ký tự. |
| 17 | Người dùng | Follow/Unfollow | Theo dõi hoặc bỏ theo dõi | `target_user_id` | Không tự follow chính mình. |
| 18 | Người dùng | Xóa follower | Loại bỏ người theo dõi mình | `target_user_id` | Chỉ chủ tài khoản được thao tác. |
| 19 | Người dùng | Xem kết nối | Xem followers/following/friends | `user_id`, `group` | `group` hợp lệ trong `followers/following/friends`. |
| 20 | Người dùng | Tạo công thức | Tạo bài công thức mới | `title`, `description`, `difficulty`, `cooking_time`, `ingredients[]`, `steps[]` | Dữ liệu bắt buộc hợp lệ; gán trạng thái theo action lưu/gửi. |
| 21 | Người dùng | Sửa công thức | Cập nhật công thức của chính mình | `recipe_id`, `fields` | Không cho sửa trực tiếp khi đã `approved` (hoặc phải gửi duyệt lại). |
| 22 | Người dùng | Xóa công thức | Xóa bài của chính mình | `recipe_id` | Không xóa khi recipe đang gắn meal plan. |
| 23 | Người dùng | Quản lý vòng đời công thức | Quản lý trạng thái bản thảo/duyệt | `recipe_id`, `action` | Tách `status` kiểm duyệt và `user_state` thao tác người dùng. |
| 24 | Người dùng | Xem “Công thức của tôi” | Quản lý bài theo tab | `group`, `page` | Tab hợp lệ, phân trang hợp lệ. |
| 25 | Người dùng | Lưu/bỏ lưu công thức | Bookmark công thức yêu thích | `recipe_id` | Chỉ lưu bài có quyền xem. |
| 26 | Người dùng | Báo cáo công thức | Báo vi phạm công thức | `recipe_id`, `reason` | Unique `user_id + recipe_id`, không cho báo cáo trùng. |
| 27 | Người dùng | Góp ý nguyên liệu | Tạo nguyên liệu chờ duyệt | `name`, `description`, `usage`, `preparation`, `storage`, `nutrition` | Trạng thái mặc định `pending`. |
| 28 | Người dùng | Gửi duyệt lại nguyên liệu | Nộp lại nguyên liệu bị từ chối | `ingredient_id` | Chỉ chủ sở hữu và đang ở `rejected`. |
| 29 | Người dùng | Góp ý mẹo vặt | Tạo mẹo chờ duyệt | `title`, `excerpt`, `content` | Trạng thái mặc định `pending`. |
| 30 | Người dùng | Gửi duyệt lại mẹo vặt | Nộp lại mẹo bị từ chối | `tip_id` | Chỉ chủ sở hữu và đang ở `rejected`. |
| 31 | Người dùng | Bình luận nội dung | Bình luận recipe/tip/ingredient | `content_type`, `content_id`, `content` | Đăng nhập bắt buộc; nội dung không rỗng; ưu tiên soft delete để audit. |
| 32 | Người dùng | Trả lời bình luận | Reply theo luồng 2 cấp | `parent_id`, `content` | Giới hạn độ sâu tối đa 2 cấp. |
| 33 | Người dùng | Báo cáo bình luận | Tố cáo comment vi phạm | `comment_id`, `reason` | Không tự report comment của mình; unique `user_id + comment_id`. |
| 34 | Khách/Người dùng | Xem danh sách công thức | Duyệt recipe công khai | `keyword`, `category`, `sort`, `page` | Khách chỉ thấy bài đã duyệt và công khai. |
| 35 | Khách/Người dùng | Xem chi tiết công thức | Đọc công thức | `recipe_id` | Không bypass ID để xem bài chưa duyệt. |
| 36 | Khách/Người dùng | Xem danh sách/chi tiết nguyên liệu | Duyệt thư viện nguyên liệu | `keyword`, `page`, `ingredient_id` | Khách chỉ thấy `approved`. |
| 37 | Khách/Người dùng | Xem danh sách/chi tiết mẹo vặt | Duyệt thư viện mẹo | `keyword`, `page`, `tip_id/slug` | Khách chỉ thấy `approved`. |
| 38 | Người dùng | Lập kế hoạch bữa ăn | Gán món vào ngày/bữa | `plan_date`, `meal_type`, `recipe_id` | Chỉ nhận recipe đủ điều kiện hiển thị; validate slot hợp lệ. |
| 39 | Người dùng | Khóa ngày/khóa tuần kế hoạch | Chặn chỉnh sửa kế hoạch | `lock_date/week_start`, `is_locked` | `is_locked` chỉ nhận `0/1`; ngày khóa không cho thêm/sửa/xóa. |
| 40 | Người dùng/Khách | Chia sẻ và xem meal plan | Chia sẻ theo visibility/token | `visibility`, `share_token` | Chỉ truy cập khi thỏa điều kiện `public/followers/friends/link`. |
| 41 | Admin | Quản lý bình luận | Ẩn/xóa/khôi phục bình luận vi phạm | `comment_id`, `action` | `action` nhận `hide/restore/delete`; ưu tiên ẩn/khôi phục trước xóa cứng. |

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








