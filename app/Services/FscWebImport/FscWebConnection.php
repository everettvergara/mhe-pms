<?php

namespace App\Services\FscWebImport;

use PDO;
use RuntimeException;

class FscWebConnection
{
    /**
     * @param  array<string, mixed>  $config
     */
    public static function connect(array $config): PDO
    {
        $host = $config['host'] ?? null;
        $database = $config['database'] ?? null;
        $username = $config['username'] ?? null;

        if (! $host || ! $database || ! $username) {
            throw new RuntimeException('FSC Web MySQL connection requires host, database, and username.');
        }

        return new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $host,
                $config['port'] ?? 3306,
                $database,
            ),
            (string) $username,
            (string) ($config['password'] ?? ''),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function configFromEnv(): array
    {
        return config('fsc_web_import.mysql', []);
    }
}
