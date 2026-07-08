<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

startUserSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('auth/registro.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectTo('auth/registro.php?error=csrf');
}

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$apellido = trim((string) ($_POST['apellido'] ?? ''));
$email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
$telefono = trim((string) ($_POST['telefono'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

if ($nombre === '' || $email === '') {
    redirectTo('auth/registro.php?error=datos');
}

if (strlen($password) < 8 || $password !== $passwordConfirm) {
    redirectTo('auth/registro.php?error=password');
}

$pdo = db();

$exists = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
$exists->execute(['email' => $email]);

if ($exists->fetch()) {
    redirectTo('auth/registro.php?error=correo');
}

$role = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'cliente' LIMIT 1");
$role->execute();
$roleId = $role->fetchColumn();

if (!$roleId) {
    redirectTo('auth/registro.php?error=error');
}

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
session_regenerate_id(true);

$_SESSION['user_id'] = $userId;
$_SESSION['user_role'] = 'cliente';
$_SESSION['user_name'] = $nombre;

$log = $pdo->prepare(
    "INSERT INTO bitacora (usuario_id, entidad, entidad_id, accion, descripcion, ip)
     VALUES (:usuario_id, 'usuarios', :entidad_id, 'registro_cliente', 'Cuenta de cliente creada desde registro publico', :ip)"
);
$log->execute([
    'usuario_id' => $userId,
    'entidad_id' => $userId,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
]);

redirectTo('cliente/dashboard.php');
