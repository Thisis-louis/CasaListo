<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAdminForTableEndpoint();

function mostrarTodasPaginasContenido(): array
{
    return tableRecords('paginas_contenido');
}

sendJson(mostrarTodasPaginasContenido());
