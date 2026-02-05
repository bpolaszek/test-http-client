<?php

declare(strict_types=1);

use BenTools\TestHttpClient\TestHttpClient;
use BenTools\TestHttpClient\Response;

use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Component\HttpKernel\KernelInterface;

use function BenTools\Pest\Symfony\inject;

describe('TestHttpClient', function () {
    it('creates client with default options', function () {
        $client = inject(TestHttpClient::class);

        expect($client)->toBeInstanceOf(TestHttpClient::class);
    });

    it('makes GET request', function () {
        $client = inject(TestHttpClient::class);
        $response = $client->request('GET', '/test');

        expect($response)->toBeInstanceOf(Response::class)
            ->and($response)->toHaveStatusCode(200);
    });

    it('makes POST request with JSON', function () {
        $client = inject(TestHttpClient::class);
        $response = $client->request('POST', '/json', [
            'json' => ['name' => 'John', 'age' => 30],
        ]);

        expect($response)->toHaveStatusCode(201);
        $data = $response->toArray();
        expect($data)->toHaveKey('received')
            ->and($data['received'])->toBe(['name' => 'John', 'age' => 30]);
    });

    it('sends custom headers', function () {
        $client = inject(TestHttpClient::class);
        $response = $client->request('GET', '/headers', [
            'headers' => ['X-Custom-Header' => 'test-value'],
        ]);

        expect($response)->toHaveStatusCode(200);
        $data = $response->toArray();
        expect($data['custom_header'])->toBe('test-value');
    });

    it('handles bearer authentication', function () {
        $client = inject(TestHttpClient::class);
        $response = $client->request('GET', '/protected', [
            'auth_bearer' => 'valid-token',
        ]);

        expect($response)->toHaveStatusCode(200);
        $data = $response->toArray();
        expect($data['message'])->toBe('Access granted');
    });

    it('handles basic authentication with array', function () {
        $client = inject(TestHttpClient::class);

        // This test just verifies that auth_basic doesn't throw
        // The actual authentication would need a controller that checks PHP_AUTH_USER
        $params = [
            'auth_basic' => ['user', 'password'],
        ];
        expect(fn () => $client->request('GET', '/test', $params))->not->toThrow(Exception::class);
    });

    it('handles basic authentication with string', function () {
        $client = inject(TestHttpClient::class);

        // Test with string format "user:password"
        $params = [
            'auth_basic' => 'user:password',
        ];
        expect(fn () => $client->request('GET', '/test', $params))->not->toThrow(Exception::class);
    });

    it('returns response with headers', function () {
        $client = inject(TestHttpClient::class);
        $response = $client->request('GET', '/headers');

        expect($response)->toHaveHeader('x-response-header', 'custom-value')
            ->and($response)->toHaveHeader('cache-control');
    });

    it('provides access to container', function () {
        $client = inject(TestHttpClient::class);
        // Make a request first to boot the kernel
        $client->request('GET', '/test');

        $container = $client->kernelBrowser->getContainer();
        expect($container)->toBeInstanceOf(ContainerInterface::class);
    });

    it('provides access to cookie jar', function () {
        $client = inject(TestHttpClient::class);

        $cookieJar = $client->kernelBrowser->getCookieJar();
        expect($cookieJar)->toBeInstanceOf(CookieJar::class);
    });

    it('provides access to kernel', function () {
        $client = inject(TestHttpClient::class);

        $kernel = $client->kernelBrowser->getKernel();
        expect($kernel)->toBeInstanceOf(KernelInterface::class);
    });

    it('can disable reboot', function () {
        $client = inject(TestHttpClient::class);

        expect(fn () => $client->kernelBrowser->disableReboot())->not->toThrow(Exception::class);
    });

    it('can enable reboot', function () {
        $client = inject(TestHttpClient::class);

        expect(fn () => $client->kernelBrowser->enableReboot())->not->toThrow(Exception::class);
    });

    it('throws on stream method', function () {
        $client = inject(TestHttpClient::class);
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
