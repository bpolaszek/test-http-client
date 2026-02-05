<?php

declare(strict_types=1);

namespace BenTools\TestHttpClient;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Trait to prevent service pollution between test requests.
 *
 * By default, Symfony resets certain services between requests to avoid state pollution.
 * In test environments, this can cause issues with service state persistence.
 *
 * Usage:
 *   class TestKernel extends Kernel
 *   {
 *       use MicroKernelTrait;
 *       use TestKernelTrait;
 *   }
 */
trait TestKernelTrait
{
    public function handle(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST, bool $catch = true): Response
    {
        static $resetServices;

        if ('test' === $this->environment) {
            // Prevent Symfony from resetting services between requests in test environment
            // This avoids data pollution issues when making multiple requests in tests
            if (null === $resetServices) {
                try {
                    $reflection = new \ReflectionClass(BaseKernel::class);
                    $resetServices = $reflection->getProperty('resetServices');
                    $resetServices->setAccessible(true);
                } catch (\ReflectionException $e) {
                    // Property doesn't exist in this Symfony version, skip
                    $resetServices = false;
                }
            }

            if ($resetServices instanceof \ReflectionProperty) {
                $resetServices->setValue($this, false);
            }
        }

        return parent::handle($request, $type, $catch);
    }
}
