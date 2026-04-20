<?php

declare(strict_types=1);

namespace App\Controller;

use PDO;
use PDOException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class DatabaseCheckController
{
    #[Route('/db-check', name: 'db_check', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $missingVars = $this->missingRequiredEnv([
            'DB_HOST',
            'DB_PORT',
            'DB_NAME',
            'DB_USER',
            'DB_PASSWORD',
        ]);

        if ($missingVars !== []) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Missing required environment variables',
                'missing' => $missingVars,
            ], 500);
        }

        $host = $this->env('DB_HOST');
        $port = $this->env('DB_PORT');
        $dbName = $this->env('DB_NAME');
        $user = $this->env('DB_USER');
        $password = $this->env('DB_PASSWORD');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbName);

        try {
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Executes a lightweight command to confirm DB session is usable.
            $pdo->query('SELECT 1');

            return new JsonResponse([
                'ok' => true,
                'message' => 'Connected to MySQL successfully',
            ]);
        } catch (PDOException $exception) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Cannot connect to MySQL',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    private function missingRequiredEnv(array $keys): array
    {
        $missing = [];

        foreach ($keys as $key) {
            $value = $this->rawEnv($key);
            if ($value === false || $value === null || trim((string) $value) === '') {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    private function env(string $name): string
    {
        return (string) $this->rawEnv($name);
    }

    private function rawEnv(string $name): string|false|null
    {
        return $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);
    }
}
