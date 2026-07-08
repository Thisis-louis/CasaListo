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
    'csrf' => 'La sesión expiró. Intenta crear la cuenta otra vez.',
    'datos' => 'Completa todos los campos obligatorios.',
    'correo' => 'Ese correo ya está registrado.',
    'password' => 'La contraseña debe tener al menos 8 caracteres y coincidir.',
    'error' => 'No se pudo crear la cuenta. Intenta nuevamente.',
];

$message = $messages[$_GET['error'] ?? ''] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear cuenta | CasaListo</title>
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
                <h1>Crea tu cuenta de cliente</h1>
                <p>Regístrate para solicitar servicios, revisar cotizaciones y dar seguimiento a tus trabajos desde CasaListo.</p>
            </div>

            <form class="auth-card" action="../php/auth/registro_cliente.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <div>
                    <p class="cl-eyebrow">Registro público</p>
                    <h2>Cuenta de cliente</h2>
                </div>

                <?php if ($message): ?>
                    <div class="auth-alert" role="alert"><?= e($message) ?></div>
                <?php endif; ?>

                <div class="auth-field-grid">
                    <label>
                        Nombre
                        <input class="cl-input" type="text" name="nombre" autocomplete="given-name" required>
                    </label>
                    <label>
                        Apellido
                        <input class="cl-input" type="text" name="apellido" autocomplete="family-name">
                    </label>
                </div>

                <label>
                    Correo electrónico
                    <input class="cl-input" type="email" name="email" autocomplete="email" required>
                </label>

                <label>
                    Teléfono
                    <input class="cl-input" type="tel" name="telefono" autocomplete="tel">
                </label>

                <div class="auth-field-grid">
                    <label>
                        Contraseña
                        <input class="cl-input" type="password" name="password" autocomplete="new-password" required>
                    </label>
                    <label>
                        Confirmar
                        <input class="cl-input" type="password" name="password_confirm" autocomplete="new-password" required>
                    </label>
                </div>

                <button class="cl-button cl-button--primary" type="submit">Crear cuenta</button>
                <p class="auth-help">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
            </form>
        </section>
    </main>
</body>
</html>
