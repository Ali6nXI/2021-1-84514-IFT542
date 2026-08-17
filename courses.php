<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

require_student();

$connection = db();
$studentId = (int) $_SESSION['user_id'];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($submittedToken)) {
        $error = 'The request could not be verified.';
    } else {
        $courseId = filter_var(
            $_POST['course_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($courseId === false || $courseId < 1) {
            $error = 'Invalid course selected.';
        } else {
            try {
                $connection->beginTransaction();

                $courseStatement = $connection->prepare(
                    'SELECT id, capacity
                     FROM courses
                     WHERE id = :id
                     FOR UPDATE'
                );

                $courseStatement->execute([
                    ':id' => $courseId,
                ]);

                $course = $courseStatement->fetch();

                if (!is_array($course)) {
                    throw new RuntimeException('Course was not found.');
                }

                $existingStatement = $connection->prepare(
                    'SELECT id, status
                     FROM enrolments
                     WHERE student_id = :student_id
                       AND course_id = :course_id
                     LIMIT 1'
                );

                $existingStatement->execute([
                    ':student_id' => $studentId,
                    ':course_id' => $courseId,
                ]);

                $existing = $existingStatement->fetch();

                if (
                    is_array($existing)
                    && $existing['status'] === 'registered'
                ) {
                    $connection->rollBack();
                    $error = 'You are already registered for this course.';
                } else {
                    $countStatement = $connection->prepare(
                        'SELECT COUNT(*)
                         FROM enrolments
                         WHERE course_id = :course_id
                           AND status = "registered"'
                    );

                    $countStatement->execute([
                        ':course_id' => $courseId,
                    ]);

                    $registeredCount = (int) $countStatement->fetchColumn();
                    $capacity = (int) $course['capacity'];

                    if ($registeredCount >= $capacity) {
                        $connection->rollBack();
                        $error = 'This course is already full.';
                    } elseif (is_array($existing)) {
                        $update = $connection->prepare(
                            'UPDATE enrolments
                             SET status = "registered",
                                 registered_at = CURRENT_TIMESTAMP
                             WHERE id = :id'
                        );

                        $update->execute([
                            ':id' => (int) $existing['id'],
                        ]);

                        $connection->commit();
                        $message = 'Course registration successful.';
                    } else {
                        $insert = $connection->prepare(
                            'INSERT INTO enrolments
                                (student_id, course_id, status)
                             VALUES
                                (:student_id, :course_id, "registered")'
                        );

                        $insert->execute([
                            ':student_id' => $studentId,
                            ':course_id' => $courseId,
                        ]);

                        $connection->commit();
                        $message = 'Course registration successful.';
                    }
                }
            } catch (Throwable $exception) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }

                error_log($exception->getMessage());
                $error = 'The registration could not be completed.';
            }
        }
    }
}

$listStatement = $connection->prepare(
    'SELECT
        c.id,
        c.course_code,
        c.title,
        c.description,
        c.capacity,
        (
            SELECT COUNT(*)
            FROM enrolments e
            WHERE e.course_id = c.id
              AND e.status = "registered"
        ) AS registered_count,
        EXISTS (
            SELECT 1
            FROM enrolments mine
            WHERE mine.course_id = c.id
              AND mine.student_id = :student_id
              AND mine.status = "registered"
        ) AS already_registered
     FROM courses c
     ORDER BY c.course_code'
);

$listStatement->execute([
    ':student_id' => $studentId,
]);

$courses = $listStatement->fetchAll();

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Registration</title>
</head>
<body>
    <h1>Course Registration</h1>

    <?php if ($message !== ''): ?>
        <p role="status"><?= escape_html($message) ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p role="alert"><?= escape_html($error) ?></p>
    <?php endif; ?>

    <?php foreach ($courses as $course): ?>
        <?php
        $registeredCount = (int) $course['registered_count'];
        $capacity = (int) $course['capacity'];
        $alreadyRegistered = (bool) $course['already_registered'];
        ?>

        <article>
            <h2>
                <?= escape_html((string) $course['course_code']) ?>
                —
                <?= escape_html((string) $course['title']) ?>
            </h2>

            <p>
                <?= escape_html((string) $course['description']) ?>
            </p>

            <p>
                Places used:
                <?= $registeredCount ?> / <?= $capacity ?>
            </p>

            <?php if ($alreadyRegistered): ?>
                <p>You are already registered.</p>
            <?php elseif ($registeredCount >= $capacity): ?>
                <p>This course is full.</p>
            <?php else: ?>
                <form method="post" action="courses.php">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= escape_html(csrf_token()) ?>"
                    >

                    <input
                        type="hidden"
                        name="course_id"
                        value="<?= (int) $course['id'] ?>"
                    >

                    <button type="submit">Register</button>
                </form>
            <?php endif; ?>
        </article>

        <hr>
    <?php endforeach; ?>

    <p>
        <a href="profile.php">My profile</a>
        |
        <a href="dashboard.php">Dashboard</a>
        |
        <a href="logout.php">Log out</a>
    </p>
</body>
</html>