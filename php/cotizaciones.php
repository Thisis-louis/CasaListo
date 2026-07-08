<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAdminForTableEndpoint();

function mostrarTodasCotizaciones(): array
{
    return tableRecords('cotizaciones');
}

sendJson(mostrarTodasCotizaciones());
