<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$connection = db();
$userId = (int) $_SESSION['user_id'];

$message = '';
$error = '';

$statement = $connection->prepare(
    'SELECT student_number, full_name, email
     FROM users
     WHERE id = :id
     LIMIT 1'
);

$statement->execute([
    ':id' => $userId,
]);

$profile = $statement->fetch();

if (!is_array($profile)) {
    http_response_code(404);
    exit('Profile not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($submittedToken)) {
        $error = 'The form could not be verified. Please try again.';
    } else {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        $profile['full_name'] = $fullName;
        $profile['email'] = $email;

        if (strlen($fullName) < 2 || strlen($fullName) > 120) {
            $error = 'Enter a name between 2 and 120 characters.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } else {
            try {
                $update = $connection->prepare(
                    'UPDATE users
                     SET full_name = :full_name,
                         email = :email
                     WHERE id = :id'
                );

                $update->execute([
                    ':full_name' => $fullName,
                    ':email' => $email,
                    ':id' => $userId,
                ]);

                $_SESSION['user_name'] = $fullName;
                $message = 'Profile updated successfully.';
            } catch (PDOException $exception) {
                error_log($exception->getMessage());

                if ($exception->getCode() === '23000') {
                    $error = 'That email address is already in use.';
                } else {
                    $error = 'The profile could not be updated.';
                }
            }
        }
    }
}

function escape_value(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
</head>
<body>
    <h1>My Profile</h1>

    <?php if ($message !== ''): ?>
        <p role="status"><?= escape_value($message) ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p role="alert"><?= escape_value($error) ?></p>
    <?php endif; ?>

    <form method="post" action="profile.php">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= escape_value(csrf_token()) ?>"
        >

        <label for="student_number">Student number</label><br>
        <input
            type="text"
            id="student_number"
            value="<?= escape_value((string) $profile['student_number']) ?>"
            readonly
        >

        <br><br>

        <label for="full_name">Full name</label><br>
        <input
            type="text"
            id="full_name"
            name="full_name"
            maxlength="120"
            value="<?= escape_value((string) $profile['full_name']) ?>"
            required
        >

        <br><br>

        <label for="email">Email</label><br>
        <input
            type="email"
            id="email"
            name="email"
            maxlength="190"
            value="<?= escape_value((string) $profile['email']) ?>"
            required
        >

        <br><br>

        <button type="submit">Save changes</button>
    </form>

    <p>
        <a href="dashboard.php">Back to dashboard</a>
        |
        <a href="logout.php">Log out</a>
    </p>
</body>
</html>