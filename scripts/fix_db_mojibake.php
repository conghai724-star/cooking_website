<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once APPROOT . '/app/models/Database.php';

/**
 * Usage:
 *   php scripts/fix_db_mojibake.php --dry-run
 *   php scripts/fix_db_mojibake.php --apply
 *   php scripts/fix_db_mojibake.php --apply --tables=users,recipes --limit=500
 */

const DEFAULT_LIMIT = 300;

$args = $argv ?? [];
$apply = in_array('--apply', $args, true);
$dryRun = !$apply;
$limit = DEFAULT_LIMIT;
$tableFilter = [];

foreach ($args as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $value = (int) substr($arg, 8);
        if ($value > 0) {
            $limit = $value;
        }
    }
    if (str_starts_with($arg, '--tables=')) {
        $list = trim((string) substr($arg, 9));
        if ($list !== '') {
            $tableFilter = array_values(array_filter(array_map('trim', explode(',', $list))));
        }
    }
}

echo 'DB: ' . DB_NAME . PHP_EOL;
echo $dryRun ? "Mode: DRY-RUN (no write)" . PHP_EOL : "Mode: APPLY (will update)" . PHP_EOL;
echo "Limit / column: {$limit}" . PHP_EOL;
if ($tableFilter !== []) {
    echo 'Table filter: ' . implode(', ', $tableFilter) . PHP_EOL;
}

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . DB_CHARSET . ' COLLATE ' . DB_COLLATION,
    ]
);

$tables = loadTables($pdo, DB_NAME, $tableFilter);
if ($tables === []) {
    echo 'No matching tables found.' . PHP_EOL;
    exit(0);
}

$updatedRows = 0;
$wouldUpdateRows = 0;
$scannedRows = 0;
$touchedColumns = 0;

foreach ($tables as $table) {
    $pkColumns = loadPrimaryKeyColumns($pdo, DB_NAME, $table);
    if ($pkColumns === []) {
        echo "[SKIP] {$table}: no primary key" . PHP_EOL;
        continue;
    }

    $textColumns = loadTextColumns($pdo, DB_NAME, $table);
    if ($textColumns === []) {
        continue;
    }

    foreach ($textColumns as $column) {
        $rows = loadSuspiciousRows($pdo, $table, $column, $pkColumns, $limit);
        if ($rows === []) {
            continue;
        }

        $touchedInColumn = 0;
        foreach ($rows as $row) {
            $orig = (string) ($row[$column] ?? '');
            $scannedRows++;
            $fixed = repairText($orig);

            if ($fixed === $orig || !isBetterText($orig, $fixed)) {
                continue;
            }

            if ($dryRun) {
                $touchedInColumn++;
                $wouldUpdateRows++;
                continue;
            }

            $ok = updateRow($pdo, $table, $column, $pkColumns, $row, $fixed);
            if ($ok) {
                $touchedInColumn++;
                $updatedRows++;
            }
        }

        if ($touchedInColumn > 0) {
            $touchedColumns++;
            echo '[' . ($dryRun ? 'DRY' : 'OK') . "] {$table}.{$column}: {$touchedInColumn}" . PHP_EOL;
        }
    }
}

echo str_repeat('-', 50) . PHP_EOL;
echo "Scanned values : {$scannedRows}" . PHP_EOL;
echo "Touched columns: {$touchedColumns}" . PHP_EOL;
echo $dryRun
    ? "Would update   : {$wouldUpdateRows}"
    : "Updated rows   : {$updatedRows}";
echo PHP_EOL;
echo 'Done.' . PHP_EOL;

function loadTables(PDO $pdo, string $dbName, array $tableFilter): array
{
    if ($tableFilter === []) {
        $stmt = $pdo->prepare(
            "SELECT TABLE_NAME
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = :db
               AND TABLE_TYPE = 'BASE TABLE'
             ORDER BY TABLE_NAME"
        );
        $stmt->execute([':db' => $dbName]);
        return array_map(static fn(array $r): string => (string) $r['TABLE_NAME'], $stmt->fetchAll());
    }

    $filtered = [];
    foreach ($tableFilter as $table) {
        if (preg_match('/^[a-zA-Z0-9_]+$/', $table) === 1) {
            $filtered[] = $table;
        }
    }
    return $filtered;
}

function loadPrimaryKeyColumns(PDO $pdo, string $dbName, string $table): array
{
    $stmt = $pdo->prepare(
        "SELECT COLUMN_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = :db
           AND TABLE_NAME = :tbl
           AND CONSTRAINT_NAME = 'PRIMARY'
         ORDER BY ORDINAL_POSITION"
    );
    $stmt->execute([':db' => $dbName, ':tbl' => $table]);
    return array_map(static fn(array $r): string => (string) $r['COLUMN_NAME'], $stmt->fetchAll());
}

function loadTextColumns(PDO $pdo, string $dbName, string $table): array
{
    $stmt = $pdo->prepare(
        "SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db
           AND TABLE_NAME = :tbl
           AND DATA_TYPE IN ('char', 'varchar', 'text', 'tinytext', 'mediumtext', 'longtext')
         ORDER BY ORDINAL_POSITION"
    );
    $stmt->execute([':db' => $dbName, ':tbl' => $table]);
    return array_map(static fn(array $r): string => (string) $r['COLUMN_NAME'], $stmt->fetchAll());
}

function loadSuspiciousRows(PDO $pdo, string $table, string $column, array $pkColumns, int $limit): array
{
    if (!isSafeIdentifier($table) || !isSafeIdentifier($column)) {
        return [];
    }
    foreach ($pkColumns as $pk) {
        if (!isSafeIdentifier($pk)) {
            return [];
        }
    }

    $selectCols = array_map(static fn(string $name): string => "`{$name}`", array_merge($pkColumns, [$column]));
    $sql = sprintf(
        "SELECT %s FROM `%s`
         WHERE `%s` IS NOT NULL
           AND `%s` <> ''
           AND (
               `%s` LIKE '%%Ã%%'
               OR `%s` LIKE '%%Â%%'
               OR `%s` LIKE '%%Ä%%'
               OR `%s` LIKE '%%áº%%'
               OR `%s` LIKE '%%Æ%%'
               OR `%s` LIKE '%%�%%'
           )
         LIMIT %d",
        implode(', ', $selectCols),
        $table,
        $column,
        $column,
        $column,
        $column,
        $column,
        $column,
        $column,
        $column,
        $limit
    );

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll() ?: [];
}

function updateRow(PDO $pdo, string $table, string $column, array $pkColumns, array $row, string $fixed): bool
{
    if (!isSafeIdentifier($table) || !isSafeIdentifier($column)) {
        return false;
    }
    foreach ($pkColumns as $pk) {
        if (!isSafeIdentifier($pk)) {
            return false;
        }
    }

    $where = [];
    $params = [':new_value' => $fixed];
    foreach ($pkColumns as $i => $pk) {
        $param = ':pk_' . $i;
        $where[] = "`{$pk}` = {$param}";
        $params[$param] = $row[$pk] ?? null;
    }

    $sql = sprintf(
        "UPDATE `%s` SET `%s` = :new_value WHERE %s LIMIT 1",
        $table,
        $column,
        implode(' AND ', $where)
    );

    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function repairText(string $value): string
{
    // Common recovery: UTF-8 bytes interpreted as Windows-1252.
    $candidate = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);
    if ($candidate !== false && $candidate !== '') {
        return $candidate;
    }

    if (function_exists('mb_convert_encoding')) {
        $candidate = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        if (is_string($candidate) && $candidate !== '') {
            return $candidate;
        }
    }

    return $value;
}

function isBetterText(string $original, string $fixed): bool
{
    if ($fixed === '' || $fixed === $original) {
        return false;
    }

    $origBad = badMarkerScore($original);
    $fixBad = badMarkerScore($fixed);

    if ($fixBad >= $origBad) {
        return false;
    }

    if (containsReplacementChar($fixed) && !containsReplacementChar($original)) {
        return false;
    }

    return true;
}

function badMarkerScore(string $value): int
{
    $score = 0;
    $markers = ['Ã', 'Â', 'Ä', 'áº', 'Æ', '�'];
    foreach ($markers as $marker) {
        $score += substr_count($value, $marker);
    }
    return $score;
}

function containsReplacementChar(string $value): bool
{
    return str_contains($value, '�');
}

function isSafeIdentifier(string $value): bool
{
    return preg_match('/^[a-zA-Z0-9_]+$/', $value) === 1;
}
