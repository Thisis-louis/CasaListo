<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/lib/functions.php';

/*
 * Fallbacks para que el endpoint no dependa de helpers que ya no existan
 * o que hayan cambiado de nombre en lib/functions_quotes.php.
 */

if (!function_exists('sendJson')) {
    function sendJson(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('tableColumns')) {
    function tableColumns(string $table): array
    {
        $stmt = dbConnection()->query("SHOW COLUMNS FROM `$table`");
        if (!$stmt) {
            return [];
        }

        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($row['Field'])) {
                $columns[] = $row['Field'];
            }
        }

        return $columns;
    }
}

if (!function_exists('tableLabel')) {
    function tableLabel(string $table): string
    {
        return ucfirst(str_replace('_', ' ', $table));
    }
}

if (!function_exists('maskSensitiveValues')) {
    function maskSensitiveValues(array $record): array
    {
        return $record;
    }
}

function solicitudesTable(): string
{
    return 'solicitudes';
}

function solicitudesRequestData(): array
{
    $raw = file_get_contents('php://input');

    if ($raw !== false && trim($raw) !== '') {
        $decoded = json_decode($raw, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
    }

    return $_POST;
}

function solicitudesColumns(): array
{
    return tableColumns(solicitudesTable());
}

function solicitudesHasColumn(string $column): bool
{
    return in_array($column, solicitudesColumns(), true);
}

function solicitudesPrimaryKey(): string
{
    $columns = solicitudesColumns();

    if (in_array('id', $columns, true)) {
        return 'id';
    }

    foreach ($columns as $column) {
        if (str_starts_with($column, 'id_')) {
            return $column;
        }
    }

    return $columns[0] ?? 'id';
}

function solicitudesEditableFields(): array
{
    return array_values(array_filter([
        'cliente_id',
        'servicio_id',
        'folio',
        'titulo',
        'descripcion',
        'direccion',
        'colonia',
        'ciudad',
        'estado_region',
        'codigo_postal',
        'fecha_preferida',
        'hora_preferida',
        'urgencia',
        'estado',
    ], static fn (string $field): bool => solicitudesHasColumn($field)));
}

function solicitudesNormalizeValue(mixed $value): mixed
{
    if (is_string($value)) {
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    return $value;
}

function solicitudesNormalizeInput(array $input): array
{
    $data = [];

    foreach (solicitudesEditableFields() as $field) {
        if (!array_key_exists($field, $input)) {
            continue;
        }

        $value = solicitudesNormalizeValue($input[$field]);

        if ($field === 'cliente_id' || $field === 'servicio_id') {
            $value = ($value === null) ? null : (int) $value;
        }

        $data[$field] = $value;
    }

    return $data;
}

function solicitudesGenerateFolio(): string
{
    if (!solicitudesHasColumn('folio')) {
        return '';
    }

    $table = solicitudesTable();
    $sql = "SELECT folio
            FROM `$table`
            WHERE folio REGEXP '^SOL-[0-9]+$'
            ORDER BY CAST(SUBSTRING(folio, 5) AS UNSIGNED) DESC
            LIMIT 1";

    $stmt = dbConnection()->query($sql);
    $lastFolio = $stmt ? $stmt->fetchColumn() : false;

    $nextNumber = 1;

    if (is_string($lastFolio) && $lastFolio !== '') {
        $nextNumber = ((int) substr($lastFolio, 4)) + 1;
    }

    return sprintf('SOL-%04d', $nextNumber);
}

function solicitudesAll(): array
{
    $table = solicitudesTable();
    $pk = solicitudesPrimaryKey();
    $columns = solicitudesColumns();

    if ($columns === []) {
        return [
            'ok' => false,
            'message' => 'No se pudieron leer las columnas de la tabla solicitudes.',
        ];
    }

    $stmt = dbConnection()->query("SELECT * FROM `$table` ORDER BY `$pk` DESC");
    $records = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    return [
        'ok' => true,
        'table' => $table,
        'title' => tableLabel($table),
        'columns' => $columns,
        'records' => array_map('maskSensitiveValues', $records),
    ];
}

function solicitudesOne(array $input): array
{
    $table = solicitudesTable();
    $pk = solicitudesPrimaryKey();
    $id = $input['id'] ?? $input[$pk] ?? null;

    if ($id === null || $id === '') {
        return [
            'ok' => false,
            'message' => 'Falta el identificador del registro.',
        ];
    }

    $stmt = dbConnection()->prepare("SELECT * FROM `$table` WHERE `$pk` = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        return [
            'ok' => false,
            'message' => 'El registro no existe.',
        ];
    }

    return [
        'ok' => true,
        'table' => $table,
        'title' => tableLabel($table),
        'record' => maskSensitiveValues($record),
    ];
}

function solicitudesCreate(array $input): array
{
    $table = solicitudesTable();
    $data = solicitudesNormalizeInput($input);

    if (solicitudesHasColumn('folio') && (!isset($data['folio']) || $data['folio'] === null)) {
        $data['folio'] = solicitudesGenerateFolio();
    }

    if (solicitudesHasColumn('urgencia') && (!isset($data['urgencia']) || $data['urgencia'] === null)) {
        $data['urgencia'] = 'normal';
    }

    if (solicitudesHasColumn('estado') && (!isset($data['estado']) || $data['estado'] === null)) {
        $data['estado'] = 'nueva';
    }

    if (solicitudesHasColumn('creado_en')) {
        $data['creado_en'] = date('Y-m-d H:i:s');
    }

    if (solicitudesHasColumn('actualizado_en')) {
        $data['actualizado_en'] = date('Y-m-d H:i:s');
    }

    if ($data === []) {
        return [
            'ok' => false,
            'message' => 'No se recibieron datos para guardar.',
        ];
    }

    $columns = array_keys($data);
    $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);

    $sql = sprintf(
        'INSERT INTO `%s` (%s) VALUES (%s)',
        $table,
        implode(', ', array_map(static fn (string $column): string => "`$column`", $columns)),
        implode(', ', $placeholders)
    );

    $stmt = dbConnection()->prepare($sql);

    foreach ($data as $column => $value) {
        $stmt->bindValue(':' . $column, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    }

    $stmt->execute();

    return [
        'ok' => true,
        'message' => 'Solicitud guardada correctamente.',
        'id' => dbConnection()->lastInsertId(),
    ];
}

function solicitudesUpdate(array $input): array
{
    $table = solicitudesTable();
    $pk = solicitudesPrimaryKey();
    $id = $input['id'] ?? $input[$pk] ?? null;

    if ($id === null || $id === '') {
        return [
            'ok' => false,
            'message' => 'Falta el identificador del registro.',
        ];
    }

    $data = solicitudesNormalizeInput($input);

    if (solicitudesHasColumn('actualizado_en')) {
        $data['actualizado_en'] = date('Y-m-d H:i:s');
    }

    if ($data === []) {
        return [
            'ok' => false,
            'message' => 'No se recibieron datos para actualizar.',
        ];
    }

    $setParts = [];
    foreach (array_keys($data) as $column) {
        $setParts[] = "`$column` = :$column";
    }

    $sql = sprintf(
        'UPDATE `%s` SET %s WHERE `%s` = :id',
        $table,
        implode(', ', $setParts),
        $pk
    );

    $stmt = dbConnection()->prepare($sql);
    $stmt->bindValue(':id', $id);

    foreach ($data as $column => $value) {
        $stmt->bindValue(':' . $column, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    }

    $stmt->execute();

    return [
        'ok' => true,
        'message' => 'Solicitud actualizada correctamente.',
    ];
}

function solicitudesDelete(array $input): array
{
    $table = solicitudesTable();
    $pk = solicitudesPrimaryKey();
    $id = $input['id'] ?? $input[$pk] ?? null;

    if ($id === null || $id === '') {
        return [
            'ok' => false,
            'message' => 'Falta el identificador del registro.',
        ];
    }

    $stmt = dbConnection()->prepare("DELETE FROM `$table` WHERE `$pk` = :id");
    $stmt->execute([':id' => $id]);

    return [
        'ok' => true,
        'message' => 'Solicitud eliminada correctamente.',
    ];
}

$input = solicitudesRequestData();
$action = (string) ($input['action'] ?? '');

switch ($action) {
    case 'getAll':
        sendJson(solicitudesAll());
        break;

    case 'getOne':
        sendJson(solicitudesOne($input));
        break;

    case 'create':
        sendJson(solicitudesCreate($input));
        break;

    case 'update':
        sendJson(solicitudesUpdate($input));
        break;

    case 'delete':
        sendJson(solicitudesDelete($input));
        break;

    default:
        http_response_code(400);
        sendJson([
            'ok' => false,
            'message' => 'Acción no válida.',
        ]);
}