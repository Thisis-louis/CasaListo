<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAdminForTableEndpoint();

function mostrarTodosServicios(): array
{
    $payload = tableRecords('servicios');
    $payload['csrf_token'] = csrfToken();
    $payload['categorias'] = listarCategoriasServicios();
    $payload['actions'] = true;

    return $payload;
}

function listarCategoriasServicios(): array
{
    $stmt = dbConnection()->query(
        "SELECT id, nombre
         FROM categorias
         ORDER BY nombre ASC"
    );

    return $stmt->fetchAll();
}

function leerEntradaJson(): array
{
    $rawInput = file_get_contents('php://input');

    if ($rawInput !== false && trim($rawInput) !== '') {
        $payload = json_decode($rawInput, true);

        if (is_array($payload)) {
            return $payload;
        }
    }

    if ($_POST !== []) {
        return $_POST;
    }

    return [];
}

function textoDemasiadoLargo(string $value, int $maxLength): bool
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value) > $maxLength;
    }

    return strlen($value) > $maxLength;
}

function slugServicio(string $value): string
{
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
    $slug = strtolower((string) $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim((string) $slug, '-');

    return $slug;
}

function tokenCsrfRequest(array $payload): ?string
{
    return $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $payload['csrf_token'] ?? null;
}

function responderError(string $message, int $status = 400): never
{
    http_response_code($status);
    sendJson([
        'ok' => false,
        'message' => $message,
    ]);
}

function validarServicio(array $payload, ?int $id = null): array
{
    $categoriaId = filter_var($payload['categoria_id'] ?? null, FILTER_VALIDATE_INT);
    $nombre = trim((string) ($payload['nombre'] ?? ''));
    $slug = trim((string) ($payload['slug'] ?? ''));
    $descripcion = trim((string) ($payload['descripcion'] ?? ''));
    $precioBase = trim((string) ($payload['precio_base'] ?? ''));
    $requiereCotizacion = !empty($payload['requiere_cotizacion']) ? 1 : 0;
    $destacado = !empty($payload['destacado']) ? 1 : 0;
    $estado = (string) ($payload['estado'] ?? 'activo');

    if (!$categoriaId) {
        responderError('Selecciona una categoría válida.');
    }

    if ($nombre === '' || textoDemasiadoLargo($nombre, 140)) {
        responderError('El nombre es obligatorio y debe tener máximo 140 caracteres.');
    }

    if ($slug === '') {
        $slug = slugServicio($nombre);
    } else {
        $slug = slugServicio($slug);
    }

    if ($slug === '' || textoDemasiadoLargo($slug, 160)) {
        responderError('El slug es obligatorio y debe tener máximo 160 caracteres.');
    }

    if ($estado !== 'activo' && $estado !== 'inactivo') {
        responderError('Selecciona un estado válido.');
    }

    $precio = null;
    if ($precioBase !== '') {
        if (!is_numeric($precioBase) || (float) $precioBase < 0) {
            responderError('El precio base debe ser un número positivo.');
        }

        $precio = number_format((float) $precioBase, 2, '.', '');
    }

    $stmt = dbConnection()->prepare('SELECT COUNT(*) FROM categorias WHERE id = :id');
    $stmt->execute(['id' => $categoriaId]);
    if ((int) $stmt->fetchColumn() === 0) {
        responderError('La categoría seleccionada no existe.');
    }

    $uniqueSql = 'SELECT COUNT(*) FROM servicios WHERE slug = :slug';
    $params = ['slug' => $slug];

    if ($id !== null) {
        $uniqueSql .= ' AND id <> :id';
        $params['id'] = $id;
    }

    $stmt = dbConnection()->prepare($uniqueSql);
    $stmt->execute($params);
    if ((int) $stmt->fetchColumn() > 0) {
        responderError('Ya existe un servicio con ese slug.');
    }

    return [
        'categoria_id' => $categoriaId,
        'nombre' => $nombre,
        'slug' => $slug,
        'descripcion' => $descripcion === '' ? null : $descripcion,
        'precio_base' => $precio,
        'requiere_cotizacion' => $requiereCotizacion,
        'destacado' => $destacado,
        'estado' => $estado,
    ];
}

function idServicioDesdeRequest(array $payload): int
{
    $id = filter_var($payload['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        responderError('El servicio seleccionado no es válido.');
    }

    return $id;
}

function crearServicio(array $payload): never
{
    $data = validarServicio($payload);
    $stmt = dbConnection()->prepare(
        "INSERT INTO servicios
            (categoria_id, nombre, slug, descripcion, precio_base, requiere_cotizacion, destacado, estado)
         VALUES
            (:categoria_id, :nombre, :slug, :descripcion, :precio_base, :requiere_cotizacion, :destacado, :estado)"
    );
    $stmt->execute($data);

    http_response_code(201);
    sendJson([
        'ok' => true,
        'message' => 'Servicio agregado correctamente.',
        'id' => (int) dbConnection()->lastInsertId(),
    ]);
}

function actualizarServicio(array $payload): never
{
    $id = idServicioDesdeRequest($payload);
    $data = validarServicio($payload, $id);
    $data['id'] = $id;

    $stmt = dbConnection()->prepare(
        "UPDATE servicios
         SET categoria_id = :categoria_id,
             nombre = :nombre,
             slug = :slug,
             descripcion = :descripcion,
             precio_base = :precio_base,
             requiere_cotizacion = :requiere_cotizacion,
             destacado = :destacado,
             estado = :estado
         WHERE id = :id"
    );
    $stmt->execute($data);

    if ($stmt->rowCount() === 0) {
        $exists = dbConnection()->prepare('SELECT COUNT(*) FROM servicios WHERE id = :id');
        $exists->execute(['id' => $id]);

        if ((int) $exists->fetchColumn() === 0) {
            responderError('El servicio no existe.', 404);
        }
    }

    sendJson([
        'ok' => true,
        'message' => 'Servicio actualizado correctamente.',
    ]);
}

function eliminarServicio(array $payload): never
{
    $id = idServicioDesdeRequest($payload);

    try {
        $stmt = dbConnection()->prepare('DELETE FROM servicios WHERE id = :id');
        $stmt->execute(['id' => $id]);
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            responderError('No se puede eliminar porque el servicio tiene registros relacionados.', 409);
        }

        throw $exception;
    }

    if ($stmt->rowCount() === 0) {
        responderError('El servicio no existe.', 404);
    }

    sendJson([
        'ok' => true,
        'message' => 'Servicio eliminado correctamente.',
    ]);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    sendJson(mostrarTodosServicios());
}

$payload = leerEntradaJson();

if (!verifyCsrfToken(tokenCsrfRequest($payload))) {
    responderError('La sesión expiró. Recarga la página e intenta de nuevo.', 419);
}

match ($method) {
    'POST' => crearServicio($payload),
    'PUT', 'PATCH' => actualizarServicio($payload),
    'DELETE' => eliminarServicio($payload),
    default => responderError('Método no permitido.', 405),
};
