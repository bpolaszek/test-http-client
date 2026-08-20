<?php

declare(strict_types=1);

namespace BenTools\TestHttpClient\Tests\Fixtures\PHPStan;

use BenTools\TestHttpClient\Response;

use function expect;
use function PHPStan\Testing\assertType;

function assertCustomExpectationsAreKnown(Response $response): void
{
    assertType('Pest\Expectation<BenTools\TestHttpClient\Response|null>', expect($response)->toHaveStatusCode(200));
    assertType('Pest\Expectation<BenTools\TestHttpClient\Response|null>', expect($response)->toHaveHeader('Content-Type'));
    assertType('Pest\Expectation<BenTools\TestHttpClient\Response|null>', expect($response)->toHaveHeader('Content-Type', 'application/json'));
    assertType('Pest\Expectation<BenTools\TestHttpClient\Response|null>', expect($response)->toBeSuccessful());
    assertType('Pest\Expectation<BenTools\TestHttpClient\Response|null>', expect($response)->toBeClientError());
    assertType('Pest\Expectation<BenTools\TestHttpClient\Response|null>', expect($response)->toBeServerError());
    assertType('Pest\Expectation<BenTools\TestHttpClient\Response|null>', expect($response)->toHaveJsonStructure());
    assertType('Pest\Expectation<BenTools\TestHttpClient\Response|null>', expect($response)->toHaveJsonStructure(['status']));

    // Chaining custom expectations keeps the resolved generic.
    assertType(
        'Pest\Expectation<BenTools\TestHttpClient\Response|null>',
        expect($response)
            ->toBeSuccessful()
            ->toHaveStatusCode(200)
            ->toHaveHeader('Content-Type', 'application/json')
            ->toHaveJsonStructure(['status']),
    );

    // Built-in matchers and custom expectations can be mixed in the same chain.
    assertType(
        'Pest\Expectation<BenTools\TestHttpClient\Response|null>',
        expect($response)->toBeSuccessful()->toBeInstanceOf(Response::class)->toHaveStatusCode(200),
    );

    // `and()` re-enters the generic with the new value.
    assertType(
        'Pest\Expectation<BenTools\TestHttpClient\Response>',
        expect($response)->toBeSuccessful()->and($response)->toHaveStatusCode(200),
    );
}
