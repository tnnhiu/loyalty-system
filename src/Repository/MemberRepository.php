<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\Request\CreateMemberRequestDto;
use App\DTO\Response\CreateMemberResponseDto;
use App\Entity\Member;
use App\Entity\Wallet;
use DomainException;
use PDO;
use RuntimeException;
use Throwable;

final class MemberRepository
{
    public function createMemberWithWallet(CreateMemberRequestDto $request): CreateMemberResponseDto
    {
        $pdo = $this->createPdoFromEnv();

        try {
            $pdo->beginTransaction();

            if ($this->memberEmailExists($pdo, $request->email)) {
                throw new DomainException('Member email already exists.');
            }

            $member = $this->insertMember($pdo, $request);
            $wallet = $this->insertWallet($pdo, $member->id);

            $pdo->commit();

            return new CreateMemberResponseDto(
                memberId: $member->id,
                fullName: $member->fullName,
                email: $member->email,
                createdAt: $member->createdAt,
                walletId: $wallet->id,
                walletBalance: $wallet->balance,
            );
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
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
            'fullName' => $request->fullName,
            'email' => $request->email,
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
