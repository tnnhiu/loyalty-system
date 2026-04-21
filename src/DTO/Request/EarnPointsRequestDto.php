<?php

declare(strict_types=1);

namespace App\DTO\Request;

use InvalidArgumentException;

final class EarnPointsRequestDto
{
    public function __construct(
        private readonly int $memberId,
        private readonly string $amount,
        private readonly ?string $description,
    ) {}

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public static function fromArray(array $payload): self
    {
        $memberId = $payload['member_id'] ?? null;
        $amount = $payload['amount'] ?? null;
        $description = $payload['description'] ?? null;

        if (!is_int($memberId) && !(is_string($memberId) && ctype_digit($memberId))) {
            throw new InvalidArgumentException('member_id must be a positive integer.');
        }

        $memberId = (int) $memberId;
        if ($memberId <= 0) {
            throw new InvalidArgumentException('member_id must be greater than 0.');
        }

        $normalizedAmount = self::normalizeAmount($amount);
        if (self::amountToCents($normalizedAmount) <= 0) {
            throw new InvalidArgumentException('amount must be greater than 0.');
        }

        if ($description !== null && !is_string($description)) {
            throw new InvalidArgumentException('description must be a string or null.');
        }

        return new self(
            memberId: $memberId,
            amount: $normalizedAmount,
            description: $description,
        );
    }

    private static function normalizeAmount(mixed $amount): string
    {
        if (is_int($amount)) {
            $amount = (string) $amount;
        } elseif (is_float($amount)) {
            // Keep decimal input predictable before regex validation.
            $amount = rtrim(rtrim(sprintf('%.10F', $amount), '0'), '.');
        }

        if (!is_string($amount)) {
            throw new InvalidArgumentException('amount must be a decimal value.');
        }

        $amount = trim($amount);
        if ($amount === '') {
            throw new InvalidArgumentException('amount is required.');
        }

        if (!preg_match('/^(0|[1-9]\d*)(\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException('amount must be a decimal with up to 2 digits after decimal point.');
        }

        if (!str_contains($amount, '.')) {
            return $amount . '.00';
        }

        [$whole, $decimal] = explode('.', $amount, 2);

        return $whole . '.' . str_pad($decimal, 2, '0');
    }

    private static function amountToCents(string $amount): int
    {
        [$whole, $decimal] = explode('.', $amount, 2);

        return ((int) $whole * 100) + (int) $decimal;
    }
}
