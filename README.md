# BenTools Test HTTP Client

[![Tests](https://github.com/bpolaszek/test-http-client/workflows/Tests/badge.svg)](https://github.com/bpolaszek/test-http-client/actions)
[![Latest Stable Version](https://poser.pugx.org/bentools/test-http-client/v/stable)](https://packagist.org/packages/bentools/test-http-client)
[![License](https://poser.pugx.org/bentools/test-http-client/license)](https://packagist.org/packages/bentools/test-http-client)

Test Symfony applications with `HttpClientInterface` without a real HTTP server.

This package provides a test implementation of Symfony's `HttpClientInterface` that uses `KernelBrowser` internally, allowing you to test your HTTP endpoints as if you were making real HTTP requests, but without the overhead of running an actual HTTP server.

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

> **Note:** If you prefer to instantiate `TestHttpClient` manually without registering the bundle, you can still do so as shown in the Quick Start section below.

## Quick Start

### With Pest

```php
use BenTools\TestHttpClient\TestHttpClient;

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

## Usage

### Basic Requests

#### GET Request

```php
$client = new TestHttpClient($kernelBrowser);
$response = $client->request('GET', '/api/endpoint');

echo $response->getStatusCode(); // 200
echo $response->getContent(); // Raw response body
```

#### POST with JSON

```php
$response = $client->request('POST', '/api/users', [
    'json' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ],
]);

$data = $response->toArray();
echo $data['id']; // Created user ID
```

### Headers and Authentication

#### Custom Headers

```php
$response = $client->request('GET', '/api/protected', [
    'headers' => [
        'Accept' => 'application/json',
        'X-Custom-Header' => 'custom-value',
    ],
]);
```

#### Bearer Authentication

```php
$response = $client->request('GET', '/api/admin', [
    'auth_bearer' => 'your-token-here',
]);
```

#### Basic Authentication

```php
$response = $client->request('GET', '/api/protected', [
    'auth_basic' => ['username', 'password'],
]);

// Or as a string
$response = $client->request('GET', '/api/protected', [
    'auth_basic' => 'username:password',
]);
```

### Query Parameters

```php
$response = $client->request('GET', '/api/users', [
    'query' => [
        'page' => 1,
        'limit' => 10,
        'sort' => 'name',
    ],
]);
// Requests: /api/users?page=1&limit=10&sort=name
```

### Default Options

Set default options that apply to all requests:

```php
$client = new TestHttpClient($kernelBrowser, [
    'headers' => [
        'Accept' => 'application/json',
        'X-API-Key' => 'default-key',
    ],
    'base_uri' => 'http://localhost',
]);

// All requests will include these headers
$response = $client->request('GET', '/api/users');
```

### Accessing Symfony Internals

One of the key benefits of `TestHttpClient` is that you can access Symfony's internals:

#### Access the Container

```php
$client = new TestHttpClient($kernelBrowser);
$response = $client->request('POST', '/api/users', [
    'json' => ['name' => 'John'],
]);

// Access the container to verify side effects
$userRepository = $client->getContainer()->get(UserRepository::class);
$user = $userRepository->findOneBy(['name' => 'John']);
```

#### Access the Profiler

```php
$client->enableProfiler();
$response = $client->request('GET', '/api/users');

$profile = $client->getProfile();
$queries = $profile->getCollector('db')->getQueries();
```

#### Access Cookies

```php
$cookieJar = $client->getCookieJar();
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

// Load expectations (in tests/Pest.php)
require_once __DIR__ . '/../vendor/bentools/test-http-client/src/Pest/Expectations.php';

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

## Testing External API Calls

If your Symfony application makes HTTP requests to external APIs, you can mock them in your test environment:

```yaml
# config/services_test.yaml
when@test:
    services:
        Symfony\Contracts\HttpClient\HttpClientInterface:
            class: Symfony\Component\HttpClient\MockHttpClient
            arguments:
                - !closure |
                    function ($method, $url, $options) {
                        // Mock external API responses
                        if (str_contains($url, 'external-api.com')) {
                            return new \Symfony\Component\HttpClient\Response\MockResponse(
                                json_encode(['status' => 'ok', 'data' => []]),
                                ['http_code' => 200]
                            );
                        }
                        throw new \Exception('Unexpected URL: ' . $url);
                    }
```

Then use `TestHttpClient` to test your application's endpoints while external APIs are mocked:

```php
$client = new TestHttpClient($kernelBrowser);

// Your app makes an external API call internally, but it's mocked
$response = $client->request('POST', '/api/fetch-external-data');

expect($response)->toBeSuccessful();
```

## Configuration

### Framework Configuration

Make sure your `framework.yaml` has test mode enabled:

```yaml
# config/packages/test/framework.yaml
framework:
    test: true
    profiler:
        enabled: true
        collect: false
```

### Creating a Test Client

#### Option 1: Using the Bundle (Recommended)

If you registered the bundle as described in the [Symfony Bundle Integration](#symfony-bundle-integration) section, the service is automatically available:

```php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use BenTools\TestHttpClient\TestHttpClient;

class ApiTest extends WebTestCase
{
    public function testApi(): void
    {
        $client = static::getContainer()->get(TestHttpClient::class);
        $response = $client->request('GET', '/api/test');

        $this->assertSame(200, $response->getStatusCode());
    }
}
```

For Pest:

```php
it('works', function () {
    $client = container()->get(TestHttpClient::class);
    $response = $client->request('GET', '/api/test');

    expect($response)->toBeSuccessful();
});
```

#### Option 2: Manual Instantiation

Alternatively, you can manually create a `TestHttpClient` instance:

```php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use BenTools\TestHttpClient\TestHttpClient;

class ApiTest extends WebTestCase
{
    protected function createHttpClient(): TestHttpClient
    {
        return new TestHttpClient(static::createClient());
    }
}
```

For Pest, add this to `tests/Pest.php`:

```php
use BenTools\TestHttpClient\TestHttpClient;

function httpClient(): TestHttpClient
{
    return new TestHttpClient(createClient());
}
```

Then use it in tests:

```php
it('works', function () {
    $response = httpClient()->request('GET', '/api/test');
    expect($response)->toBeSuccessful();
});
```

## Comparison with Alternatives

### vs. Real HTTP Server

**TestHttpClient:**
- ✅ No server setup required
- ✅ Much faster (no network overhead)
- ✅ Access to Symfony internals (container, profiler)
- ❌ Doesn't test HTTP layer issues (CORS, HTTP/2, etc.)

**Real HTTP Server:**
- ✅ Tests complete HTTP stack
- ❌ Slower
- ❌ Requires server setup
- ❌ No access to internals

### vs. MockHttpClient

**TestHttpClient:**
- ✅ Tests real controller logic
- ✅ Tests routing, validation, serialization
- ❌ Requires booting kernel

**MockHttpClient:**
- ✅ Extremely fast
- ✅ No kernel needed
- ❌ Doesn't test real logic, only HTTP calls

## Limitations

- ❌ **No streaming support** - `stream()` method throws `LogicException`
- ❌ **No async requests** - All requests are synchronous
- ⚠️ **Minimal response info** - Timing and connection info is not available
- ⚠️ **Not for testing HTTP clients** - Use this to test your Symfony app, not external HTTP clients

## Troubleshooting

### Data Pollution Between Requests

**Problem:** You make multiple requests in a test, but data from the first request doesn't persist (sessions, database changes, service state, etc.).

**Example of the issue:**
```php
it('persists data across requests', function () {
    $client = new TestHttpClient(createClient());

    // First request: create a user
    $response1 = $client->request('POST', '/api/users', [
        'json' => ['name' => 'John'],
    ]);
    expect($response1)->toHaveStatusCode(201);
    $userId = $response1->toArray()['id'];

    // Second request: should find the user
    $response2 = $client->request('GET', "/api/users/{$userId}");
    expect($response2)->toHaveStatusCode(200); // ❌ Fails with 404!
});
```

**Solution:** Use `TestKernelTrait` in your test kernel (see Configuration section above).

**Why this happens:** Symfony's default behavior is to reset certain services between requests to prevent state leaking. This is good in production but can cause issues in tests where you expect state to persist.

### Risky Tests Warning

If you see warnings like "Test code or tested code did not remove its own exception handlers", this is normal with Symfony kernels in tests. These warnings are harmless and can be disabled in `phpunit.xml`:

```xml
<phpunit failOnRisky="false">
```

## Examples

### Testing a REST API

```php
describe('User API', function () {
    it('creates a user', function () {
        $client = httpClient();

        $response = $client->request('POST', '/api/users', [
            'json' => [
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ],
        ]);

        expect($response)
            ->toHaveStatusCode(201)
            ->toHaveJsonStructure()
            ->toHaveJsonStructure(['id', 'name', 'email', 'createdAt']);

        $data = $response->toArray();
        expect($data['name'])->toBe('Alice');

        // Verify in database
        $repository = $client->getContainer()->get(UserRepository::class);
        $user = $repository->find($data['id']);
        expect($user)->not->toBeNull();
        expect($user->getEmail())->toBe('alice@example.com');
    });

    it('validates user input', function () {
        $client = httpClient();

        $response = $client->request('POST', '/api/users', [
            'json' => ['name' => ''], // Invalid: empty name
        ]);

        expect($response)
            ->toHaveStatusCode(400)
            ->toBeClientError();

        $data = $response->toArray(false);
        expect($data)->toHaveKey('errors');
    });

    it('requires authentication', function () {
        $client = httpClient();

        $response = $client->request('DELETE', '/api/users/1');

        expect($response)->toHaveStatusCode(401);

        // Now with auth
        $response = $client->request('DELETE', '/api/users/1', [
            'auth_bearer' => 'valid-admin-token',
        ]);

        expect($response)->toHaveStatusCode(204);
    });
});
```

### Testing with Database Transactions

```php
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserApiTest extends KernelTestCase
{
    private TestHttpClient $client;

    protected function setUp(): void
    {
        $this->client = new TestHttpClient(static::createClient());

        // Start transaction
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $em->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback transaction
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $em->rollback();
    }

    public function testCreateUser(): void
    {
        $response = $this->client->request('POST', '/api/users', [
            'json' => ['name' => 'Test User'],
        ]);

        $this->assertSame(201, $response->getStatusCode());
        // Changes will be rolled back in tearDown
    }
}
```

## Credits

Inspired by [Prism](https://github.com/bpolaszek/prism) by [Benoît Polaszek](https://github.com/bpolaszek).

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

- **Issues**: [GitHub Issues](https://github.com/bpolaszek/test-http-client/issues)
- **Discussions**: [GitHub Discussions](https://github.com/bpolaszek/test-http-client/discussions)
