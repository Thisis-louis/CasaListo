<?php

declare(strict_types=1);

require_once __DIR__ . '/config/connection.php';

function dbConnection(): PDO
{
    return db();
}
