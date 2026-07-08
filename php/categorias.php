<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAdminForTableEndpoint();

function mostrarTodasCategorias(): array
{
    return tableRecords('categorias');
}

sendJson(mostrarTodasCategorias());
