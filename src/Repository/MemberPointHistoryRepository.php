<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\Request\GetMemberPointHistoryRequestDto;
use App\DTO\Response\GetMemberPointHistoryResponseDto;
use App\DTO\Response\PointHistoryItemDto;
use DomainException;
use PDO;
use RuntimeException;

final class MemberPointHistoryRepository
{
    private const HISTORY_LIMIT = 10;

    public function getHistory(GetMemberPointHistoryRequestDto $request): GetMemberPointHistoryResponseDto
    {
        $pdo = $this->createPdoFromEnv();

        if (!$this->memberExists($pdo, $request->memberId)) {
            throw new DomainException('Member not found.');
        }

        $wallet = $this->findWalletByMemberId($pdo, $request->memberId);
        if ($wallet === null) {
            throw new DomainException('Wallet not found.');
        }

        $items = $this->findPointHistoryByWalletId($pdo, $wallet['id']);

        return new GetMemberPointHistoryResponseDto(
            memberId: $request->memberId,
            walletId: (int) $wallet['id'],
            walletBalance: (string) $wallet['balance'],
            totalItems: count($items),
            items: $items,
        );
    }

    private function memberExists(PDO $pdo, int $memberId): bool
    {
        $statement = $pdo->prepare('SELECT id FROM members WHERE id = :memberId');
        $statement->execute(['memberId' => $memberId]);

        return $statement->fetch() !== false;
    }

    /** @return array{id:int,balance:string}|null */
    private function findWalletByMemberId(PDO $pdo, int $memberId): ?array
    {
        $statement = $pdo->prepare('SELECT id, balance FROM wallets WHERE member_id = :memberId');
        $statement->execute(['memberId' => $memberId]);

        $row = $statement->fetch();
        if ($row === false) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'balance' => (string) $row['balance'],
        ];
    }

    /** @return PointHistoryItemDto[] */
    private function findPointHistoryByWalletId(PDO $pdo, int $walletId): array
    {
        $sql = 'SELECT p.id, p.point_amount, p.description, p.transaction_id, p.redemption_id, p.created_at, '
            . 't.amount AS transaction_amount, r.gift_id, g.gift_name '
            . 'FROM points p '
            . 'LEFT JOIN transactions t ON t.id = p.transaction_id '
            . 'LEFT JOIN redemptions r ON r.id = p.redemption_id '
            . 'LEFT JOIN gifts g ON g.id = r.gift_id '
            . 'WHERE p.wallet_id = :walletId '
            . 'ORDER BY p.created_at DESC, p.id DESC '
            . 'LIMIT :limit';

        $statement = $pdo->prepare($sql);
        $statement->bindValue('walletId', $walletId, PDO::PARAM_INT);
        $statement->bindValue('limit', self::HISTORY_LIMIT, PDO::PARAM_INT);
        $statement->execute();

        $rows = $statement->fetchAll();
        $items = [];

        foreach ($rows as $row) {
            $items[] = new PointHistoryItemDto(
                pointId: (int) $row['id'],
                pointAmount: (int) $row['point_amount'],
                description: $row['description'] !== null ? (string) $row['description'] : null,
                transactionId: $row['transaction_id'] !== null ? (int) $row['transaction_id'] : null,
                transactionAmount: $row['transaction_amount'] !== null ? (string) $row['transaction_amount'] : null,
                redemptionId: $row['redemption_id'] !== null ? (int) $row['redemption_id'] : null,
                giftId: $row['gift_id'] !== null ? (int) $row['gift_id'] : null,
                giftName: $row['gift_name'] !== null ? (string) $row['gift_name'] : null,
                createdAt: (string) $row['created_at'],
            );
        }

        return $items;
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
