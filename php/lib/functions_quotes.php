<?php
declare(strict_types=1);

/**
 * Helpers base para CasaListo.
 * Este archivo reutiliza la conexión real del proyecto.
 */

require_once __DIR__ . '/../config/connection.php';

if (!function_exists('dbConnection')) {
    function dbConnection(): PDO
    {
        return db();
    }
}

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

/**
 * Obtiene todas las cotizaciones de la BD CasaListo
 */
function getAllQuotes(): array|false
{
    $pdo = dbConnection();

    try {
        $stmt = $pdo->query("SELECT id, servicio_id, descripcion, monto_estimado, estado, creado_en FROM cotizaciones ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Inserta una nueva cotización respetando el esquema relacional
 */
function processQuote(array $data): array
{
    $pdo = dbConnection();

    try {
        $sql = "INSERT INTO cotizaciones (usuario_id, servicio_id, descripcion, monto_estimado, estado) 
                VALUES (1, :servicio_id, :descripcion, :monto_estimado, 'pendiente')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':servicio_id' => $data['servicio_id'],
            ':descripcion' => $data['descripcion'],
            ':monto_estimado' => $data['monto_estimado']
        ]);

        return [
            "status" => "success",
            "message" => "¡Cotización guardada en CasaListo!"
        ];
    } catch (Throwable $e) {
        return [
            "status" => "error",
            "message" => "Error al guardar: " . $e->getMessage()
        ];
    }
}

/**
 * Actualiza los campos de la cotización
 */
function updateQuote(array $data): array
{
    $pdo = dbConnection();

    try {
        $sql = "UPDATE cotizaciones SET 
                    servicio_id = :servicio_id, 
                    descripcion = :descripcion, 
                    monto_estimado = :monto_estimado 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':servicio_id' => $data['servicio_id'],
            ':descripcion' => $data['descripcion'],
            ':monto_estimado' => $data['monto_estimado'],
            ':id' => $data['id']
        ]);

        return [
            "status" => "success",
            "message" => "¡Cotización actualizada con éxito!"
        ];
    } catch (Throwable $e) {
        return [
            "status" => "error",
            "message" => "Error al actualizar: " . $e->getMessage()
        ];
    }
}

/**
 * Elimina físicamente el registro de cotización
 */
function deleteQuote(int|string $id): array
{
    $pdo = dbConnection();

    try {
        $sql = "DELETE FROM cotizaciones WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return [
            "status" => "success",
            "message" => "Registro eliminado de CasaListo correctamente."
        ];
    } catch (Throwable $e) {
        return [
            "status" => "error",
            "message" => "Error al eliminar: " . $e->getMessage()
        ];
    }
}