<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';

function moduleTables(): array
{
    return [
        'roles' => 'Roles',
        'usuarios' => 'Usuarios',
        'categorias' => 'Categorías',
        'servicios' => 'Servicios',
        'tecnicos' => 'Técnicos',
        'tecnicos_servicios' => 'Técnicos y servicios',
        'solicitudes' => 'Solicitudes',
        'solicitud_archivos' => 'Archivos de solicitudes',
        'cotizaciones' => 'Cotizaciones',
        'asignaciones' => 'Asignaciones',
        'pagos' => 'Pagos',
        'calificaciones' => 'Calificaciones',
        'notificaciones' => 'Notificaciones',
        'paginas_contenido' => 'Contenido de páginas',
        'bitacora' => 'Bitácora',
    ];
}

function requireAdminForTableEndpoint(): void
{
    requireAuth(['administrador']);
}

function assertAllowedTable(string $table): void
{
    if (!array_key_exists($table, moduleTables())) {
        http_response_code(404);
        sendJson([
            'ok' => false,
            'message' => 'Tabla no permitida.',
        ]);
    }
}

function tableLabel(string $table): string
{
    assertAllowedTable($table);

    return moduleTables()[$table];
}

function tableColumns(string $table): array
{
    assertAllowedTable($table);

    $stmt = dbConnection()->query("DESCRIBE `$table`");

    return array_map(
        static fn (array $column): string => $column['Field'],
        $stmt->fetchAll()
    );
}

function tableOrderBy(string $table, array $columns): string
{
    if (in_array('id', $columns, true)) {
        return 'id DESC';
    }

    return '`' . $columns[0] . '` ASC';
}

function tableRecords(string $table, int $limit = 200): array
{
    assertAllowedTable($table);

    $columns = tableColumns($table);
    $orderBy = tableOrderBy($table, $columns);
    $safeLimit = max(1, min($limit, 500));
    $stmt = dbConnection()->query("SELECT * FROM `$table` ORDER BY $orderBy LIMIT $safeLimit");
    $records = array_map('maskSensitiveValues', $stmt->fetchAll());

    return [
        'ok' => true,
        'table' => $table,
        'title' => tableLabel($table),
        'columns' => $columns,
        'records' => $records,
    ];
}

function maskSensitiveValues(array $record): array
{
    foreach ($record as $column => $value) {
        if (str_contains((string) $column, 'password')) {
            $record[$column] = $value === null ? null : '********';
        }
    }

    return $record;
}

function sendJson(array $payload): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
