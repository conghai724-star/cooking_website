<?php

declare(strict_types=1);

class StatsAdminService
{
    public function buildManageStatsData(array $query): array
    {
        [$from, $to, $granularity] = $this->statsFiltersFromRequest($query);

        /** @var AdminStatsModel $statsModel */
        $statsModel = $this->model('AdminStatsModel');

        return [
            'from' => $from,
            'to' => $to,
            'granularity' => $granularity,
            'overview' => $statsModel->overview($from, $to),
            'series' => $statsModel->timeSeries($from, $to, $granularity),
            'topAuthors' => $statsModel->topAuthors($from, $to, 8),
            'topContents' => $statsModel->topContents($from, $to, 8),
        ];
    }

    public function exportStatsCsv(array $query): void
    {
        [$from, $to, $granularity] = $this->statsFiltersFromRequest($query);

        /** @var AdminStatsModel $statsModel */
        $statsModel = $this->model('AdminStatsModel');
        $overview = $statsModel->overview($from, $to);
        $series = $statsModel->timeSeries($from, $to, $granularity);
        $topAuthors = $statsModel->topAuthors($from, $to, 20);
        $topContents = $statsModel->topContents($from, $to, 20);

        $filename = 'admin-stats-' . $from . '-to-' . $to . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'wb');
        if ($out === false) {
            exit;
        }

        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Tong quan', '', '']);
        fputcsv($out, ['Metric', 'Value', 'Note']);
        fputcsv($out, ['users_new', (int) ($overview['users_new'] ?? 0), '']);
        fputcsv($out, ['users_active', (int) ($overview['users_active'] ?? 0), '']);
        fputcsv($out, ['reports_new', (int) ($overview['reports_new'] ?? 0), '']);
        fputcsv($out, ['reports_resolved', (int) ($overview['reports_resolved'] ?? 0), '']);
        fputcsv($out, ['comments_new', (int) ($overview['comments_new'] ?? 0), '']);
        fputcsv($out, ['comments_hidden_deleted', (int) ($overview['comments_hidden_deleted'] ?? 0), '']);
        fputcsv($out, ['approval_rate_percent', (float) ($overview['approval_rate'] ?? 0), '']);
        fputcsv($out, ['total_submitted', (int) ($overview['total_submitted'] ?? 0), '']);
        fputcsv($out, ['total_approved', (int) ($overview['total_approved'] ?? 0), '']);
        fputcsv($out, []);

        fputcsv($out, ['Trang thai noi dung', '', '']);
        fputcsv($out, ['content_type', 'status', 'count']);
        foreach (['recipes', 'ingredients', 'tips'] as $type) {
            $bucket = (array) ($overview[$type] ?? []);
            fputcsv($out, [$type, 'pending', (int) ($bucket['pending'] ?? 0)]);
            fputcsv($out, [$type, 'approved', (int) ($bucket['approved'] ?? 0)]);
            fputcsv($out, [$type, 'rejected', (int) ($bucket['rejected'] ?? 0)]);
        }
        fputcsv($out, []);

        fputcsv($out, ['Time series (' . $granularity . ')', '', '', '', '']);
        fputcsv($out, ['bucket', 'registrations', 'submissions', 'reports_new', 'reports_resolved']);
        $labels = (array) ($series['labels'] ?? []);
        $registrations = (array) ($series['registrations'] ?? []);
        $submissions = (array) ($series['submissions'] ?? []);
        $reportsNew = (array) ($series['reports_new'] ?? []);
        $reportsResolved = (array) ($series['reports_resolved'] ?? []);
        $maxRows = max(count($labels), count($registrations), count($submissions), count($reportsNew), count($reportsResolved));
        for ($i = 0; $i < $maxRows; $i++) {
            fputcsv($out, [
                (string) ($labels[$i] ?? ''),
                (int) ($registrations[$i] ?? 0),
                (int) ($submissions[$i] ?? 0),
                (int) ($reportsNew[$i] ?? 0),
                (int) ($reportsResolved[$i] ?? 0),
            ]);
        }
        fputcsv($out, []);

        fputcsv($out, ['Top tac gia', '', '']);
        fputcsv($out, ['rank', 'author_name', 'total_posts']);
        foreach ($topAuthors as $idx => $row) {
            fputcsv($out, [
                $idx + 1,
                (string) ($row['author_name'] ?? ''),
                (int) ($row['total_posts'] ?? 0),
            ]);
        }
        fputcsv($out, []);

        fputcsv($out, ['Top noi dung', '', '', '', '']);
        fputcsv($out, ['rank', 'recipe_title', 'save_count', 'comment_count', 'interaction_score']);
        foreach ($topContents as $idx => $row) {
            fputcsv($out, [
                $idx + 1,
                (string) ($row['title'] ?? ''),
                (int) ($row['save_count'] ?? 0),
                (int) ($row['comment_count'] ?? 0),
                (int) ($row['interaction_score'] ?? 0),
            ]);
        }

        fclose($out);
        exit;
    }

    private function statsFiltersFromRequest(array $query): array
    {
        $fromRaw = trim((string) ($query['from'] ?? ''));
        $toRaw = trim((string) ($query['to'] ?? ''));
        $granularity = (string) ($query['g'] ?? 'day');

        $today = date('Y-m-d');
        $defaultFrom = date('Y-m-d', strtotime('-29 days'));
        $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromRaw) ? $fromRaw : $defaultFrom;
        $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $toRaw) ? $toRaw : $today;

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        if (!in_array($granularity, ['day', 'week', 'month'], true)) {
            $granularity = 'day';
        }

        return [$from, $to, $granularity];
    }

    private function model(string $model): object
    {
        $modelPath = APPROOT . '/app/models/' . $model . '.php';
        if (!file_exists($modelPath)) {
            throw new RuntimeException('Không tìm thấy model: ' . $model);
        }

        require_once $modelPath;
        return new $model();
    }
}