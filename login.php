<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/rate_limit.php';

start_app_session();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $clientIp = (string) (
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    );

    if (login_is_rate_limited($clientIp)) {
        log_security_event(
            null,
            'authentication',
            'login_rate_limited',
            [
                'resource' => 'login.php',
                'limit' => LOGIN_RATE_LIMIT,
                'window_minutes' => LOGIN_RATE_WINDOW_MINUTES,
            ]
        );

        $error = 'Too many unsuccessful attempts. Try again later.';
    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL)
        || $password === ''
    ) {
        log_security_event(
            null,
            'authentication',
            'login_rejected',
            [
                'resource' => 'login.php',
                'reason' => 'invalid_input',
            ]
        );

        $error = 'Invalid email or password.';
    } else {
        try {
            $statement = db()->prepare(
                'SELECT
                    id,
                    full_name,
                    email,
                    password_hash,
                    role
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );

            $statement->execute([
                ':email' => $email,
            ]);

            $user = $statement->fetch();

            if (
                is_array($user)
                && password_verify(
                    $password,
                    (string) $user['password_hash']
                )
            ) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_name'] = (string) $user['full_name'];
                $_SESSION['user_role'] = (string) $user['role'];

                log_security_event(
                    (int) $user['id'],
                    'authentication',
                    'login_success',
                    [
                        'resource' => 'login.php',
                        'role' => (string) $user['role'],
                    ]
                );

                header('Location: dashboard.php');
                exit;
            }

            log_security_event(
                null,
                'authentication',
                'login_failure',
                [
                    'resource' => 'login.php',
                    'reason' => 'invalid_credentials',
                ]
            );

            $error = 'Invalid email or password.';
        } catch (Throwable $exception) {
            error_log($exception->getMessage());

            log_security_event(
                null,
                'authentication',
                'login_error',
                [
                    'resource' => 'login.php',
                    'reason' => 'internal_error',
                ]
            );

            $error = 'Unable to process the login right now.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IFT542 Login</title>
</head>
<body>
    <h1>Student Registration Application</h1>

    <?php if ($error !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="post" action="login.php">
        <label for="email">Email</label><br>

        <input
            type="email"
            id="email"
            name="email"
            maxlength="190"
            value="<?= htmlspecialchars(
                $email,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            required
        >

        <br><br>

        <label for="password">Password</label><br>

        <input
            type="password"
            id="password"
            name="password"
            required
        >

        <br><br>

        <button type="submit">Log in</button>
    </form>
</body>
</html>