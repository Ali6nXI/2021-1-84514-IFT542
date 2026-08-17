<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$connection = db();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($submittedToken)) {
        $error = 'The request could not be verified.';
    } elseif (($_POST['action'] ?? '') !== 'add_course') {
        $error = 'Invalid action.';
    } else {
        $courseCode = strtoupper(
            trim((string) ($_POST['course_code'] ?? ''))
        );

        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));

        $capacity = filter_var(
            $_POST['capacity'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!preg_match('/^[A-Z0-9-]{2,20}$/', $courseCode)) {
            $error = 'Enter a valid course code.';
        } elseif (strlen($title) < 2 || strlen($title) > 150) {
            $error = 'Enter a valid course title.';
        } elseif (
            $capacity === false
            || $capacity < 1
            || $capacity > 500
        ) {
            $error = 'Capacity must be between 1 and 500.';
        } else {
            try {
                $insert = $connection->prepare(
                    'INSERT INTO courses
                        (course_code, title, description, capacity)
                     VALUES
                        (:course_code, :title, :description, :capacity)'
                );

                $insert->execute([
                    ':course_code' => $courseCode,
                    ':title' => $title,
                    ':description' => $description,
                    ':capacity' => $capacity,
                ]);

                $message = 'Course added successfully.';
            } catch (PDOException $exception) {
                error_log($exception->getMessage());

                if ($exception->getCode() === '23000') {
                    $error = 'That course code already exists.';
                } else {
                    $error = 'The course could not be added.';
                }
            }
        }
    }
}

$courseStatement = $connection->query(
    'SELECT
        c.id,
        c.course_code,
        c.title,
        c.description,
        c.capacity,
        COUNT(e.id) AS registered_count
     FROM courses c
     LEFT JOIN enrolments e
        ON e.course_id = c.id
       AND e.status = "registered"
     GROUP BY
        c.id,
        c.course_code,
        c.title,
        c.description,
        c.capacity
     ORDER BY c.course_code'
);

$courses = $courseStatement->fetchAll();

$enrolmentStatement = $connection->query(
    'SELECT
        e.registered_at,
        u.student_number,
        u.full_name,
        u.email,
        c.course_code,
        c.title
     FROM enrolments e
     INNER JOIN users u ON u.id = e.student_id
     INNER JOIN courses c ON c.id = e.course_id
     WHERE e.status = "registered"
     ORDER BY e.registered_at DESC'
);

$enrolments = $enrolmentStatement->fetchAll();

function html_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Administrator Dashboard</title>
</head>
<body>
    <h1>Administrator Dashboard</h1>

    <?php if ($message !== ''): ?>
        <p role="status"><?= html_escape($message) ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p role="alert"><?= html_escape($error) ?></p>
    <?php endif; ?>

    <h2>Add Course</h2>

    <form method="post" action="index.php">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= html_escape(csrf_token()) ?>"
        >

        <input type="hidden" name="action" value="add_course">

        <label for="course_code">Course code</label><br>
        <input
            type="text"
            id="course_code"
            name="course_code"
            maxlength="20"
            placeholder="IFT530"
            required
        >

        <br><br>

        <label for="title">Course title</label><br>
        <input
            type="text"
            id="title"
            name="title"
            maxlength="150"
            required
        >

        <br><br>

        <label for="description">Description</label><br>
        <textarea
            id="description"
            name="description"
            rows="3"
            cols="50"
        ></textarea>

        <br><br>

        <label for="capacity">Capacity</label><br>
        <input
            type="number"
            id="capacity"
            name="capacity"
            min="1"
            max="500"
            required
        >

        <br><br>

        <button type="submit">Add course</button>
    </form>

    <h2>Courses</h2>

    <table border="1" cellpadding="6">
        <tr>
            <th>Code</th>
            <th>Title</th>
            <th>Capacity</th>
            <th>Registered</th>
        </tr>

        <?php foreach ($courses as $course): ?>
            <tr>
                <td><?= html_escape((string) $course['course_code']) ?></td>
                <td><?= html_escape((string) $course['title']) ?></td>
                <td><?= (int) $course['capacity'] ?></td>
                <td><?= (int) $course['registered_count'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Registered Students</h2>

    <table border="1" cellpadding="6">
        <tr>
            <th>Student number</th>
            <th>Name</th>
            <th>Email</th>
            <th>Course</th>
            <th>Registered at</th>
        </tr>

        <?php foreach ($enrolments as $enrolment): ?>
            <tr>
                <td>
                    <?= html_escape((string) $enrolment['student_number']) ?>
                </td>
                <td>
                    <?= html_escape((string) $enrolment['full_name']) ?>
                </td>
                <td>
                    <?= html_escape((string) $enrolment['email']) ?>
                </td>
                <td>
                    <?= html_escape((string) $enrolment['course_code']) ?>
                    —
                    <?= html_escape((string) $enrolment['title']) ?>
                </td>
                <td>
                    <?= html_escape((string) $enrolment['registered_at']) ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <p>
        <a href="../dashboard.php">Dashboard</a>
        |
        <a href="../logout.php">Log out</a>
    </p>
</body>
</html>