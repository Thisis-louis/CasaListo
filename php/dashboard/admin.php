<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAuth(['administrador']);

$period = dashboardPeriod((string) ($_GET['period'] ?? 'month'));
$periodParams = periodParams($period);

$requests = (int) fetchScalar(
    'SELECT COUNT(*) FROM solicitudes WHERE creado_en BETWEEN :start AND :end',
    $periodParams
);
$completed = (int) fetchScalar(
    "SELECT COUNT(*) FROM solicitudes WHERE estado = 'completada' AND actualizado_en BETWEEN :start AND :end",
    $periodParams
);
$revenue = (float) fetchScalar(
    "SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE estado = 'pagado' AND COALESCE(pagado_en, creado_en) BETWEEN :start AND :end",
    $periodParams
);
$activeTechnicians = (int) fetchScalar(
    'SELECT COUNT(*) FROM tecnicos WHERE disponible = 1'
);

$statusRows = fetchRows(
    'SELECT estado AS label, COUNT(*) AS value
     FROM solicitudes
     WHERE creado_en BETWEEN :start AND :end
     GROUP BY estado
     ORDER BY value DESC',
    $periodParams
);

$categoryRows = fetchRows(
    'SELECT c.nombre AS label, COUNT(s.id) AS value
     FROM solicitudes so
     INNER JOIN servicios s ON s.id = so.servicio_id
     INNER JOIN categorias c ON c.id = s.categoria_id
     WHERE so.creado_en BETWEEN :start AND :end
     GROUP BY c.id, c.nombre
     ORDER BY value DESC
     LIMIT 6',
    $periodParams
);

$revenueRows = trendRows(
    'creado_en',
    'p',
    'COALESCE(SUM(p.monto), 0)',
    'FROM pagos p',
    "AND p.estado = 'pagado'",
    $period
);

$requestTrendRows = trendRows(
    'creado_en',
    'so',
    'COUNT(*)',
    'FROM solicitudes so',
    '',
    $period
);

sendDashboardJson([
    'ok' => true,
    'role' => 'administrador',
    'period' => [
        'key' => $period['period'],
        'label' => $period['label'],
    ],
    'metrics' => [
        metric('Solicitudes', $requests, 'Creadas en el periodo'),
        metric('Completadas', $completed, 'Trabajos cerrados'),
        metric('Ingresos', moneyValue($revenue), 'Pagos confirmados'),
        metric('Técnicos activos', $activeTechnicians, 'Disponibles para asignar'),
    ],
    'charts' => [
        [
            'id' => 'admin-revenue',
            'title' => 'Ingresos confirmados',
            'type' => 'bar',
            'value_type' => 'money',
            'rows' => $revenueRows,
        ],
        [
            'id' => 'admin-categories',
            'title' => 'Solicitudes por tipo de servicio',
            'type' => 'bar',
            'value_type' => 'number',
            'rows' => $categoryRows,
        ],
        [
            'id' => 'admin-status',
            'title' => 'Estado de solicitudes',
            'type' => 'bar',
            'value_type' => 'number',
            'rows' => $statusRows,
        ],
        [
            'id' => 'admin-requests-trend',
            'title' => 'Historial de solicitudes',
            'type' => 'bar',
            'value_type' => 'number',
            'rows' => $requestTrendRows,
        ],
    ],
]);
