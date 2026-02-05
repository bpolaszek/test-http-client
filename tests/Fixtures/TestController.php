<?php

declare(strict_types=1);

namespace BenTools\TestHttpClient\Tests\Fixtures;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class TestController
{
    /**
     * Simple GET endpoint returning JSON.
     */
    public function get(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Hello, World!',
            'method' => 'GET',
        ]);
    }

    /**
     * POST endpoint accepting and returning JSON.
     */
    public function postJson(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        return new JsonResponse([
            'received' => $data,
            'method' => 'POST',
        ], Response::HTTP_CREATED);
    }

    /**
     * Protected endpoint requiring Bearer authentication.
     */
    public function protected(Request $request): JsonResponse
    {
        $authorization = $request->headers->get('Authorization');

        if (!$authorization || !str_starts_with($authorization, 'Bearer ')) {
            return new JsonResponse([
                'error' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = substr($authorization, 7);

        if ($token !== 'valid-token') {
            return new JsonResponse([
                'error' => 'Invalid token',
            ], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'message' => 'Access granted',
            'user' => 'test-user',
        ]);
    }

    /**
     * Endpoint returning custom headers.
     */
    public function headers(Request $request): Response
    {
        $response = new JsonResponse([
            'custom_header' => $request->headers->get('X-Custom-Header', 'not set'),
        ]);

        $response->headers->set('X-Response-Header', 'custom-value');
        $response->headers->set('Cache-Control', 'max-age=3600');

        return $response;
    }

    /**
     * Endpoint returning specific HTTP error codes.
     */
    public function error(int $code): JsonResponse
    {
        $messages = [
            400 => 'Bad Request',
            404 => 'Not Found',
            500 => 'Internal Server Error',
        ];

        return new JsonResponse([
            'error' => $messages[$code] ?? 'Error',
            'code' => $code,
        ], $code);
    }
}
