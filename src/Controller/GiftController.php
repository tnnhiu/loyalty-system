<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Request\CreateGiftRequestDto;
use App\DTO\Request\RedeemGiftRequestDto;
use App\Repository\GiftRepository;
use App\Repository\RedeemGiftRepository;
use DomainException;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class GiftController
{
    public function __construct(
        private readonly GiftRepository $giftRepository,
        private readonly RedeemGiftRepository $redeemGiftRepository,
    ) {}

    #[Route('/api/gifts', name: 'api_create_gift', methods: ['POST'])]
    public function createGift(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($payload)) {
                return new JsonResponse([
                    'ok' => false,
                    'message' => 'Request body must be a JSON object.',
                ], 400);
            }

            $dto = CreateGiftRequestDto::fromArray($payload);
            $result = $this->giftRepository->createGift($dto);

            return new JsonResponse([
                'ok' => true,
                'message' => 'Create gift success.',
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
        } catch (Throwable $exception) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Cannot create gift.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    #[Route('/api/redemptions', name: 'api_redeem_gift', methods: ['POST'])]
    public function redeemGift(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($payload)) {
                return new JsonResponse([
                    'ok' => false,
                    'message' => 'Request body must be a JSON object.',
                ], 400);
            }

            $dto = RedeemGiftRequestDto::fromArray($payload);
            $result = $this->redeemGiftRepository->redeem($dto);

            return new JsonResponse([
                'ok' => true,
                'message' => 'Redeem gift success.',
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
                'message' => 'Cannot redeem gift.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
