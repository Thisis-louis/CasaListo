<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

$user = requireAuth(['cliente']);
$period = dashboardPeriod((string) ($_GET['period'] ?? 'month'));
$periodParams = periodParams($period);
$baseParams = array_merge($periodParams, ['cliente_id' => $user['id']]);

$requests = (int) fetchScalar(
    'SELECT COUNT(*) FROM solicitudes WHERE cliente_id = :cliente_id AND creado_en BETWEEN :start AND :end',
    $baseParams
);
$completed = (int) fetchScalar(
    "SELECT COUNT(*) FROM solicitudes
     WHERE cliente_id = :cliente_id
       AND estado = 'completada'
       AND actualizado_en BETWEEN :start AND :end",
    $baseParams
);
$quotes = (int) fetchScalar(
    'SELECT COUNT(*)
     FROM cotizaciones co
     INNER JOIN solicitudes so ON so.id = co.solicitud_id
     WHERE so.cliente_id = :cliente_id
       AND co.creado_en BETWEEN :start AND :end',
    $baseParams
);
$paid = (float) fetchScalar(
    "SELECT COALESCE(SUM(monto), 0)
     FROM pagos
     WHERE cliente_id = :cliente_id
       AND estado = 'pagado'
       AND COALESCE(pagado_en, creado_en) BETWEEN :start AND :end",
    $baseParams
);

$statusRows = fetchRows(
    'SELECT estado AS label, COUNT(*) AS value
     FROM solicitudes
     WHERE cliente_id = :cliente_id
       AND creado_en BETWEEN :start AND :end
     GROUP BY estado
     ORDER BY value DESC',
    $baseParams
);

$categoryRows = fetchRows(
    'SELECT c.nombre AS label, COUNT(so.id) AS value
     FROM solicitudes so
     INNER JOIN servicios s ON s.id = so.servicio_id
     INNER JOIN categorias c ON c.id = s.categoria_id
     WHERE so.cliente_id = :cliente_id
       AND so.creado_en BETWEEN :start AND :end
     GROUP BY c.id, c.nombre
     ORDER BY value DESC
     LIMIT 6',
    $baseParams
);

$requestTrendRows = trendRows(
    'creado_en',
    'so',
    'COUNT(*)',
    'FROM solicitudes so',
    'AND so.cliente_id = :cliente_id',
    $period,
    ['cliente_id' => $user['id']]
);

$paymentRows = fetchRows(
    'SELECT estado AS label, COUNT(*) AS value
     FROM pagos
     WHERE cliente_id = :cliente_id
       AND creado_en BETWEEN :start AND :end
     GROUP BY estado
     ORDER BY value DESC',
    $baseParams
);

sendDashboardJson([
    'ok' => true,
    'role' => 'cliente',
    'period' => [
        'key' => $period['period'],
        'label' => $period['label'],
    ],
    'metrics' => [
        metric('Solicitudes', $requests, 'Creadas en el periodo'),
        metric('Completadas', $completed, 'Servicios cerrados'),
        metric('Cotizaciones', $quotes, 'Recibidas o generadas'),
        metric('Pagado', moneyValue($paid), 'Pagos confirmados'),
    ],
    'charts' => [
        [
            'id' => 'client-status',
            'title' => 'Mis solicitudes por estado',
            'type' => 'bar',
            'value_type' => 'number',
            'rows' => $statusRows,
        ],
        [
            'id' => 'client-categories',
            'title' => 'Mis servicios por categoría',
            'type' => 'bar',
            'value_type' => 'number',
            'rows' => $categoryRows,
        ],
        [
            'id' => 'client-requests-trend',
            'title' => 'Historial de solicitudes',
            'type' => 'bar',
            'value_type' => 'number',
            'rows' => $requestTrendRows,
        ],
        [
            'id' => 'client-payments',
            'title' => 'Estado de mis pagos',
            'type' => 'bar',
            'value_type' => 'number',
            'rows' => $paymentRows,
        ],
    ],
]);
