# Community Quiz Workflow (User Submit -> Admin Review -> Publish)

## 1) Muc tieu
- User co the tao bo cau hoi va gui admin duyet.
- Admin/mod duyet noi dung; super_admin phat hanh.
- Luu lich su review + version de audit.

## 2) Trang thai bo de
- `draft`: tac gia dang soan.
- `submitted`: da gui duyet.
- `in_review`: admin nhan xu ly.
- `needs_revision`: admin tra lai de sua.
- `approved`: da duyet noi dung.
- `published`: da phat hanh cho cong dong.
- `rejected`: tu choi.
- `archived`: dong bo de.

## 3) RBAC de xuat
- `user.quizzes.create`: tao bo de.
- `user.quizzes.edit_own`: sua bo de cua chinh minh.
- `user.quizzes.submit`: gui duyet.
- `admin.quizzes.review`: tiep nhan review, yeu cau sua, approve/reject.
- `admin.quizzes.publish`: phat hanh bo de da approve.

Role map:
- `user`: create + edit_own + submit.
- `mod`: review.
- `super_admin`: review + publish.
- `support`: khong cap quyen quiz o ban dau.

## 4) Route de xuat (theo style `public/index.php`)

User side:
- `GET /quizzes/my` -> danh sach bo de cua toi (`user.quizzes.create` hoac login).
- `GET /quizzes/create` -> form tao bo de (`user.quizzes.create`).
- `POST /quizzes/create` -> tao bo de (`user.quizzes.create`).
- `GET /quizzes/{id}/edit` -> form sua (`user.quizzes.edit_own`).
- `POST /quizzes/{id}/edit` -> cap nhat bo de + cau hoi (`user.quizzes.edit_own`).
- `POST /quizzes/{id}/submit` -> gui duyet (`user.quizzes.submit`).
- `POST /quizzes/{id}/withdraw` -> rut ve `draft` neu chua bi admin nhan (`user.quizzes.submit`).

Public:
- `GET /quizzes` -> danh sach bo de `published`.
- `GET /quizzes/{id}` -> chi hien thi neu `published` hoac la tac gia/admin.

Admin side:
- `GET /admin/quizzes` -> danh sach cho review (`admin.quizzes.review`).
- `GET /admin/quizzes/{id}` -> chi tiet bo de (`admin.quizzes.review`).
- `POST /admin/quizzes/{id}/start-review` -> `submitted -> in_review` (`admin.quizzes.review`).
- `POST /admin/quizzes/{id}/request-revision` -> `in_review -> needs_revision` (`admin.quizzes.review`).
- `POST /admin/quizzes/{id}/approve` -> `in_review -> approved` (`admin.quizzes.review`).
- `POST /admin/quizzes/{id}/reject` -> `in_review -> rejected` (`admin.quizzes.review`).
- `POST /admin/quizzes/{id}/publish` -> `approved -> published` (`admin.quizzes.publish`).
- `POST /admin/quizzes/{id}/archive` -> `published|rejected -> archived` (`admin.quizzes.review`).

## 5) Validation rule quan trong
- Moi cau hoi phai co it nhat 2 lua chon.
- Moi cau hoi phai co dung 1 dap an dung (MVP).
- Bo de phai co toi thieu N cau (de xuat N=5) moi duoc submit.
- Tac gia chi duoc sua khi `draft` hoac `needs_revision`.
- Sau khi `published`, neu tac gia sua thi tao `current_version + 1` va quay ve `draft`.

## 6) Transition guard
- `draft -> submitted`: user submit.
- `submitted -> in_review`: admin nhan xu ly.
- `in_review -> needs_revision|approved|rejected`: admin review.
- `approved -> published`: super_admin publish.
- `needs_revision -> submitted`: user submit lai.
- `published -> archived`: admin dong bo de.

## 7) Audit va log
- Moi hanh dong review ghi vao `quiz_set_reviews`.
- Moi lan submit/review snapshot noi dung vao `quiz_set_versions`.
- Neu da co `system_log_write`, de xuat them:
  - `quiz.submit`
  - `admin.quiz.review.start`
  - `admin.quiz.revision.request`
  - `admin.quiz.approve`
  - `admin.quiz.publish`

## 8) Skeleton service methods de xay
- `QuizService::createSet(array $input, int $authorId): int|false`
- `QuizService::updateSet(int $setId, int $authorId, array $input): bool`
- `QuizService::submitSet(int $setId, int $authorId): bool`
- `QuizService::withdrawSet(int $setId, int $authorId): bool`
- `QuizAdminService::startReview(int $setId, int $reviewerId): bool`
- `QuizAdminService::requestRevision(int $setId, int $reviewerId, string $note): bool`
- `QuizAdminService::approve(int $setId, int $reviewerId, string $note = ''): bool`
- `QuizAdminService::publish(int $setId, int $publisherId): bool`

## 9) SQL migration
- Da duoc tao tai:
  - `database/migrations/2026_04_04_community_quiz_workflow.sql`
