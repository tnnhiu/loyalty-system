<?php

declare(strict_types=1);

namespace App\DTO\Request;

use InvalidArgumentException;

final class CreateMemberRequestDto
{
    public function __construct(
        private readonly string $fullName,
        private readonly string $email,
    ) {}

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public static function fromArray(array $payload): self
    {
        $fullName = $payload['full_name'] ?? null;
        $email = $payload['email'] ?? null;

        if (!is_string($fullName) || trim($fullName) === '') {
            throw new InvalidArgumentException('full_name is required.');
        }

        if (!is_string($email) || trim($email) === '') {
            throw new InvalidArgumentException('email is required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('email format is invalid.');
        }

        return new self(
            fullName: trim($fullName),
            email: strtolower(trim($email)),
        );
    }
}
