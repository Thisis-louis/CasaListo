<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$admin = requireAuth(['administrador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('admin/crear-usuario.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectTo('admin/crear-usuario.php?error=csrf');
}

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$apellido = trim((string) ($_POST['apellido'] ?? ''));
$email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
$telefono = trim((string) ($_POST['telefono'] ?? ''));
$rol = trim((string) ($_POST['rol'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$nombreComercial = trim((string) ($_POST['nombre_comercial'] ?? ''));
$zona = trim((string) ($_POST['zona'] ?? ''));
$bio = trim((string) ($_POST['bio'] ?? ''));

if ($nombre === '' || $email === '' || $rol === '') {
    redirectTo('admin/crear-usuario.php?error=datos');
}

if (strlen($password) < 8) {
    redirectTo('admin/crear-usuario.php?error=password');
}

$pdo = db();

$roleStmt = $pdo->prepare('SELECT id FROM roles WHERE nombre = :rol LIMIT 1');
$roleStmt->execute(['rol' => $rol]);
$roleId = $roleStmt->fetchColumn();

if (!$roleId) {
    redirectTo('admin/crear-usuario.php?error=rol');
}

$exists = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
$exists->execute(['email' => $email]);

if ($exists->fetch()) {
    redirectTo('admin/crear-usuario.php?error=correo');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (rol_id, nombre, apellido, email, telefono, password_hash, estado)
         VALUES (:rol_id, :nombre, :apellido, :email, :telefono, :password_hash, "activo")'
    );
    $stmt->execute([
        'rol_id' => $roleId,
        'nombre' => $nombre,
        'apellido' => $apellido !== '' ? $apellido : null,
        'email' => $email,
        'telefono' => $telefono !== '' ? $telefono : null,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    $userId = (int) $pdo->lastInsertId();

    if ($rol === 'tecnico') {
        $techStmt = $pdo->prepare(
            'INSERT INTO tecnicos (usuario_id, nombre_comercial, bio, zona, verificado, disponible)
             VALUES (:usuario_id, :nombre_comercial, :bio, :zona, 0, 1)'
        );
        $techStmt->execute([
            'usuario_id' => $userId,
            'nombre_comercial' => $nombreComercial !== '' ? $nombreComercial : null,
            'bio' => $bio !== '' ? $bio : null,
            'zona' => $zona !== '' ? $zona : null,
        ]);
    }

    $log = $pdo->prepare(
        "INSERT INTO bitacora (usuario_id, entidad, entidad_id, accion, descripcion, ip)
         VALUES (:usuario_id, 'usuarios', :entidad_id, 'crear_usuario', :descripcion, :ip)"
    );
    $log->execute([
        'usuario_id' => $admin['id'],
        'entidad_id' => $userId,
        'descripcion' => 'Usuario creado desde panel administrativo con rol ' . $rol,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirectTo('admin/crear-usuario.php?error=error');
}

redirectTo('admin/crear-usuario.php?status=creado');
