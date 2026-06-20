# CasaListo

CasaListo es una plataforma local de servicios para el hogar en Cancun y Riviera Maya.

## Identidad visual

El logo principal del proyecto esta en:

```text
assets/img/logo-casalisto.png
```

La hoja base de estilos de marca esta en:

```text
assets/css/casalisto-theme.css
```

Colores principales tomados del logo:

- Azul CasaListo: `#003060`
- Azul profundo: `#002858`
- Naranja CasaListo: `#F86010`
- Naranja activo: `#FF6A1A`
- Fondo claro: `#F4F7FB`
- Blanco: `#FFFFFF`

Los iconos de servicios deben guardarse en:

```text
assets/icons/services/
```

La pagina debe usar estos colores en navegacion, botones, tarjetas, formularios, estados y secciones principales para mantener consistencia con el logo.

## Base de datos

El esquema relacional inicial esta en:

```text
database/casalisto_schema.sql
```

Incluye tablas para:

- roles
- usuarios
- categorias
- servicios
- tecnicos
- relacion tecnico-servicio
- solicitudes
- archivos de solicitudes
- cotizaciones
- asignaciones
- pagos
- calificaciones
- notificaciones
- contenido editable de pagina
- bitacora

El archivo es no destructivo: usa `CREATE TABLE IF NOT EXISTS` e `INSERT IGNORE`, por lo que no borra informacion existente.

## Importar en HeidiSQL

1. Inicia MySQL/MariaDB desde XAMPP.
2. Abre HeidiSQL.
3. Crea o abre una conexion local con estos datos habituales:
   - Host: `127.0.0.1`
   - Puerto: `3306`
   - Usuario: `root`
   - Contrasena: vacia, salvo que hayas configurado una
4. Abre el archivo `database/casalisto_schema.sql`.
5. Ejecuta el script completo.
6. Verifica que exista la base de datos `casalisto`.

## Conexion PHP

La plantilla de configuracion esta en:

```text
php/config/database.example.php
```

Para usarla:

1. Copia `php/config/database.example.php` como `php/config/database.php`.
2. Ajusta host, puerto, usuario y contrasena si tu MySQL no usa los valores por defecto de XAMPP.
3. Usa `php/config/connection.php` desde las paginas PHP para obtener una conexion PDO.

## Datos que necesito si quieres que Codex importe la base

Si quieres que yo conecte e importe la base desde aqui, necesito:

- Host de MySQL
- Puerto
- Usuario
- Contrasena
- Confirmacion de que MySQL esta iniciado en XAMPP

Si es XAMPP local por defecto, normalmente basta con iniciar MySQL y usar `root` sin contrasena.

## Login

La pantalla de acceso esta en:

```text
auth/login.php
```

La validacion de credenciales esta en:

```text
php/auth/login.php
```

Usuario administrador inicial para desarrollo local:

- Correo: `admin@casalisto.local`
- Contrasena: `CasaListo2026!`

Este usuario debe cambiarse antes de usar el sistema en produccion.
