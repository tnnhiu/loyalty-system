<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\Request\RedeemGiftRequestDto;
use App\DTO\Response\RedeemGiftResponseDto;
use App\Entity\Gift;
use App\Entity\Redemption;
use App\Entity\Wallet;
use DomainException;
use PDO;
use RuntimeException;
use Throwable;

final class RedeemGiftRepository
{
    public function redeem(RedeemGiftRequestDto $request): RedeemGiftResponseDto
    {
        $pdo = $this->createPdoFromEnv();

        try {
            $pdo->beginTransaction();

            if (!$this->memberExists($pdo, $request->memberId)) {
                throw new DomainException('Member not found.');
            }

            $gift = $this->findGiftForRedeem($pdo, $request->giftId);
            $wallet = $this->findWalletByMemberIdForUpdate($pdo, $request->memberId);

            if ($wallet === null) {
                throw new DomainException('Wallet not found.');
            }

            if ((float) $wallet->balance < $gift->pointCost) {
                throw new DomainException('Insufficient points in wallet.');
            }

            $redemption = $this->insertRedemption($pdo, $request->memberId, $gift->id, $gift->pointCost);
            $this->decreaseGiftStock($pdo, $gift->id);
            $wallet = $this->decreaseWalletBalance($pdo, $wallet, $gift->pointCost);
            $this->insertPointHistory($pdo, $wallet->id, $redemption->id, $gift->pointCost, $gift->giftName);

            $pdo->commit();

            return new RedeemGiftResponseDto(
                redemptionId: $redemption->id,
                memberId: $redemption->memberId,
                giftId: $redemption->giftId,
                giftName: $gift->giftName,
                pointsUsed: $redemption->pointsUsed,
                walletBalance: $wallet->balance,
                status: $redemption->status,
                createdAt: $redemption->createdAt,
            );
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function memberExists(PDO $pdo, int $memberId): bool
    {
        $statement = $pdo->prepare('SELECT id FROM members WHERE id = :memberId');
        $statement->execute(['memberId' => $memberId]);

        return $statement->fetch() !== false;
    }

    private function findGiftForRedeem(PDO $pdo, int $giftId): Gift
    {
        $statement = $pdo->prepare(
            'SELECT id, gift_name, point_cost, stock, status FROM gifts WHERE id = :giftId FOR UPDATE'
        );
        $statement->execute(['giftId' => $giftId]);

        $row = $statement->fetch();
        if ($row === false) {
            throw new DomainException('Gift not found.');
        }

        $gift = new Gift(
            id: (int) $row['id'],
            giftName: (string) $row['gift_name'],
            pointCost: (int) $row['point_cost'],
            stock: (int) $row['stock'],
            status: (string) $row['status'],
        );

        if ($gift->status !== 'ACTIVE') {
            throw new DomainException('Gift is not active.');
        }

        if ($gift->stock <= 0) {
            throw new DomainException('Gift out of stock.');
        }

        return $gift;
    }

    private function findWalletByMemberIdForUpdate(PDO $pdo, int $memberId): ?Wallet
    {
        $statement = $pdo->prepare(
            'SELECT id, member_id, balance FROM wallets WHERE member_id = :memberId FOR UPDATE'
        );
        $statement->execute(['memberId' => $memberId]);

        $row = $statement->fetch();
        if ($row === false) {
            return null;
        }

        return new Wallet(
            id: (int) $row['id'],
            memberId: (int) $row['member_id'],
            balance: (string) $row['balance'],
        );
    }

    private function insertRedemption(PDO $pdo, int $memberId, int $giftId, int $pointsUsed): Redemption
    {
        $statement = $pdo->prepare(
            'INSERT INTO redemptions (member_id, gift_id, points_used, status) '
                . 'VALUES (:memberId, :giftId, :pointsUsed, :status)'
        );
        $statement->execute([
            'memberId' => $memberId,
            'giftId' => $giftId,
            'pointsUsed' => $pointsUsed,
            'status' => 'COMPLETED',
        ]);

        $redemptionId = (int) $pdo->lastInsertId();
        $statement = $pdo->prepare(
            'SELECT id, member_id, gift_id, points_used, status, created_at FROM redemptions WHERE id = :id'
        );
        $statement->execute(['id' => $redemptionId]);

        $row = $statement->fetch();
        if ($row === false) {
            throw new RuntimeException('Cannot read created redemption.');
        }

        return new Redemption(
            id: (int) $row['id'],
            memberId: (int) $row['member_id'],
            giftId: (int) $row['gift_id'],
            pointsUsed: (int) $row['points_used'],
            status: (string) $row['status'],
            createdAt: (string) $row['created_at'],
        );
    }

    private function decreaseGiftStock(PDO $pdo, int $giftId): void
    {
        $statement = $pdo->prepare('UPDATE gifts SET stock = stock - 1 WHERE id = :giftId');
        $statement->execute(['giftId' => $giftId]);
    }

    private function decreaseWalletBalance(PDO $pdo, Wallet $wallet, int $pointsUsed): Wallet
    {
        $statement = $pdo->prepare('UPDATE wallets SET balance = balance - :pointsUsed WHERE id = :walletId');
        $statement->execute([
            'pointsUsed' => $pointsUsed,
            'walletId' => $wallet->id,
        ]);

        $statement = $pdo->prepare('SELECT id, member_id, balance FROM wallets WHERE id = :walletId');
        $statement->execute(['walletId' => $wallet->id]);

        $row = $statement->fetch();
        if ($row === false) {
            throw new RuntimeException('Cannot read wallet after redeem.');
        }

        return new Wallet(
            id: (int) $row['id'],
            memberId: (int) $row['member_id'],
            balance: (string) $row['balance'],
        );
    }

    private function insertPointHistory(
        PDO $pdo,
        int $walletId,
        int $redemptionId,
        int $pointsUsed,
        string $giftName,
    ): void {
        $statement = $pdo->prepare(
            'INSERT INTO points (wallet_id, redemption_id, point_amount, description) '
                . 'VALUES (:walletId, :redemptionId, :pointAmount, :description)'
        );
        $statement->execute([
            'walletId' => $walletId,
            'redemptionId' => $redemptionId,
            'pointAmount' => -$pointsUsed,
            'description' => 'Redeem gift: ' . $giftName,
        ]);
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
