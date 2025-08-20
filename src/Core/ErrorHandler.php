<?php
namespace Core;

class ErrorHandler
{
    public static function register(bool $debug = false): void
    {
        set_exception_handler(function ($e) use ($debug) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            $payload = ['error' => 'Server Error'];
            if ($debug) {
                $payload['exception'] = [
                    'type' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString()),
                ];
            }
            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        });

        set_error_handler(function ($severity, $message, $file, $line) use ($debug) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }
}