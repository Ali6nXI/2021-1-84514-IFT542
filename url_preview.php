<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ssrf_guard.php';

require_admin();

$userId = (int) $_SESSION['user_id'];

$message = '';
$error = '';
$preview = '';
$urlInput = '';

function escape_preview_value(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function fetch_safe_preview(array $target): array
{
    $body = '';
    $maximumBytes = 65536;

    $handle = curl_init($target['url']);

    if ($handle === false) {
        return [
            'ok' => false,
            'reason' => 'curl_initialization_failed',
        ];
    }

    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => false,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_MAXREDIRS => 0,
        CURLOPT_PROXY => '',
        CURLOPT_USERAGENT => 'IFT542-LocalPreview/1.0',
        CURLOPT_HTTPHEADER => [
            'Accept: text/plain, application/json, text/html',
            'Connection: close',
        ],
        CURLOPT_WRITEFUNCTION => static function (
            $curlHandle,
            string $chunk
        ) use (&$body, $maximumBytes): int {
            $remaining = $maximumBytes - strlen($body);

            if ($remaining <= 0) {
                return 0;
            }

            if (strlen($chunk) > $remaining) {
                $body .= substr($chunk, 0, $remaining);
                return 0;
            }

            $body .= $chunk;
            return strlen($chunk);
        },
    ]);

    if (defined('CURLOPT_PROTOCOLS')) {
        curl_setopt(
            $handle,
            CURLOPT_PROTOCOLS,
            CURLPROTO_HTTP | CURLPROTO_HTTPS
        );
    }

    if (defined('CURLOPT_REDIR_PROTOCOLS')) {
        curl_setopt(
            $handle,
            CURLOPT_REDIR_PROTOCOLS,
            CURLPROTO_HTTP | CURLPROTO_HTTPS
        );
    }

    /*
     * For non-lab destinations, pin the request to the IP that was
     * validated before the request was made. Redirects are disabled.
     */
    if (
        empty($target['lab_exception'])
        && !empty($target['ips'][0])
    ) {
        curl_setopt(
            $handle,
            CURLOPT_RESOLVE,
            [
                $target['host']
                . ':'
                . $target['port']
                . ':'
                . $target['ips'][0],
            ]
        );
    }

    $result = curl_exec($handle);
    $curlErrorNumber = curl_errno($handle);
    $curlError = curl_error($handle);
    $information = curl_getinfo($handle);

    curl_close($handle);

    if ($result === false) {
        if (
            $curlErrorNumber === CURLE_WRITE_ERROR
            && strlen($body) >= $maximumBytes
        ) {
            return [
                'ok' => false,
                'reason' => 'response_too_large',
            ];
        }

        error_log('Preview request failed: ' . $curlError);

        return [
            'ok' => false,
            'reason' => 'preview_request_failed',
        ];
    }

    $statusCode = (int) ($information['http_code'] ?? 0);
    $contentType = strtolower(
        (string) ($information['content_type'] ?? '')
    );

    if ($statusCode < 200 || $statusCode >= 300) {
        return [
            'ok' => false,
            'reason' => 'unexpected_http_status',
        ];
    }

    if (
        !str_starts_with($contentType, 'text/')
        && !str_starts_with($contentType, 'application/json')
    ) {
        return [
            'ok' => false,
            'reason' => 'content_type_not_allowed',
        ];
    }

    return [
        'ok' => true,
        'body' => $body,
        'status_code' => $statusCode,
        'content_type' => $contentType,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $urlInput = trim((string) ($_POST['url'] ?? ''));
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($submittedToken)) {
        log_security_event(
            $userId,
            'validation',
            'url_preview_csrf_rejected',
            [
                'resource' => 'url_preview.php',
            ]
        );

        $error = 'The request could not be verified.';
    } else {
        $validation = ssrf_validate_url($urlInput);

        if (!$validation['ok']) {
            log_security_event(
                $userId,
                'validation',
                'url_preview_rejected',
                [
                    'resource' => 'url_preview.php',
                    'reason' => $validation['reason'],
                ]
            );

            $error = 'The destination is not allowed.';
        } else {
            $fetchResult = fetch_safe_preview($validation);

            if (!$fetchResult['ok']) {
                log_security_event(
                    $userId,
                    'validation',
                    'url_preview_fetch_failed',
                    [
                        'resource' => 'url_preview.php',
                        'reason' => $fetchResult['reason'],
                        'host' => $validation['host'],
                    ]
                );

                $error = 'The local preview could not be retrieved.';
            } else {
                $preview = (string) $fetchResult['body'];
                $message = 'Preview retrieved successfully.';

                log_security_event(
                    $userId,
                    'application',
                    'url_preview_success',
                    [
                        'resource' => 'url_preview.php',
                        'host' => $validation['host'],
                        'status_code' => $fetchResult['status_code'],
                        'bytes' => strlen($preview),
                    ]
                );
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Local URL Preview</title>
</head>
<body>
    <h1>Local URL Preview</h1>

    <p>
        This feature accepts only explicitly approved destinations.
        Testing is restricted to localhost.
    </p>

    <?php if ($message !== ''): ?>
        <p role="status">
            <?= escape_preview_value($message) ?>
        </p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p role="alert">
            <?= escape_preview_value($error) ?>
        </p>
    <?php endif; ?>

    <form method="post" action="url_preview.php">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= escape_preview_value(csrf_token()) ?>"
        >

        <label for="url">URL to preview</label><br>

        <input
            type="url"
            id="url"
            name="url"
            maxlength="2048"
            size="70"
            value="<?= escape_preview_value($urlInput) ?>"
            placeholder="http://localhost/ift542_app/mock_target.php"
            required
        >

        <br><br>

        <button type="submit">Preview URL</button>
    </form>

    <?php if ($preview !== ''): ?>
        <h2>Preview result</h2>

        <pre><?= escape_preview_value($preview) ?></pre>
    <?php endif; ?>

    <p>
        <a href="admin/">Administrator dashboard</a>
        |
        <a href="dashboard.php">Dashboard</a>
        |
        <a href="logout.php">Log out</a>
    </p>
</body>
</html>