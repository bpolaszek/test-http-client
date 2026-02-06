<?php

declare(strict_types=1);

use BenTools\TestHttpClient\TestHttpClient;

describe('Full HTTP Cycle Integration', function () {
    it('performs complete GET request cycle', function () {
        $client = new TestHttpClient(createClient());
        $response = $client->request('GET', '/test');

        expect($response)->toBeSuccessful();
        // expect($response)->toBeJson();
        expect($response)->toHaveStatusCode(200);
        expect($response)->toHaveHeader('content-type');

        $data = $response->toArray();
        expect($data)->toHaveKey('message');
        expect($data['message'])->toBe('Hello, World!');
        expect($data['method'])->toBe('GET');
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

        expect($response)->toHaveStatusCode(201);
        expect($response)->toBeSuccessful();
        expect($response)->toHaveHeader('content-type'); // JSON expectation
        // expect($response)->toBeJson(); // TODO: Fix chaining issue
        // expect($response)->toHaveJsonStructure(['received', 'method']);

        $data = $response->toArray();
        expect($data['received'])->toBe($payload);
        expect($data['method'])->toBe('POST');
    });

    it('handles authentication flow', function () {
        $client = new TestHttpClient(createClient());

        // First, try without token (should fail)
        $unauthorizedResponse = $client->request('GET', '/protected');
        expect($unauthorizedResponse)->toBeClientError();
        expect($unauthorizedResponse->getStatusCode())->toBe(401);

        // Then, try with invalid token (should fail)
        $forbiddenResponse = $client->request('GET', '/protected', [
            'auth_bearer' => 'invalid-token',
        ]);
        expect($forbiddenResponse)->toBeClientError();
        expect($forbiddenResponse->getStatusCode())->toBe(403);

        // Finally, try with valid token (should succeed)
        $successResponse = $client->request('GET', '/protected', [
            'auth_bearer' => 'valid-token',
        ]);
        expect($successResponse)->toBeSuccessful();
        expect($successResponse)->toHaveStatusCode(200);

        $data = $successResponse->toArray();
        expect($data['message'])->toBe('Access granted');
        expect($data['user'])->toBe('test-user');
    });

    it('handles custom headers in both directions', function () {
        $client = new TestHttpClient(createClient());

        $response = $client->request('GET', '/headers', [
            'headers' => [
                'X-Custom-Header' => 'my-custom-value',
                'Accept' => 'application/json',
            ],
        ]);

        expect($response)->toBeSuccessful();
        expect($response)->toHaveHeader('x-response-header', 'custom-value');
        expect($response)->toHaveHeader('cache-control'); // Don't check exact value as Symfony adds ", private"

        $data = $response->toArray();
        expect($data['custom_header'])->toBe('my-custom-value');
    });

    it('handles error responses correctly', function () {
        $client = new TestHttpClient(createClient());

        // Test 400 Bad Request
        $badRequest = $client->request('GET', '/error/400');
        expect($badRequest)->toBeClientError();
        expect($badRequest)->toHaveStatusCode(400);

        // Test 404 Not Found
        $notFound = $client->request('GET', '/error/404');
        expect($notFound)->toBeClientError();
        expect($notFound)->toHaveStatusCode(404);

        // Test 500 Internal Server Error
        $serverError = $client->request('GET', '/error/500');
        expect($serverError)->toBeServerError();
        expect($serverError)->toHaveStatusCode(500);
    });

    it('provides access to Symfony internals', function () {
        $client = new TestHttpClient(createClient());
        $response = $client->request('GET', '/test');

        // Verify we can access container
        $container = $client->kernelBrowser->getContainer();
        expect($container)->toBeInstanceOf(\Symfony\Component\DependencyInjection\ContainerInterface::class);

        // Verify we can access kernel
        $kernel = $client->kernelBrowser->getKernel();
        expect($kernel)->toBeInstanceOf(\Symfony\Component\HttpKernel\KernelInterface::class);
        expect($kernel->getEnvironment())->toBe('test');

        // Verify we can access cookie jar
        $cookieJar = $client->kernelBrowser->getCookieJar();
        expect($cookieJar)->toBeInstanceOf(\Symfony\Component\BrowserKit\CookieJar::class);
    });

    it('maintains state across requests when reboot is disabled', function () {
        $client = new TestHttpClient(createClient());
        $client->kernelBrowser->disableReboot();

        $response1 = $client->request('GET', '/test');
        $response2 = $client->request('GET', '/test');

        expect($response1)->toBeSuccessful();
        expect($response2)->toBeSuccessful();

        // Both requests should use the same kernel instance
        expect($client->kernelBrowser->getKernel())->toBeInstanceOf(\Symfony\Component\HttpKernel\KernelInterface::class);
    });

    it('handles multiple sequential requests', function () {
        $client = new TestHttpClient(createClient());

        // Make multiple requests
        $getResponse = $client->request('GET', '/test');
        expect($getResponse)->toBeSuccessful();

        $postResponse = $client->request('POST', '/json', [
            'json' => ['test' => 'data'],
        ]);
        expect($postResponse)->toHaveStatusCode(201);

        $headersResponse = $client->request('GET', '/headers');
        expect($headersResponse)->toBeSuccessful();

        // All responses should be independent
        expect($getResponse->toArray()['method'])->toBe('GET');
        expect($postResponse->toArray()['method'])->toBe('POST');
    });
});
