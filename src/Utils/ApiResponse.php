<?php

namespace Taxi\Utils;

class ApiResponse
{
    public static function success(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data,
        ]);
    }

    public static function error(string $message, int $statusCode = 400): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
        ]);
    }

    public static function getJsonInput(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
