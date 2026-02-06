<?php

declare(strict_types=1);

namespace BenTools\TestHttpClient;

use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpClient\HttpClientTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

use function explode;
use function implode;
use function in_array;
use function is_array;
use function sprintf;
use function str_replace;
use function strtoupper;

final class TestHttpClient implements HttpClientInterface
{
    use HttpClientTrait;

    /**
     * @see HttpClientInterface::OPTIONS_DEFAULTS
     */
    public const API_OPTIONS_DEFAULTS = [
        'auth_basic' => null,
        'auth_bearer' => null,
        'query' => [],
        'headers' => ['accept' => ['application/ld+json']],
        'body' => '',
        'json' => null,
        'base_uri' => 'http://localhost',
        'extra' => [],
    ];

    /** @var array<string, mixed> */
    private array $defaultOptions = self::API_OPTIONS_DEFAULTS;

    /**
     * @param array<string, mixed> $defaultOptions Default options for the requests
     *
     * @see HttpClientInterface::OPTIONS_DEFAULTS for available options
     */
    public function __construct(public readonly KernelBrowser $kernelBrowser, array $defaultOptions = [])
    {
        $kernelBrowser->followRedirects(false);
        if ($defaultOptions) {
            [, $this->defaultOptions] = self::prepareRequest(null, null, $defaultOptions, self::API_OPTIONS_DEFAULTS);
        }
    }

    public function withAuthBearer(?string $token): self
    {
        return $this->withOptions(['auth_bearer' => $token]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function request(string $method, string $url, array $options = []): Response
    {
        $basic = $options['auth_basic'] ?? null;
        [$url, $options] = self::prepareRequest($method, $url, $options, $this->defaultOptions);
        $resolvedUrl = implode('', $url);
        $server = [];

        // Convert headers to a $_SERVER-like array
        foreach (self::extractHeaders($options) as $key => $value) {
            $normalizedHeaderName = strtoupper(str_replace('-', '_', $key));
            $header = in_array($normalizedHeaderName, ['CONTENT_TYPE', 'REMOTE_ADDR'], true) ? $normalizedHeaderName : sprintf('HTTP_%s', $normalizedHeaderName);
            // BrowserKit doesn't support setting several headers with the same name
            $server[$header] = $value[0] ?? '';
        }

        if ($basic) {
            if (is_array($basic)) {
                $credentials = $basic;
            } elseif (is_string($basic)) {
                $credentials = explode(':', $basic, 2);
            } else {
                // @codeCoverageIgnoreStart
                $credentials = [];
                // @codeCoverageIgnoreEnd
            }
            $server['PHP_AUTH_USER'] = $credentials[0] ?? '';
            $server['PHP_AUTH_PW'] = $credentials[1] ?? '';
        }

        $info = [
            'response_headers' => [],
            'redirect_count' => 0,
            'redirect_url' => null,
            'start_time' => 0.0,
            'http_method' => $method,
            'http_code' => 0,
            'error' => null,
            'user_data' => $options['user_data'] ?? null,
            'url' => $resolvedUrl,
            'primary_port' => 'http:' === $url['scheme'] ? 80 : 443,
        ];
        $this->kernelBrowser->request($method, $resolvedUrl, $options['extra']['parameters'] ?? [], $options['extra']['files'] ?? [], $server, $options['body'] ?? null);

        return new Response($this->kernelBrowser->getResponse(), $this->kernelBrowser->getInternalResponse(), $info);
    }

    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        throw new LogicException('Not implemented.');
    }

    /**
     * Extracts headers depending on the symfony/http-client version being used.
     *
     * @param array<string, mixed> $options
     * @return array<string, string[]>
     *
     * @codeCoverageIgnore
     */
    private static function extractHeaders(array $options): array
    {
        if (!isset($options['normalized_headers'])) {
            /** @var array<string, string[]> */
            return $options['headers'] ?? [];
        }

        $headers = [];

        $normalizedHeaders = $options['normalized_headers'];
        if (!is_array($normalizedHeaders)) {
            return [];
        }

        /** @var string $key */
        foreach ($normalizedHeaders as $key => $values) {
            if (!is_array($values)) {
                continue;
            }

            foreach ($values as $value) {
                if (!is_string($value)) {
                    continue;
                }
                [, $extractedValue] = explode(': ', $value, 2);
                $headers[$key][] = $extractedValue;
            }
        }

        return $headers;
    }
}
