<?php

declare(strict_types=1);

use BenTools\TestHttpClient\TestHttpClient;

describe('Bundle Integration', function () {
    it('registers TestHttpClient service in container', function () {
        $kernelBrowser = createClient();

        // Boot the kernel by making a request
        $kernelBrowser->request('GET', '/test');

        $container = $kernelBrowser->getContainer();

        expect($container->has(TestHttpClient::class))->toBeTrue();

        $testHttpClient = $container->get(TestHttpClient::class);
        expect($testHttpClient)->toBeInstanceOf(TestHttpClient::class);
    });

    it('can use TestHttpClient from container', function () {
        $kernelBrowser = createClient();

        // Boot the kernel by making a request
        $kernelBrowser->request('GET', '/test');

        $testHttpClient = $kernelBrowser->getContainer()->get(TestHttpClient::class);

        $response = $testHttpClient->request('GET', '/test');

        expect($response)->toBeSuccessful();
        expect($response)->toHaveStatusCode(200);

        $data = $response->toArray();
        expect($data['message'])->toBe('Hello, World!');
    });

    it('shares same KernelBrowser instance', function () {
        $kernelBrowser = createClient();

        // Boot the kernel by making a request
        $kernelBrowser->request('GET', '/test');

        $testHttpClient = $kernelBrowser->getContainer()->get(TestHttpClient::class);

        // The service should use the same KernelBrowser instance as the one we created
        expect($testHttpClient->kernelBrowser)->toBeInstanceOf(\Symfony\Bundle\FrameworkBundle\KernelBrowser::class);
    });
});
