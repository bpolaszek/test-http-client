<?php

declare(strict_types=1);

use BenTools\TestHttpClient\TestHttpClient;

use function BenTools\Pest\Symfony\inject;


describe('Bundle Integration', function () {
    it('registers TestHttpClient service in container', function () {
        expect(inject(TestHttpClient::class))->toBeInstanceOf(TestHttpClient::class);
    });

    it('can use TestHttpClient from container', function () {
        $testHttpClient = inject(TestHttpClient::class);

        $response = $testHttpClient->request('GET', '/test');
        $data = $response->toArray(false);

        expect($response)->toBeSuccessful()
            ->and($response)->toHaveStatusCode(200)
            ->and($data['message'])->toBe('Hello, World!');

    });
});
