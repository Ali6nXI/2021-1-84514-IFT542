<?php
declare(strict_types=1);

$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';

$allowedLocalAddresses = [
    '127.0.0.1',
    '::1',
];

if (!in_array($remoteAddress, $allowedLocalAddresses, true)) {
    http_response_code(404);
    exit('Not found.');
}

header('Content-Type: application/json; charset=utf-8');

echo json_encode(
    [
        'service' => 'IFT542 Local Mock URL Target',
        'status' => 'ok',
        'message' => 'Fictitious local preview response.',
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);