<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAdminForTableEndpoint();

function mostrarTodosPagos(): array
{
    return tableRecords('pagos');
}

sendJson(mostrarTodosPagos());
