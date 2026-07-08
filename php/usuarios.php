<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAdminForTableEndpoint();

function mostrarTodosUsuarios(): array
{
    return tableRecords('usuarios');
}

sendJson(mostrarTodosUsuarios());
