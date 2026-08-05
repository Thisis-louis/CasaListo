<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

function dashboardPeriod(string $period): array
{
    $period = in_array($period, ['day', 'week', 'month', 'year'], true) ? $period : 'month';
    $now = new DateTimeImmutable('now');

    return match ($period) {
        'day' => [
            'period' => 'day',
            'label' => 'Hoy',
            'start' => $now->setTime(0, 0),
            'end' => $now->setTime(23, 59, 59),
            'bucket_sql' => "DATE_FORMAT(%s, '%%H:00')",
        ],
        'week' => [
            'period' => 'week',
            'label' => 'Últimos 7 días',
            'start' => $now->modify('-6 days')->setTime(0, 0),
            'end' => $now->setTime(23, 59, 59),
            'bucket_sql' => 'DATE(%s)',
        ],
        'year' => [
            'period' => 'year',
            'label' => 'Este año',
            'start' => $now->setDate((int) $now->format('Y'), 1, 1)->setTime(0, 0),
            'end' => $now->setDate((int) $now->format('Y'), 12, 31)->setTime(23, 59, 59),
            'bucket_sql' => "DATE_FORMAT(%s, '%%Y-%%m')",
        ],
        default => [
            'period' => 'month',
            'label' => 'Este mes',
            'start' => $now->setDate((int) $now->format('Y'), (int) $now->format('m'), 1)->setTime(0, 0),
            'end' => $now->modify('last day of this month')->setTime(23, 59, 59),
            'bucket_sql' => 'DATE(%s)',
        ],
    };
}

function periodParams(array $period): array
{
    return [
        'start' => $period['start']->format('Y-m-d H:i:s'),
        'end' => $period['end']->format('Y-m-d H:i:s'),
    ];
}

function moneyValue(float|int|string|null $value): string
{
    return '$' . number_format((float) $value, 2);
}

function metric(string $label, string|int|float $value, string $hint): array
{
    return [
        'label' => $label,
        'value' => (string) $value,
        'hint' => $hint,
    ];
}

function fetchScalar(string $sql, array $params = []): int|float|string|null
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchColumn();
}

function fetchRows(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function trendRows(string $dateColumn, string $tableAlias, string $selectSql, string $fromSql, string $whereSql, array $period, array $params = []): array
{
    $bucket = sprintf($period['bucket_sql'], $tableAlias . '.' . $dateColumn);
    $sql = "
        SELECT $bucket AS label, $selectSql AS value
        $fromSql
        WHERE $tableAlias.$dateColumn BETWEEN :start AND :end
        $whereSql
        GROUP BY label
        ORDER BY label ASC
    ";

    return fetchRows($sql, array_merge(periodParams($period), $params));
}

function sendDashboardJson(array $payload): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
