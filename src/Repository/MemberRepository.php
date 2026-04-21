<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\Request\CreateMemberRequestDto;
use App\DTO\Request\GetMemberPointHistoryRequestDto;
use App\DTO\Response\CreateMemberResponseDto;
use App\DTO\Response\GetMemberPointHistoryResponseDto;
use App\DTO\Response\PointHistoryItemDto;
use App\Entity\Member;
use App\Entity\Wallet;
use DomainException;
use PDO;
use RuntimeException;
use Throwable;

final class MemberRepository extends AbstractPdoRepository
{
    private const HISTORY_LIMIT = 10;

    public function createMemberWithWallet(CreateMemberRequestDto $request): CreateMemberResponseDto
    {
        $pdo = $this->createPdoFromEnv();

        try {
            $pdo->beginTransaction();

            if ($this->memberEmailExists($pdo, $request->getEmail())) {
                throw new DomainException('Member email already exists.');
            }

            $member = $this->insertMember($pdo, $request);
            $wallet = $this->insertWallet($pdo, $member->getId());

            $pdo->commit();

            return new CreateMemberResponseDto(
                memberId: $member->getId(),
                fullName: $member->getFullName(),
                email: $member->getEmail(),
                createdAt: $member->getCreatedAt(),
                walletId: $wallet->getId(),
                walletBalance: $wallet->getBalance(),
            );
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function getHistory(GetMemberPointHistoryRequestDto $request): GetMemberPointHistoryResponseDto
    {
        $pdo = $this->createPdoFromEnv();

        if (!$this->memberExists($pdo, $request->getMemberId())) {
            throw new DomainException('Member not found.');
        }

        $wallet = $this->findWalletByMemberId($pdo, $request->getMemberId());
        if ($wallet === null) {
            throw new DomainException('Wallet not found.');
        }

        $items = $this->findPointHistoryByWalletId($pdo, $wallet->getId());

        return new GetMemberPointHistoryResponseDto(
            memberId: $request->getMemberId(),
            walletId: $wallet->getId(),
            walletBalance: $wallet->getBalance(),
            totalItems: count($items),
            items: $items,
        );
    }

    private function memberEmailExists(PDO $pdo, string $email): bool
    {
        $statement = $pdo->prepare('SELECT id FROM members WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);

        return $statement->fetch() !== false;
    }

    private function insertMember(PDO $pdo, CreateMemberRequestDto $request): Member
    {
        $statement = $pdo->prepare('INSERT INTO members (full_name, email) VALUES (:fullName, :email)');
        $statement->execute([
            'fullName' => $request->getFullName(),
            'email' => $request->getEmail(),
        ]);

        $memberId = (int) $pdo->lastInsertId();
        $statement = $pdo->prepare('SELECT id, full_name, email, created_at FROM members WHERE id = :id');
        $statement->execute(['id' => $memberId]);

        $row = $statement->fetch();
        if ($row === false) {
            throw new RuntimeException('Cannot read created member.');
        }

        return new Member(
            id: (int) $row['id'],
            fullName: (string) $row['full_name'],
            email: (string) $row['email'],
            createdAt: (string) $row['created_at'],
        );
    }

    private function insertWallet(PDO $pdo, int $memberId): Wallet
    {
        $statement = $pdo->prepare('INSERT INTO wallets (member_id, balance) VALUES (:memberId, 0.00)');
        $statement->execute(['memberId' => $memberId]);

        $walletId = (int) $pdo->lastInsertId();
        $statement = $pdo->prepare('SELECT id, member_id, balance FROM wallets WHERE id = :id');
        $statement->execute(['id' => $walletId]);

        $row = $statement->fetch();
        if ($row === false) {
            throw new RuntimeException('Cannot read created wallet.');
        }

        return new Wallet(
            id: (int) $row['id'],
            memberId: (int) $row['member_id'],
            balance: (string) $row['balance'],
        );
    }

    private function memberExists(PDO $pdo, int $memberId): bool
    {
        $statement = $pdo->prepare('SELECT id FROM members WHERE id = :memberId');
        $statement->execute(['memberId' => $memberId]);

        return $statement->fetch() !== false;
    }

    private function findWalletByMemberId(PDO $pdo, int $memberId): ?Wallet
    {
        $statement = $pdo->prepare('SELECT id, member_id, balance FROM wallets WHERE member_id = :memberId');
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
}
