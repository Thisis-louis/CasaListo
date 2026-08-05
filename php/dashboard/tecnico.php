<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

$user = requireAuth(['tecnico']);
$period = dashboardPeriod((string) ($_GET['period'] ?? 'month'));
$periodParams = periodParams($period);

$technician = fetchRows(
    'SELECT id, calificacion_promedio, disponible FROM tecnicos WHERE usuario_id = :usuario_id LIMIT 1',
    ['usuario_id' => $user['id']]
);

if ($technician === []) {
    sendDashboardJson([
        'ok' => true,
        'role' => 'tecnico',
        'period' => [
            'key' => $period['period'],
            'label' => $period['label'],
        ],
        'metrics' => [
            metric('Asignaciones', 0, 'No hay perfil técnico asociado'),
            metric('Completadas', 0, 'Trabajos cerrados'),
            metric('Pendientes', 0, 'Por atender'),
            metric('Calificación', '0.00', 'Promedio actual'),
        ],
        'charts' => [],
        'message' => 'Tu usuario aún no tiene perfil técnico asociado.',
    ]);
}

$technician = $technician[0];
$technicianId = (int) $technician['id'];
$baseParams = array_merge($periodParams, ['tecnico_id' => $technicianId]);

$assigned = (int) fetchScalar(
    'SELECT COUNT(*) FROM asignaciones WHERE tecnico_id = :tecnico_id AND creado_en BETWEEN :start AND :end',
    $baseParams
);
$completed = (int) fetchScalar(
    "SELECT COUNT(*) FROM asignaciones
     WHERE tecnico_id = :tecnico_id
       AND estado = 'completada'
       AND COALESCE(fecha_fin, actualizado_en) BETWEEN :start AND :end",
    $baseParams
);
$pending = (int) fetchScalar(
    "SELECT COUNT(*) FROM asignaciones
     WHERE tecnico_id = :tecnico_id
       AND estado IN ('pendiente', 'aceptada', 'en_camino', 'en_proceso')
       AND creado_en BETWEEN :start AND :end",
    $baseParams
);
$rating = (string) ($technician['calificacion_promedio'] ?? '0.00');

$statusRows = fetchRows(
    'SELECT estado AS label, COUNT(*) AS value
     FROM asignaciones
     WHERE tecnico_id = :tecnico_id
       AND creado_en BETWEEN :start AND :end
     GROUP BY estado
     ORDER BY value DESC',
    $baseParams
);

$categoryRows = fetchRows(
    'SELECT c.nombre AS label, COUNT(a.id) AS value
     FROM asignaciones a
     INNER JOIN solicitudes so ON so.id = a.solicitud_id
     INNER JOIN servicios s ON s.id = so.servicio_id
     INNER JOIN categorias c ON c.id = s.categoria_id
     WHERE a.tecnico_id = :tecnico_id
       AND a.creado_en BETWEEN :start AND :end
     GROUP BY c.id, c.nombre
     ORDER BY value DESC
     LIMIT 6',
    $baseParams
);

$completedTrendRows = trendRows(
    'fecha_fin',
    'a',
    'COUNT(*)',
    'FROM asignaciones a',
    "AND a.tecnico_id = :tecnico_id AND a.estado = 'completada' AND a.fecha_fin IS NOT NULL",
    $period,
    ['tecnico_id' => $technicianId]
);

$ratingsRows = fetchRows(
    'SELECT puntuacion AS label, COUNT(*) AS value
     FROM calificaciones
     WHERE tecnico_id = :tecnico_id
       AND creado_en BETWEEN :start AND :end
     GROUP BY puntuacion
     ORDER BY puntuacion ASC',
    $baseParams
);

sendDashboardJson([
    'ok' => true,
    'role' => 'tecnico',
    'period' => [
        'key' => $period['period'],
        'label' => $period['label'],
    ],
    'metrics' => [
        metric('Asignaciones', $assigned, 'Recibidas en el periodo'),
        metric('Completadas', $completed, 'Trabajos cerrados'),
        metric('Pendientes', $pending, 'Por atender'),
        metric('Calificación', number_format((float) $rating, 2), 'Promedio actual'),
    ],
    'charts' => [
        [
            'id' => 'tech-status',
            'title' => 'Mis trabajos por estado',
            'type' => 'bar',
            'value_type' => 'number',
            'rows' => $statusRows,
        ],
        [
            'id' => 'tech-categories',
            'title' => 'Mis servicios por categoría',
            'type' => 'bar',
            'value_type' => 'number',
            'rows' => $categoryRows,
        ],
        [
            'id' => 'tech-completed-trend',
            'title' => 'Historial de trabajos completados',
            'type' => 'bar',
            'value_type' => 'number',
            'rows' => $completedTrendRows,
        ],
        [
            'id' => 'tech-ratings',
            'title' => 'Calificaciones recibidas',
            'type' => 'bar',
            'value_type' => 'number',
            'rows' => $ratingsRows,
        ],
    ],
]);
