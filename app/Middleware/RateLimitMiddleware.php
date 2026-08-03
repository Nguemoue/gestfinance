<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Middleware pour limiter le taux de requêtes par IP.
 */
class RateLimitMiddleware
{
    private const MAX_REQUESTS = 30;
    private const TIME_WINDOW = 60; // 1 minute

    public static function handle(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = "rate_limit_" . md5($ip);
        
        $tempDir = sys_get_temp_dir() . '/gestfinance_cache';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0700, true);
        }

        $file = $tempDir . '/' . $key;
        $now = time();
        $data = ['count' => 0, 'start_time' => $now];

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
        }

        if ($now - $data['start_time'] > self::TIME_WINDOW) {
            $data = ['count' => 1, 'start_time' => $now];
        } else {
            $data['count']++;
        }

        file_put_contents($file, json_encode($data), LOCK_EX);

        if ($data['count'] > self::MAX_REQUESTS) {
            http_response_code(429);
            die("Trop de requêtes. Veuillez patienter une minute.");
        }
    }
}
