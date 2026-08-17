<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

start_app_session();

$_SESSION = [];

setcookie(session_name(), '', [
    'expires' => time() - 42000,
    'path' => '/ift542_app',
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_destroy();

header('Location: login.php');
exit;