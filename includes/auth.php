<?php
declare(strict_types=1);

require_once __DIR__ . '/logger.php';

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/ift542_app',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function require_login(): void
{
    start_app_session();

    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function require_student(): void
{
    require_login();

    if (($_SESSION['user_role'] ?? '') !== 'student') {
        log_security_event(
            (int) $_SESSION['user_id'],
            'authorization',
            'access_denied',
            [
                'resource' => basename(
                    $_SERVER['SCRIPT_NAME'] ?? 'unknown'
                ),
                'required_role' => 'student',
                'actual_role' => $_SESSION['user_role'] ?? 'unknown',
            ]
        );

        http_response_code(403);
        exit('Access denied.');
    }
}

function require_admin(): void
{
    require_login();

    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        log_security_event(
            (int) $_SESSION['user_id'],
            'authorization',
            'access_denied',
            [
                'resource' => basename(
                    $_SERVER['SCRIPT_NAME'] ?? 'unknown'
                ),
                'required_role' => 'admin',
                'actual_role' => $_SESSION['user_role'] ?? 'unknown',
            ]
        );

        http_response_code(403);
        exit('Access denied.');
    }
}

function csrf_token(): string
{
    start_app_session();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function verify_csrf_token(string $token): bool
{
    start_app_session();

    return isset($_SESSION['csrf_token'])
        && $token !== ''
        && hash_equals(
            (string) $_SESSION['csrf_token'],
            $token
        );
}