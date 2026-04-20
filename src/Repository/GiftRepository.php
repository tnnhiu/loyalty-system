<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\Request\CreateGiftRequestDto;
use App\DTO\Response\CreateGiftResponseDto;
use App\Entity\Gift;
use PDO;
use RuntimeException;

final class GiftRepository
{
    public function createGift(CreateGiftRequestDto $request): CreateGiftResponseDto
    {
        $pdo = $this->createPdoFromEnv();
        $gift = $this->insertGift($pdo, $request);

        return new CreateGiftResponseDto(
            id: $gift->id,
            giftName: $gift->giftName,
            pointCost: $gift->pointCost,
            stock: $gift->stock,
            status: $gift->status,
        );
    }

    private function insertGift(PDO $pdo, CreateGiftRequestDto $request): Gift
    {
        $statement = $pdo->prepare(
            'INSERT INTO gifts (gift_name, point_cost, stock, status) VALUES (:giftName, :pointCost, :stock, :status)'
        );
        $statement->execute([
            'giftName' => $request->giftName,
            'pointCost' => $request->pointCost,
            'stock' => $request->stock,
            'status' => $request->status,
        ]);

        $giftId = (int) $pdo->lastInsertId();
        $statement = $pdo->prepare('SELECT id, gift_name, point_cost, stock, status FROM gifts WHERE id = :id');
        $statement->execute(['id' => $giftId]);

        $row = $statement->fetch();
        if ($row === false) {
            throw new RuntimeException('Cannot read created gift.');
        }

        return new Gift(
            id: (int) $row['id'],
            giftName: (string) $row['gift_name'],
            pointCost: (int) $row['point_cost'],
            stock: (int) $row['stock'],
            status: (string) $row['status'],
        );
    }

    private function createPdoFromEnv(): PDO
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
