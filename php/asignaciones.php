<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAdminForTableEndpoint();

function mostrarTodasAsignaciones(): array
{
    return tableRecords('asignaciones');
}

sendJson(mostrarTodasAsignaciones());
