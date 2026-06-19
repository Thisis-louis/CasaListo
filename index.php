<?php

declare(strict_types=1);

require __DIR__ . '/php/config/connection.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function initials(string $text): string
{
    $words = preg_split('/\s+/', trim($text)) ?: [];
    $letters = '';

    foreach ($words as $word) {
        $letters .= mb_substr($word, 0, 1);

        if (mb_strlen($letters) >= 2) {
            break;
        }
    }

    return mb_strtoupper($letters ?: 'CL');
}

$dbReady = true;
$content = [];
$categories = [];
$featuredServices = [];
$allServices = [];

try {
    $pdo = db();

    $contentRows = $pdo
        ->query("SELECT clave, titulo, contenido FROM paginas_contenido WHERE estado = 'publicado'")
        ->fetchAll();

    foreach ($contentRows as $row) {
        $content[$row['clave']] = $row;
    }

    $categories = $pdo
        ->query("SELECT id, nombre, slug, descripcion, icono FROM categorias WHERE estado = 'activa' ORDER BY orden, nombre")
        ->fetchAll();

    $featuredServices = $pdo
        ->query(
            "SELECT s.id, s.nombre, s.slug, s.descripcion, c.nombre AS categoria, c.icono
             FROM servicios s
             INNER JOIN categorias c ON c.id = s.categoria_id
             WHERE s.estado = 'activo' AND s.destacado = 1
             ORDER BY c.orden, s.nombre
             LIMIT 6"
        )
        ->fetchAll();

    $allServices = $pdo
        ->query(
            "SELECT s.id, s.nombre, s.slug, s.descripcion, c.nombre AS categoria, c.slug AS categoria_slug, c.icono
             FROM servicios s
             INNER JOIN categorias c ON c.id = s.categoria_id
             WHERE s.estado = 'activo'
             ORDER BY c.orden, s.nombre"
        )
        ->fetchAll();
} catch (Throwable $error) {
    $dbReady = false;
}

if (!$categories) {
    $categories = [
        ['nombre' => 'Plomería', 'slug' => 'plomeria', 'descripcion' => 'Reparaciones e instalaciones hidráulicas.', 'icono' => 'wrench'],
        ['nombre' => 'Electricidad', 'slug' => 'electricidad', 'descripcion' => 'Fallas, contactos, lámparas y tableros.', 'icono' => 'zap'],
        ['nombre' => 'Albercas', 'slug' => 'albercas', 'descripcion' => 'Limpieza y mantenimiento de albercas.', 'icono' => 'waves'],
        ['nombre' => 'Aire acondicionado', 'slug' => 'aire-acondicionado', 'descripcion' => 'Servicio preventivo y correctivo.', 'icono' => 'snowflake'],
    ];
}

if (!$featuredServices) {
    $featuredServices = [
        ['nombre' => 'Reparación de fugas', 'descripcion' => 'Atención para fugas visibles o reportadas.', 'categoria' => 'Plomería', 'icono' => 'wrench'],
        ['nombre' => 'Revisión eléctrica', 'descripcion' => 'Diagnóstico de fallas eléctricas residenciales.', 'categoria' => 'Electricidad', 'icono' => 'zap'],
        ['nombre' => 'Mantenimiento de alberca', 'descripcion' => 'Limpieza, revisión de equipo y tratamiento básico.', 'categoria' => 'Albercas', 'icono' => 'waves'],
        ['nombre' => 'Mantenimiento para Airbnb', 'descripcion' => 'Atención ordenada para renta vacacional.', 'categoria' => 'Airbnb', 'icono' => 'home'],
    ];
}

if (!$allServices) {
    $allServices = $featuredServices;
}

$heroTitle = $content['hero_titulo']['titulo'] ?? 'CasaListo';
$heroLead = $content['hero_titulo']['contenido'] ?? 'Servicios confiables para el hogar en Cancún y Riviera Maya.';
$heroSubtitle = $content['hero_subtitulo']['titulo'] ?? 'Llegamos, lo arreglamos, te olvidas.';
$heroCopy = $content['hero_subtitulo']['contenido'] ?? 'Conecta con técnicos verificados, solicita cotizaciones y da seguimiento a cada trabajo desde una sola plataforma.';
$ctaTitle = $content['cta_final']['titulo'] ?? 'Tu casa lista, sin vueltas';
$ctaCopy = $content['cta_final']['contenido'] ?? 'Solicita un servicio y recibe atención ordenada desde CasaListo.';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CasaListo | Servicios para el hogar en Cancún</title>
    <meta name="description" content="CasaListo conecta clientes con técnicos para mantenimiento, reparaciones y servicios residenciales en Cancún y Riviera Maya.">
    <link rel="icon" href="assets/img/logo-casalisto.png">
    <link rel="stylesheet" href="assets/css/casalisto-theme.css">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>
    <div class="cl-page">
        <header class="cl-navbar">
            <div class="cl-container cl-navbar__inner">
                <a class="cl-brand" href="#inicio" aria-label="CasaListo inicio">
                    <img class="cl-brand__logo" src="assets/img/logo-casalisto.png" alt="CasaListo">
                    <span class="cl-brand__name">Casa<span>Listo</span></span>
                </a>

                <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <nav class="main-nav" id="main-nav">
                    <a href="#servicios">Servicios</a>
                    <a href="#como-funciona">Cómo funciona</a>
                    <a href="#catalogo">Catálogo</a>
                    <a href="#contacto">Contacto</a>
                    <a href="auth/login.php">Ingresar</a>
                    <a class="cl-button cl-button--primary" href="#solicitar">Solicitar servicio</a>
                </nav>
            </div>
        </header>

        <main id="inicio">
            <section class="hero">
                <div class="cl-container hero__grid">
                    <div class="hero__content">
                        <p class="cl-eyebrow">Cancún y Riviera Maya</p>
                        <h1 class="cl-title"><?= e($heroTitle) ?></h1>
                        <p class="hero__lead"><?= e($heroLead) ?></p>
                        <p class="cl-subtitle"><?= e($heroSubtitle) ?> <?= e($heroCopy) ?></p>

                        <div class="hero__actions">
                            <a class="cl-button cl-button--primary" href="#solicitar">Pedir cotización</a>
                            <a class="cl-button cl-button--ghost" href="#servicios">Ver servicios</a>
                        </div>

                        <div class="hero__stats" aria-label="Indicadores CasaListo">
                            <div>
                                <strong><?= count($categories) ?>+</strong>
                                <span>Categorías</span>
                            </div>
                            <div>
                                <strong><?= count($allServices) ?>+</strong>
                                <span>Servicios</span>
                            </div>
                            <div>
                                <strong>24/7</strong>
                                <span>Emergencias</span>
                            </div>
                        </div>
                    </div>

                    <aside class="hero-panel" aria-label="Resumen de solicitud">
                        <img class="hero-panel__logo" src="assets/img/logo-casalisto.png" alt="">
                        <div class="hero-panel__body">
                            <span class="cl-status"><?= $dbReady ? 'Base conectada' : 'Modo respaldo' ?></span>
                            <h2>Llegamos, lo arreglamos, te olvidas.</h2>
                            <p>Solicita plomería, electricidad, albercas, aire acondicionado, Airbnb y mantenimiento general desde un solo lugar.</p>
                        </div>
                    </aside>
                </div>
            </section>

            <section class="cl-section cl-section--white" id="servicios">
                <div class="cl-container">
                    <div class="section-heading">
                        <div>
                            <p class="cl-eyebrow">Servicios destacados</p>
                            <h2>Lo que CasaListo puede resolver por ti</h2>
                        </div>
                        <p>El catálogo viene desde MySQL para que después puedas administrar servicios, categorías e iconos sin tocar la página.</p>
                    </div>

                    <div class="services-grid">
                        <?php foreach ($featuredServices as $service): ?>
                            <article class="cl-card service-card">
                                <div class="cl-card__body">
                                    <div class="service-card__top">
                                        <div class="cl-service-icon" data-service-icon="<?= e($service['icono'] ?? 'home') ?>">
                                            <?= e(initials($service['nombre'])) ?>
                                        </div>
                                        <span><?= e($service['categoria'] ?? 'Servicio') ?></span>
                                    </div>
                                    <h3><?= e($service['nombre']) ?></h3>
                                    <p><?= e($service['descripcion'] ?? 'Servicio residencial disponible por cotización.') ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="cl-section" id="como-funciona">
                <div class="cl-container process-layout">
                    <div>
                        <p class="cl-eyebrow">Cómo funciona</p>
                        <h2>Una operación simple para trabajos bien controlados</h2>
                        <p class="cl-subtitle">CasaListo se organiza alrededor de solicitudes, cotizaciones, asignaciones, pagos y calificaciones para que cada servicio tenga seguimiento.</p>
                    </div>

                    <div class="process-steps">
                        <div class="process-step">
                            <span>1</span>
                            <h3>Elige el servicio</h3>
                            <p>Selecciona la categoría y describe qué necesitas resolver.</p>
                        </div>
                        <div class="process-step">
                            <span>2</span>
                            <h3>Recibe cotización</h3>
                            <p>El administrador revisa el caso y prepara una propuesta clara.</p>
                        </div>
                        <div class="process-step">
                            <span>3</span>
                            <h3>Se asigna técnico</h3>
                            <p>Un técnico disponible toma el trabajo y actualiza el avance.</p>
                        </div>
                        <div class="process-step">
                            <span>4</span>
                            <h3>Califica el servicio</h3>
                            <p>Al cerrar, el cliente deja una calificación para cuidar la calidad.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="cl-section cl-section--white" id="catalogo">
                <div class="cl-container catalog-layout">
                    <div>
                        <p class="cl-eyebrow">Catálogo</p>
                        <h2>Servicios por categoría</h2>
                        <p class="cl-subtitle">Este bloque ya está preparado para crecer cuando agreguemos tus iconos y más servicios desde HeidiSQL.</p>
                    </div>

                    <div class="catalog-tools">
                        <label for="service-filter">Filtrar por categoría</label>
                        <select class="cl-select" id="service-filter">
                            <option value="todos">Todos los servicios</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= e($category['slug'] ?? '') ?>"><?= e($category['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="catalog-list" id="service-list">
                        <?php foreach ($allServices as $service): ?>
                            <article class="catalog-item" data-category="<?= e($service['categoria_slug'] ?? 'todos') ?>">
                                <div class="cl-service-icon" data-service-icon="<?= e($service['icono'] ?? 'home') ?>">
                                    <?= e(initials($service['nombre'])) ?>
                                </div>
                                <div>
                                    <span><?= e($service['categoria'] ?? 'Servicio') ?></span>
                                    <h3><?= e($service['nombre']) ?></h3>
                                    <p><?= e($service['descripcion'] ?? 'Disponible por cotización.') ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="cl-section" id="solicitar">
                <div class="cl-container request-layout">
                    <div>
                        <p class="cl-eyebrow">Solicita atención</p>
                        <h2><?= e($ctaTitle) ?></h2>
                        <p class="cl-subtitle"><?= e($ctaCopy) ?></p>
                    </div>

                    <form class="request-form" action="#solicitar" method="post">
                        <div class="field-grid">
                            <label>
                                Nombre
                                <input class="cl-input" type="text" name="nombre" placeholder="Tu nombre">
                            </label>
                            <label>
                                Teléfono
                                <input class="cl-input" type="tel" name="telefono" placeholder="WhatsApp o llamada">
                            </label>
                        </div>
                        <label>
                            Servicio
                            <select class="cl-select" name="servicio">
                                <option value="">Selecciona un servicio</option>
                                <?php foreach ($allServices as $service): ?>
                                    <option value="<?= e((string) ($service['id'] ?? '')) ?>"><?= e($service['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            ¿Qué necesitas?
                            <textarea class="cl-textarea" name="descripcion" placeholder="Describe el problema, zona y urgencia"></textarea>
                        </label>
                        <button class="cl-button cl-button--secondary" type="button">Preparar solicitud</button>
                    </form>
                </div>
            </section>

            <section class="cl-section cl-section--white" id="contacto">
                <div class="cl-container final-cta">
                    <img src="assets/img/logo-casalisto.png" alt="CasaListo">
                    <div>
                        <p class="cl-eyebrow">CasaListo</p>
                        <h2>Servicios confiables para mantener tu casa lista.</h2>
                        <p>La siguiente fase será activar login, registro de clientes y guardado real de solicitudes.</p>
                    </div>
                    <a class="cl-button cl-button--primary" href="#solicitar">Empezar</a>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <div class="cl-container site-footer__inner">
                <p>© <?= date('Y') ?> CasaListo. Cancún y Riviera Maya.</p>
                <p>Llegamos, lo arreglamos, te olvidas.</p>
            </div>
        </footer>
    </div>

    <script src="js/landing.js"></script>
</body>
</html>
