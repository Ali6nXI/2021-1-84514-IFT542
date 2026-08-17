<?php
declare(strict_types=1);

function ssrf_result(
    bool $ok,
    string $reason,
    array $extra = []
): array {
    return array_merge(
        [
            'ok' => $ok,
            'reason' => $reason,
        ],
        $extra
    );
}

function ssrf_is_forbidden_ip(string $ip): bool
{
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) === false;
}

function ssrf_resolve_host(string $host): array
{
    $addresses = [];

    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return [$host];
    }

    $records = @dns_get_record(
        $host,
        DNS_A | DNS_AAAA
    );

    if (is_array($records)) {
        foreach ($records as $record) {
            if (isset($record['ip'])) {
                $addresses[] = $record['ip'];
            }

            if (isset($record['ipv6'])) {
                $addresses[] = $record['ipv6'];
            }
        }
    }

    $ipv4Addresses = @gethostbynamel($host);

    if (is_array($ipv4Addresses)) {
        $addresses = array_merge($addresses, $ipv4Addresses);
    }

    return array_values(array_unique($addresses));
}

function ssrf_allowed_hosts(): array
{
    $configured = (string) (
        getenv('IFT542_PREVIEW_ALLOWLIST') ?: ''
    );

    if ($configured === '') {
        return [];
    }

    $hosts = array_map(
        static fn (string $host): string =>
            strtolower(trim($host)),
        explode(',', $configured)
    );

    return array_values(
        array_filter(
            $hosts,
            static fn (string $host): bool => $host !== ''
        )
    );
}

function ssrf_validate_url(string $rawUrl): array
{
    $rawUrl = trim($rawUrl);

    if ($rawUrl === '' || strlen($rawUrl) > 2048) {
        return ssrf_result(false, 'invalid_url_length');
    }

    if (preg_match('/[\r\n]/', $rawUrl)) {
        return ssrf_result(false, 'invalid_url_characters');
    }

    $parts = parse_url($rawUrl);

    if (!is_array($parts)) {
        return ssrf_result(false, 'invalid_url');
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));

    if (!in_array($scheme, ['http', 'https'], true)) {
        return ssrf_result(false, 'unsupported_scheme');
    }

    if (
        isset($parts['user'])
        || isset($parts['pass'])
    ) {
        return ssrf_result(false, 'embedded_credentials_not_allowed');
    }

    $host = strtolower(
        rtrim((string) ($parts['host'] ?? ''), '.')
    );

    if ($host === '') {
        return ssrf_result(false, 'missing_host');
    }

    $defaultPort = $scheme === 'https' ? 443 : 80;
    $port = isset($parts['port'])
        ? (int) $parts['port']
        : $defaultPort;

    if (
        ($scheme === 'http' && $port !== 80)
        || ($scheme === 'https' && $port !== 443)
    ) {
        return ssrf_result(false, 'port_not_allowed');
    }

    $path = (string) ($parts['path'] ?? '/');

    if ($path === '') {
        $path = '/';
    }

    /*
     * The exact local mock is permitted only for the isolated
     * localhost demonstration. It is not a general loopback
     * exception.
     */
    $isLabMock =
        getenv('IFT542_SSRF_LAB') === '1'
        && $scheme === 'http'
        && $host === 'localhost'
        && $port === 80
        && $path === '/ift542_app/mock_target.php'
        && !isset($parts['query'])
        && !isset($parts['fragment']);

    if ($isLabMock) {
        return ssrf_result(
            true,
            'approved_local_lab_mock',
            [
                'url' => 'http://localhost/ift542_app/mock_target.php',
                'host' => $host,
                'port' => $port,
                'ips' => ['127.0.0.1'],
                'lab_exception' => true,
            ]
        );
    }

    // Explicitly reject common local aliases.
    if (
        $host === 'localhost'
        || $host === 'localhost.localdomain'
    ) {
        return ssrf_result(false, 'loopback_not_allowed');
    }

    // Reject literal loopback/private/reserved IPs.
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        if (ssrf_is_forbidden_ip($host)) {
            return ssrf_result(
                false,
                'private_loopback_or_reserved_address'
            );
        }

        return ssrf_result(
            false,
            'literal_ip_not_allowlisted'
        );
    }

    $allowedHosts = ssrf_allowed_hosts();

    if (!in_array($host, $allowedHosts, true)) {
        return ssrf_result(false, 'destination_not_allowlisted');
    }

    $ips = ssrf_resolve_host($host);

    if ($ips === []) {
        return ssrf_result(false, 'host_could_not_be_resolved');
    }

    foreach ($ips as $ip) {
        if (ssrf_is_forbidden_ip($ip)) {
            return ssrf_result(
                false,
                'resolved_to_private_loopback_or_reserved_address'
            );
        }
    }

    return ssrf_result(
        true,
        'approved_destination',
        [
            'url' => $rawUrl,
            'host' => $host,
            'port' => $port,
            'ips' => $ips,
            'lab_exception' => false,
        ]
    );
}