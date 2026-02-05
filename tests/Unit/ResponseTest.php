<?php

declare(strict_types=1);

use BenTools\TestHttpClient\Response;
use Symfony\Component\BrowserKit\Response as BrowserKitResponse;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\JsonException;
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
        expect($headers)->toHaveKey('content-type');
        expect($headers['content-type'])->toContain('application/json');
        expect($headers)->toHaveKey('x-custom');
        expect($headers['x-custom'])->toContain('value');
    });

    it('converts JSON to array', function () {
        $jsonData = json_encode(['foo' => 'bar', 'count' => 42]);
        $httpResponse = new HttpFoundationResponse($jsonData);
        $httpResponse->headers->set('Content-Type', 'application/json');
        $browserKitResponse = new BrowserKitResponse($jsonData);
        $response = new Response($httpResponse, $browserKitResponse, []);

        $data = $response->toArray();
        expect($data)->toBe(['foo' => 'bar', 'count' => 42]);
    });

    it('caches JSON data', function () {
        $jsonData = json_encode(['cached' => true]);
        $httpResponse = new HttpFoundationResponse($jsonData);
        $httpResponse->headers->set('Content-Type', 'application/json');
        $browserKitResponse = new BrowserKitResponse($jsonData);
        $response = new Response($httpResponse, $browserKitResponse, []);

        $first = $response->toArray();
        $second = $response->toArray();

        expect($first)->toBe($second);
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

        expect($response->getInfo('custom'))->toBe('value');
        expect($response->getInfo('http_method'))->toBe('GET');
        expect($response->getInfo())->toBeArray();
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
});
