<?php

declare(strict_types=1);

use BenTools\TestHttpClient\Response;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\BrowserKit\Response as BrowserKitResponse;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

describe('Pest Expectations', function () {
    describe('toHaveStatusCode', function () {
        it('passes when status code matches', function () {
            $httpResponse = new HttpFoundationResponse('', 200);
            $browserKitResponse = new BrowserKitResponse('');
            $response = new Response($httpResponse, $browserKitResponse, []);

            expect($response)->toHaveStatusCode(200);
        });

        it('throws when not a ResponseInterface', function () {
            expect(fn () => expect('not a response')->toHaveStatusCode(200))
                ->toThrow(ExpectationFailedException::class);
        });
    });

    describe('toHaveHeader', function () {
        it('passes when header exists', function () {
            $httpResponse = new HttpFoundationResponse('');
            $httpResponse->headers->set('Content-Type', 'application/json');
            $browserKitResponse = new BrowserKitResponse('');
            $response = new Response($httpResponse, $browserKitResponse, []);

            expect($response)->toHaveHeader('content-type');
        });

        it('passes when header exists with value', function () {
            $httpResponse = new HttpFoundationResponse('');
            $httpResponse->headers->set('Content-Type', 'application/json');
            $browserKitResponse = new BrowserKitResponse('');
            $response = new Response($httpResponse, $browserKitResponse, []);

            expect($response)->toHaveHeader('content-type', 'application/json');
        });

        it('throws when not a ResponseInterface', function () {
            expect(fn () => expect('not a response')->toHaveHeader('content-type'))
                ->toThrow(ExpectationFailedException::class);
        });

        it('throws when header does not exist', function () {
            $httpResponse = new HttpFoundationResponse('');
            $browserKitResponse = new BrowserKitResponse('');
            $response = new Response($httpResponse, $browserKitResponse, []);

            expect(fn () => expect($response)->toHaveHeader('x-missing-header'))
                ->toThrow(ExpectationFailedException::class);
        });

        it('throws when header value does not match', function () {
            $httpResponse = new HttpFoundationResponse('');
            $httpResponse->headers->set('Content-Type', 'application/json');
            $browserKitResponse = new BrowserKitResponse('');
            $response = new Response($httpResponse, $browserKitResponse, []);

            expect(fn () => expect($response)->toHaveHeader('content-type', 'text/html'))
                ->toThrow(ExpectationFailedException::class);
        });
    });

    describe('toBeSuccessful', function () {
        it('passes for 2xx status codes', function () {
            $httpResponse = new HttpFoundationResponse('', 200);
            $browserKitResponse = new BrowserKitResponse('');
            $response = new Response($httpResponse, $browserKitResponse, []);

            expect($response)->toBeSuccessful();
            expect(true)->toBeTrue(); // Assertion to avoid risky test
        });

        it('throws when not a ResponseInterface', function () {
            expect(fn () => expect('not a response')->toBeSuccessful())
                ->toThrow(ExpectationFailedException::class);
        });

        it('throws for non-2xx status codes', function () {
            $httpResponse = new HttpFoundationResponse('', 404);
            $browserKitResponse = new BrowserKitResponse('');
            $response = new Response($httpResponse, $browserKitResponse, []);

            expect(fn () => expect($response)->toBeSuccessful())
                ->toThrow(ExpectationFailedException::class);
        });
    });

    describe('toBeClientError', function () {
        it('passes for 4xx status codes', function () {
            $httpResponse = new HttpFoundationResponse('', 404);
            $browserKitResponse = new BrowserKitResponse('');
            $response = new Response($httpResponse, $browserKitResponse, []);

            expect($response)->toBeClientError();
            expect(true)->toBeTrue(); // Assertion to avoid risky test
        });

        it('throws when not a ResponseInterface', function () {
            expect(fn () => expect('not a response')->toBeClientError())
                ->toThrow(ExpectationFailedException::class);
        });

        it('throws for non-4xx status codes', function () {
            $httpResponse = new HttpFoundationResponse('', 200);
            $browserKitResponse = new BrowserKitResponse('');
            $response = new Response($httpResponse, $browserKitResponse, []);

            expect(fn () => expect($response)->toBeClientError())
                ->toThrow(ExpectationFailedException::class);
        });
    });

    describe('toBeServerError', function () {
        it('passes for 5xx status codes', function () {
            $httpResponse = new HttpFoundationResponse('', 500);
            $browserKitResponse = new BrowserKitResponse('');
            $response = new Response($httpResponse, $browserKitResponse, []);

            expect($response)->toBeServerError();
            expect(true)->toBeTrue(); // Assertion to avoid risky test
        });

        it('throws when not a ResponseInterface', function () {
            expect(fn () => expect('not a response')->toBeServerError())
                ->toThrow(ExpectationFailedException::class);
        });

        it('throws for non-5xx status codes', function () {
            $httpResponse = new HttpFoundationResponse('', 200);
            $browserKitResponse = new BrowserKitResponse('');
            $response = new Response($httpResponse, $browserKitResponse, []);

            expect(fn () => expect($response)->toBeServerError())
                ->toThrow(ExpectationFailedException::class);
        });
    });

    describe('toHaveJsonStructure', function () {
        it('passes when content-type is JSON without keys', function () {
            $httpResponse = new HttpFoundationResponse(json_encode(['foo' => 'bar']));
            $httpResponse->headers->set('Content-Type', 'application/json');
            $browserKitResponse = new BrowserKitResponse(json_encode(['foo' => 'bar']));
            $response = new Response($httpResponse, $browserKitResponse, []);

            expect($response)->toHaveJsonStructure();
            expect(true)->toBeTrue(); // Assertion to avoid risky test
        });

        it('passes when content-type is JSON with keys', function () {
            $httpResponse = new HttpFoundationResponse(json_encode(['foo' => 'bar', 'baz' => 123]));
            $httpResponse->headers->set('Content-Type', 'application/json');
            $browserKitResponse = new BrowserKitResponse(json_encode(['foo' => 'bar', 'baz' => 123]));
            $response = new Response($httpResponse, $browserKitResponse, []);

            expect($response)->toHaveJsonStructure(['foo', 'baz']);
            expect(true)->toBeTrue(); // Assertion to avoid risky test
        });

        it('throws when not a ResponseInterface', function () {
            expect(fn () => expect('not a response')->toHaveJsonStructure())
                ->toThrow(ExpectationFailedException::class);
        });

        it('throws when content-type is not JSON', function () {
            $httpResponse = new HttpFoundationResponse('text content');
            $httpResponse->headers->set('Content-Type', 'text/plain');
            $browserKitResponse = new BrowserKitResponse('text content');
            $response = new Response($httpResponse, $browserKitResponse, []);

            expect(fn () => expect($response)->toHaveJsonStructure())
                ->toThrow(ExpectationFailedException::class);
        });

        it('throws when missing expected keys', function () {
            $httpResponse = new HttpFoundationResponse(json_encode(['foo' => 'bar']));
            $httpResponse->headers->set('Content-Type', 'application/json');
            $browserKitResponse = new BrowserKitResponse(json_encode(['foo' => 'bar']));
            $response = new Response($httpResponse, $browserKitResponse, []);

            expect(fn () => expect($response)->toHaveJsonStructure(['foo', 'missing']))
                ->toThrow(ExpectationFailedException::class);
        });
    });
});
