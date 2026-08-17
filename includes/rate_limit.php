<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

const LOGIN_RATE_LIMIT = 5;
const LOGIN_RATE_WINDOW_MINUTES = 5;

function login_is_rate_limited(string $ipAddress): bool
{
    try {
        $statement = db()->prepare(
            'SELECT COUNT(*)
             FROM audit_logs
             WHERE event_type = "authentication"
               AND event_action IN (
                   "login_failure",
                   "login_rejected"
               )
               AND ip_address = :ip_address
               AND created_at >= (
                   CURRENT_TIMESTAMP
                   - INTERVAL 5 MINUTE
               )'
        );

        $statement->execute([
            ':ip_address' => $ipAddress,
        ]);

        $attemptCount = (int) $statement->fetchColumn();

        return $attemptCount >= LOGIN_RATE_LIMIT;
    } catch (Throwable $exception) {
        error_log(
            'Login rate-limit check failed: '
            . $exception->getMessage()
        );

        // Normal authentication will still fail if the database is unavailable.
        return false;
    }
}