<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/includes/auth.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$user = requireAuth(['administrador']);

$metrics = [
    'Solicitudes' => (int) db()->query('SELECT COUNT(*) FROM solicitudes')->fetchColumn(),
    'Cotizaciones' => (int) db()->query('SELECT COUNT(*) FROM cotizaciones')->fetchColumn(),
    'Técnicos' => (int) db()->query('SELECT COUNT(*) FROM tecnicos')->fetchColumn(),
    'Servicios' => (int) db()->query('SELECT COUNT(*) FROM servicios')->fetchColumn(),
];

$modules = [
    'roles' => 'Roles',
    'usuarios' => 'Usuarios',
    'categorias' => 'Categorías',
    'servicios' => 'Servicios',
    'tecnicos' => 'Técnicos',
    'tecnicos_servicios' => 'Técnicos y servicios',
    'solicitudes' => 'Solicitudes',
    'solicitud_archivos' => 'Archivos de solicitudes',
    'cotizaciones' => 'Cotizaciones',
    'asignaciones' => 'Asignaciones',
    'pagos' => 'Pagos',
    'calificaciones' => 'Calificaciones',
    'notificaciones' => 'Notificaciones',
    'paginas_contenido' => 'Contenido de páginas',
    'bitacora' => 'Bitácora',
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin | CasaListo</title>
    <link rel="icon" href="../assets/img/Logo.png">
    <link rel="stylesheet" href="../assets/css/casalisto-theme.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="dashboard-page">
        <header class="dashboard-header">
            <div class="cl-container dashboard-header__inner">
                <a class="cl-brand" href="../index.php">
                    <img class="cl-brand__logo" src="../assets/img/Logo.png" alt="CasaListo">
                    <span class="cl-brand__name">Casa<span>Listo</span></span>
                </a>
                <nav class="module-nav">
                    <a class="cl-button cl-button--secondary" href="crear-usuario.php">Crear usuario</a>
                    <a class="cl-button cl-button--ghost" href="../auth/logout.php">Salir</a>
                </nav>
            </div>
        </header>

        <main class="cl-container dashboard-shell">
            <section class="dashboard-title">
                <div>
                    <p class="cl-eyebrow">Panel administrativo</p>
                    <h1>Hola, <?= e($user['nombre']) ?></h1>
                    <p>Desde aquí se controlarán solicitudes, cotizaciones, técnicos, pagos y reportes.</p>
                </div>
                <span class="cl-status">Administrador</span>
            </section>

            <section class="dashboard-grid">
                <?php foreach ($metrics as $label => $value): ?>
                    <article class="metric-card">
                        <span><?= e($label) ?></span>
                        <strong><?= $value ?></strong>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="dashboard-modules">
                <div class="dashboard-title">
                    <div>
                        <p class="cl-eyebrow">Módulos por tabla</p>
                        <h2>Administración de registros</h2>
                        <p>Cada acceso abre el `index.html` del módulo correspondiente.</p>
                    </div>
                </div>

                <div class="dashboard-module-grid">
                    <?php foreach ($modules as $folder => $label): ?>
                        <a class="module-link" href="../<?= e($folder) ?>/">
                            <span><?= e($label) ?></span>
                            <strong><?= e($folder) ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
