<?php

declare(strict_types=1);

use BenTools\TestHttpClient\TestHttpClient;
use BenTools\TestHttpClient\Response;

describe('TestHttpClient', function () {
    it('creates client with default options', function () {
        $client = new TestHttpClient(createClient());

        expect($client)->toBeInstanceOf(TestHttpClient::class);
    });

    it('makes GET request', function () {
        $client = new TestHttpClient(createClient());
        $response = $client->request('GET', '/test');

        expect($response)->toBeInstanceOf(Response::class);
        expect($response)->toHaveStatusCode(200);
    });

    it('makes POST request with JSON', function () {
        $client = new TestHttpClient(createClient());
        $response = $client->request('POST', '/json', [
            'json' => ['name' => 'John', 'age' => 30],
        ]);

        expect($response)->toHaveStatusCode(201);
        $data = $response->toArray();
        expect($data)->toHaveKey('received');
        expect($data['received'])->toBe(['name' => 'John', 'age' => 30]);
    });

    it('sends custom headers', function () {
        $client = new TestHttpClient(createClient());
        $response = $client->request('GET', '/headers', [
            'headers' => ['X-Custom-Header' => 'test-value'],
        ]);

        expect($response)->toHaveStatusCode(200);
        $data = $response->toArray();
        expect($data['custom_header'])->toBe('test-value');
    });

    it('handles bearer authentication', function () {
        $client = new TestHttpClient(createClient());
        $response = $client->request('GET', '/protected', [
            'auth_bearer' => 'valid-token',
        ]);

        expect($response)->toHaveStatusCode(200);
        $data = $response->toArray();
        expect($data['message'])->toBe('Access granted');
    });

    it('handles basic authentication', function () {
        $client = new TestHttpClient(createClient());

        // This test just verifies that auth_basic doesn't throw
        // The actual authentication would need a controller that checks PHP_AUTH_USER
        expect(fn () => $client->request('GET', '/test', [
            'auth_basic' => ['user', 'password'],
        ]))->not->toThrow(Exception::class);
    });

    it('returns response with headers', function () {
        $client = new TestHttpClient(createClient());
        $response = $client->request('GET', '/headers');

        expect($response)->toHaveHeader('x-response-header', 'custom-value');
        expect($response)->toHaveHeader('cache-control');
    });

    it('provides access to container', function () {
        $client = new TestHttpClient(createClient());
        // Make a request first to boot the kernel
        $client->request('GET', '/test');

        $container = $client->kernelBrowser->getContainer();
        expect($container)->toBeInstanceOf(\Symfony\Component\DependencyInjection\ContainerInterface::class);
    });

    it('provides access to cookie jar', function () {
        $client = new TestHttpClient(createClient());

        $cookieJar = $client->kernelBrowser->getCookieJar();
        expect($cookieJar)->toBeInstanceOf(\Symfony\Component\BrowserKit\CookieJar::class);
    });

    it('provides access to kernel', function () {
        $client = new TestHttpClient(createClient());

        $kernel = $client->kernelBrowser->getKernel();
        expect($kernel)->toBeInstanceOf(\Symfony\Component\HttpKernel\KernelInterface::class);
    });

    it('can disable reboot', function () {
        $client = new TestHttpClient(createClient());

        expect(fn () => $client->kernelBrowser->disableReboot())->not->toThrow(Exception::class);
    });

    it('can enable reboot', function () {
        $client = new TestHttpClient(createClient());

        expect(fn () => $client->kernelBrowser->enableReboot())->not->toThrow(Exception::class);
    });

    it('throws on stream method', function () {
        $client = new TestHttpClient(createClient());
        $response = $client->request('GET', '/test');

        expect(fn () => $client->stream($response))->toThrow(LogicException::class);
    });

    it('sets default options', function () {
        $client = new TestHttpClient(createClient(), [
            'headers' => ['X-Custom-Header' => 'default-value'],
        ]);

        $response = $client->request('GET', '/headers');
        $data = $response->toArray();

        expect($data['custom_header'])->toBe('default-value');
    });
});
