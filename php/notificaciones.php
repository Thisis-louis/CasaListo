<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAdminForTableEndpoint();

function mostrarTodasNotificaciones(): array
{
    return tableRecords('notificaciones');
}

sendJson(mostrarTodasNotificaciones());
