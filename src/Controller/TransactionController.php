<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Request\EarnPointsRequestDto;
use App\Repository\EarnPointsRepository;
use DomainException;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class TransactionController
{
    public function __construct(private readonly EarnPointsRepository $earnPointsRepository) {}

    #[Route('/api/transactions/earn-points', name: 'api_earn_points', methods: ['POST'])]
    public function earnPoints(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($payload)) {
                return new JsonResponse([
                    'ok' => false,
                    'message' => 'Request body must be a JSON object.',
                ], 400);
            }

            $dto = EarnPointsRequestDto::fromArray($payload);
            $result = $this->earnPointsRepository->earnPoints($dto);

            return new JsonResponse([
                'ok' => true,
                'message' => 'Earn points success.',
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
            ], 404);
        } catch (Throwable $exception) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Cannot process earn points transaction.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
