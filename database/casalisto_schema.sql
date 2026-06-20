-- CasaListo - Esquema relacional inicial
-- Importar este archivo desde HeidiSQL o phpMyAdmin.

CREATE DATABASE IF NOT EXISTS casalisto
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE casalisto;

CREATE TABLE IF NOT EXISTS roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE,
  descripcion VARCHAR(255) NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rol_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  apellido VARCHAR(120) NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  telefono VARCHAR(30) NULL,
  password_hash VARCHAR(255) NOT NULL,
  estado ENUM('activo', 'inactivo', 'suspendido') NOT NULL DEFAULT 'activo',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_usuarios_rol (rol_id),
  CONSTRAINT fk_usuarios_roles
    FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorias (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL UNIQUE,
  slug VARCHAR(120) NOT NULL UNIQUE,
  descripcion TEXT NULL,
  icono VARCHAR(80) NULL,
  estado ENUM('activa', 'inactiva') NOT NULL DEFAULT 'activa',
  orden INT UNSIGNED NOT NULL DEFAULT 0,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS servicios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(140) NOT NULL,
  slug VARCHAR(160) NOT NULL UNIQUE,
  descripcion TEXT NULL,
  precio_base DECIMAL(10,2) NULL,
  requiere_cotizacion TINYINT(1) NOT NULL DEFAULT 1,
  destacado TINYINT(1) NOT NULL DEFAULT 0,
  estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_servicios_categoria (categoria_id),
  CONSTRAINT fk_servicios_categorias
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tecnicos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL UNIQUE,
  nombre_comercial VARCHAR(140) NULL,
  bio TEXT NULL,
  zona VARCHAR(120) NULL,
  experiencia_anios TINYINT UNSIGNED NULL,
  verificado TINYINT(1) NOT NULL DEFAULT 0,
  disponible TINYINT(1) NOT NULL DEFAULT 1,
  calificacion_promedio DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tecnicos_usuarios
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tecnicos_servicios (
  tecnico_id INT UNSIGNED NOT NULL,
  servicio_id INT UNSIGNED NOT NULL,
  precio_referencia DECIMAL(10,2) NULL,
  PRIMARY KEY (tecnico_id, servicio_id),
  CONSTRAINT fk_tecnicos_servicios_tecnicos
    FOREIGN KEY (tecnico_id) REFERENCES tecnicos(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_tecnicos_servicios_servicios
    FOREIGN KEY (servicio_id) REFERENCES servicios(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS solicitudes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT UNSIGNED NOT NULL,
  servicio_id INT UNSIGNED NOT NULL,
  folio VARCHAR(30) NOT NULL UNIQUE,
  titulo VARCHAR(160) NOT NULL,
  descripcion TEXT NOT NULL,
  direccion VARCHAR(255) NOT NULL,
  colonia VARCHAR(120) NULL,
  ciudad VARCHAR(120) NOT NULL DEFAULT 'Cancun',
  estado_region VARCHAR(120) NOT NULL DEFAULT 'Quintana Roo',
  codigo_postal VARCHAR(20) NULL,
  fecha_preferida DATE NULL,
  hora_preferida TIME NULL,
  urgencia ENUM('normal', 'alta', 'emergencia') NOT NULL DEFAULT 'normal',
  estado ENUM('nueva', 'en_revision', 'cotizada', 'aprobada', 'asignada', 'en_proceso', 'completada', 'cancelada') NOT NULL DEFAULT 'nueva',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_solicitudes_cliente (cliente_id),
  KEY idx_solicitudes_servicio (servicio_id),
  KEY idx_solicitudes_estado (estado),
  CONSTRAINT fk_solicitudes_clientes
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
  CONSTRAINT fk_solicitudes_servicios
    FOREIGN KEY (servicio_id) REFERENCES servicios(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS solicitud_archivos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  solicitud_id INT UNSIGNED NOT NULL,
  ruta_archivo VARCHAR(255) NOT NULL,
  tipo_archivo VARCHAR(80) NULL,
  descripcion VARCHAR(180) NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_solicitud_archivos_solicitudes
    FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cotizaciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  solicitud_id INT UNSIGNED NOT NULL,
  tecnico_id INT UNSIGNED NULL,
  folio VARCHAR(30) NOT NULL UNIQUE,
  descripcion_trabajo TEXT NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  impuestos DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  descuento DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  vigencia DATE NULL,
  estado ENUM('borrador', 'enviada', 'aceptada', 'rechazada', 'vencida') NOT NULL DEFAULT 'borrador',
  notas TEXT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_cotizaciones_solicitud (solicitud_id),
  CONSTRAINT fk_cotizaciones_solicitudes
    FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_cotizaciones_tecnicos
    FOREIGN KEY (tecnico_id) REFERENCES tecnicos(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS asignaciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  solicitud_id INT UNSIGNED NOT NULL,
  tecnico_id INT UNSIGNED NOT NULL,
  asignado_por INT UNSIGNED NOT NULL,
  fecha_programada DATETIME NULL,
  fecha_inicio DATETIME NULL,
  fecha_fin DATETIME NULL,
  estado ENUM('pendiente', 'aceptada', 'rechazada', 'en_camino', 'en_proceso', 'completada', 'cancelada') NOT NULL DEFAULT 'pendiente',
  notas TEXT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_asignaciones_tecnico (tecnico_id),
  CONSTRAINT fk_asignaciones_solicitudes
    FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_asignaciones_tecnicos
    FOREIGN KEY (tecnico_id) REFERENCES tecnicos(id),
  CONSTRAINT fk_asignaciones_admin
    FOREIGN KEY (asignado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pagos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  solicitud_id INT UNSIGNED NOT NULL,
  cotizacion_id INT UNSIGNED NULL,
  cliente_id INT UNSIGNED NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  metodo ENUM('efectivo', 'transferencia', 'tarjeta', 'link_pago') NOT NULL,
  referencia VARCHAR(120) NULL,
  estado ENUM('pendiente', 'pagado', 'fallido', 'reembolsado', 'cancelado') NOT NULL DEFAULT 'pendiente',
  pagado_en DATETIME NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_pagos_estado (estado),
  CONSTRAINT fk_pagos_solicitudes
    FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id),
  CONSTRAINT fk_pagos_cotizaciones
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id),
  CONSTRAINT fk_pagos_clientes
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS calificaciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  solicitud_id INT UNSIGNED NOT NULL UNIQUE,
  cliente_id INT UNSIGNED NOT NULL,
  tecnico_id INT UNSIGNED NOT NULL,
  puntuacion TINYINT UNSIGNED NOT NULL,
  comentario TEXT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_calificaciones_puntuacion
    CHECK (puntuacion BETWEEN 1 AND 5),
  CONSTRAINT fk_calificaciones_solicitudes
    FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_calificaciones_clientes
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
  CONSTRAINT fk_calificaciones_tecnicos
    FOREIGN KEY (tecnico_id) REFERENCES tecnicos(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notificaciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  titulo VARCHAR(160) NOT NULL,
  mensaje TEXT NOT NULL,
  tipo ENUM('sistema', 'solicitud', 'cotizacion', 'pago', 'asignacion') NOT NULL DEFAULT 'sistema',
  leida TINYINT(1) NOT NULL DEFAULT 0,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notificaciones_usuarios
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS paginas_contenido (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(80) NOT NULL UNIQUE,
  titulo VARCHAR(160) NOT NULL,
  contenido MEDIUMTEXT NULL,
  imagen_url VARCHAR(255) NULL,
  estado ENUM('publicado', 'borrador') NOT NULL DEFAULT 'publicado',
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bitacora (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  entidad VARCHAR(80) NOT NULL,
  entidad_id INT UNSIGNED NULL,
  accion VARCHAR(80) NOT NULL,
  descripcion TEXT NULL,
  ip VARCHAR(45) NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_bitacora_entidad (entidad, entidad_id),
  CONSTRAINT fk_bitacora_usuarios
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO roles (nombre, descripcion) VALUES
('administrador', 'Gestiona plataforma, solicitudes, tecnicos, pagos y reportes'),
('cliente', 'Solicita servicios, revisa cotizaciones y califica trabajos'),
('tecnico', 'Recibe asignaciones, atiende servicios y reporta avances');

INSERT IGNORE INTO categorias (nombre, slug, descripcion, icono, orden) VALUES
('Plomeria', 'plomeria', 'Reparaciones e instalaciones hidraulicas para casa y departamentos.', 'wrench', 10),
('Electricidad', 'electricidad', 'Instalaciones, fallas electricas, contactos, lamparas y tableros.', 'zap', 20),
('Jardineria', 'jardineria', 'Mantenimiento y mejora de jardines residenciales.', 'leaf', 30),
('Albercas', 'albercas', 'Limpieza, mantenimiento y revision de albercas.', 'waves', 40),
('Pintura', 'pintura', 'Pintura interior, exterior y retoques residenciales.', 'paint-roller', 50),
('Aire acondicionado', 'aire-acondicionado', 'Instalacion, mantenimiento y reparacion de minisplits y aires.', 'snowflake', 60),
('Remodelaciones', 'remodelaciones', 'Mejoras, reparaciones mayores y proyectos residenciales.', 'hammer', 70),
('Camaras de seguridad', 'camaras-seguridad', 'Instalacion y mantenimiento de camaras para casa o negocio.', 'camera', 80),
('Airbnb', 'airbnb', 'Mantenimiento operativo para propiedades de renta vacacional.', 'home', 90),
('Emergencias', 'emergencias', 'Atencion prioritaria para problemas urgentes del hogar.', 'alarm-clock', 100);

INSERT IGNORE INTO servicios (categoria_id, nombre, slug, descripcion, requiere_cotizacion, destacado) VALUES
((SELECT id FROM categorias WHERE slug = 'plomeria'), 'Reparacion de fugas', 'reparacion-fugas', 'Revision y reparacion de fugas visibles o reportadas.', 1, 1),
((SELECT id FROM categorias WHERE slug = 'electricidad'), 'Revision electrica residencial', 'revision-electrica-residencial', 'Diagnostico de fallas electricas en vivienda.', 1, 1),
((SELECT id FROM categorias WHERE slug = 'jardineria'), 'Mantenimiento de jardin', 'mantenimiento-jardin', 'Poda, limpieza y mantenimiento general de areas verdes.', 1, 1),
((SELECT id FROM categorias WHERE slug = 'albercas'), 'Mantenimiento de alberca', 'mantenimiento-alberca', 'Limpieza, revision de equipo y tratamiento basico.', 1, 1),
((SELECT id FROM categorias WHERE slug = 'pintura'), 'Pintura residencial', 'pintura-residencial', 'Pintura interior o exterior segun alcance del proyecto.', 1, 0),
((SELECT id FROM categorias WHERE slug = 'aire-acondicionado'), 'Mantenimiento de aire acondicionado', 'mantenimiento-aire-acondicionado', 'Servicio preventivo y correctivo para equipos de aire acondicionado.', 1, 1),
((SELECT id FROM categorias WHERE slug = 'remodelaciones'), 'Remodelacion residencial', 'remodelacion-residencial', 'Cotizacion y ejecucion de mejoras residenciales.', 1, 0),
((SELECT id FROM categorias WHERE slug = 'camaras-seguridad'), 'Instalacion de camaras', 'instalacion-camaras', 'Instalacion y configuracion de camaras de seguridad.', 1, 0),
((SELECT id FROM categorias WHERE slug = 'airbnb'), 'Mantenimiento para Airbnb', 'mantenimiento-airbnb', 'Atencion de mantenimiento para propiedades de renta vacacional.', 1, 1),
((SELECT id FROM categorias WHERE slug = 'emergencias'), 'Servicio de emergencia', 'servicio-emergencia', 'Atencion prioritaria para incidencias urgentes.', 1, 1);

INSERT IGNORE INTO paginas_contenido (clave, titulo, contenido) VALUES
('hero_titulo', 'CasaListo', 'Servicios confiables para el hogar en Cancun y Riviera Maya.'),
('hero_subtitulo', 'Mantenimiento, reparaciones y emergencias desde una sola plataforma.', 'Conecta con tecnicos verificados, solicita cotizaciones y da seguimiento a cada trabajo.'),
('cta_final', 'Tu casa lista, sin vueltas', 'Solicita un servicio y recibe atencion ordenada desde CasaListo.');
