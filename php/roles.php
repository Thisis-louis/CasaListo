<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAdminForTableEndpoint();

function mostrarTodosRoles(): array
{
    return tableRecords('roles');
}

sendJson(mostrarTodosRoles());
