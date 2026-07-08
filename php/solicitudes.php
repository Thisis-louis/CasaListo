<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAdminForTableEndpoint();

function mostrarTodasSolicitudes(): array
{
    return tableRecords('solicitudes');
}

sendJson(mostrarTodasSolicitudes());
