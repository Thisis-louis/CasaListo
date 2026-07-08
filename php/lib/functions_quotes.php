<?php
/**
 * Obtiene todas las cotizaciones de la BD CasaListo
 */
function getAllQuotes() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT id, servicio_id, descripcion, monto_estimado, estado, creado_en FROM cotizaciones ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Inserta una nueva cotización respetando el esquema relacional
 */
function processQuote($data) {
    global $pdo;
    try {
        // Se asume un usuario_id = 1 temporal por la llave foránea obligatoria de la BD
        $sql = "INSERT INTO cotizaciones (usuario_id, servicio_id, descripcion, monto_estimado, estado) 
                VALUES (1, :servicio_id, :descripcion, :monto_estimado, 'pendiente')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':servicio_id' => $data['servicio_id'],
            ':descripcion' => $data['descripcion'],
            ':monto_estimado' => $data['monto_estimado']
        ]);
        return ["status" => "success", "message" => "¡Cotización guardada en CasaListo!"];
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Error al guardar: " . $e->getMessage()];
    }
}

/**
 * Actualiza los campos de la cotización
 */
function updateQuote($data) {
    global $pdo;
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
        return ["status" => "success", "message" => "¡Cotización actualizada con éxito!"];
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Error al actualizar: " . $e->getMessage()];
    }
}

/**
 * Elimina físicamente el registro de cotización
 */
function deleteQuote($id) {
    global $pdo;
    try {
        $sql = "DELETE FROM cotizaciones WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return ["status" => "success", "message" => "Registro eliminado de CasaListo correctamente."];
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Error al eliminar: " . $e->getMessage()];
    }
}
?>