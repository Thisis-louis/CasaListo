<?php

declare(strict_types=1);

require_once __DIR__ . '/functions_quotes.php';

if (!function_exists('sendJson')) {
    function sendJson(array $payload): never
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('requireAdminForTableEndpoint')) {
    function requireAdminForTableEndpoint(): void
    {
        // Temporalmente vacío.
    }
}