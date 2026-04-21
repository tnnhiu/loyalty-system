<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\Request\EarnPointsRequestDto;
use App\DTO\Response\EarnPointsResponseDto;
use App\Entity\Point;
use App\Entity\Transaction;
use App\Entity\Wallet;
use DomainException;
use PDO;
use RuntimeException;
use Throwable;

final class TransactionRepository extends AbstractPdoRepository
{
    public function earnPoints(EarnPointsRequestDto $request): EarnPointsResponseDto
    {
        $pdo = $this->createPdoFromEnv();

        try {
            $pdo->beginTransaction();

            if (!$this->memberExists($pdo, $request->getMemberId())) {
                throw new DomainException('Member not found.');
            }

            $wallet = $this->findOrCreateWallet($pdo, $request->getMemberId());
            $earnedPoints = $this->calculatePoints($request->getAmount());

            $transaction = $this->insertTransaction($pdo, $request);
            $this->insertPoint($pdo, $wallet, $transaction, $earnedPoints, $request->getDescription());
            $wallet = $this->increaseWalletBalance($pdo, $wallet, $earnedPoints);

            $pdo->commit();

            return new EarnPointsResponseDto(
                transactionId: $transaction->getId(),
                memberId: $transaction->getMemberId(),
                amount: $transaction->getAmount(),
                earnedPoints: $earnedPoints,
                walletBalance: $wallet->getBalance(),
                status: $transaction->getStatus(),
                createdAt: $transaction->getCreatedAt(),
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

    private function findOrCreateWallet(PDO $pdo, int $memberId): Wallet
    {
        $wallet = $this->findWalletByMemberId($pdo, $memberId);

        if ($wallet !== null) {
            return $wallet;
        }

        $statement = $pdo->prepare('INSERT INTO wallets (member_id, balance) VALUES (:memberId, 0.00)');
        $statement->execute(['memberId' => $memberId]);

        $wallet = $this->findWalletByMemberId($pdo, $memberId);
        if ($wallet === null) {
            throw new RuntimeException('Cannot create wallet for member.');
        }

        return $wallet;
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

    private function insertTransaction(PDO $pdo, EarnPointsRequestDto $request): Transaction
    {
        $statement = $pdo->prepare(
            'INSERT INTO transactions (member_id, amount, status) VALUES (:memberId, :amount, :status)'
        );
        $statement->execute([
            'memberId' => $request->getMemberId(),
            'amount' => $request->getAmount(),
            'status' => 'SUCCESS',
        ]);

        $transactionId = (int) $pdo->lastInsertId();
        $statement = $pdo->prepare(
            'SELECT id, member_id, amount, status, created_at FROM transactions WHERE id = :id'
        );
        $statement->execute(['id' => $transactionId]);

        $row = $statement->fetch();
        if ($row === false) {
            throw new RuntimeException('Cannot read created transaction.');
        }

        return new Transaction(
            id: (int) $row['id'],
            memberId: (int) $row['member_id'],
            amount: (string) $row['amount'],
            status: (string) $row['status'],
            createdAt: (string) $row['created_at'],
        );
    }

    private function insertPoint(
        PDO $pdo,
        Wallet $wallet,
        Transaction $transaction,
        int $earnedPoints,
        ?string $description,
    ): Point {
        $statement = $pdo->prepare(
            'INSERT INTO points (wallet_id, transaction_id, point_amount, description) '
                . 'VALUES (:walletId, :transactionId, :pointAmount, :description)'
        );
        $statement->execute([
            'walletId' => $wallet->getId(),
            'transactionId' => $transaction->getId(),
            'pointAmount' => $earnedPoints,
            'description' => $description,
        ]);

        $pointId = (int) $pdo->lastInsertId();
        $statement = $pdo->prepare(
            'SELECT id, wallet_id, transaction_id, redemption_id, point_amount, description, created_at '
                . 'FROM points WHERE id = :id'
        );
        $statement->execute(['id' => $pointId]);

        $row = $statement->fetch();
        if ($row === false) {
            throw new RuntimeException('Cannot read created point.');
        }

        return new Point(
            id: (int) $row['id'],
            walletId: (int) $row['wallet_id'],
            transactionId: $row['transaction_id'] !== null ? (int) $row['transaction_id'] : null,
            redemptionId: $row['redemption_id'] !== null ? (int) $row['redemption_id'] : null,
            pointAmount: (int) $row['point_amount'],
            description: $row['description'] !== null ? (string) $row['description'] : null,
            createdAt: (string) $row['created_at'],
        );
    }

    private function increaseWalletBalance(PDO $pdo, Wallet $wallet, int $earnedPoints): Wallet
    {
        $statement = $pdo->prepare('UPDATE wallets SET balance = balance + :amount WHERE id = :id');
        $statement->execute([
            'amount' => $earnedPoints,
            'id' => $wallet->getId(),
        ]);

        $updatedWallet = $this->findWalletByMemberId($pdo, $wallet->getMemberId());
        if ($updatedWallet === null) {
            throw new RuntimeException('Cannot read wallet after balance update.');
        }

        return $updatedWallet;
    }

    private function calculatePoints(string $amount): int
    {
        $amountInCents = $this->amountToCents($amount);

        return intdiv($amountInCents, 10000);
    }

    private function amountToCents(string $amount): int
    {
        [$whole, $decimal] = explode('.', $amount, 2);

        return ((int) $whole * 100) + (int) $decimal;
    }
}
