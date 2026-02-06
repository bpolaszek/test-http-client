<?php

declare(strict_types=1);

use BenTools\TestHttpClient\Response;
use Symfony\Component\BrowserKit\Response as BrowserKitResponse;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

describe('Response', function () {
    it('returns content', function () {
        $httpResponse = new HttpFoundationResponse('test content');
        $browserKitResponse = new BrowserKitResponse('test content');
        $response = new Response($httpResponse, $browserKitResponse, []);

        expect($response->getContent())->toBe('test content');
    });

    it('returns status code', function () {
        $httpResponse = new HttpFoundationResponse('', 201);
        $browserKitResponse = new BrowserKitResponse('');
        $response = new Response($httpResponse, $browserKitResponse, []);

        expect($response->getStatusCode())->toBe(201);
    });

    it('returns headers', function () {
        $httpResponse = new HttpFoundationResponse();
        $httpResponse->headers->set('Content-Type', 'application/json');
        $httpResponse->headers->set('X-Custom', 'value');
        $browserKitResponse = new BrowserKitResponse('');
        $response = new Response($httpResponse, $browserKitResponse, []);

        $headers = $response->getHeaders();
        expect($headers)->toHaveKey('content-type')
            ->and($headers['content-type'])->toContain('application/json')
            ->and($headers)->toHaveKey('x-custom')
            ->and($headers['x-custom'])->toContain('value');
    });

    it('converts JSON to array', function () {
        $jsonData = json_encode(['foo' => 'bar', 'count' => 42]);
        $httpResponse = new HttpFoundationResponse($jsonData);
        $httpResponse->headers->set('Content-Type', 'application/json');
        $browserKitResponse = new BrowserKitResponse($jsonData);
        $response = new Response($httpResponse, $browserKitResponse, []);

        expect([...$response])->toBe(['foo' => 'bar', 'count' => 42]);
    });

    it('throws JsonException for invalid JSON', function () {
        $httpResponse = new HttpFoundationResponse('not valid json');
        $httpResponse->headers->set('Content-Type', 'application/json');
        $browserKitResponse = new BrowserKitResponse('not valid json');
        $response = new Response($httpResponse, $browserKitResponse, []);

        expect(fn () => $response->toArray())->toThrow(JsonException::class);
    });

    it('throws JsonException for wrong content-type', function () {
        $httpResponse = new HttpFoundationResponse('some text');
        $httpResponse->headers->set('Content-Type', 'text/plain');
        $browserKitResponse = new BrowserKitResponse('some text');
        $response = new Response($httpResponse, $browserKitResponse, []);

        expect(fn () => $response->toArray())->toThrow(JsonException::class);
    });

    it('throws TransportException for empty JSON body', function () {
        $httpResponse = new HttpFoundationResponse('');
        $httpResponse->headers->set('Content-Type', 'application/json');
        $browserKitResponse = new BrowserKitResponse('');
        $response = new Response($httpResponse, $browserKitResponse, []);

        expect(fn () => $response->toArray())->toThrow(TransportException::class);
    });

    it('throws ClientException for 4xx status', function () {
        $httpResponse = new HttpFoundationResponse('', 404);
        $browserKitResponse = new BrowserKitResponse('');
        $response = new Response($httpResponse, $browserKitResponse, []);

        expect(fn () => $response->getContent())->toThrow(ClientException::class);
    });

    it('throws ServerException for 5xx status', function () {
        $httpResponse = new HttpFoundationResponse('', 500);
        $browserKitResponse = new BrowserKitResponse('');
        $response = new Response($httpResponse, $browserKitResponse, []);

        expect(fn () => $response->getContent())->toThrow(ServerException::class);
    });

    it('does not throw when $throw is false', function () {
        $httpResponse = new HttpFoundationResponse('error content', 500);
        $browserKitResponse = new BrowserKitResponse('error content');
        $response = new Response($httpResponse, $browserKitResponse, []);

        $content = $response->getContent(false);
        expect($content)->toBe('error content');

        $headers = $response->getHeaders(false);
        expect($headers)->toBeArray();
    });

    it('returns info', function () {
        $httpResponse = new HttpFoundationResponse('');
        $browserKitResponse = new BrowserKitResponse('');
        $info = ['custom' => 'value', 'http_method' => 'GET'];
        $response = new Response($httpResponse, $browserKitResponse, $info);

        expect($response->getInfo('custom'))->toBe('value')
            ->and($response->getInfo('http_method'))->toBe('GET')
            ->and($response->getInfo())->toBeArray();
    });

    it('returns kernel response', function () {
        $httpResponse = new HttpFoundationResponse('test');
        $browserKitResponse = new BrowserKitResponse('test');
        $response = new Response($httpResponse, $browserKitResponse, []);

        expect($response->getKernelResponse())->toBe($httpResponse);
    });

    it('returns browser kit response', function () {
        $httpResponse = new HttpFoundationResponse('test');
        $browserKitResponse = new BrowserKitResponse('test');
        $response = new Response($httpResponse, $browserKitResponse, []);

        expect($response->getBrowserKitResponse())->toBe($browserKitResponse);
    });

    it('can be canceled', function () {
        $httpResponse = new HttpFoundationResponse('test');
        $browserKitResponse = new BrowserKitResponse('test');
        $response = new Response($httpResponse, $browserKitResponse, []);

        $response->cancel();

        expect($response->getInfo('error'))->toBe('Response has been canceled.');
    });

    it('throws RedirectionException for 3xx status', function () {
        $httpResponse = new HttpFoundationResponse('', 301);
        $browserKitResponse = new BrowserKitResponse('');
        $response = new Response($httpResponse, $browserKitResponse, []);

        expect(fn () => $response->getContent())->toThrow(RedirectionException::class);
    });

    it('caches JSON data', function () {
        $jsonData = json_encode(['foo' => 'bar']);
        $httpResponse = new HttpFoundationResponse($jsonData);
        $httpResponse->headers->set('Content-Type', 'application/json');
        $browserKitResponse = new BrowserKitResponse($jsonData);
        $response = new Response($httpResponse, $browserKitResponse, []);

        // First call - decodes JSON
        $data1 = $response->toArray();
        // Second call - uses cache
        $data2 = $response->toArray();

        expect($data1)->toBe($data2)
            ->and($data1)->toBe(['foo' => 'bar']);
    });

    it('throws JsonException when JSON decodes to non-array', function () {
        $jsonData = json_encode('string value');
        $httpResponse = new HttpFoundationResponse($jsonData);
        $httpResponse->headers->set('Content-Type', 'application/json');
        $browserKitResponse = new BrowserKitResponse($jsonData);
        $response = new Response($httpResponse, $browserKitResponse, []);

        expect(fn () => $response->toArray())->toThrow(JsonException::class);
    });

    it('supports ArrayAccess offsetExists', function () {
        $jsonData = json_encode(['foo' => 'bar', 'baz' => null]);
        $httpResponse = new HttpFoundationResponse($jsonData);
        $httpResponse->headers->set('Content-Type', 'application/json');
        $browserKitResponse = new BrowserKitResponse($jsonData);
        $response = new Response($httpResponse, $browserKitResponse, []);

        expect(isset($response['foo']))->toBeTrue()
            ->and(isset($response['missing']))->toBeFalse();
    });

    it('supports ArrayAccess offsetGet', function () {
        $jsonData = json_encode(['foo' => 'bar']);
        $httpResponse = new HttpFoundationResponse($jsonData);
        $httpResponse->headers->set('Content-Type', 'application/json');
        $browserKitResponse = new BrowserKitResponse($jsonData);
        $response = new Response($httpResponse, $browserKitResponse, []);

        expect($response['foo'])->toBe('bar')
            ->and($response['missing'])->toBeNull();
    });

    it('throws LogicException on offsetSet', function () {
        $jsonData = json_encode(['foo' => 'bar']);
        $httpResponse = new HttpFoundationResponse($jsonData);
        $httpResponse->headers->set('Content-Type', 'application/json');
        $browserKitResponse = new BrowserKitResponse($jsonData);
        $response = new Response($httpResponse, $browserKitResponse, []);

        expect(fn () => $response['foo'] = 'new value')->toThrow(LogicException::class);
    });

    it('throws LogicException on offsetUnset', function () {
        $jsonData = json_encode(['foo' => 'bar']);
        $httpResponse = new HttpFoundationResponse($jsonData);
        $httpResponse->headers->set('Content-Type', 'application/json');
        $browserKitResponse = new BrowserKitResponse($jsonData);
        $response = new Response($httpResponse, $browserKitResponse, []);

        try {
            unset($response['foo']);
            expect(false)->toBeTrue('Expected LogicException to be thrown');
        } catch (LogicException $e) {
            expect($e)->toBeInstanceOf(LogicException::class);
        }
    });
});
