<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/includes/auth.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$user = requireAuth(['administrador']);
$roles = db()->query("SELECT nombre FROM roles ORDER BY FIELD(nombre, 'tecnico', 'administrador', 'cliente'), nombre")->fetchAll();

$messages = [
    'creado' => 'Usuario creado correctamente.',
    'csrf' => 'La sesión expiró. Intenta guardar otra vez.',
    'datos' => 'Completa los campos obligatorios.',
    'correo' => 'Ese correo ya está registrado.',
    'password' => 'La contraseña debe tener al menos 8 caracteres.',
    'rol' => 'Selecciona un rol válido.',
    'error' => 'No se pudo crear el usuario.',
];

$status = $_GET['status'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear usuario | CasaListo</title>
    <link rel="icon" href="../assets/img/Logo.png">
    <link rel="stylesheet" href="../assets/css/casalisto-theme.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="dashboard-page">
        <header class="dashboard-header">
            <div class="cl-container dashboard-header__inner">
                <a class="cl-brand" href="dashboard.php">
                    <img class="cl-brand__logo" src="../assets/img/Logo.png" alt="CasaListo">
                    <span class="cl-brand__name">Casa<span>Listo</span></span>
                </a>
                <nav class="module-nav">
                    <a class="cl-button cl-button--ghost" href="dashboard.php">Dashboard</a>
                    <a class="cl-button cl-button--ghost" href="../auth/logout.php">Salir</a>
                </nav>
            </div>
        </header>

        <main class="cl-container dashboard-shell">
            <section class="dashboard-title">
                <div>
                    <p class="cl-eyebrow">Administración interna</p>
                    <h1>Crear usuario</h1>
                    <p>Esta vista no aparece para clientes. Úsala para crear técnicos, administradores u otros usuarios internos.</p>
                </div>
                <span class="cl-status">Solo administrador</span>
            </section>

            <form class="admin-form-card" action="../php/auth/crear_usuario.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <?php if ($status === 'creado'): ?>
                    <div class="auth-success" role="status"><?= e($messages['creado']) ?></div>
                <?php endif; ?>

                <?php if ($error && isset($messages[$error])): ?>
                    <div class="auth-alert" role="alert"><?= e($messages[$error]) ?></div>
                <?php endif; ?>

                <div class="auth-field-grid">
                    <label>
                        Nombre
                        <input class="cl-input" type="text" name="nombre" required>
                    </label>
                    <label>
                        Apellido
                        <input class="cl-input" type="text" name="apellido">
                    </label>
                </div>

                <div class="auth-field-grid">
                    <label>
                        Correo electrónico
                        <input class="cl-input" type="email" name="email" required>
                    </label>
                    <label>
                        Teléfono
                        <input class="cl-input" type="tel" name="telefono">
                    </label>
                </div>

                <div class="auth-field-grid">
                    <label>
                        Rol
                        <select class="cl-select" name="rol" required>
                            <option value="">Selecciona rol</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= e($role['nombre']) ?>"><?= e(ucfirst($role['nombre'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Contraseña temporal
                        <input class="cl-input" type="password" name="password" required>
                    </label>
                </div>

                <div class="auth-field-grid">
                    <label>
                        Nombre comercial técnico
                        <input class="cl-input" type="text" name="nombre_comercial" placeholder="Solo si el rol es técnico">
                    </label>
                    <label>
                        Zona
                        <input class="cl-input" type="text" name="zona" placeholder="Cancún, Riviera Maya...">
                    </label>
                </div>

                <label>
                    Bio o notas del técnico
                    <textarea class="cl-textarea" name="bio" placeholder="Especialidad, experiencia o notas internas"></textarea>
                </label>

                <button class="cl-button cl-button--primary" type="submit">Crear usuario</button>
            </form>
        </main>
    </div>
</body>
</html>
