<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use RuntimeException;

abstract class AbstractPdoRepository
{
    protected function createPdoFromEnv(): PDO
    {
        $required = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
        $missing = [];

        foreach ($required as $key) {
            $value = $this->env($key);
            if ($value === null || trim($value) === '') {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            throw new RuntimeException('Missing required environment variables: ' . implode(', ', $missing));
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            (string) $this->env('DB_HOST'),
            (string) $this->env('DB_PORT'),
            (string) $this->env('DB_NAME')
        );

        return new PDO($dsn, (string) $this->env('DB_USER'), (string) $this->env('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private function env(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);
        if ($value === false || $value === null) {
            return null;
        }

        return (string) $value;
    }
}
