<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/includes/auth.php';

requireAuth(['administrador']);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Módulos | CasaListo</title>
    <link rel="icon" href="../assets/img/Logo.png">
    <link rel="stylesheet" href="../assets/css/casalisto-theme.css">
    <link rel="stylesheet" href="../assets/css/modules.css">
    <link rel="stylesheet" href="../modules/css/styles.css">
</head>
<body class="module-page">
    <header class="module-header">
        <div class="cl-container module-header__inner">
            <a class="cl-brand" href="../index.php">
                <img class="cl-brand__logo" src="../assets/img/Logo.png" alt="CasaListo">
                <span class="cl-brand__name">Casa<span>Listo</span></span>
            </a>
            <nav class="module-nav">
                <a class="cl-button cl-button--ghost" href="dashboard.php">Admin</a>
                <a class="cl-button cl-button--secondary" href="../auth/logout.php">Salir</a>
            </nav>
        </div>
    </header>

    <main class="cl-container module-layout">
        <aside class="module-sidebar" id="moduleMenu"></aside>

        <section class="module-panel">
            <div class="module-panel-title">
                <div>
                    <p class="cl-eyebrow">Módulo</p>
                    <h1 id="moduleTitle">Registros</h1>
                </div>
                <span class="cl-status" id="recordCount">0 registros</span>
            </div>

            <div class="module-toolbar">
                <button id="addBtn" type="button">Agregar</button>
                <button id="listBtn" type="button" class="hidden">Volver</button>
            </div>

            <div id="message" class="module-message" hidden></div>

            <div id="tableList">
                <div class="simple-table-wrap">
                    <table class="simple-table">
                        <thead>
                            <tr id="tableHead"></tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>
            </div>

            <div id="formContainer" class="hidden"></div>
        </section>
    </main>

    <script type="module" src="../modules/js/app.js"></script>
</body>
</html>
