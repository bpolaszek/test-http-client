<?php

declare(strict_types=1);

namespace BenTools\TestHttpClient\Pest;

use Pest\Expectation;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Contracts\HttpClient\ResponseInterface;

use function array_key_exists;
use function expect;
use function function_exists;
use function get_debug_type;
use function sprintf;
use function str_contains;
use function strtolower;

if (function_exists('expect')) {

    /**
     * Custom Pest expectations for HTTP responses.
     *
     * Usage:
     *   expect($response)->toHaveStatusCode(200);
     *   expect($response)->toHaveHeader('content-type', 'application/json');
     *   expect($response)->toBeSuccessful();
     *   expect($response)->toHaveJsonStructure();
     */

    expect()->extend('toHaveStatusCode', function (int $expectedStatusCode) {
        /** @var Expectation<mixed> $expectation */
        $expectation = $this;

        $response = $expectation->value;
        if (!$response instanceof ResponseInterface) {
            throw new ExpectationFailedException(sprintf('Expected instance of %s, %s given.', ResponseInterface::class, get_debug_type($response)));
        }

        return $expectation->getStatusCode()->toBe($expectedStatusCode);
    });

    expect()->extend('toHaveHeader', function (string $header, ?string $value = null) {
        /** @var Expectation<mixed> $expectation */
        $expectation = $this;

        $response = $expectation->value;
        if (!$response instanceof ResponseInterface) {
            throw new ExpectationFailedException(sprintf('Expected instance of %s, %s given.', ResponseInterface::class, get_debug_type($response)));
        }

        $headers = new HeaderBag($response->getHeaders(false));

        if (!$headers->has($header)) {
            throw new ExpectationFailedException(sprintf('Header "%s" not found in response', $header));
        }

        if (null !== $value && $headers->get($header) !== $value) {
            throw new ExpectationFailedException(sprintf('Header "%s" does not match expected value `%s` (got `%s`).', $header, $value, $headers->get($header)));
        }

        return $expectation->not->toBeEmpty();
    });

    expect()->extend('toBeSuccessful', function () {
        /** @var Expectation<mixed> $expectation */
        $expectation = $this;

        $response = $expectation->value;
        if (!$response instanceof ResponseInterface) {
            throw new ExpectationFailedException(sprintf('Expected instance of %s, %s given.', ResponseInterface::class, get_debug_type($response)));
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new ExpectationFailedException(sprintf('Expected successful response (2xx), got %d', $statusCode));
        }


        return true;
    });

    expect()->extend('toBeClientError', function () {
        /** @var Expectation<mixed> $expectation */
        $expectation = $this;

        $response = $expectation->value;
        if (!$response instanceof ResponseInterface) {
            throw new ExpectationFailedException(sprintf('Expected instance of %s, %s given.', ResponseInterface::class, get_debug_type($response)));
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode < 400 || $statusCode >= 500) {
            throw new ExpectationFailedException(sprintf('Expected client error (4xx), got %d', $statusCode));
        }


        return true;
    });

    expect()->extend('toBeServerError', function () {
        /** @var Expectation<mixed> $expectation */
        $expectation = $this;

        $response = $expectation->value;
        if (!$response instanceof ResponseInterface) {
            throw new ExpectationFailedException(sprintf('Expected instance of %s, %s given.', ResponseInterface::class, get_debug_type($response)));
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode < 500 || $statusCode >= 600) {
            throw new ExpectationFailedException(sprintf('Expected server error (5xx), got %d', $statusCode));
        }


        return true;
    });

    expect()->extend('toHaveJsonStructure', function (?array $keys = null) {
        /** @var Expectation<ResponseInterface> $expectation */
        $expectation = $this;

        $response = $expectation->value;
        if (!$response instanceof ResponseInterface) {
            throw new ExpectationFailedException(sprintf('Expected instance of %s, %s given.', ResponseInterface::class, get_debug_type($response)));
        }


        $headers = $response->getHeaders(false);
        $contentType = '';

        if (isset($headers['content-type'][0])) {
            $contentType = $headers['content-type'][0];
        }

        if (!str_contains(strtolower($contentType), 'json')) {
            throw new ExpectationFailedException(sprintf('Expected JSON content-type, got "%s"', $contentType));
        }

        if (null !== $keys) {
            $data = $response->toArray(false);

            foreach ($keys as $key) {
                if (!array_key_exists($key, $data)) {
                    throw new ExpectationFailedException(sprintf('Expected JSON to have key "%s"', $key));
                }
            }
        }

        return true;
    });
}
