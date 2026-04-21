<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\Request\CreateGiftRequestDto;
use App\DTO\Request\RedeemGiftRequestDto;
use App\DTO\Response\CreateGiftResponseDto;
use App\DTO\Response\RedeemGiftResponseDto;
use App\Entity\Gift;
use App\Entity\Redemption;
use App\Entity\Wallet;
use DomainException;
use PDO;
use RuntimeException;
use Throwable;

final class GiftRepository extends AbstractPdoRepository
{
    public function createGift(CreateGiftRequestDto $request): CreateGiftResponseDto
    {
        $pdo = $this->createPdoFromEnv();
        $gift = $this->insertGift($pdo, $request);

        return new CreateGiftResponseDto(
            id: $gift->getId(),
            giftName: $gift->getGiftName(),
            pointCost: $gift->getPointCost(),
            stock: $gift->getStock(),
            status: $gift->getStatus(),
        );
    }

    public function redeem(RedeemGiftRequestDto $request): RedeemGiftResponseDto
    {
        $pdo = $this->createPdoFromEnv();

        try {
            $pdo->beginTransaction();

            if (!$this->memberExists($pdo, $request->getMemberId())) {
                throw new DomainException('Member not found.');
            }

            $gift = $this->findGiftForRedeem($pdo, $request->getGiftId());
            $wallet = $this->findWalletByMemberIdForUpdate($pdo, $request->getMemberId());

            if ($wallet === null) {
                throw new DomainException('Wallet not found.');
            }

            if ((float) $wallet->getBalance() < $gift->getPointCost()) {
                throw new DomainException('Insufficient points in wallet.');
            }

            $redemption = $this->insertRedemption($pdo, $request->getMemberId(), $gift->getId(), $gift->getPointCost());
            $this->decreaseGiftStock($pdo, $gift->getId());
            $wallet = $this->decreaseWalletBalance($pdo, $wallet, $gift->getPointCost());
            $this->insertPointHistory($pdo, $wallet->getId(), $redemption->getId(), $gift->getPointCost(), $gift->getGiftName());

            $pdo->commit();

            return new RedeemGiftResponseDto(
                redemptionId: $redemption->getId(),
                memberId: $redemption->getMemberId(),
                giftId: $redemption->getGiftId(),
                giftName: $gift->getGiftName(),
                pointsUsed: $redemption->getPointsUsed(),
                walletBalance: $wallet->getBalance(),
                status: $redemption->getStatus(),
                createdAt: $redemption->getCreatedAt(),
            );
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function insertGift(PDO $pdo, CreateGiftRequestDto $request): Gift
    {
        $statement = $pdo->prepare(
            'INSERT INTO gifts (gift_name, point_cost, stock, status) VALUES (:giftName, :pointCost, :stock, :status)'
        );
        $statement->execute([
            'giftName' => $request->getGiftName(),
            'pointCost' => $request->getPointCost(),
            'stock' => $request->getStock(),
            'status' => $request->getStatus(),
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

        if ($gift->getStatus() !== 'ACTIVE') {
            throw new DomainException('Gift is not active.');
        }

        if ($gift->getStock() <= 0) {
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
            'walletId' => $wallet->getId(),
        ]);

        $statement = $pdo->prepare('SELECT id, member_id, balance FROM wallets WHERE id = :walletId');
        $statement->execute(['walletId' => $wallet->getId()]);

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
}
