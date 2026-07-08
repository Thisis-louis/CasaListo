<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAdminForTableEndpoint();

function mostrarTodosTecnicosServicios(): array
{
    return tableRecords('tecnicos_servicios');
}

sendJson(mostrarTodosTecnicosServicios());
