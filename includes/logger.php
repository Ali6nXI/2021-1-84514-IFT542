<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function log_security_event(
    ?int $userId,
    string $eventType,
    string $eventAction,
    array $details = []
): void {
    $safeDetails = [];

    foreach ($details as $key => $value) {
        $key = (string) $key;

        // Never store secrets in security logs.
        if (preg_match(
            '/password|token|secret|cookie|session|api[_-]?key/i',
            $key
        )) {
            continue;
        }

        if (is_scalar($value) || $value === null) {
            $safeDetails[$key] = is_string($value)
                ? substr($value, 0, 200)
                : $value;
        }
    }

    $detailsJson = json_encode(
        $safeDetails,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    if ($detailsJson === false) {
        $detailsJson = '{}';
    }

    try {
        $statement = db()->prepare(
            'INSERT INTO audit_logs
                (
                    user_id,
                    event_type,
                    event_action,
                    ip_address,
                    details
                )
             VALUES
                (
                    :user_id,
                    :event_type,
                    :event_action,
                    :ip_address,
                    :details
                )'
        );

        $statement->execute([
            ':user_id' => $userId,
            ':event_type' => substr($eventType, 0, 50),
            ':event_action' => substr($eventAction, 0, 100),
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':details' => $detailsJson,
        ]);
    } catch (Throwable $exception) {
        // Do not expose logging/database errors to the user.
        error_log('Security logging failed: ' . $exception->getMessage());
    }
}