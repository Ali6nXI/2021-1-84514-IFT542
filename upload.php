<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$connection = db();
$userId = (int) $_SESSION['user_id'];
$storageDirectory = 'C:\\xampp\\ift542_storage';

$message = '';
$error = '';

if (!is_dir($storageDirectory)) {
    mkdir($storageDirectory, 0700, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($submittedToken)) {
        $error = 'The request could not be verified.';
    } elseif (!isset($_FILES['document'])) {
        $error = 'Please select a document.';
    } else {
        $file = $_FILES['document'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'The upload could not be completed.';
        } elseif ((int) $file['size'] > 5 * 1024 * 1024) {
            $error = 'The document must not exceed 5 MB.';
        } elseif (!is_uploaded_file($file['tmp_name'])) {
            $error = 'Invalid upload.';
        } else {
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = $fileInfo
                ? finfo_file($fileInfo, $file['tmp_name'])
                : false;

            if ($fileInfo) {
                finfo_close($fileInfo);
            }

            $allowedTypes = [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
            ];

            if (
                $mimeType === false
                || !isset($allowedTypes[$mimeType])
            ) {
                $error = 'Only PDF, JPG, and PNG files are allowed.';
            } else {
                $storedName = bin2hex(random_bytes(16))
                    . '.'
                    . $allowedTypes[$mimeType];

                $destination = $storageDirectory
                    . DIRECTORY_SEPARATOR
                    . $storedName;

                if (!move_uploaded_file(
                    $file['tmp_name'],
                    $destination
                )) {
                    $error = 'The document could not be saved.';
                } else {
                    try {
                        $insert = $connection->prepare(
                            'INSERT INTO documents
                                (
                                    user_id,
                                    original_name,
                                    stored_name,
                                    mime_type,
                                    size_bytes
                                )
                             VALUES
                                (
                                    :user_id,
                                    :original_name,
                                    :stored_name,
                                    :mime_type,
                                    :size_bytes
                                )'
                        );

                        $insert->execute([
                            ':user_id' => $userId,
                            ':original_name' => $file['name'],
                            ':stored_name' => $storedName,
                            ':mime_type' => $mimeType,
                            ':size_bytes' => (int) $file['size'],
                        ]);

                        $message = 'Document uploaded successfully.';
                    } catch (Throwable $exception) {
                        unlink($destination);
                        error_log($exception->getMessage());
                        $error = 'The document could not be recorded.';
                    }
                }
            }
        }
    }
}

$list = $connection->prepare(
    'SELECT original_name, mime_type, size_bytes, uploaded_at
     FROM documents
     WHERE user_id = :user_id
     ORDER BY uploaded_at DESC'
);

$list->execute([
    ':user_id' => $userId,
]);

$documents = $list->fetchAll();

function escape_upload_value(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document Upload</title>
</head>
<body>
    <h1>Document Upload</h1>

    <?php if ($message !== ''): ?>
        <p role="status">
            <?= escape_upload_value($message) ?>
        </p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p role="alert">
            <?= escape_upload_value($error) ?>
        </p>
    <?php endif; ?>

    <p>Allowed files: PDF, JPG, and PNG. Maximum size: 5 MB.</p>

    <form
        method="post"
        action="upload.php"
        enctype="multipart/form-data"
    >
        <input
            type="hidden"
            name="csrf_token"
            value="<?= escape_upload_value(csrf_token()) ?>"
        >

        <label for="document">Choose a document</label><br>
        <input
            type="file"
            id="document"
            name="document"
            accept=".pdf,.jpg,.jpeg,.png"
            required
        >

        <br><br>

        <button type="submit">Upload document</button>
    </form>

    <h2>My uploaded documents</h2>

    <?php if (count($documents) === 0): ?>
        <p>No documents uploaded yet.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>Original name</th>
                <th>Type</th>
                <th>Size</th>
                <th>Uploaded at</th>
            </tr>

            <?php foreach ($documents as $document): ?>
                <tr>
                    <td>
                        <?= escape_upload_value(
                            (string) $document['original_name']
                        ) ?>
                    </td>
                    <td>
                        <?= escape_upload_value(
                            (string) $document['mime_type']
                        ) ?>
                    </td>
                    <td>
                        <?= (int) $document['size_bytes'] ?> bytes
                    </td>
                    <td>
                        <?= escape_upload_value(
                            (string) $document['uploaded_at']
                        ) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <p>
        <a href="dashboard.php">Dashboard</a>
        |
        <a href="logout.php">Log out</a>
    </p>
</body>
</html>