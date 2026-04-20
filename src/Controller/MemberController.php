<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Request\CreateMemberRequestDto;
use App\DTO\Request\GetMemberPointHistoryRequestDto;
use App\Repository\MemberPointHistoryRepository;
use App\Repository\MemberRepository;
use DomainException;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class MemberController
{
    public function __construct(
        private readonly MemberRepository $memberRepository,
        private readonly MemberPointHistoryRepository $memberPointHistoryRepository,
    ) {}

    #[Route('/api/members', name: 'api_create_member', methods: ['POST'])]
    public function createMember(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($payload)) {
                return new JsonResponse([
                    'ok' => false,
                    'message' => 'Request body must be a JSON object.',
                ], 400);
            }

            $dto = CreateMemberRequestDto::fromArray($payload);
            $result = $this->memberRepository->createMemberWithWallet($dto);

            return new JsonResponse([
                'ok' => true,
                'message' => 'Create member success.',
                'data' => $result->toArray(),
            ], 201);
        } catch (JsonException) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Invalid JSON body.',
            ], 400);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 400);
        } catch (DomainException $exception) {
            return new JsonResponse([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 409);
        } catch (Throwable $exception) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Cannot create member.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    #[Route('/api/members/{member_id}/wallet', name: 'api_member_wallet', methods: ['GET'])]
    public function getMemberPointHistory(int $member_id): JsonResponse
    {
        try {
            $dto = GetMemberPointHistoryRequestDto::fromArray([
                'member_id' => $member_id,
            ]);

            $result = $this->memberPointHistoryRepository->getHistory($dto);

            return new JsonResponse([
                'ok' => true,
                'message' => 'Get member point history success.',
                'data' => $result->toArray(),
            ]);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 400);
        } catch (DomainException $exception) {
            return new JsonResponse([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 404);
        } catch (Throwable $exception) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Cannot get member point history.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}