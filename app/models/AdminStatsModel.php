<?php

declare(strict_types=1);

class AdminStatsModel extends Model
{
    private array $columnsCache = [];

    private function tableExists(string $table): bool
    {
        $this->db->query('SELECT 1
                          FROM information_schema.tables
                          WHERE table_schema = DATABASE()
                            AND table_name = :table
                          LIMIT 1')
            ->bind(':table', $table)
            ->execute();
        return (bool) $this->db->single();
    }

    private function tableColumns(string $table): array
    {
        if (array_key_exists($table, $this->columnsCache)) {
            return $this->columnsCache[$table];
        }

        if (!$this->tableExists($table)) {
            $this->columnsCache[$table] = [];
            return [];
        }

        $this->db->query("SHOW COLUMNS FROM {$table}")->execute();
        $rows = $this->db->resultSet();
        $columns = [];
        foreach ($rows as $row) {
            if (isset($row['Field'])) {
                $columns[] = (string) $row['Field'];
            }
        }
        $this->columnsCache[$table] = $columns;
        return $columns;
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->tableColumns($table), true);
    }

    private function normalizeGranularity(string $value): string
    {
        return in_array($value, ['day', 'week', 'month'], true) ? $value : 'day';
    }

    private function bucketExpr(string $column, string $granularity): string
    {
        return match ($granularity) {
            'week' => "DATE_FORMAT(DATE_SUB($column, INTERVAL WEEKDAY($column) DAY), '%Y-%m-%d')",
            'month' => "DATE_FORMAT($column, '%Y-%m')",
            default => "DATE_FORMAT($column, '%Y-%m-%d')",
        };
    }

    private function countByDateRange(string $table, string $dateColumn, string $fromDate, string $toDate, string $extraWhere = '', array $binds = []): int
    {
        if (!$this->tableExists($table) || !$this->hasColumn($table, $dateColumn)) {
            return 0;
        }

        $sql = "SELECT COUNT(*) AS total
                FROM {$table}
                WHERE {$dateColumn} >= :from_date
                  AND {$dateColumn} < DATE_ADD(:to_date, INTERVAL 1 DAY)";
        if ($extraWhere !== '') {
            $sql .= ' AND ' . $extraWhere;
        }

        $query = $this->db->query($sql)
            ->bind(':from_date', $fromDate)
            ->bind(':to_date', $toDate);

        foreach ($binds as $k => $v) {
            $query->bind($k, $v);
        }

        $query->execute();
        $row = $query->single();
        return (int) ($row['total'] ?? 0);
    }

    private function countByStatus(string $table, string $status, ?string $fromDate = null, ?string $toDate = null): int
    {
        if (!$this->tableExists($table) || !$this->hasColumn($table, 'status')) {
            return 0;
        }

        $sql = "SELECT COUNT(*) AS total FROM {$table} WHERE status = :status";
        $query = $this->db->query($sql)->bind(':status', $status);

        if ($fromDate !== null && $toDate !== null && $this->hasColumn($table, 'created_at')) {
            $sql .= ' AND created_at >= :from_date AND created_at < DATE_ADD(:to_date, INTERVAL 1 DAY)';
            $query = $this->db->query($sql)
                ->bind(':status', $status)
                ->bind(':from_date', $fromDate)
                ->bind(':to_date', $toDate);
        }

        $query->execute();
        $row = $query->single();
        return (int) ($row['total'] ?? 0);
    }

    private function seriesFromTable(
        string $table,
        string $dateColumn,
        string $fromDate,
        string $toDate,
        string $granularity,
        string $extraWhere = '',
        array $binds = []
    ): array {
        if (!$this->tableExists($table) || !$this->hasColumn($table, $dateColumn)) {
            return [];
        }

        $bucket = $this->bucketExpr($dateColumn, $granularity);
        $sql = "SELECT {$bucket} AS bucket, COUNT(*) AS total
                FROM {$table}
                WHERE {$dateColumn} >= :from_date
                  AND {$dateColumn} < DATE_ADD(:to_date, INTERVAL 1 DAY)";
        if ($extraWhere !== '') {
            $sql .= ' AND ' . $extraWhere;
        }
        $sql .= ' GROUP BY bucket ORDER BY bucket ASC';

        $query = $this->db->query($sql)
            ->bind(':from_date', $fromDate)
            ->bind(':to_date', $toDate);
        foreach ($binds as $k => $v) {
            $query->bind($k, $v);
        }
        $query->execute();

        $out = [];
        foreach ($query->resultSet() as $row) {
            $bucketValue = (string) ($row['bucket'] ?? '');
            if ($bucketValue === '') {
                continue;
            }
            $out[$bucketValue] = (int) ($row['total'] ?? 0);
        }
        return $out;
    }

    private function buildBuckets(string $fromDate, string $toDate, string $granularity): array
    {
        $buckets = [];
        $from = new DateTimeImmutable($fromDate);
        $to = new DateTimeImmutable($toDate);

        if ($granularity === 'month') {
            $cursor = $from->modify('first day of this month');
            $end = $to->modify('first day of this month');
            while ($cursor <= $end) {
                $buckets[] = $cursor->format('Y-m');
                $cursor = $cursor->modify('+1 month');
            }
            return $buckets;
        }

        if ($granularity === 'week') {
            $cursor = $from->modify('monday this week');
            $end = $to->modify('monday this week');
            while ($cursor <= $end) {
                $buckets[] = $cursor->format('Y-m-d');
                $cursor = $cursor->modify('+1 week');
            }
            return $buckets;
        }

        $cursor = $from;
        while ($cursor <= $to) {
            $buckets[] = $cursor->format('Y-m-d');
            $cursor = $cursor->modify('+1 day');
        }
        return $buckets;
    }

    private function fillSeries(array $buckets, array $series): array
    {
        $filled = [];
        foreach ($buckets as $bucket) {
            $filled[] = (int) ($series[$bucket] ?? 0);
        }
        return $filled;
    }

    public function overview(string $fromDate, string $toDate): array
    {
        $userExtra = $this->hasColumn('users', 'deleted_at') ? 'deleted_at IS NULL' : '';
        $usersNew = $this->countByDateRange('users', 'created_at', $fromDate, $toDate, $userExtra);

        $usersActive = 0;
        if ($this->tableExists('users')) {
            $where = [];
            if ($this->hasColumn('users', 'deleted_at')) {
                $where[] = 'deleted_at IS NULL';
            }
            if ($this->hasColumn('users', 'status')) {
                $where[] = "status = 'active'";
            }
            $sql = 'SELECT COUNT(*) AS total FROM users';
            if ($where !== []) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $this->db->query($sql)->execute();
            $usersActive = (int) (($this->db->single()['total'] ?? 0));
        }

        $recipePending = $this->countByStatus('recipes', 'pending');
        $recipeApproved = $this->countByStatus('recipes', 'approved');
        $recipeRejected = $this->countByStatus('recipes', 'rejected');

        $ingredientPending = $this->countByStatus('ingredients', 'pending');
        $ingredientApproved = $this->countByStatus('ingredients', 'approved');
        $ingredientRejected = $this->countByStatus('ingredients', 'rejected');

        $tipPending = $this->countByStatus('tips', 'pending');
        $tipApproved = $this->countByStatus('tips', 'approved');
        $tipRejected = $this->countByStatus('tips', 'rejected');

        $reportsNew = $this->countByDateRange('reports', 'created_at', $fromDate, $toDate);
        $reportsResolved = $this->countByDateRange(
            'reports',
            'updated_at',
            $fromDate,
            $toDate,
            $this->hasColumn('reports', 'status') ? "status = 'resolved'" : ''
        );

        $commentsNew = $this->countByDateRange('comments', 'created_at', $fromDate, $toDate);
        $commentsHiddenDeleted = 0;
        if ($this->tableExists('comments')) {
            $sql = 'SELECT COUNT(*) AS total FROM comments';
            if ($this->hasColumn('comments', 'status')) {
                $sql .= " WHERE status IN ('hidden', 'deleted')";
            } elseif ($this->hasColumn('comments', 'deleted_at')) {
                $sql .= ' WHERE deleted_at IS NOT NULL';
            }
            $this->db->query($sql)->execute();
            $commentsHiddenDeleted = (int) (($this->db->single()['total'] ?? 0));
        }

        $totalSubmitted = $this->countByDateRange('recipes', 'created_at', $fromDate, $toDate)
            + $this->countByDateRange('ingredients', 'created_at', $fromDate, $toDate)
            + $this->countByDateRange('tips', 'created_at', $fromDate, $toDate);

        $totalApproved = $this->countByStatus('recipes', 'approved', $fromDate, $toDate)
            + $this->countByStatus('ingredients', 'approved', $fromDate, $toDate)
            + $this->countByStatus('tips', 'approved', $fromDate, $toDate);

        $approvalRate = $totalSubmitted > 0 ? round(($totalApproved * 100) / $totalSubmitted, 1) : 0.0;

        return [
            'users_new' => $usersNew,
            'users_active' => $usersActive,
            'recipes' => [
                'pending' => $recipePending,
                'approved' => $recipeApproved,
                'rejected' => $recipeRejected,
            ],
            'ingredients' => [
                'pending' => $ingredientPending,
                'approved' => $ingredientApproved,
                'rejected' => $ingredientRejected,
            ],
            'tips' => [
                'pending' => $tipPending,
                'approved' => $tipApproved,
                'rejected' => $tipRejected,
            ],
            'reports_new' => $reportsNew,
            'reports_resolved' => $reportsResolved,
            'comments_new' => $commentsNew,
            'comments_hidden_deleted' => $commentsHiddenDeleted,
            'approval_rate' => $approvalRate,
            'total_submitted' => $totalSubmitted,
            'total_approved' => $totalApproved,
        ];
    }

    public function timeSeries(string $fromDate, string $toDate, string $granularity): array
    {
        $g = $this->normalizeGranularity($granularity);
        $buckets = $this->buildBuckets($fromDate, $toDate, $g);

        $userSeries = $this->seriesFromTable(
            'users',
            'created_at',
            $fromDate,
            $toDate,
            $g,
            $this->hasColumn('users', 'deleted_at') ? 'deleted_at IS NULL' : ''
        );

        $recipeSubmit = $this->seriesFromTable('recipes', 'created_at', $fromDate, $toDate, $g);
        $ingredientSubmit = $this->seriesFromTable('ingredients', 'created_at', $fromDate, $toDate, $g);
        $tipSubmit = $this->seriesFromTable('tips', 'created_at', $fromDate, $toDate, $g);

        $submitSeries = [];
        foreach ($buckets as $bucket) {
            $submitSeries[$bucket] = (int) ($recipeSubmit[$bucket] ?? 0)
                + (int) ($ingredientSubmit[$bucket] ?? 0)
                + (int) ($tipSubmit[$bucket] ?? 0);
        }

        $reportNewSeries = $this->seriesFromTable('reports', 'created_at', $fromDate, $toDate, $g);
        $reportResolvedSeries = $this->seriesFromTable(
            'reports',
            'updated_at',
            $fromDate,
            $toDate,
            $g,
            $this->hasColumn('reports', 'status') ? "status = 'resolved'" : ''
        );

        return [
            'labels' => $buckets,
            'registrations' => $this->fillSeries($buckets, $userSeries),
            'submissions' => $this->fillSeries($buckets, $submitSeries),
            'reports_new' => $this->fillSeries($buckets, $reportNewSeries),
            'reports_resolved' => $this->fillSeries($buckets, $reportResolvedSeries),
        ];
    }

    public function topAuthors(string $fromDate, string $toDate, int $limit = 8): array
    {
        if (!$this->tableExists('users')) {
            return [];
        }

        $joins = [];
        $sumParts = [];
        $sources = ['recipes', 'ingredients', 'tips'];
        $idx = 0;
        $dateBinds = [];
        foreach ($sources as $table) {
            if (!$this->tableExists($table) || !$this->hasColumn($table, 'user_id') || !$this->hasColumn($table, 'created_at')) {
                continue;
            }
            $alias = 's' . $idx++;
            $fromKey = ':from_date_' . $alias;
            $toKey = ':to_date_' . $alias;
            $joins[] = "LEFT JOIN (
                            SELECT user_id, COUNT(*) AS total
                            FROM {$table}
                            WHERE user_id IS NOT NULL
                              AND created_at >= {$fromKey}
                              AND created_at < DATE_ADD({$toKey}, INTERVAL 1 DAY)
                            GROUP BY user_id
                        ) {$alias} ON {$alias}.user_id = u.id";
            $sumParts[] = "COALESCE({$alias}.total, 0)";
            $dateBinds[$fromKey] = $fromDate;
            $dateBinds[$toKey] = $toDate;
        }

        $totalExpr = $sumParts === [] ? '0' : implode(' + ', $sumParts);
        $nameExpr = "COALESCE(NULLIF(u.name, ''), NULLIF(u.username, ''), u.email, CONCAT('User #', u.id))";

        $sql = "SELECT u.id, {$nameExpr} AS author_name, {$totalExpr} AS total_posts
                FROM users u
                " . implode("\n", $joins);
        if ($this->hasColumn('users', 'deleted_at')) {
            $sql .= "\nWHERE u.deleted_at IS NULL";
        }
        $sql .= "\nHAVING total_posts > 0
                 ORDER BY total_posts DESC, u.id DESC
                 LIMIT :limit";

        $query = $this->db->query($sql)->bind(':limit', $limit);
        foreach ($dateBinds as $key => $value) {
            $query->bind($key, $value);
        }
        $query->execute();
        return $query->resultSet();
    }

    public function topContents(string $fromDate, string $toDate, int $limit = 8): array
    {
        if (!$this->tableExists('recipes')) {
            return [];
        }

        $saveJoin = '';
        if ($this->tableExists('saved_items')) {
            $saveJoin = "LEFT JOIN (
                            SELECT item_id, COUNT(*) AS save_count
                            FROM saved_items
                            WHERE item_type = 'recipe'
                            GROUP BY item_id
                        ) sc ON sc.item_id = r.id";
        } else {
            $saveJoin = 'LEFT JOIN (SELECT NULL AS item_id, 0 AS save_count) sc ON 1=0';
        }

        $commentJoin = '';
        if ($this->tableExists('comments') && $this->hasColumn('comments', 'content_type') && $this->hasColumn('comments', 'content_id')) {
            $commentWhere = "content_type = 'recipe'";
            if ($this->hasColumn('comments', 'status')) {
                $commentWhere .= " AND status IN ('active', 'visible')";
            }
            $commentJoin = "LEFT JOIN (
                                SELECT content_id, COUNT(*) AS comment_count
                                FROM comments
                                WHERE {$commentWhere}
                                GROUP BY content_id
                            ) cc ON cc.content_id = r.id";
        } else {
            $commentJoin = 'LEFT JOIN (SELECT NULL AS content_id, 0 AS comment_count) cc ON 1=0';
        }

        $where = [];
        if ($this->hasColumn('recipes', 'created_at')) {
            $where[] = 'r.created_at >= :from_date';
            $where[] = 'r.created_at < DATE_ADD(:to_date, INTERVAL 1 DAY)';
        }
        if ($this->hasColumn('recipes', 'status')) {
            $where[] = "r.status = 'approved'";
        }

        $sql = "SELECT r.id,
                       r.title,
                       COALESCE(sc.save_count, 0) AS save_count,
                       COALESCE(cc.comment_count, 0) AS comment_count,
                       (COALESCE(sc.save_count, 0) + COALESCE(cc.comment_count, 0)) AS interaction_score
                FROM recipes r
                {$saveJoin}
                {$commentJoin}";
        if ($where !== []) {
            $sql .= "\nWHERE " . implode(' AND ', $where);
        }
        $sql .= "\nORDER BY interaction_score DESC, r.id DESC
                 LIMIT :limit";

        $query = $this->db->query($sql)->bind(':limit', $limit);
        if ($this->hasColumn('recipes', 'created_at')) {
            $query->bind(':from_date', $fromDate)->bind(':to_date', $toDate);
        }
        $query->execute();

        return $query->resultSet();
    }
}
