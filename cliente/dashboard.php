<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/includes/auth.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$user = requireAuth(['cliente']);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cliente | CasaListo</title>
    <link rel="icon" href="../assets/img/logo-casalisto.png">
    <link rel="stylesheet" href="../assets/css/casalisto-theme.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="dashboard-page">
        <header class="dashboard-header">
            <div class="cl-container dashboard-header__inner">
                <a class="cl-brand" href="../index.php">
                    <img class="cl-brand__logo" src="../assets/img/logo-casalisto.png" alt="CasaListo">
                    <span class="cl-brand__name">Casa<span>Listo</span></span>
                </a>
                <a class="cl-button cl-button--ghost" href="../auth/logout.php">Salir</a>
            </div>
        </header>

        <main class="cl-container dashboard-shell">
            <section class="dashboard-title">
                <div>
                    <p class="cl-eyebrow">Panel de cliente</p>
                    <h1>Hola, <?= e($user['nombre']) ?></h1>
                    <p>Aquí podrás crear solicitudes, revisar cotizaciones, pagos y calificar servicios.</p>
                </div>
                <span class="cl-status">Cliente</span>
            </section>
        </main>
    </div>
</body>
</html>
