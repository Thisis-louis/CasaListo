<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';

const MODULE_PAGE_SIZE = 50;

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

    return array_map(
        static fn (array $column): string => $column['Field'],
        tableColumnMeta($table)
    );
}

function tableColumnMeta(string $table): array
{
    assertAllowedTable($table);

    $stmt = dbConnection()->query("DESCRIBE `$table`");

    return $stmt->fetchAll();
}

function searchableColumns(array $columns): array
{
    return array_values(array_filter(
        $columns,
        static fn (string $column): bool => !str_contains($column, 'password')
    ));
}

function preferredSortColumns(): array
{
    return [
        'roles' => ['id', 'nombre', 'creado_en'],
        'usuarios' => ['nombre', 'email', 'estado', 'creado_en'],
        'categorias' => ['nombre', 'slug', 'orden', 'estado'],
        'servicios' => ['nombre', 'categoria_id', 'estado', 'precio_base', 'destacado'],
        'tecnicos' => ['usuario_id', 'zona', 'calificacion_promedio', 'disponible'],
        'tecnicos_servicios' => ['tecnico_id', 'servicio_id', 'precio_referencia'],
        'solicitudes' => ['folio', 'estado', 'urgencia', 'creado_en'],
        'solicitud_archivos' => ['solicitud_id', 'tipo_archivo', 'creado_en'],
        'cotizaciones' => ['folio', 'total', 'estado', 'vigencia'],
        'asignaciones' => ['fecha_programada', 'estado', 'tecnico_id', 'creado_en'],
        'pagos' => ['monto', 'metodo', 'estado', 'creado_en'],
        'calificaciones' => ['puntuacion', 'cliente_id', 'tecnico_id', 'creado_en'],
        'notificaciones' => ['usuario_id', 'tipo', 'leida', 'creado_en'],
        'paginas_contenido' => ['clave', 'titulo', 'estado', 'actualizado_en'],
        'bitacora' => ['entidad', 'accion', 'creado_en', 'usuario_id'],
    ];
}

function sortableColumns(string $table, array $columns): array
{
    $available = array_values(array_filter(
        searchableColumns($columns),
        static fn (string $column): bool => in_array($column, $columns, true)
    ));

    $preferred = preferredSortColumns()[$table] ?? [];
    $ordered = [];

    foreach ($preferred as $column) {
        if (in_array($column, $available, true) && !in_array($column, $ordered, true)) {
            $ordered[] = $column;
        }
    }

    foreach ($available as $column) {
        if (!in_array($column, $ordered, true)) {
            $ordered[] = $column;
        }
    }

    return array_slice($ordered, 0, max(3, min(count($ordered), 5)));
}

function normalizedSortDirection(?string $direction): string
{
    return mb_strtolower((string) $direction) === 'asc' ? 'ASC' : 'DESC';
}

function escapeLike(string $value): string
{
    return str_replace(
        ['\\', '%', '_'],
        ['\\\\', '\\%', '\\_'],
        $value
    );
}

function tableSearchClause(array $columns, string $search): array
{
    $search = trim($search);

    if ($search === '') {
        return ['', []];
    }

    $conditions = [];
    $params = [];

    foreach (searchableColumns($columns) as $index => $column) {
        $param = 'q' . $index;
        $conditions[] = "CAST(`$column` AS CHAR) LIKE :$param ESCAPE '\\\\'";
        $params[$param] = '%' . escapeLike($search) . '%';
    }

    if ($conditions === []) {
        return ['', []];
    }

    return ['WHERE ' . implode(' OR ', $conditions), $params];
}

function normalizedPage(mixed $page): int
{
    $page = filter_var($page, FILTER_VALIDATE_INT);

    if (!$page || $page < 1) {
        return 1;
    }

    return $page;
}

function tableTotalRecords(string $table, string $whereSql, array $params): int
{
    $stmt = dbConnection()->prepare("SELECT COUNT(*) FROM `$table` $whereSql");
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function tableRecords(
    string $table,
    int $limit = MODULE_PAGE_SIZE,
    ?string $search = null,
    ?string $sort = null,
    ?string $direction = null,
    mixed $page = null
): array
{
    assertAllowedTable($table);

    $search = $search ?? (string) ($_GET['q'] ?? '');
    $sort = $sort ?? (string) ($_GET['sort'] ?? '');
    $direction = $direction ?? (string) ($_GET['dir'] ?? '');
    $page = normalizedPage($page ?? ($_GET['page'] ?? 1));
    $columns = tableColumns($table);
    $sortableColumns = sortableColumns($table, $columns);
    [$whereSql, $params] = tableSearchClause($columns, $search);
    $orderBy = tableOrderBy($table, $columns, $sortableColumns, $sort, $direction);
    $pageSize = max(MODULE_PAGE_SIZE, min($limit, MODULE_PAGE_SIZE));
    $totalRecords = tableTotalRecords($table, $whereSql, $params);
    $totalPages = max(1, (int) ceil($totalRecords / $pageSize));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $pageSize;
    $stmt = dbConnection()->prepare("SELECT * FROM `$table` $whereSql ORDER BY $orderBy LIMIT :limit OFFSET :offset");

    foreach ($params as $param => $value) {
        $stmt->bindValue(':' . $param, $value, PDO::PARAM_STR);
    }

    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $records = array_map('maskSensitiveValues', $stmt->fetchAll());

    return [
        'ok' => true,
        'table' => $table,
        'title' => tableLabel($table),
        'columns' => $columns,
        'sortable_columns' => $sortableColumns,
        'sort' => activeSortColumn($columns, $sortableColumns, $sort),
        'dir' => normalizedSortDirection($direction),
        'records' => $records,
        'search' => trim($search),
        'pagination' => [
            'page' => $page,
            'per_page' => $pageSize,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages,
        ],
    ];
}

function activeSortColumn(array $columns, array $sortableColumns, ?string $sort): string
{
    if ($sort !== null && in_array($sort, $sortableColumns, true)) {
        return $sort;
    }

    if ($sortableColumns !== []) {
        return $sortableColumns[0];
    }

    if (in_array('id', $columns, true)) {
        return 'id';
    }

    return $columns[0];
}

function tableOrderBy(
    string $table,
    array $columns,
    array $sortableColumns,
    ?string $sort = null,
    ?string $direction = null
): string
{
    $sortColumn = activeSortColumn($columns, $sortableColumns, $sort);
    $sortDirection = normalizedSortDirection($direction);

    return "`$sortColumn` $sortDirection";
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
