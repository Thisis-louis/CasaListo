# CasaListo

CasaListo es una plataforma local de servicios para el hogar en Cancun y Riviera Maya.

## Identidad visual

El logo principal del proyecto esta en:

```text
assets/img/Logo.png
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

Los accesos administrativos no deben mostrarse en pantallas publicas. Si se usa un usuario inicial en desarrollo local, debe cambiarse antes de trabajar con datos reales.

## Registro de usuarios

Registro visible para clientes:

```text
auth/registro.php
php/auth/registro_cliente.php
```

Este flujo siempre crea usuarios con rol `cliente` y los manda al panel de cliente.

Creacion interna de usuarios:

```text
admin/crear-usuario.php
php/auth/crear_usuario.php
```

Esta vista requiere sesion de `administrador`. Desde ahi se crean tecnicos, administradores u otros roles. Si el rol elegido es `tecnico`, tambien se crea el registro relacionado en la tabla `tecnicos`.

## Modulos por tabla

Cada tabla principal de la base de datos tiene una carpeta con su `index.html`:

```text
roles/
usuarios/
categorias/
servicios/
tecnicos/
tecnicos_servicios/
solicitudes/
solicitud_archivos/
cotizaciones/
asignaciones/
pagos/
calificaciones/
notificaciones/
paginas_contenido/
bitacora/
```

Cada modulo consume un endpoint PHP con el mismo nombre de la tabla:

```text
php/roles.php
php/usuarios.php
php/categorias.php
...
```

La conexion y funciones reutilizables estan centralizadas en:

```text
php/db.php
php/functions.php
```

La tabla visual de cada modulo se renderiza con:

```text
js/module-table.js
assets/css/modules.css
```

Todos los modulos incluyen un filtro de busqueda. El filtro envia el parametro `q` al endpoint PHP correspondiente y la busqueda se ejecuta en SQL desde `php/functions.php`, no solamente en JavaScript.

Todos los modulos incluyen ordenamiento por SQL. Cada endpoint acepta:

```text
sort=nombre_columna
dir=asc|desc
```

Cada modulo expone al menos tres columnas coherentes para ordenar en formato ascendente o descendente. La lista de columnas permitidas vive en `php/functions.php`.

## Dashboards por rol

Los dashboards principales de administrador, cliente y tecnico cargan indicadores desde SQL al iniciar sesion:

```text
admin/dashboard.php
cliente/dashboard.php
tecnico/dashboard.php
php/dashboard/admin.php
php/dashboard/cliente.php
php/dashboard/tecnico.php
php/dashboard/functions.php
js/dashboard.js
```

Cada dashboard permite consultar historial por:

- Dia
- Semana
- Mes
- Año

El selector envia `period=day|week|month|year` al endpoint correspondiente. Las metricas y graficas se calculan con consultas SQL sobre solicitudes, asignaciones, pagos, categorias, servicios y calificaciones.