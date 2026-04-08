<?php

declare(strict_types=1);

final class QuizController extends Controller
{
    public function manage(): void
    {
        require_admin_permission('admin.recipes.review');

        /** @var QuizModel $quizModel */
        $quizModel = $this->model('QuizModel');

        $notice = (string) ($_GET['notice'] ?? '');
        $noticeText = match ($notice) {
            'created' => 'Da tao bo cau hoi.',
            'updated' => 'Da cap nhat bo cau hoi.',
            'deleted' => 'Da xoa bo cau hoi.',
            'certificate_deleted' => 'Da xoa chung nhan.',
            'save_failed' => 'Khong the luu bo cau hoi. Kiem tra lai du lieu.',
            'delete_failed' => 'Khong the xoa. Thu lai.',
            default => '',
        };

        $sets = $quizModel->listSetsForAdmin();
        $participantPreviews = [];
        $passerPreviews = [];
        foreach ($sets as $setRow) {
            $setId = (int) ($setRow['id'] ?? 0);
            if ($setId <= 0) {
                continue;
            }

            $participants = $quizModel->participantsBySetForAdmin($setId);
            $participantLabels = [];
            foreach ($participants as $participant) {
                $name = trim((string) ($participant['user_name'] ?? ''));
                $email = trim((string) ($participant['user_email'] ?? ''));
                $participantLabels[] = $name !== '' ? $name : ($email !== '' ? $email : ('User #' . (int) ($participant['user_id'] ?? 0)));
            }
            $participantPreviews[$setId] = array_slice($participantLabels, 0, 3);

            $passers = $quizModel->passersBySetForAdmin($setId);
            $passerLabels = [];
            foreach ($passers as $passer) {
                $name = trim((string) ($passer['user_name'] ?? ''));
                $email = trim((string) ($passer['user_email'] ?? ''));
                $passerLabels[] = $name !== '' ? $name : ($email !== '' ? $email : ('User #' . (int) ($passer['user_id'] ?? 0)));
            }
            $passerPreviews[$setId] = array_slice($passerLabels, 0, 3);
        }

        $this->adminView('admin/manage_quizzes', [
            'sets' => $sets,
            'noticeText' => $noticeText,
            'participantPreviews' => $participantPreviews,
            'passerPreviews' => $passerPreviews,
        ]);
    }

    public function show(string $id): void
    {
        require_admin_permission('admin.recipes.review');

        $setId = (int) $id;
        if ($setId <= 0) {
            $this->redirect('/admin/quizzes');
        }

        /** @var QuizModel $quizModel */
        $quizModel = $this->model('QuizModel');
        $set = $quizModel->findSetByIdForAdmin($setId);
        if (!$set) {
            $this->redirect('/admin/quizzes');
        }

        $this->adminView('admin/quiz_detail', [
            'set' => $set,
            'questions' => $quizModel->questionsWithChoices($setId),
        ]);
    }

    public function users(string $id): void
    {
        require_admin_permission('admin.recipes.review');

        $setId = (int) $id;
        if ($setId <= 0) {
            $this->redirect('/admin/quizzes');
        }

        /** @var QuizModel $quizModel */
        $quizModel = $this->model('QuizModel');
        $set = $quizModel->findSetByIdForAdmin($setId);
        if (!$set) {
            $this->redirect('/admin/quizzes');
        }

        $notice = (string) ($_GET['notice'] ?? '');
        $noticeText = match ($notice) {
            'certificate_deleted' => 'Da xoa chung nhan.',
            'delete_failed' => 'Khong the xoa. Thu lai.',
            default => '',
        };

        $this->adminView('admin/quiz_users', [
            'set' => $set,
            'participants' => $quizModel->participantsBySetForAdmin($setId),
            'passers' => $quizModel->passersBySetForAdmin($setId),
            'noticeText' => $noticeText,
        ]);
    }

    public function create(): void
    {
        require_admin_permission('admin.recipes.review');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/admin/quizzes');
        }

        $payload = $this->readPayloadFromPost();
        if ($payload === null) {
            $this->redirect('/admin/quizzes?notice=save_failed');
        }

        $admin = current_admin() ?? [];
        $adminId = (int) ($admin['id'] ?? 0);

        /** @var QuizModel $quizModel */
        $quizModel = $this->model('QuizModel');
        $setId = $quizModel->createSetByAdmin($adminId, $payload);

        if ($setId === false) {
            $this->redirect('/admin/quizzes?notice=save_failed');
        }

        $this->redirect('/admin/quizzes?notice=created');
    }

    public function update(string $id): void
    {
        require_admin_permission('admin.recipes.review');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/admin/quizzes');
        }

        $setId = (int) $id;
        if ($setId <= 0) {
            $this->redirect('/admin/quizzes?notice=save_failed');
        }

        $payload = $this->readPayloadFromPost();
        if ($payload === null) {
            $this->redirect('/admin/quizzes/' . $setId . '?notice=save_failed');
        }

        $admin = current_admin() ?? [];
        $adminId = (int) ($admin['id'] ?? 0);

        /** @var QuizModel $quizModel */
        $quizModel = $this->model('QuizModel');
        $ok = $quizModel->updateSetByAdmin($setId, $payload, $adminId);

        if (!$ok) {
            $this->redirect('/admin/quizzes/' . $setId . '?notice=save_failed');
        }

        $this->redirect('/admin/quizzes?notice=updated');
    }

    public function delete(string $id): void
    {
        require_admin_permission('admin.recipes.review');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/admin/quizzes');
        }

        $setId = (int) $id;
        if ($setId <= 0) {
            $this->redirect('/admin/quizzes?notice=delete_failed');
        }

        /** @var QuizModel $quizModel */
        $quizModel = $this->model('QuizModel');
        $ok = $quizModel->deleteSetByAdmin($setId);
        $this->redirect('/admin/quizzes?notice=' . ($ok ? 'deleted' : 'delete_failed'));
    }

    public function deleteCertificate(string $id): void
    {
        require_admin_permission('admin.recipes.review');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/admin/quizzes');
        }

        $certificateId = (int) $id;
        $setId = max(0, (int) ($_POST['set_id'] ?? 0));

        /** @var QuizModel $quizModel */
        $quizModel = $this->model('QuizModel');
        $ok = $certificateId > 0 ? $quizModel->deleteCertificateByIdForAdmin($certificateId) : false;

        if ($setId > 0) {
            $this->redirect('/admin/quizzes/' . $setId . '/users?notice=' . ($ok ? 'certificate_deleted' : 'delete_failed'));
        }

        $this->redirect('/admin/quizzes?notice=' . ($ok ? 'certificate_deleted' : 'delete_failed'));
    }

    private function readPayloadFromPost(): ?array
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $topic = trim((string) ($_POST['topic'] ?? ''));
        $timeLimitMinutes = max(0, min(600, (int) ($_POST['time_limit_minutes'] ?? 0)));
        $passMinCorrect = max(0, (int) ($_POST['pass_min_correct'] ?? 0));
        $passMinPoints = max(0, (int) ($_POST['pass_min_points'] ?? 0));
        $difficulty = (string) ($_POST['difficulty'] ?? 'easy');
        if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $difficulty = 'easy';
        }

        $rawQuestions = $_POST['questions'] ?? [];
        if (!is_array($rawQuestions) || $title === '') {
            return null;
        }

        $questions = [];
        foreach ($rawQuestions as $index => $rawQuestion) {
            if (!is_array($rawQuestion)) {
                continue;
            }

            $questionText = trim((string) ($rawQuestion['text'] ?? ''));
            $explanation = trim((string) ($rawQuestion['explanation'] ?? ''));
            $questionPoints = max(1, (int) ($rawQuestion['points'] ?? 1));
            $questionType = (string) ($rawQuestion['question_type'] ?? 'single_choice');
            $existingImage = trim((string) ($rawQuestion['existing_image'] ?? ''));
            $image = upload_image_from_array('question_images', (int) $index, APPROOT . '/public/uploads') ?? $existingImage;

            if (!in_array($questionType, ['single_choice', 'multiple_choice', 'fill_blank', 'ordering'], true)) {
                $questionType = 'single_choice';
            }

            if ($questionText === '') {
                continue;
            }

            $choiceLines = trim((string) ($rawQuestion['choice_lines'] ?? ''));
            $answerKeyRaw = trim((string) ($rawQuestion['answer_key'] ?? ''));
            $rawOptions = $rawQuestion['options'] ?? [];
            $rawOrderingItems = $rawQuestion['ordering_items'] ?? [];
            $rawOrderingPositions = $rawQuestion['ordering_positions'] ?? [];
            $orderingCountRaw = (int) ($rawQuestion['ordering_count'] ?? 0);
            $rawCorrectMulti = $rawQuestion['correct_multi'] ?? [];
            $correctMultiCountRaw = (int) ($rawQuestion['correct_count'] ?? 0);
            $correctSingleRaw = trim((string) ($rawQuestion['correct_single'] ?? ''));
            $fillAnswersRaw = trim((string) ($rawQuestion['fill_answers'] ?? ''));
            $fillTemplateRaw = trim((string) ($rawQuestion['fill_template'] ?? ''));
            $rawFillBlankAnswers = $rawQuestion['fill_blank_answers'] ?? [];

            $questionPayload = [
                'text' => $questionText,
                'explanation' => $explanation,
                'points' => $questionPoints,
                'question_type' => $questionType,
                'image' => $image,
                'choices' => [],
                'answer_key' => null,
            ];

            if (in_array($questionType, ['single_choice', 'multiple_choice', 'ordering'], true)) {
                if ($questionType === 'ordering') {
                    $orderingEntries = [];
                    if (is_array($rawOrderingItems) && is_array($rawOrderingPositions)) {
                        $limit = $orderingCountRaw > 0 ? $orderingCountRaw : count($rawOrderingItems);
                        foreach ($rawOrderingItems as $idx => $item) {
                            if ((int) $idx >= $limit) {
                                continue;
                            }
                            $text = trim((string) $item);
                            $pos = (int) ($rawOrderingPositions[$idx] ?? 0);
                            if ($text !== '' && $pos > 0) {
                                $orderingEntries[] = ['text' => $text, 'pos' => $pos];
                            }
                        }
                    }
                    if ($orderingEntries === []) {
                        $orderingItems = [];
                        $lines = preg_split('/\R/u', $choiceLines) ?: [];
                        foreach ($lines as $line) {
                            $text = trim((string) $line);
                            if ($text !== '') {
                                $orderingItems[] = $text;
                            }
                        }
                        if (count($orderingItems) < 2) {
                            continue;
                        }
                        foreach ($orderingItems as $choiceText) {
                            $questionPayload['choices'][] = ['text' => $choiceText, 'is_correct' => 0];
                        }
                    } else {
                        $max = count($orderingEntries);
                        if ($max < 2) {
                            continue;
                        }
                        $used = [];
                        foreach ($orderingEntries as $entry) {
                            if ($entry['pos'] > $max || isset($used[$entry['pos']])) {
                                continue 2;
                            }
                            $used[$entry['pos']] = true;
                        }
                        if (count($used) !== $max) {
                            continue;
                        }
                        usort($orderingEntries, static fn(array $a, array $b): int => $a['pos'] <=> $b['pos']);
                        foreach ($orderingEntries as $entry) {
                            $questionPayload['choices'][] = ['text' => $entry['text'], 'is_correct' => 0];
                        }
                    }
                } elseif ($questionType === 'single_choice') {
                    $options = [];
                    if (is_array($rawOptions)) {
                        foreach ($rawOptions as $option) {
                            $text = trim((string) $option);
                            if ($text !== '') {
                                $options[] = $text;
                            }
                        }
                    }
                    if ($options === []) {
                        $lines = preg_split('/\R/u', $choiceLines) ?: [];
                        foreach ($lines as $line) {
                            $text = trim((string) $line);
                            if ($text !== '') {
                                $options[] = $text;
                            }
                        }
                    }
                    if (count($options) < 2) {
                        continue;
                    }

                    $correctIndexSource = $correctSingleRaw !== '' ? $correctSingleRaw : $answerKeyRaw;
                    $correctIndex = max(1, (int) $correctIndexSource) - 1;
                    if ($correctIndex < 0 || $correctIndex >= count($options)) {
                        continue;
                    }
                    foreach ($options as $idx => $choiceText) {
                        $questionPayload['choices'][] = [
                            'text' => $choiceText,
                            'is_correct' => $idx === $correctIndex ? 1 : 0,
                        ];
                    }
                } else {
                    $options = [];
                    if (is_array($rawOptions)) {
                        foreach ($rawOptions as $option) {
                            $text = trim((string) $option);
                            if ($text !== '') {
                                $options[] = $text;
                            }
                        }
                    }
                    if ($options === []) {
                        $lines = preg_split('/\R/u', $choiceLines) ?: [];
                        foreach ($lines as $line) {
                            $text = trim((string) $line);
                            if ($text !== '') {
                                $options[] = $text;
                            }
                        }
                    }
                    if (count($options) < 2) {
                        continue;
                    }

                    $correctIndexes = [];
                    if (is_array($rawCorrectMulti)) {
                        foreach ($rawCorrectMulti as $part) {
                            $value = (int) trim((string) $part);
                            if ($value > 0) {
                                $correctIndexes[$value - 1] = true;
                            }
                        }
                    }
                    if ($correctIndexes === []) {
                        $parts = preg_split('/[;,\s]+/u', $answerKeyRaw) ?: [];
                        foreach ($parts as $part) {
                            $value = (int) trim((string) $part);
                            if ($value > 0) {
                                $correctIndexes[$value - 1] = true;
                            }
                        }
                    }
                    $correctCount = count($correctIndexes);
                    if ($correctCount < 1) {
                        continue;
                    }
                    if ($correctMultiCountRaw > 0 && $correctCount !== $correctMultiCountRaw) {
                        continue;
                    }

                    foreach ($options as $idx => $choiceText) {
                        $questionPayload['choices'][] = [
                            'text' => $choiceText,
                            'is_correct' => isset($correctIndexes[$idx]) ? 1 : 0,
                        ];
                    }
                }
            }

            if ($questionType === 'fill_blank') {
                if ($fillTemplateRaw !== '') {
                    $questionPayload['text'] = $fillTemplateRaw;
                }

                $blanks = [];
                if (is_array($rawFillBlankAnswers)) {
                    foreach ($rawFillBlankAnswers as $line) {
                        $lineText = trim((string) $line);
                        if ($lineText === '') {
                            continue;
                        }
                        $variants = [];
                        foreach (preg_split('/[|]/u', $lineText) ?: [] as $variant) {
                            $v = trim((string) $variant);
                            if ($v !== '') {
                                $variants[] = $v;
                            }
                        }
                        if ($variants !== []) {
                            $blanks[] = $variants;
                        }
                    }
                }

                if ($blanks === []) {
                    $source = $fillAnswersRaw !== '' ? $fillAnswersRaw : $answerKeyRaw;
                    $parts = array_map('trim', preg_split('/[|\n\r]+/u', $source) ?: []);
                    $accepted = [];
                    foreach ($parts as $part) {
                        if ($part !== '') {
                            $accepted[] = $part;
                        }
                    }
                    if ($accepted === []) {
                        continue;
                    }
                    $questionPayload['answer_key'] = $accepted;
                } else {
                    $questionPayload['answer_key'] = $blanks;
                }
            }

            $questions[] = $questionPayload;
        }

        if ($questions === []) {
            return null;
        }

        $totalQuestions = count($questions);
        $totalPoints = 0;
        foreach ($questions as $question) {
            $totalPoints += max(1, (int) ($question['points'] ?? 1));
        }

        $passMinCorrect = min($passMinCorrect, $totalQuestions);
        $passMinPoints = min($passMinPoints, max(1, $totalPoints));

        return [
            'title' => $title,
            'description' => $description,
            'topic' => $topic,
            'difficulty' => $difficulty,
            'time_limit_minutes' => $timeLimitMinutes,
            'pass_min_correct' => $passMinCorrect,
            'pass_min_points' => $passMinPoints,
            'questions' => $questions,
        ];
    }
}

