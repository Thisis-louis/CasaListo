<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/connection.php';

const CASALISTO_BASE_URL = '/CasaListo';

function startUserSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}

function sendSecurityHeaders(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

function baseUrl(string $path = ''): string
{
    return CASALISTO_BASE_URL . '/' . ltrim($path, '/');
}

function redirectTo(string $path): never
{
    header('Location: ' . baseUrl($path));
    exit;
}

function csrfToken(): string
{
    startUserSession();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    startUserSession();

    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function currentUser(): ?array
{
    startUserSession();

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = db()->prepare(
        "SELECT u.id, u.nombre, u.apellido, u.email, u.estado, r.nombre AS rol
         FROM usuarios u
         INNER JOIN roles r ON r.id = u.rol_id
         WHERE u.id = :id
         LIMIT 1"
    );
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || $user['estado'] !== 'activo') {
        logoutUser();
        return null;
    }

    return $user;
}

function requireAuth(?array $allowedRoles = null): array
{
    $user = currentUser();

    if (!$user) {
        if (expectsJson()) {
            authJsonError('Sesión requerida.', 401);
        }

        redirectTo('auth/login.php');
    }

    if ($allowedRoles !== null && !in_array($user['rol'], $allowedRoles, true)) {
        if (expectsJson()) {
            authJsonError('No tienes permiso para esta acción.', 403);
        }

        redirectTo('auth/login.php?error=sin_permiso');
    }

    return $user;
}

function expectsJson(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    return str_contains($accept, 'application/json')
        || str_contains($requestUri, '/php/');
}

function authJsonError(string $message, int $status): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function roleHome(string $role): string
{
    return match ($role) {
        'administrador' => 'admin/dashboard.php',
        'tecnico' => 'tecnico/dashboard.php',
        default => 'cliente/dashboard.php',
    };
}

function logoutUser(): void
{
    startUserSession();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

sendSecurityHeaders();
