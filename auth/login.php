<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/includes/auth.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$user = currentUser();

if ($user) {
    redirectTo(roleHome($user['rol']));
}

$messages = [
    'credenciales' => 'El correo o la contraseña no coinciden.',
    'csrf' => 'La sesión expiró. Intenta iniciar sesión otra vez.',
    'inactivo' => 'Tu usuario no está activo. Contacta al administrador.',
    'sin_permiso' => 'No tienes permiso para entrar a esa sección.',
];

$error = $_GET['error'] ?? '';
$message = $messages[$error] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar | CasaListo</title>
    <link rel="icon" href="../assets/img/Logo.png">
    <link rel="stylesheet" href="../assets/css/casalisto-theme.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <main class="auth-page">
        <section class="auth-shell">
            <div class="auth-brand">
                <a class="cl-brand" href="../index.php">
                    <img class="cl-brand__logo" src="../assets/img/Logo.png" alt="CasaListo">
                    <span class="cl-brand__name">Casa<span>Listo</span></span>
                </a>
                <h1>Acceso a la plataforma</h1>
                <p>Clientes, técnicos y administradores entran desde el mismo login. El rol define el panel de destino.</p>
            </div>

            <form class="auth-card" action="../php/auth/login.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <div>
                    <p class="cl-eyebrow">Iniciar sesión</p>
                    <h2>Bienvenido de vuelta</h2>
                </div>

                <?php if ($message): ?>
                    <div class="auth-alert" role="alert"><?= e($message) ?></div>
                <?php endif; ?>

                <label>
                    Correo electrónico
                    <input class="cl-input" type="email" name="email" autocomplete="email" required>
                </label>

                <label>
                    Contraseña
                    <input class="cl-input" type="password" name="password" autocomplete="current-password" required>
                </label>

                <button class="cl-button cl-button--primary" type="submit">Entrar</button>

                <p class="auth-help">¿Eres cliente nuevo? <a href="registro.php">Crea tu cuenta</a></p>
                <p class="auth-help">Usuario inicial: <strong>admin@casalisto.local</strong></p>
            </form>
        </section>
    </main>
</body>
</html>
