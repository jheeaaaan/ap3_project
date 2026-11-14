<?php
declare(strict_types=1);

set_exception_handler(static function (Throwable $exception): void {
    $isDatabaseError = $exception instanceof PDOException
        || ($exception instanceof RuntimeException
            && str_starts_with($exception->getMessage(), 'Database '));

    if (!$isDatabaseError) {
        error_log((string) $exception);
        http_response_code(500);
        exit('The system encountered an unexpected error.');
    }

    error_log('Database error: ' . (string) $exception);
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    exit('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Temporarily unavailable</title></head><body><main><h1>The system is temporarily unavailable. Please try again later.</h1></main></body></html>');
});
