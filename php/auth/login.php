<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

startUserSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('auth/login.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectTo('auth/login.php?error=csrf');
}

$email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    redirectTo('auth/login.php?error=credenciales');
}

$stmt = db()->prepare(
    "SELECT u.id, u.nombre, u.email, u.password_hash, u.estado, r.nombre AS rol
     FROM usuarios u
     INNER JOIN roles r ON r.id = u.rol_id
     WHERE u.email = :email
     LIMIT 1"
);
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    redirectTo('auth/login.php?error=credenciales');
}

if ($user['estado'] !== 'activo') {
    redirectTo('auth/login.php?error=inactivo');
}

session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['user_role'] = $user['rol'];
$_SESSION['user_name'] = $user['nombre'];

$log = db()->prepare(
    "INSERT INTO bitacora (usuario_id, entidad, entidad_id, accion, descripcion, ip)
     VALUES (:usuario_id, 'usuarios', :entidad_id, 'login', 'Inicio de sesion exitoso', :ip)"
);
$log->execute([
    'usuario_id' => $user['id'],
    'entidad_id' => $user['id'],
    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
]);

redirectTo(roleHome($user['rol']));
