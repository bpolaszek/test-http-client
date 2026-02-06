<?php

declare(strict_types=1);

namespace BenTools\TestHttpClient;

use ArrayAccess;
use LogicException;
use Symfony\Component\BrowserKit\Response as BrowserKitResponse;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

use Traversable;

use function gettype;
use function is_array;
use function sprintf;

use const JSON_BIGINT_AS_STRING;
use const JSON_THROW_ON_ERROR;
use IteratorAggregate;

/**
 * Response wrapper that implements ResponseInterface.
 *
 * Wraps Symfony HttpFoundation Response to provide HttpClient's ResponseInterface.
 *
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
final class Response implements ResponseInterface, ArrayAccess, IteratorAggregate
{
    /** @var array<string, list<string>> */
    private readonly array $headers;

    /** @var array<string, mixed> */
    private array $info;

    private readonly string $content;

    /** @var array<string, mixed>|null */
    private ?array $jsonData = null;

    /**
     * @param array<string, mixed> $info
     */
    public function __construct(
        private readonly HttpFoundationResponse $httpFoundationResponse,
        private readonly BrowserKitResponse $browserKitResponse,
        array $info,
    ) {
        $rawHeaders = $httpFoundationResponse->headers->all();
        /** @var array<string, list<string>> $filteredHeaders */
        $filteredHeaders = array_map(
            fn (array $values): array => array_values(array_filter($values, fn ($v): bool => $v !== null)),
            $rawHeaders
        );
        $this->headers = $filteredHeaders;

        // Compute raw headers
        $responseHeaders = [];
        foreach ($this->headers as $key => $values) {
            foreach ($values as $value) {
                $responseHeaders[] = sprintf('%s: %s', $key, $value);
            }
        }

        $this->content = (string) $httpFoundationResponse->getContent();
        $this->info = [
            'http_code' => $httpFoundationResponse->getStatusCode(),
            'error' => null,
            'response_headers' => $responseHeaders,
        ] + $info;
    }

    public function getInfo(?string $type = null): mixed
    {
        if ($type) {
            return $this->info[$type] ?? null;
        }

        return $this->info;
    }

    /**
     * Checks the status, and try to extract message if appropriate.
     */
    private function checkStatusCode(): void
    {
        if (500 <= $this->info['http_code']) {
            throw new ServerException($this);
        }

        if (400 <= $this->info['http_code']) {
            throw new ClientException($this);
        }

        if (300 <= $this->info['http_code']) {
            throw new RedirectionException($this);
        }
    }

    public function getContent(bool $throw = true): string
    {
        if ($throw) {
            $this->checkStatusCode();
        }

        return $this->content;
    }

    public function getStatusCode(): int
    {
        /** @var int */
        return $this->info['http_code'];
    }

    /**
     * @return array<string, list<string>>
     */
    public function getHeaders(bool $throw = true): array
    {
        if ($throw) {
            $this->checkStatusCode();
        }

        return $this->headers;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(bool $throw = true): array
    {
        if ('' === $content = $this->getContent($throw)) {
            throw new TransportException('Response body is empty.');
        }

        if (null !== $this->jsonData) {
            return $this->jsonData;
        }

        $contentType = $this->headers['content-type'][0] ?? 'application/json';

        if (!preg_match('/\bjson\b/i', $contentType)) {
            throw new JsonException(sprintf('Response content-type is "%s" while a JSON-compatible one was expected.', $contentType));
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new JsonException($e->getMessage(), $e->getCode());
        }

        if (!is_array($decoded)) {
            throw new JsonException(sprintf('JSON content was expected to decode to an array, %s returned.', gettype($decoded)));
        }

        /** @var array<string, mixed> $decoded */
        return $this->jsonData = $decoded;
    }

    /**
     * Returns the internal HttpKernel response.
     */
    public function getKernelResponse(): HttpFoundationResponse
    {
        return $this->httpFoundationResponse;
    }

    /**
     * Returns the internal BrowserKit response.
     */
    public function getBrowserKitResponse(): BrowserKitResponse
    {
        return $this->browserKitResponse;
    }

    /**
     * {@inheritdoc}.
     */
    public function cancel(): void
    {
        $this->info['error'] = 'Response has been canceled.';
    }

    public function offsetExists(mixed $offset): bool
    {
        // @codeCoverageIgnoreStart
        if (!is_string($offset)) {
            return false;
        }
        // @codeCoverageIgnoreEnd

        if (null === $this->jsonData) {
            $this->toArray();
        }

        return isset($this->jsonData[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        // @codeCoverageIgnoreStart
        if (!is_string($offset)) {
            return null;
        }
        // @codeCoverageIgnoreEnd

        if (null === $this->jsonData) {
            $this->toArray();
        }

        return $this->jsonData[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException("This method is read-only.");
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException("This method is read-only.");
    }

    public function getIterator(): Traversable
    {
        yield from $this->toArray();
    }
}
