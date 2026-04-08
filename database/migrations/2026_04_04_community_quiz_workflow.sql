USE cooking_website;

START TRANSACTION;

-- Community quiz sets created by users and reviewed/published by admins.
CREATE TABLE IF NOT EXISTS quiz_sets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    topic VARCHAR(120) NULL,
    difficulty ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'easy',
    status ENUM('draft', 'submitted', 'in_review', 'needs_revision', 'approved', 'published', 'rejected', 'archived')
        NOT NULL DEFAULT 'draft',
    review_note TEXT NULL,
    submitted_at DATETIME NULL,
    approved_at DATETIME NULL,
    approved_by INT NULL,
    published_at DATETIME NULL,
    published_by INT NULL,
    current_version INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_sets_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_sets_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_quiz_sets_published_by FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_quiz_sets_author_status (author_id, status),
    INDEX idx_quiz_sets_status_topic (status, topic),
    INDEX idx_quiz_sets_published (published_at)
);

CREATE TABLE IF NOT EXISTS quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_set_id INT NOT NULL,
    question_text TEXT NOT NULL,
    explanation TEXT NULL,
    display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_questions_set FOREIGN KEY (quiz_set_id) REFERENCES quiz_sets(id) ON DELETE CASCADE,
    INDEX idx_quiz_questions_set_order (quiz_set_id, display_order)
);

CREATE TABLE IF NOT EXISTS quiz_question_choices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    choice_text VARCHAR(500) NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_choices_question FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
    INDEX idx_quiz_choices_question_order (question_id, display_order),
    INDEX idx_quiz_choices_correct (question_id, is_correct)
);

-- Snapshot each submit/review cycle for audit and rollback.
CREATE TABLE IF NOT EXISTS quiz_set_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_set_id INT NOT NULL,
    version_no INT NOT NULL,
    snapshot_json LONGTEXT NOT NULL,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_versions_set FOREIGN KEY (quiz_set_id) REFERENCES quiz_sets(id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_versions_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_quiz_set_version (quiz_set_id, version_no),
    INDEX idx_quiz_versions_creator (created_by)
);

-- Review actions history.
CREATE TABLE IF NOT EXISTS quiz_set_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_set_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    action ENUM('start_review', 'request_revision', 'approve', 'publish', 'reject', 'archive') NOT NULL,
    note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_reviews_set FOREIGN KEY (quiz_set_id) REFERENCES quiz_sets(id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_reviews_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_quiz_reviews_set_time (quiz_set_id, created_at),
    INDEX idx_quiz_reviews_reviewer_time (reviewer_id, created_at)
);

-- New RBAC permissions for quiz contribution + moderation.
INSERT INTO permissions (permission_name, description)
SELECT 'user.quizzes.create', 'Tao va cap nhat bo cau hoi cua chinh minh'
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE permission_name = 'user.quizzes.create'
);

INSERT INTO permissions (permission_name, description)
SELECT 'user.quizzes.submit', 'Gui bo cau hoi len de admin duyet'
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE permission_name = 'user.quizzes.submit'
);

INSERT INTO permissions (permission_name, description)
SELECT 'user.quizzes.edit_own', 'Sua bo cau hoi cua chinh minh'
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE permission_name = 'user.quizzes.edit_own'
);

INSERT INTO permissions (permission_name, description)
SELECT 'admin.quizzes.review', 'Duyet bo cau hoi cong dong'
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE permission_name = 'admin.quizzes.review'
);

INSERT INTO permissions (permission_name, description)
SELECT 'admin.quizzes.publish', 'Phat hanh bo cau hoi da duoc duyet'
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE permission_name = 'admin.quizzes.publish'
);

-- Role mapping: user can create/submit own sets.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_name = 'user.quizzes.create'
WHERE r.role_name = 'user'
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_name = 'user.quizzes.submit'
WHERE r.role_name = 'user'
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_name = 'user.quizzes.edit_own'
WHERE r.role_name = 'user'
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );

-- mod can review, super_admin can review + publish.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_name = 'admin.quizzes.review'
WHERE r.role_name IN ('mod', 'super_admin')
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_name = 'admin.quizzes.publish'
WHERE r.role_name = 'super_admin'
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );

COMMIT;
