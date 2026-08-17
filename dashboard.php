<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

require_login();

$name = htmlspecialchars(
    (string) $_SESSION['user_name'],
    ENT_QUOTES,
    'UTF-8'
);

$role = htmlspecialchars(
    (string) $_SESSION['user_role'],
    ENT_QUOTES,
    'UTF-8'
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <h1>Welcome, <?= $name ?></h1>

    <p>Your role is: <?= $role ?></p>

    <p>
        You are successfully logged in to the IFT542 application.
    </p>

    <p>
        <a href="logout.php">Log out</a>
    </p>
</body>
</html>