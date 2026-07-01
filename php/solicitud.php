<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/lib/function.php';

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'getAll':
            $data = getAllSolicitudes();
            echo json_encode([
                'status' => 'success',
                'data' => $data
            ]);
            break;

        case 'create':
            $ok = createSolicitud($input);
            echo json_encode([
                'status' => $ok ? 'success' : 'error',
                'message' => $ok ? null : 'No se pudo guardar'
            ]);
            break;

        case 'delete':
            $id = $input['id'] ?? null;
            $ok = deleteSolicitud($id);
            echo json_encode([
                'status' => $ok ? 'success' : 'error',
                'message' => $ok ? null : 'No se pudo eliminar'
            ]);
            break;

        default:
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action'
            ]);
            break;
    }
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}