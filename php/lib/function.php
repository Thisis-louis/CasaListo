<?php
require_once __DIR__ . '/../config/connection.php';

$db = db();

function getAllSolicitudes() {
    global $db;

    $stmt = $db->prepare("
        SELECT
            id,
            cliente_id,
            servicio_id,
            folio,
            titulo,
            descripcion,
            direccion,
            colonia,
            ciudad,
            estado_region,
            codigo_postal,
            fecha_preferida,
            hora_preferida,
            urgencia,
            estado,
            creado_en,
            actualizado_en
        FROM solicitudes
        ORDER BY id DESC
    ");

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createSolicitud($datos) {
    global $db;

    $stmt = $db->prepare("
        INSERT INTO solicitudes (
            cliente_id, servicio_id, folio, titulo, descripcion, direccion,
            colonia, ciudad, estado_region, codigo_postal,
            fecha_preferida, hora_preferida, urgencia, estado
        ) VALUES (
            :cliente_id, :servicio_id, :folio, :titulo, :descripcion, :direccion,
            :colonia, :ciudad, :estado_region, :codigo_postal,
            :fecha_preferida, :hora_preferida, :urgencia, :estado
        )
    ");

    return $stmt->execute([
        ':cliente_id' => $datos['cliente_id'] ?? null,
        ':servicio_id' => $datos['servicio_id'] ?? null,
        ':folio' => $datos['folio'] ?? '',
        ':titulo' => $datos['titulo'] ?? '',
        ':descripcion' => $datos['descripcion'] ?? '',
        ':direccion' => $datos['direccion'] ?? '',
        ':colonia' => $datos['colonia'] ?? '',
        ':ciudad' => $datos['ciudad'] ?? '',
        ':estado_region' => $datos['estado_region'] ?? '',
        ':codigo_postal' => $datos['codigo_postal'] ?? '',
        ':fecha_preferida' => $datos['fecha_preferida'] ?? null,
        ':hora_preferida' => $datos['hora_preferida'] ?? null,
        ':urgencia' => $datos['urgencia'] ?? '',
        ':estado' => $datos['estado'] ?? ''
    ]);
}

function deleteSolicitud($id) {
    global $db;

    $stmt = $db->prepare("
        DELETE FROM solicitudes
        WHERE id = :id
    ");

    return $stmt->execute([
        ':id' => $id
    ]);
}