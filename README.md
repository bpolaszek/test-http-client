# Test HTTP Client for Symfony

[![Tests](https://github.com/bpolaszek/test-http-client/actions/workflows/tests.yml/badge.svg)](https://github.com/bpolaszek/test-http-client/actions/workflows/tests.yml)
[![Code Coverage](https://codecov.io/gh/bpolaszek/test-http-client/graph/badge.svg?token=sFy8s3FahH)](https://codecov.io/gh/bpolaszek/test-http-client)

Test your Symfony / Api-Platform routes and controllers with `symfony/http-client`.

This package provides a test implementation of Symfony's `HttpClientInterface` that uses `KernelBrowser` internally, 
allowing you to test your HTTP endpoints as if you were making real HTTP requests, but without the overhead of running an actual HTTP server.

Compatible with both **PHPUnit** and **Pest**.

## Example

```php
// src/Controller/HealthController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class HealthController 
{
    #[Route('/health')]
    public function __invoke(): JsonResponse {
        return new JsonResponse(['status' => '😎']);
    }
}
```

### Pest
```php
// tests/Controller/HealthControllerTest.php

use BenTools\TestHttpClient\TestHttpClient;

use function BenTools\Pest\Symfony\inject;

it('works', function () {
    $client  = inject(TestHttpClient::class); // <-- See package `bentools/pest-symfony-kernel`
    $response = $client->request('GET', '/health');
    
    expect($response)->toBeSuccessful()
        ->and($response)->toHaveStatusCode(200)
        ->and($response)->toHaveHeader('Content-Type', 'application/json')
        ->and($response)->toHaveJsonStructure(['status'])
        ->and($response['status'])->toBe('😎')
    ;
});
```

### PHPUnit

```php
// tests/Controller/HealthControllerTest.php
use BenTools\TestHttpClient\TestHttpClient;

class HealthControllerTest extends WebTestCase
{
    public function testHealth(): void
    {
        $client = static::getContainer()->get(TestHttpClient::class);
        $response = $client->request('GET', '/health');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains('application/json', $response->getHeaders()['content-type'] ?? '');
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('😎', $response['status']);
    }
}
```

## Features

- ✅ **Implements `HttpClientInterface`** - Drop-in replacement for real HTTP clients in tests
- ✅ **No HTTP server required** - Uses Symfony's `KernelBrowser` under the hood
- ✅ **Fast integration tests** - Test your API endpoints in milliseconds
- ✅ **Access to internals** - Get the container, profiler, cookies, and more
- ✅ **Pest expectations included** - Custom expectations for testing responses
- ✅ **Full HTTP feature support** - Headers, authentication, JSON, query parameters, etc.

## Installation

```bash
composer require --dev bentools/test-http-client
```

## Symfony Bundle Integration

For Symfony applications, you can register the bundle to automatically wire the `TestHttpClient` service:

### 1. Register the Bundle

Add the bundle to your `config/bundles.php` (only in test environment):

```php
// config/bundles.php
return [
    // ... other bundles
    BenTools\TestHttpClient\Bundle\TestHttpClientBundle::class => ['test' => true],
];
```

### 2. Use the Service

Once registered, the `TestHttpClient` service is automatically available in your test environment:

```php
use BenTools\TestHttpClient\TestHttpClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiTest extends WebTestCase
{
    public function testUsersList(): void
    {
        $client = static::getContainer()->get(TestHttpClient::class);
        $response = $client->request('GET', '/api/users');

        $this->assertSame(200, $response->getStatusCode());
    }
}
```

For Pest tests:

```php
it('returns users list', function () {
    $client = container()->get(TestHttpClient::class);
    $response = $client->request('GET', '/api/users');

    expect($response)->toHaveStatusCode(200);
});
```

> [!NOTE]
> If you prefer to instantiate `TestHttpClient` manually without registering the bundle, you can still do so as shown in the Quick Start section below.

## Quick Start

### With Pest

```php
use BenTools\TestHttpClient\TestHttpClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

uses(WebTestCase::class);

it('returns users list', function () {
    $client = new TestHttpClient(static::createClient());
    $response = $client->request('GET', '/api/users');

    expect($response)
        ->toHaveStatusCode(200)
        ->toHaveJsonStructure()
        ->toHaveHeader('content-type', 'application/json');

    $data = $response->toArray();
    expect($data)->toHaveKey('users');
});
```

### With PHPUnit

```php
use BenTools\TestHttpClient\TestHttpClient;

public function testReturnsUsersList(): void
{
    $client = new TestHttpClient(static::createClient());
    $response = $client->request('GET', '/api/users');

    $this->assertSame(200, $response->getStatusCode());
    $this->assertArrayHasKey('users', $response->toArray());
}
```

### Bearer Authentication

```php
$response = $client->withAuthBearer('your-token-here')->request('GET', '/api/admin');
```

### Access Cookies

```php
$cookieJar = $client->kernelBrowser->getCookieJar();
$cookies = $cookieJar->all();
```

### Performance Optimization

By default, Symfony reboots the kernel between requests. For better performance in tests with multiple requests:

```php
$client = new TestHttpClient($kernelBrowser);
$client->kernelBrowser->disableReboot();

// Make multiple requests - kernel stays booted
$response1 = $client->request('GET', '/api/users');
$response2 = $client->request('GET', '/api/posts');
$response3 = $client->request('GET', '/api/comments');
```

## Pest Expectations

If you're using Pest, the package includes custom expectations for testing responses:

```php
use BenTools\TestHttpClient\TestHttpClient;

it('tests various response aspects', function () {
    $client = new TestHttpClient(static::createClient());
    $response = $client->request('GET', '/api/users');

    // Status code
    expect($response)->toHaveStatusCode(200);

    // Success (2xx)
    expect($response)->toBeSuccessful();

    // Client error (4xx)
    expect($response)->toBeClientError();

    // Server error (5xx)
    expect($response)->toBeServerError();

    // Headers
    expect($response)->toHaveHeader('content-type');
    expect($response)->toHaveHeader('cache-control', 'max-age=3600');

    // JSON
    expect($response)->toHaveJsonStructure();

    // JSON structure
    expect($response)->toHaveJsonStructure(['users', 'total', 'page']);
});
```

### Available Expectations

- `toHaveStatusCode(int $code)` - Assert status code
- `toBeSuccessful()` - Assert 2xx status
- `toBeClientError()` - Assert 4xx status
- `toBeServerError()` - Assert 5xx status
- `toHaveHeader(string $name, ?string $value = null)` - Assert header exists (optionally with value)
- `toHaveJsonStructure()` - Assert content-type is JSON
- `toHaveJsonStructure(array $keys)` - Assert JSON contains specific keys

### PHPStan

Custom Pest expectations are registered at runtime by `expect()->extend()`, so PHPStan cannot discover them
on its own and reports `Call to an undefined method Pest\Expectation<...>::toHaveStatusCode()`.

This package ships a PHPStan extension that declares them. If you use
[`phpstan/extension-installer`](https://github.com/phpstan/extension-installer), it is enabled automatically.
Otherwise, include it manually in your `phpstan.neon`:

```neon
includes:
    - vendor/bentools/test-http-client/extension.neon
```

## Credits

Borrowed and adapted from Api-Platform's [TestClient](https://github.com/api-platform/core/blob/main/src/Symfony/Bundle/Test/Client.php).

## License

MIT.
