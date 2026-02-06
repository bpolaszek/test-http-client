<?php

declare(strict_types=1);

use BenTools\TestHttpClient\TestHttpClient;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\HttpKernel\KernelInterface;

describe('Full HTTP Cycle Integration', function () {
    it('performs complete GET request cycle', function () {
        $client = new TestHttpClient(createClient());
        $response = $client->request('GET', '/test');

        expect($response)->toBeSuccessful()
            ->and($response)->toHaveJsonStructure()
            ->and($response)->toHaveStatusCode(200)
            ->and($response)->toHaveHeader('content-type')
            ->and($response)->toHaveKey('message')
            ->and($response['message'])->toBe('Hello, World!')
            ->and($response['method'])->toBe('GET');
    });

    it('performs complete POST request cycle', function () {
        $client = new TestHttpClient(createClient());
        $payload = [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'active' => true,
        ];

        $response = $client->request('POST', '/json', [
            'json' => $payload,
        ]);

        expect($response)->toHaveStatusCode(201)
            ->and($response)->toBeSuccessful()
            ->and($response)->toHaveHeader('content-type')
            ->and($response)->toHaveJsonStructure()
            ->and($response)->toHaveJsonStructure(['received', 'method'])
            ->and($response['received'])->toBe($payload)
            ->and($response['method'])->toBe('POST');
    });

    it('handles authentication flow', function () {
        $client = new TestHttpClient(createClient());

        // First, try without token (should fail)
        $unauthorizedResponse = $client->request('GET', '/protected');
        expect($unauthorizedResponse)->toBeClientError()
            ->and($unauthorizedResponse->getStatusCode())->toBe(401);

        // Then, try with invalid token (should fail)
        $forbiddenResponse = $client->withAuthBearer('invalid-token')->request('GET', '/protected');
        expect($forbiddenResponse)->toBeClientError()
            ->and($forbiddenResponse->getStatusCode())->toBe(403);

        // Finally, try with valid token (should succeed)
        $successResponse = $client->withAuthBearer('valid-token')->request('GET', '/protected');
        expect($successResponse)->toBeSuccessful()
            ->and($successResponse)->toHaveStatusCode(200);

        $data = $successResponse->toArray();
        expect($data['message'])->toBe('Access granted')
            ->and($data['user'])->toBe('test-user');
    });

    it('handles custom headers in both directions', function () {
        $client = new TestHttpClient(createClient());

        $response = $client->request('GET', '/headers', [
            'headers' => [
                'X-Custom-Header' => 'my-custom-value',
                'Accept' => 'application/json',
            ],
        ]);

        expect($response)->toBeSuccessful()
            ->and($response)->toHaveHeader('x-response-header', 'custom-value')
            ->and($response)->toHaveHeader('cache-control') // Don't check exact value as Symfony adds ", private"
            ->and($response['custom_header'])->toBe('my-custom-value');

    });

    it('handles error responses correctly', function () {
        $client = new TestHttpClient(createClient());

        // Test 400 Bad Request
        $badRequest = $client->request('GET', '/error/400');
        expect($badRequest)->toBeClientError()
            ->and($badRequest)->toHaveStatusCode(400);

        // Test 404 Not Found
        $notFound = $client->request('GET', '/error/404');
        expect($notFound)->toBeClientError()
            ->and($notFound)->toHaveStatusCode(404);

        // Test 500 Internal Server Error
        $serverError = $client->request('GET', '/error/500');
        expect($serverError)->toBeServerError()
            ->and($serverError)->toHaveStatusCode(500);
    });

    it('provides access to Symfony internals', function () {
        $client = new TestHttpClient(createClient());

        // Verify we can access cookie jar
        $cookieJar = $client->kernelBrowser->getCookieJar();
        expect($cookieJar)->toBeInstanceOf(CookieJar::class);
    });

    it('maintains state across requests when reboot is disabled', function () {
        $client = new TestHttpClient(createClient());
        $client->kernelBrowser->disableReboot();
        $kernel1 = $client->kernelBrowser->getKernel();
        $response1 = $client->request('GET', '/test');
        $response2 = $client->request('GET', '/test');
        $kernel2 = $client->kernelBrowser->getKernel();

        // Both requests should use the same kernel instance
        expect($response1)->toBeSuccessful()
            ->and($response2)->toBeSuccessful()
            ->and($kernel1)->toBeInstanceOf(KernelInterface::class)
            ->and($kernel1)->toBe($kernel2);
    });

    it('handles multiple sequential requests', function () {
        $client = new TestHttpClient(createClient());

        // Make multiple requests
        $getResponse = $client->request('GET', '/test');
        $postResponse = $client->request('POST', '/json', [
            'json' => ['test' => 'data'],
        ]);
        $headersResponse = $client->request('GET', '/headers');

        // All responses should be independent
        expect($getResponse)->toBeSuccessful()
            ->and($postResponse)->toHaveStatusCode(201)
            ->and($headersResponse)->toBeSuccessful()
            ->and($getResponse->toArray()['method'])->toBe('GET')
            ->and($postResponse->toArray()['method'])->toBe('POST');
    });
});
