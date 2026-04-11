<?php

declare(strict_types=1);

class QuizModel extends Model
{
    private bool $ready = false;

    private function ensureSchema(): void
    {
        if ($this->ready) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS quiz_sets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            author_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            topic VARCHAR(120) NULL,
            difficulty ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'easy',
            time_limit_minutes INT NULL,
            pass_min_correct INT NULL,
            pass_min_points INT NULL,
            status ENUM('draft', 'submitted', 'in_review', 'needs_revision', 'approved', 'published', 'rejected', 'archived') NOT NULL DEFAULT 'published',
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();
        $this->db->query("SHOW COLUMNS FROM quiz_sets LIKE 'pass_min_correct'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE quiz_sets ADD COLUMN pass_min_correct INT NULL AFTER difficulty")->execute();
        }
        $this->db->query("SHOW COLUMNS FROM quiz_sets LIKE 'time_limit_minutes'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE quiz_sets ADD COLUMN time_limit_minutes INT NULL AFTER difficulty")->execute();
        }
        $this->db->query("SHOW COLUMNS FROM quiz_sets LIKE 'pass_min_points'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE quiz_sets ADD COLUMN pass_min_points INT NULL AFTER pass_min_correct")->execute();
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS quiz_questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quiz_set_id INT NOT NULL,
            question_type VARCHAR(40) NOT NULL DEFAULT 'single_choice',
            question_text TEXT NOT NULL,
            question_image VARCHAR(255) NULL,
            points INT NOT NULL DEFAULT 1,
            answer_key_json LONGTEXT NULL,
            explanation TEXT NULL,
            display_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_quiz_questions_set FOREIGN KEY (quiz_set_id) REFERENCES quiz_sets(id) ON DELETE CASCADE,
            INDEX idx_quiz_questions_set_order (quiz_set_id, display_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();

        $this->db->query("SHOW COLUMNS FROM quiz_questions LIKE 'question_type'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE quiz_questions ADD COLUMN question_type VARCHAR(40) NOT NULL DEFAULT 'single_choice' AFTER quiz_set_id")->execute();
        }
        $this->db->query("SHOW COLUMNS FROM quiz_questions LIKE 'question_image'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE quiz_questions ADD COLUMN question_image VARCHAR(255) NULL AFTER question_text")->execute();
        }
        $this->db->query("SHOW COLUMNS FROM quiz_questions LIKE 'answer_key_json'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE quiz_questions ADD COLUMN answer_key_json LONGTEXT NULL AFTER question_image")->execute();
        }
        $this->db->query("SHOW COLUMNS FROM quiz_questions LIKE 'points'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE quiz_questions ADD COLUMN points INT NOT NULL DEFAULT 1 AFTER question_image")->execute();
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS quiz_question_choices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question_id INT NOT NULL,
            choice_text VARCHAR(500) NOT NULL,
            is_correct TINYINT(1) NOT NULL DEFAULT 0,
            display_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_quiz_choices_question FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
            INDEX idx_quiz_choices_question_order (question_id, display_order),
            INDEX idx_quiz_choices_correct (question_id, is_correct)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();

        $this->db->query("CREATE TABLE IF NOT EXISTS quiz_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quiz_set_id INT NOT NULL,
            user_id INT NOT NULL,
            score_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
            total_questions INT NOT NULL DEFAULT 0,
            correct_answers INT NOT NULL DEFAULT 0,
            is_passed TINYINT(1) NOT NULL DEFAULT 0,
            submitted_answers_json LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_quiz_attempts_set FOREIGN KEY (quiz_set_id) REFERENCES quiz_sets(id) ON DELETE CASCADE,
            CONSTRAINT fk_quiz_attempts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_quiz_attempts_user_set (user_id, quiz_set_id),
            INDEX idx_quiz_attempts_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();

        $this->db->query("CREATE TABLE IF NOT EXISTS quiz_certificates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quiz_set_id INT NOT NULL,
            user_id INT NOT NULL,
            certificate_code VARCHAR(64) NOT NULL,
            score_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
            awarded_reputation_points INT NOT NULL DEFAULT 0,
            awarded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_quiz_certificates_set FOREIGN KEY (quiz_set_id) REFERENCES quiz_sets(id) ON DELETE CASCADE,
            CONSTRAINT fk_quiz_certificates_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY uq_quiz_certificate_user_set (quiz_set_id, user_id),
            UNIQUE KEY uq_quiz_certificate_code (certificate_code),
            INDEX idx_quiz_certificates_user_time (user_id, awarded_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();

        $this->db->query("SHOW COLUMNS FROM users LIKE 'reputation_points'")->execute();
        if (!$this->db->single()) {
            $this->db->query("ALTER TABLE users ADD COLUMN reputation_points INT NOT NULL DEFAULT 0 AFTER bio")->execute();
        }

        $this->ready = true;
    }

    public function listPublishedSets(): array
    {
        $this->ensureSchema();

        $this->db->query("SELECT qs.id,
                                 qs.title,
                                 qs.description,
                                 qs.topic,
                                 qs.difficulty,
                                 qs.time_limit_minutes,
                                 qs.published_at,
                                 COUNT(DISTINCT qq.id) AS question_count
                          FROM quiz_sets qs
                          LEFT JOIN quiz_questions qq ON qq.quiz_set_id = qs.id
                          WHERE qs.status = 'published'
                          GROUP BY qs.id, qs.title, qs.description, qs.topic, qs.difficulty, qs.time_limit_minutes, qs.published_at
                          ORDER BY qs.published_at DESC, qs.id DESC")
            ->execute();

        return $this->db->resultSet();
    }

    public function listSetsForAdmin(): array
    {
        $this->ensureSchema();

        $this->db->query("SELECT qs.id,
                                 qs.title,
                                 qs.topic,
                                 qs.difficulty,
                                 qs.time_limit_minutes,
                                 qs.status,
                                 qs.published_at,
                                 u.name AS author_name,
                                 COUNT(DISTINCT qq.id) AS question_count,
                                 COUNT(DISTINCT qa.id) AS attempt_count,
                                 COUNT(DISTINCT qc.id) AS certificate_count
                          FROM quiz_sets qs
                          LEFT JOIN users u ON u.id = qs.author_id
                          LEFT JOIN quiz_questions qq ON qq.quiz_set_id = qs.id
                          LEFT JOIN quiz_attempts qa ON qa.quiz_set_id = qs.id
                          LEFT JOIN quiz_certificates qc ON qc.quiz_set_id = qs.id
                          GROUP BY qs.id, qs.title, qs.topic, qs.difficulty, qs.time_limit_minutes, qs.status, qs.published_at, u.name
                          ORDER BY qs.created_at DESC, qs.id DESC")
            ->execute();

        return $this->db->resultSet();
    }

    public function findPublishedSetById(int $setId): array|false
    {
        $this->ensureSchema();

        $this->db->query("SELECT qs.id,
                                 qs.title,
                                 qs.description,
                                 qs.topic,
                                 qs.difficulty,
                                 qs.time_limit_minutes,
                                 qs.published_at,
                                 u.name AS author_name
                          FROM quiz_sets qs
                          LEFT JOIN users u ON u.id = qs.author_id
                          WHERE qs.id = :id
                            AND qs.status = 'published'
                          LIMIT 1")
            ->bind(':id', $setId)
            ->execute();

        return $this->db->single();
    }

    public function findSetByIdForAdmin(int $setId): array|false
    {
        $this->ensureSchema();

        $this->db->query("SELECT qs.*, u.name AS author_name
                          FROM quiz_sets qs
                          LEFT JOIN users u ON u.id = qs.author_id
                          WHERE qs.id = :id
                          LIMIT 1")
            ->bind(':id', $setId)
            ->execute();

        return $this->db->single();
    }

    public function questionsWithChoices(int $setId): array
    {
        $this->ensureSchema();

        $this->db->query("SELECT id,
                                 question_type,
                                 question_text,
                                 question_image,
                                 points,
                                 answer_key_json,
                                 explanation,
                                 display_order
                          FROM quiz_questions
                          WHERE quiz_set_id = :set_id
                          ORDER BY display_order ASC, id ASC")
            ->bind(':set_id', $setId)
            ->execute();
        $questions = $this->db->resultSet();

        foreach ($questions as &$question) {
            $questionId = (int) ($question['id'] ?? 0);
            $question['answer_key_json'] = (string) ($question['answer_key_json'] ?? '');
            $this->db->query("SELECT id, choice_text, is_correct, display_order
                              FROM quiz_question_choices
                              WHERE question_id = :question_id
                              ORDER BY display_order ASC, id ASC")
                ->bind(':question_id', $questionId)
                ->execute();
            $question['choices'] = $this->db->resultSet();
        }

        return $questions;
    }

    public function createSetByAdmin(int $adminId, array $payload): int|false
    {
        $this->ensureSchema();

        $title = trim((string) ($payload['title'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $topic = trim((string) ($payload['topic'] ?? ''));
        $difficulty = (string) ($payload['difficulty'] ?? 'easy');
        $difficulty = in_array($difficulty, ['easy', 'medium', 'hard'], true) ? $difficulty : 'easy';
        $timeLimitMinutes = max(0, min(600, (int) ($payload['time_limit_minutes'] ?? 0)));
        $questions = is_array($payload['questions'] ?? null) ? $payload['questions'] : [];
        $passMinCorrect = max(0, (int) ($payload['pass_min_correct'] ?? 0));
        $passMinPoints = max(0, (int) ($payload['pass_min_points'] ?? 0));

        if ($adminId <= 0 || $title === '' || $questions === []) {
            return false;
        }

        try {
            $this->db->query('START TRANSACTION')->execute();

            $ok = $this->db
                ->query("INSERT INTO quiz_sets (
                            author_id, title, description, topic, difficulty, time_limit_minutes, pass_min_correct, pass_min_points, status,
                            submitted_at, approved_at, approved_by, published_at, published_by,
                            created_at, updated_at
                        ) VALUES (
                            :author_id, :title, :description, :topic, :difficulty, :time_limit_minutes, :pass_min_correct, :pass_min_points, 'published',
                            NOW(), NOW(), :approved_by, NOW(), :published_by, NOW(), NOW()
                        )")
                ->bind(':author_id', $adminId)
                ->bind(':title', mb_substr($title, 0, 255))
                ->bind(':description', $description !== '' ? $description : null)
                ->bind(':topic', $topic !== '' ? mb_substr($topic, 0, 120) : null)
                ->bind(':difficulty', $difficulty)
                ->bind(':time_limit_minutes', $timeLimitMinutes > 0 ? $timeLimitMinutes : null)
                ->bind(':pass_min_correct', $passMinCorrect > 0 ? $passMinCorrect : null)
                ->bind(':pass_min_points', $passMinPoints > 0 ? $passMinPoints : null)
                ->bind(':approved_by', $adminId)
                ->bind(':published_by', $adminId)
                ->execute();

            if (!$ok) {
                $this->db->query('ROLLBACK')->execute();
                return false;
            }

            $setId = (int) $this->db->lastInsertId();
            if (!$this->insertQuestions($setId, $questions)) {
                $this->db->query('ROLLBACK')->execute();
                return false;
            }

            $this->db->query('COMMIT')->execute();
            return $setId;
        } catch (Throwable $e) {
            try {
                $this->db->query('ROLLBACK')->execute();
            } catch (Throwable $ignored) {
            }
            return false;
        }
    }

    public function updateSetByAdmin(int $setId, array $payload, int $adminId): bool
    {
        $this->ensureSchema();

        if ($setId <= 0 || $adminId <= 0) {
            return false;
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $topic = trim((string) ($payload['topic'] ?? ''));
        $difficulty = (string) ($payload['difficulty'] ?? 'easy');
        $difficulty = in_array($difficulty, ['easy', 'medium', 'hard'], true) ? $difficulty : 'easy';
        $timeLimitMinutes = max(0, min(600, (int) ($payload['time_limit_minutes'] ?? 0)));
        $questions = is_array($payload['questions'] ?? null) ? $payload['questions'] : [];
        $passMinCorrect = max(0, (int) ($payload['pass_min_correct'] ?? 0));
        $passMinPoints = max(0, (int) ($payload['pass_min_points'] ?? 0));

        if ($title === '' || $questions === []) {
            return false;
        }

        try {
            $this->db->query('START TRANSACTION')->execute();

            $updated = $this->db
                ->query("UPDATE quiz_sets
                         SET title = :title,
                             description = :description,
                             topic = :topic,
                             difficulty = :difficulty,
                             time_limit_minutes = :time_limit_minutes,
                             pass_min_correct = :pass_min_correct,
                             pass_min_points = :pass_min_points,
                             status = 'published',
                             approved_at = NOW(),
                             approved_by = :approved_by,
                             published_at = NOW(),
                             published_by = :published_by,
                             updated_at = NOW()
                         WHERE id = :id")
                ->bind(':title', mb_substr($title, 0, 255))
                ->bind(':description', $description !== '' ? $description : null)
                ->bind(':topic', $topic !== '' ? mb_substr($topic, 0, 120) : null)
                ->bind(':difficulty', $difficulty)
                ->bind(':time_limit_minutes', $timeLimitMinutes > 0 ? $timeLimitMinutes : null)
                ->bind(':pass_min_correct', $passMinCorrect > 0 ? $passMinCorrect : null)
                ->bind(':pass_min_points', $passMinPoints > 0 ? $passMinPoints : null)
                ->bind(':approved_by', $adminId)
                ->bind(':published_by', $adminId)
                ->bind(':id', $setId)
                ->execute();

            if (!$updated) {
                $this->db->query('ROLLBACK')->execute();
                return false;
            }

            $this->db->query('DELETE FROM quiz_questions WHERE quiz_set_id = :set_id')
                ->bind(':set_id', $setId)
                ->execute();

            if (!$this->insertQuestions($setId, $questions)) {
                $this->db->query('ROLLBACK')->execute();
                return false;
            }

            $this->db->query('COMMIT')->execute();
            return true;
        } catch (Throwable $e) {
            try {
                $this->db->query('ROLLBACK')->execute();
            } catch (Throwable $ignored) {
            }
            return false;
        }
    }

    private function insertQuestions(int $setId, array $questions): bool
    {
        $order = 1;
        foreach ($questions as $question) {
            $questionType = (string) ($question['question_type'] ?? 'single_choice');
            if (!in_array($questionType, ['single_choice', 'multiple_choice', 'fill_blank', 'ordering'], true)) {
                return false;
            }

            $questionText = trim((string) ($question['text'] ?? ''));
            $questionImage = trim((string) ($question['image'] ?? ''));
            $questionPoints = max(1, (int) ($question['points'] ?? 1));
            $explanation = trim((string) ($question['explanation'] ?? ''));
            $choices = is_array($question['choices'] ?? null) ? $question['choices'] : [];
            $answerKey = $question['answer_key'] ?? null;

            if ($questionText === '') {
                return false;
            }

            $answerKeyJson = null;
            if ($questionType === 'fill_blank') {
                $accepted = is_array($answerKey) ? $answerKey : [];
                $allAreArrays = true;
                foreach ($accepted as $item) {
                    if (!is_array($item)) {
                        $allAreArrays = false;
                        break;
                    }
                }

                if ($allAreArrays && $accepted !== []) {
                    $blanks = [];
                    foreach ($accepted as $blankAnswers) {
                        $blankSet = [];
                        foreach ((array) $blankAnswers as $variant) {
                            $text = $this->normalizeText((string) $variant);
                            if ($text !== '') {
                                $blankSet[$text] = true;
                            }
                        }
                        if ($blankSet === []) {
                            return false;
                        }
                        $blanks[] = array_keys($blankSet);
                    }
                    $answerKeyJson = json_encode(['blanks' => $blanks], JSON_UNESCAPED_UNICODE);
                } else {
                    $normalized = [];
                    foreach ($accepted as $item) {
                        $text = $this->normalizeText((string) $item);
                        if ($text !== '') {
                            $normalized[$text] = true;
                        }
                    }
                    if ($normalized === []) {
                        return false;
                    }
                    $answerKeyJson = json_encode(array_keys($normalized), JSON_UNESCAPED_UNICODE);
                }
            }

            $ok = $this->db
                ->query('INSERT INTO quiz_questions (quiz_set_id, question_type, question_text, question_image, points, answer_key_json, explanation, display_order, created_at, updated_at)
                         VALUES (:set_id, :question_type, :question_text, :question_image, :points, :answer_key_json, :explanation, :display_order, NOW(), NOW())')
                ->bind(':set_id', $setId)
                ->bind(':question_type', $questionType)
                ->bind(':question_text', $questionText)
                ->bind(':question_image', $questionImage !== '' ? $questionImage : null)
                ->bind(':points', $questionPoints)
                ->bind(':answer_key_json', $answerKeyJson)
                ->bind(':explanation', $explanation !== '' ? $explanation : null)
                ->bind(':display_order', $order)
                ->execute();

            if (!$ok) {
                return false;
            }

            $questionId = (int) $this->db->lastInsertId();

            if (in_array($questionType, ['single_choice', 'multiple_choice', 'ordering'], true)) {
                if (count($choices) < 2) {
                    return false;
                }

                $choiceOrder = 1;
                $correctCount = 0;
                foreach ($choices as $choice) {
                    $choiceText = trim((string) ($choice['text'] ?? ''));
                    if ($choiceText === '') {
                        return false;
                    }

                    $isCorrect = (int) ($choice['is_correct'] ?? 0) === 1 ? 1 : 0;
                    if ($questionType === 'ordering') {
                        $isCorrect = 0;
                    }
                    if ($isCorrect === 1) {
                        $correctCount++;
                    }

                    $saved = $this->db
                        ->query('INSERT INTO quiz_question_choices (question_id, choice_text, is_correct, display_order, created_at)
                                 VALUES (:question_id, :choice_text, :is_correct, :display_order, NOW())')
                        ->bind(':question_id', $questionId)
                        ->bind(':choice_text', mb_substr($choiceText, 0, 500))
                        ->bind(':is_correct', $isCorrect)
                        ->bind(':display_order', $choiceOrder)
                        ->execute();

                    if (!$saved) {
                        return false;
                    }
                    $choiceOrder++;
                }

                if ($questionType === 'single_choice' && $correctCount !== 1) {
                    return false;
                }
                if ($questionType === 'multiple_choice' && $correctCount < 1) {
                    return false;
                }
            }

            $order++;
        }

        return true;
    }

    public function submitAttempt(int $setId, int $userId, array $answers): array
    {
        $this->ensureSchema();

        $set = $this->findPublishedSetById($setId);
        if (!$set) {
            return ['ok' => false, 'error' => 'quiz_not_found'];
        }

        $questions = $this->questionsWithChoices($setId);
        if ($questions === []) {
            return ['ok' => false, 'error' => 'quiz_empty'];
        }

        $latestAttempt = $this->latestAttempt($setId, $userId);
        if (is_array($latestAttempt) && (int) ($latestAttempt['is_passed'] ?? 0) === 0) {
            $lastTime = strtotime((string) ($latestAttempt['created_at'] ?? ''));
            if ($lastTime !== false) {
                $elapsed = time() - $lastTime;
                $cooldownSeconds = 180;
                $remaining = $cooldownSeconds - $elapsed;
                if ($remaining > 0) {
                    return [
                        'ok' => false,
                        'error' => 'retry_cooldown',
                        'wait_seconds' => $remaining,
                    ];
                }
            }
        }

        $total = count($questions);
        $totalPoints = 0;
        $correct = 0;
        $earnedPoints = 0;

        foreach ($questions as $question) {
            $questionId = (int) ($question['id'] ?? 0);
            $questionType = (string) ($question['question_type'] ?? 'single_choice');
            $questionPoints = max(1, (int) ($question['points'] ?? 1));
            $totalPoints += $questionPoints;
            $userAnswer = $answers[$questionId] ?? null;

            if ($this->isCorrectAnswer($questionType, $question, $userAnswer)) {
                $correct++;
                $earnedPoints += $questionPoints;
            }
        }

        $score = round(($earnedPoints * 100) / max(1, $totalPoints), 2);
        $requiredCorrect = max(1, min($total, (int) ($set['pass_min_correct'] ?? 0)));
        $requiredPoints = max(1, min($totalPoints, (int) ($set['pass_min_points'] ?? 0)));
        if ((int) ($set['pass_min_correct'] ?? 0) <= 0) {
            $requiredCorrect = $total;
        }
        if ((int) ($set['pass_min_points'] ?? 0) <= 0) {
            $requiredPoints = $totalPoints;
        }
        $isPassed = $correct >= $requiredCorrect && $earnedPoints >= $requiredPoints;

        $attemptSaved = $this->db
            ->query('INSERT INTO quiz_attempts (quiz_set_id, user_id, score_percent, total_questions, correct_answers, is_passed, submitted_answers_json, created_at)
                     VALUES (:set_id, :user_id, :score, :total_questions, :correct_answers, :is_passed, :answers_json, NOW())')
            ->bind(':set_id', $setId)
            ->bind(':user_id', $userId)
            ->bind(':score', $score)
            ->bind(':total_questions', $total)
            ->bind(':correct_answers', $correct)
            ->bind(':is_passed', $isPassed ? 1 : 0)
            ->bind(':answers_json', json_encode($answers, JSON_UNESCAPED_UNICODE))
            ->execute();

        if (!$attemptSaved) {
            return ['ok' => false, 'error' => 'attempt_save_failed'];
        }

        $certificate = $this->findCertificate($setId, $userId);
        $awardedPoints = 0;
        if ($isPassed && !$certificate) {
            $awardedPoints = 10;
            $certificateCode = 'QZ-' . $setId . '-' . $userId . '-' . strtoupper(bin2hex(random_bytes(4)));

            try {
                $this->db->query('START TRANSACTION')->execute();

                $insertCertificate = $this->db
                    ->query('INSERT INTO quiz_certificates (quiz_set_id, user_id, certificate_code, score_percent, awarded_reputation_points, awarded_at)
                             VALUES (:set_id, :user_id, :certificate_code, :score, :points, NOW())')
                    ->bind(':set_id', $setId)
                    ->bind(':user_id', $userId)
                    ->bind(':certificate_code', $certificateCode)
                    ->bind(':score', $score)
                    ->bind(':points', $awardedPoints)
                    ->execute();

                if (!$insertCertificate) {
                    $this->db->query('ROLLBACK')->execute();
                } else {
                    $this->db->query('UPDATE users SET reputation_points = reputation_points + :points WHERE id = :user_id')
                        ->bind(':points', $awardedPoints)
                        ->bind(':user_id', $userId)
                        ->execute();
                    $this->db->query('COMMIT')->execute();
                }
            } catch (Throwable $e) {
                try {
                    $this->db->query('ROLLBACK')->execute();
                } catch (Throwable $ignored) {
                }
                $awardedPoints = 0;
            }

            $certificate = $this->findCertificate($setId, $userId);
        }

        return [
            'ok' => true,
            'score_percent' => $score,
            'total_questions' => $total,
            'correct_answers' => $correct,
            'is_passed' => $isPassed,
            'awarded_points' => $awardedPoints,
            'certificate' => $certificate,
        ];
    }

    private function isCorrectAnswer(string $questionType, array $question, mixed $userAnswer): bool
    {
        $choices = (array) ($question['choices'] ?? []);

        if ($questionType === 'single_choice') {
            $selected = is_scalar($userAnswer) ? (int) $userAnswer : 0;
            $correctId = 0;
            foreach ($choices as $choice) {
                if ((int) ($choice['is_correct'] ?? 0) === 1) {
                    $correctId = (int) ($choice['id'] ?? 0);
                    break;
                }
            }
            return $selected > 0 && $selected === $correctId;
        }

        if ($questionType === 'multiple_choice') {
            $selected = is_array($userAnswer) ? $userAnswer : [];
            $selectedIds = [];
            foreach ($selected as $id) {
                $value = (int) $id;
                if ($value > 0) {
                    $selectedIds[$value] = true;
                }
            }

            $correctIds = [];
            foreach ($choices as $choice) {
                if ((int) ($choice['is_correct'] ?? 0) === 1) {
                    $correctIds[(int) ($choice['id'] ?? 0)] = true;
                }
            }

            if ($selectedIds === [] || $correctIds === []) {
                return false;
            }

            $selectedKeys = array_keys($selectedIds);
            $correctKeys = array_keys($correctIds);
            sort($selectedKeys);
            sort($correctKeys);
            return $selectedKeys === $correctKeys;
        }

        if ($questionType === 'fill_blank') {
            $decoded = json_decode((string) ($question['answer_key_json'] ?? '[]'), true);
            if (!is_array($decoded) || $decoded === []) {
                return false;
            }

            if (isset($decoded['blanks']) && is_array($decoded['blanks'])) {
                $submitted = is_array($userAnswer) ? $userAnswer : [];
                $blanks = $decoded['blanks'];
                if ($blanks === []) {
                    return false;
                }
                foreach ($blanks as $idx => $variants) {
                    $position = $idx + 1;
                    $answerText = $this->normalizeText((string) ($submitted[$position] ?? ''));
                    if ($answerText === '') {
                        return false;
                    }
                    $acceptedSet = [];
                    foreach ((array) $variants as $variant) {
                        $normalized = $this->normalizeText((string) $variant);
                        if ($normalized !== '') {
                            $acceptedSet[$normalized] = true;
                        }
                    }
                    if ($acceptedSet === [] || !isset($acceptedSet[$answerText])) {
                        return false;
                    }
                }
                return true;
            }

            $text = $this->normalizeText(is_scalar($userAnswer) ? (string) $userAnswer : '');
            if ($text === '') {
                return false;
            }
            $set = [];
            foreach ($decoded as $item) {
                $normalized = $this->normalizeText((string) $item);
                if ($normalized !== '') {
                    $set[$normalized] = true;
                }
            }
            return isset($set[$text]);
        }

        if ($questionType === 'ordering') {
            $submittedOrder = is_array($userAnswer) ? $userAnswer : [];
            $submitted = [];
            foreach ($submittedOrder as $choiceId) {
                $id = (int) $choiceId;
                if ($id > 0) {
                    $submitted[] = $id;
                }
            }

            $correct = [];
            foreach ($choices as $choice) {
                $correct[] = (int) ($choice['id'] ?? 0);
            }

            return $submitted !== []
                && count($submitted) === count($correct)
                && $submitted === $correct;
        }

        return false;
    }

    private function normalizeText(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return mb_strtolower($value, 'UTF-8');
    }

    public function findCertificate(int $setId, int $userId): array|false
    {
        $this->ensureSchema();

        $this->db->query('SELECT * FROM quiz_certificates WHERE quiz_set_id = :set_id AND user_id = :user_id LIMIT 1')
            ->bind(':set_id', $setId)
            ->bind(':user_id', $userId)
            ->execute();

        return $this->db->single();
    }

    public function latestAttempt(int $setId, int $userId): array|false
    {
        $this->ensureSchema();

        $this->db->query('SELECT id, score_percent, total_questions, correct_answers, is_passed, created_at
                          FROM quiz_attempts
                          WHERE quiz_set_id = :set_id AND user_id = :user_id
                          ORDER BY id DESC
                          LIMIT 1')
            ->bind(':set_id', $setId)
            ->bind(':user_id', $userId)
            ->execute();

        return $this->db->single();
    }

    public function certificatesByUser(int $userId): array
    {
        $this->ensureSchema();

        $this->db->query("SELECT qc.id,
                                 qc.quiz_set_id,
                                 qc.certificate_code,
                                 qc.score_percent,
                                 qc.awarded_reputation_points,
                                 qc.awarded_at,
                                 qs.title AS quiz_title,
                                 qs.topic AS quiz_topic
                          FROM quiz_certificates qc
                          INNER JOIN quiz_sets qs ON qs.id = qc.quiz_set_id
                          WHERE qc.user_id = :user_id
                          ORDER BY qc.awarded_at DESC, qc.id DESC")
            ->bind(':user_id', $userId)
            ->execute();

        return $this->db->resultSet();
    }

    public function certificateCountByUser(int $userId): int
    {
        $this->ensureSchema();

        $this->db->query('SELECT COUNT(*) AS total FROM quiz_certificates WHERE user_id = :user_id')
            ->bind(':user_id', $userId)
            ->execute();
        $row = $this->db->single();
        return (int) ($row['total'] ?? 0);
    }

    private function normalizePositiveIds(array $ids): array
    {
        $normalized = [];
        foreach ($ids as $id) {
            $value = (int) $id;
            if ($value > 0) {
                $normalized[$value] = $value;
            }
        }
        return array_values($normalized);
    }

    public function participantsPreviewBySetIdsForAdmin(array $setIds, int $limitPerSet = 3): array
    {
        $this->ensureSchema();

        $ids = $this->normalizePositiveIds($setIds);
        if ($ids === []) {
            return [];
        }

        $limit = max(1, $limitPerSet);
        $placeholders = [];
        foreach ($ids as $idx => $_id) {
            $placeholders[] = ':set_' . $idx;
        }

        $query = $this->db->query(
            "SELECT qa.quiz_set_id,
                    u.id AS user_id,
                    u.name AS user_name,
                    u.email AS user_email,
                    COUNT(qa.id) AS attempts_count,
                    MAX(qa.created_at) AS last_attempt_at,
                    MAX(qa.score_percent) AS best_score_percent,
                    MAX(CASE WHEN qa.is_passed = 1 THEN 1 ELSE 0 END) AS has_passed,
                    COALESCE(qc.certificate_code, '') AS certificate_code
             FROM quiz_attempts qa
             INNER JOIN users u ON u.id = qa.user_id
             LEFT JOIN quiz_certificates qc
                    ON qc.quiz_set_id = qa.quiz_set_id
                   AND qc.user_id = qa.user_id
             WHERE qa.quiz_set_id IN (" . implode(', ', $placeholders) . ")
             GROUP BY qa.quiz_set_id, u.id, u.name, u.email, qc.certificate_code
             ORDER BY qa.quiz_set_id ASC, last_attempt_at DESC, qa.user_id DESC"
        );
        foreach ($ids as $idx => $setId) {
            $query->bind(':set_' . $idx, $setId, PDO::PARAM_INT);
        }
        $query->execute();

        $rows = $query->resultSet();
        $grouped = [];
        foreach ($rows as $row) {
            $setId = (int) ($row['quiz_set_id'] ?? 0);
            if ($setId <= 0) {
                continue;
            }
            if (!isset($grouped[$setId])) {
                $grouped[$setId] = [];
            }
            if (count($grouped[$setId]) >= $limit) {
                continue;
            }
            $grouped[$setId][] = $row;
        }

        return $grouped;
    }

    public function passersPreviewBySetIdsForAdmin(array $setIds, int $limitPerSet = 3): array
    {
        $this->ensureSchema();

        $ids = $this->normalizePositiveIds($setIds);
        if ($ids === []) {
            return [];
        }

        $limit = max(1, $limitPerSet);
        $placeholders = [];
        foreach ($ids as $idx => $_id) {
            $placeholders[] = ':set_' . $idx;
        }

        $query = $this->db->query(
            "SELECT qc.quiz_set_id,
                    qc.id AS certificate_id,
                    u.id AS user_id,
                    u.name AS user_name,
                    u.email AS user_email,
                    qc.certificate_code,
                    qc.score_percent,
                    qc.awarded_reputation_points,
                    qc.awarded_at
             FROM quiz_certificates qc
             INNER JOIN users u ON u.id = qc.user_id
             WHERE qc.quiz_set_id IN (" . implode(', ', $placeholders) . ")
             ORDER BY qc.quiz_set_id ASC, qc.awarded_at DESC, qc.id DESC"
        );
        foreach ($ids as $idx => $setId) {
            $query->bind(':set_' . $idx, $setId, PDO::PARAM_INT);
        }
        $query->execute();

        $rows = $query->resultSet();
        $grouped = [];
        foreach ($rows as $row) {
            $setId = (int) ($row['quiz_set_id'] ?? 0);
            if ($setId <= 0) {
                continue;
            }
            if (!isset($grouped[$setId])) {
                $grouped[$setId] = [];
            }
            if (count($grouped[$setId]) >= $limit) {
                continue;
            }
            $grouped[$setId][] = $row;
        }

        return $grouped;
    }

    public function participantsBySetForAdmin(int $setId): array
    {
        $this->ensureSchema();

        $this->db->query("SELECT u.id AS user_id,
                                 u.name AS user_name,
                                 u.email AS user_email,
                                 COUNT(qa.id) AS attempts_count,
                                 MAX(qa.created_at) AS last_attempt_at,
                                 MAX(qa.score_percent) AS best_score_percent,
                                 MAX(CASE WHEN qa.is_passed = 1 THEN 1 ELSE 0 END) AS has_passed,
                                 COALESCE(qc.certificate_code, '') AS certificate_code
                          FROM quiz_attempts qa
                          INNER JOIN users u ON u.id = qa.user_id
                          LEFT JOIN quiz_certificates qc
                                 ON qc.quiz_set_id = qa.quiz_set_id
                                AND qc.user_id = qa.user_id
                          WHERE qa.quiz_set_id = :set_id
                          GROUP BY u.id, u.name, u.email, qc.certificate_code
                          ORDER BY last_attempt_at DESC, qa.user_id DESC")
            ->bind(':set_id', $setId)
            ->execute();

        return $this->db->resultSet();
    }

    public function passersBySetForAdmin(int $setId): array
    {
        $this->ensureSchema();

        $this->db->query("SELECT qc.id AS certificate_id,
                                 u.id AS user_id,
                                 u.name AS user_name,
                                 u.email AS user_email,
                                 qc.certificate_code,
                                 qc.score_percent,
                                 qc.awarded_reputation_points,
                                 qc.awarded_at
                          FROM quiz_certificates qc
                          INNER JOIN users u ON u.id = qc.user_id
                          WHERE qc.quiz_set_id = :set_id
                          ORDER BY qc.awarded_at DESC, qc.id DESC")
            ->bind(':set_id', $setId)
            ->execute();

        return $this->db->resultSet();
    }

    public function deleteCertificateByIdForAdmin(int $certificateId): bool
    {
        $this->ensureSchema();

        if ($certificateId <= 0) {
            return false;
        }

        $this->db->query('SELECT id, user_id, awarded_reputation_points
                          FROM quiz_certificates
                          WHERE id = :id
                          LIMIT 1')
            ->bind(':id', $certificateId)
            ->execute();
        $certificate = $this->db->single();
        if (!$certificate) {
            return false;
        }

        $userId = (int) ($certificate['user_id'] ?? 0);
        $awarded = max(0, (int) ($certificate['awarded_reputation_points'] ?? 0));

        try {
            $this->db->query('START TRANSACTION')->execute();

            if ($awarded > 0 && $userId > 0) {
                $this->db->query('UPDATE users
                                  SET reputation_points = GREATEST(0, reputation_points - :points)
                                  WHERE id = :user_id')
                    ->bind(':points', $awarded)
                    ->bind(':user_id', $userId)
                    ->execute();
            }

            $deleted = $this->db->query('DELETE FROM quiz_certificates WHERE id = :id')
                ->bind(':id', $certificateId)
                ->execute();

            if (!$deleted) {
                $this->db->query('ROLLBACK')->execute();
                return false;
            }

            $this->db->query('COMMIT')->execute();
            return true;
        } catch (Throwable $e) {
            try {
                $this->db->query('ROLLBACK')->execute();
            } catch (Throwable $ignored) {
            }
            return false;
        }
    }

    public function deleteSetByAdmin(int $setId): bool
    {
        $this->ensureSchema();

        if ($setId <= 0) {
            return false;
        }

        $set = $this->findSetByIdForAdmin($setId);
        if (!$set) {
            return false;
        }

        try {
            $this->db->query('START TRANSACTION')->execute();

            $this->db->query('SELECT user_id, SUM(awarded_reputation_points) AS total_awarded
                              FROM quiz_certificates
                              WHERE quiz_set_id = :set_id
                              GROUP BY user_id')
                ->bind(':set_id', $setId)
                ->execute();
            $awardedRows = $this->db->resultSet();

            foreach ($awardedRows as $row) {
                $userId = (int) ($row['user_id'] ?? 0);
                $points = max(0, (int) ($row['total_awarded'] ?? 0));
                if ($userId <= 0 || $points <= 0) {
                    continue;
                }

                $this->db->query('UPDATE users
                                  SET reputation_points = GREATEST(0, reputation_points - :points)
                                  WHERE id = :user_id')
                    ->bind(':points', $points)
                    ->bind(':user_id', $userId)
                    ->execute();
            }

            $deleted = $this->db->query('DELETE FROM quiz_sets WHERE id = :id')
                ->bind(':id', $setId)
                ->execute();
            if (!$deleted) {
                $this->db->query('ROLLBACK')->execute();
                return false;
            }

            $this->db->query('COMMIT')->execute();
            return true;
        } catch (Throwable $e) {
            try {
                $this->db->query('ROLLBACK')->execute();
            } catch (Throwable $ignored) {
            }
            return false;
        }
    }
}
