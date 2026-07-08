<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAdminForTableEndpoint();

function mostrarTodosSolicitudArchivos(): array
{
    return tableRecords('solicitud_archivos');
}

sendJson(mostrarTodosSolicitudArchivos());
