<?php

declare(strict_types=1);

class QuizController extends Controller
{
    public function index(): void
    {
        /** @var QuizModel $quizModel */
        $quizModel = $this->model('QuizModel');

        $this->view('quizzes/index', [
            'title' => 'Bộ câu hỏi',
            'useRecipeHubLayout' => true,
            'sets' => $quizModel->listPublishedSets(),
        ]);
    }

    public function show(string $id): void
    {
        $setId = (int) $id;
        if ($setId <= 0) {
            $this->renderNotFound('Không tìm thấy bộ câu hỏi.');
            return;
        }

        /** @var QuizModel $quizModel */
        $quizModel = $this->model('QuizModel');
        $set = $quizModel->findPublishedSetById($setId);
        if (!$set) {
            $this->renderNotFound('Không tìm thấy bộ câu hỏi.');
            return;
        }

        $userId = (int) (current_user_id() ?? 0);
        $certificate = $userId > 0 ? $quizModel->findCertificate($setId, $userId) : false;
        $latestAttempt = $userId > 0 ? $quizModel->latestAttempt($setId, $userId) : false;
        $cooldownRemainingSeconds = 0;
        if (is_array($latestAttempt) && (int) ($latestAttempt['is_passed'] ?? 0) === 0) {
            $lastTime = strtotime((string) ($latestAttempt['created_at'] ?? ''));
            if ($lastTime !== false) {
                $cooldownRemainingSeconds = max(0, 180 - (time() - $lastTime));
            }
        }

        $notice = (string) ($_GET['notice'] ?? '');
        $waitSeconds = max(0, (int) ($_GET['wait'] ?? 0));
        $noticeText = match ($notice) {
            'passed' => 'Bạn đã trả lời đúng toàn bộ. Chứng nhận đã được cấp.',
            'failed' => 'Bạn chưa đạt điều kiện. Hãy thử lại để nhận chứng nhận.',
            'retry_wait' => 'Bạn chưa đạt. Vui lòng chờ ' . max(1, $waitSeconds) . ' giây để thi lại.',
            'submit_error' => 'Không thể nộp bài lúc này. Vui lòng thử lại.',
            default => '',
        };

        $this->view('quizzes/show', [
            'title' => 'Lam quiz',
            'useRecipeHubLayout' => true,
            'set' => $set,
            'questions' => $quizModel->questionsWithChoices($setId),
            'certificate' => $certificate,
            'latestAttempt' => $latestAttempt,
            'cooldownRemainingSeconds' => $cooldownRemainingSeconds,
            'noticeText' => $noticeText,
        ]);
    }

    public function submitAttempt(string $id): void
    {
        require_login();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/quizzes/' . (int) $id);
        }

        $setId = (int) $id;
        $userId = (int) (current_user_id() ?? 0);
        if ($setId <= 0 || $userId <= 0) {
            $this->redirect('/quizzes?notice=submit_error');
        }

        $answers = [];

        $single = $_POST['answers_single'] ?? [];
        if (is_array($single)) {
            foreach ($single as $questionId => $choiceId) {
                $qid = (int) $questionId;
                $cid = (int) $choiceId;
                if ($qid > 0 && $cid > 0) {
                    $answers[$qid] = $cid;
                }
            }
        }

        $multi = $_POST['answers_multi'] ?? [];
        if (is_array($multi)) {
            foreach ($multi as $questionId => $choiceIds) {
                $qid = (int) $questionId;
                if ($qid <= 0 || !is_array($choiceIds)) {
                    continue;
                }
                $values = [];
                foreach ($choiceIds as $choiceId) {
                    $cid = (int) $choiceId;
                    if ($cid > 0) {
                        $values[] = $cid;
                    }
                }
                $answers[$qid] = $values;
            }
        }

        $fill = $_POST['answers_text'] ?? [];
        if (is_array($fill)) {
            foreach ($fill as $questionId => $text) {
                $qid = (int) $questionId;
                if ($qid <= 0) {
                    continue;
                }
                if (is_array($text)) {
                    $mapped = [];
                    foreach ($text as $blankNo => $blankValue) {
                        $n = (int) $blankNo;
                        if ($n > 0) {
                            $mapped[$n] = trim((string) $blankValue);
                        }
                    }
                    $answers[$qid] = $mapped;
                } else {
                    $answers[$qid] = trim((string) $text);
                }
            }
        }

        $order = $_POST['answers_order'] ?? [];
        if (is_array($order)) {
            foreach ($order as $questionId => $rankByChoice) {
                $qid = (int) $questionId;
                if ($qid <= 0 || !is_array($rankByChoice)) {
                    continue;
                }

                $rankMap = [];
                foreach ($rankByChoice as $choiceId => $rank) {
                    $cid = (int) $choiceId;
                    $r = (int) $rank;
                    if ($cid > 0 && $r > 0) {
                        $rankMap[] = ['choice_id' => $cid, 'rank' => $r];
                    }
                }
                usort($rankMap, static fn(array $a, array $b): int => $a['rank'] <=> $b['rank']);

                $ordered = [];
                foreach ($rankMap as $item) {
                    $ordered[] = (int) $item['choice_id'];
                }
                $answers[$qid] = $ordered;
            }
        }

        /** @var QuizModel $quizModel */
        $quizModel = $this->model('QuizModel');
        $result = $quizModel->submitAttempt($setId, $userId, $answers);
        if (!(bool) ($result['ok'] ?? false)) {
            if ((string) ($result['error'] ?? '') === 'retry_cooldown') {
                $wait = max(1, (int) ($result['wait_seconds'] ?? 1));
                $this->redirect('/quizzes/' . $setId . '?notice=retry_wait&wait=' . $wait);
            }
            $this->redirect('/quizzes/' . $setId . '?notice=submit_error');
        }

        $notice = ((bool) ($result['is_passed'] ?? false)) ? 'passed' : 'failed';
        $this->redirect('/quizzes/' . $setId . '?notice=' . $notice);
    }

    public function myCertificates(): void
    {
        require_login();

        $userId = (int) (current_user_id() ?? 0);
        /** @var QuizModel $quizModel */
        $quizModel = $this->model('QuizModel');

        $this->view('quizzes/certificates', [
            'title' => 'Chứng nhận của tôi',
            'useRecipeHubLayout' => true,
            'certificates' => $quizModel->certificatesByUser($userId),
        ]);
    }
}
