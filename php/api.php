<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAdminForTableEndpoint();

function apiError(string $message, int $status = 400): never
{
    http_response_code($status);
    sendJson([
        'ok' => false,
        'message' => $message,
    ]);
}

function requestData(): array
{
    $input = file_get_contents('php://input');

    if ($input !== false && trim($input) !== '') {
        $json = json_decode($input, true);

        if (is_array($json)) {
            return $json;
        }
    }

    return $_POST;
}

function currentTable(): string
{
    $table = (string) ($_GET['table'] ?? '');

    if ($table === '') {
        apiError('Falta seleccionar un módulo.');
    }

    assertAllowedTable($table);

    return $table;
}

function describeTable(string $table): array
{
    assertAllowedTable($table);

    $stmt = dbConnection()->query("DESCRIBE `$table`");

    return $stmt->fetchAll();
}

function columnLabel(string $column): string
{
    return ucwords(str_replace('_', ' ', $column));
}

function enumOptions(string $type): array
{
    if (!str_starts_with($type, 'enum(')) {
        return [];
    }

    $raw = substr($type, 5, -1);

    return str_getcsv($raw, ',', "'");
}

function inputType(array $column): string
{
    $field = (string) $column['Field'];
    $type = (string) $column['Type'];

    if ($field === 'password_hash') {
        return 'password';
    }

    if (str_starts_with($type, 'enum(')) {
        return 'select';
    }

    if ($type === 'tinyint(1)') {
        return 'checkbox';
    }

    if (str_contains($type, 'text')) {
        return 'textarea';
    }

    if (str_contains($type, 'datetime') || str_contains($type, 'timestamp')) {
        return 'datetime-local';
    }

    if (str_contains($type, 'date')) {
        return 'date';
    }

    if (str_contains($type, 'time')) {
        return 'time';
    }

    if (str_contains($type, 'int') || str_contains($type, 'decimal')) {
        return 'number';
    }

    return 'text';
}

function isPrimary(array $column): bool
{
    return (string) $column['Key'] === 'PRI';
}

function isReadonly(array $column): bool
{
    $field = (string) $column['Field'];
    $extra = strtolower((string) $column['Extra']);

    return str_contains($extra, 'auto_increment')
        || $field === 'creado_en'
        || $field === 'actualizado_en';
}

function isRequired(array $column): bool
{
    if (isReadonly($column)) {
        return false;
    }

    return (string) $column['Null'] === 'NO'
        && $column['Default'] === null;
}

function fieldInfo(array $column): array
{
    $field = (string) $column['Field'];

    return [
        'name' => $field,
        'label' => $field === 'password_hash' ? 'Password' : columnLabel($field),
        'input' => inputType($column),
        'options' => enumOptions((string) $column['Type']),
        'primary' => isPrimary($column),
        'readonly' => isReadonly($column),
        'required' => isRequired($column),
    ];
}

function metadata(string $table): array
{
    $columns = describeTable($table);

    return [
        'fields' => array_map('fieldInfo', $columns),
        'columns' => array_map(static fn (array $column): string => (string) $column['Field'], $columns),
        'primary' => array_values(array_map(
            static fn (array $column): string => (string) $column['Field'],
            array_filter($columns, 'isPrimary')
        )),
    ];
}

function orderBySql(string $table, array $columns): string
{
    if (in_array('id', $columns, true)) {
        return '`id` DESC';
    }

    return '`' . $columns[0] . '` ASC';
}

function listRecords(string $table): array
{
    $meta = metadata($table);
    $orderBy = orderBySql($table, $meta['columns']);
    $stmt = dbConnection()->query("SELECT * FROM `$table` ORDER BY $orderBy LIMIT 200");
    $records = array_map('maskSensitiveValues', $stmt->fetchAll());

    return [
        'ok' => true,
        'table' => $table,
        'title' => tableLabel($table),
        'csrf_token' => csrfToken(),
        'modules' => moduleTables(),
        'columns' => $meta['columns'],
        'fields' => $meta['fields'],
        'primary' => $meta['primary'],
        'records' => $records,
    ];
}

function slugText(string $value): string
{
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
    $slug = strtolower((string) $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

    return trim((string) $slug, '-');
}

function preparePayload(string $table, array $payload, bool $editing): array
{
    $columns = describeTable($table);
    $values = [];

    if (array_key_exists('slug', $payload) && trim((string) $payload['slug']) === '' && !empty($payload['nombre'])) {
        $payload['slug'] = slugText((string) $payload['nombre']);
    }

    foreach ($columns as $column) {
        $field = (string) $column['Field'];
        $input = inputType($column);

        if (isReadonly($column) || ($editing && isPrimary($column))) {
            continue;
        }

        if ($field === 'password_hash') {
            $password = trim((string) ($payload[$field] ?? ''));

            if ($password === '') {
                if ($editing) {
                    continue;
                }

                apiError('El password es obligatorio.');
            }

            $values[$field] = password_hash($password, PASSWORD_DEFAULT);
            continue;
        }

        if (!array_key_exists($field, $payload)) {
            if (!$editing && isRequired($column)) {
                apiError('Falta el campo ' . columnLabel($field) . '.');
            }

            continue;
        }

        $value = $payload[$field];

        if ($input === 'checkbox') {
            $values[$field] = !empty($value) ? 1 : 0;
            continue;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($input === 'datetime-local' && is_string($value)) {
            $value = str_replace('T', ' ', $value);
        }

        if ($value === '') {
            if ((string) $column['Null'] === 'YES') {
                $values[$field] = null;
                continue;
            }

            if ($column['Default'] !== null) {
                continue;
            }

            apiError('Falta el campo ' . columnLabel($field) . '.');
        }

        $values[$field] = $value;
    }

    return $values;
}

function primaryValues(string $table, array $payload): array
{
    $meta = metadata($table);
    $pk = $payload['_pk'] ?? null;

    if (!is_array($pk)) {
        $pk = [];
    }

    if (isset($payload['id']) && in_array('id', $meta['primary'], true)) {
        $pk['id'] = $payload['id'];
    }

    foreach ($meta['primary'] as $field) {
        if (!isset($pk[$field]) || $pk[$field] === '') {
            apiError('Falta la llave primaria ' . columnLabel($field) . '.');
        }
    }

    return $pk;
}

function whereSql(array $pk): string
{
    $parts = [];

    foreach (array_keys($pk) as $field) {
        $parts[] = "`$field` = :pk_$field";
    }

    return implode(' AND ', $parts);
}

function whereParams(array $pk): array
{
    $params = [];

    foreach ($pk as $field => $value) {
        $params["pk_$field"] = $value;
    }

    return $params;
}

function insertRecord(string $table, array $payload): never
{
    $values = preparePayload($table, $payload, false);

    if ($values === []) {
        apiError('No hay datos para insertar.');
    }

    $columns = array_keys($values);
    $names = implode(', ', array_map(static fn (string $field): string => "`$field`", $columns));
    $marks = implode(', ', array_map(static fn (string $field): string => ":$field", $columns));
    $stmt = dbConnection()->prepare("INSERT INTO `$table` ($names) VALUES ($marks)");
    $stmt->execute($values);

    sendJson([
        'ok' => true,
        'message' => 'Registro insertado correctamente.',
    ]);
}

function updateRecord(string $table, array $payload): never
{
    $values = preparePayload($table, $payload, true);
    $pk = primaryValues($table, $payload);

    if ($values === []) {
        apiError('No hay datos para editar.');
    }

    $sets = implode(', ', array_map(static fn (string $field): string => "`$field` = :$field", array_keys($values)));
    $stmt = dbConnection()->prepare("UPDATE `$table` SET $sets WHERE " . whereSql($pk));
    $stmt->execute(array_merge($values, whereParams($pk)));

    sendJson([
        'ok' => true,
        'message' => 'Registro editado correctamente.',
    ]);
}

function deleteRecord(string $table, array $payload): never
{
    $pk = primaryValues($table, $payload);
    $stmt = dbConnection()->prepare("DELETE FROM `$table` WHERE " . whereSql($pk));
    $stmt->execute(whereParams($pk));

    if ($stmt->rowCount() === 0) {
        apiError('No se encontró el registro.', 404);
    }

    sendJson([
        'ok' => true,
        'message' => 'Registro eliminado correctamente.',
    ]);
}

function checkCsrf(array $payload): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $payload['csrf_token'] ?? null;

    if (!verifyCsrfToken($token)) {
        apiError('La sesión expiró. Recarga la página.', 419);
    }
}

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET' && !isset($_GET['table'])) {
        sendJson([
            'ok' => true,
            'modules' => moduleTables(),
        ]);
    }

    $table = currentTable();

    if ($method === 'GET') {
        sendJson(listRecords($table));
    }

    $payload = requestData();
    checkCsrf($payload);

    match ($method) {
        'POST' => insertRecord($table, $payload),
        'PUT', 'PATCH' => updateRecord($table, $payload),
        'DELETE' => deleteRecord($table, $payload),
        default => apiError('Método no permitido.', 405),
    };
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        apiError('No se puede guardar o eliminar porque existe una relación o un dato repetido.', 409);
    }

    throw $exception;
}
